<?php

namespace Tests\Feature;

use App\Models\Foto;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Revisão de segurança de 12/10/2026 (README §6). Um teste para cada correção:
 * o link do "Esqueci minha senha", sessões depois da troca de senha, limites
 * de tentativas, cache do CEP, cabeçalhos e metadados das fotos.
 */
class RevisaoSegurancaTest extends TestCase
{
    // ------------------------------------------------------------------
    // Link do "Esqueci minha senha"
    // ------------------------------------------------------------------

    public function test_cabecalho_falso_nao_muda_o_endereco_do_link_de_troca_de_senha(): void
    {
        Notification::fake();

        // Antes, este cabeçalho fazia o e-mail sair com o link para o site do golpista.
        $this->withHeaders(['X-Forwarded-Host' => 'site-do-golpista.test'])
            ->post('/forgot-password', ['email' => 'ana@facilmed.test'])
            ->assertSessionHasNoErrors();

        $ana = User::where('email', 'ana@facilmed.test')->firstOrFail();
        Notification::assertSentTo($ana, ResetPassword::class, function (ResetPassword $aviso) use ($ana) {
            $link = $aviso->toMail($ana)->actionUrl;

            return ! str_contains($link, 'golpista') && str_starts_with($link, rtrim(config('app.url'), '/') . '/reset-password/');
        });
    }

    // ------------------------------------------------------------------
    // Sessões
    // ------------------------------------------------------------------

    public function test_senha_trocada_em_outro_lugar_derruba_esta_sessao(): void
    {
        $ana = User::where('email', 'ana@facilmed.test')->firstOrFail();

        $this->actingAs($ana)->get('/usuario')->assertOk();

        // A senha muda em outro aparelho (ou pelo "Esqueci minha senha").
        $ana->forceFill(['password' => 'senha-nova-de-outro-lugar'])->save();

        $this->get('/usuario')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_quem_trocou_a_senha_continua_logado(): void
    {
        $this->comoUsuarioFinal()->get('/usuario/perfil')->assertOk();

        $this->put('/password', [
            'current_password' => 'facilmed2026',
            'password' => 'uma-senha-nova-2026', 'password_confirmation' => 'uma-senha-nova-2026',
        ])->assertSessionHasNoErrors();

        $this->get('/usuario/perfil')->assertOk();
        $this->assertTrue(Hash::check('uma-senha-nova-2026', User::where('email', 'ana@facilmed.test')->value('password')));
    }

    // ------------------------------------------------------------------
    // Limites de tentativas
    // ------------------------------------------------------------------

    public function test_cadastro_tem_limite_de_tentativas(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post('/cadastro/usuario', ['name' => 'Robo', 'email' => "robo{$i}@exemplo.test", 'cpf' => '802.301.401-30'])
                ->assertStatus(302);
        }

        $this->post('/cadastro/usuario', ['name' => 'Robo', 'email' => 'robo@exemplo.test', 'cpf' => '802.301.401-30'])
            ->assertStatus(429);
    }

    public function test_esqueci_minha_senha_tem_limite_por_endereco(): void
    {
        Notification::fake();

        for ($i = 0; $i < 6; $i++) {
            $this->post('/forgot-password', ['email' => "alguem{$i}@exemplo.test"])->assertStatus(302);
        }

        $this->post('/forgot-password', ['email' => 'ana@facilmed.test'])->assertStatus(429);
        Notification::assertNothingSent();
    }

    public function test_avaliacao_tem_limite_de_tentativas(): void
    {
        $this->comoUsuarioFinal();

        for ($i = 0; $i < 20; $i++) {
            $this->post('/local/2/avaliar', ['estrelas' => 5])->assertStatus(302);
        }

        $this->post('/local/2/avaliar', ['estrelas' => 1])->assertStatus(429);
    }

    // ------------------------------------------------------------------
    // Busca por CEP
    // ------------------------------------------------------------------

    private function internetFalsa(): void
    {
        config(['localizacao.servico_externo' => true]);
        Http::fake([
            'viacep.com.br/*' => Http::response(['logradouro' => 'Rua A', 'bairro' => 'Centro', 'localidade' => 'São José dos Campos', 'uf' => 'SP']),
            'nominatim.openstreetmap.org/*' => Http::response([['lat' => '-23.18', 'lon' => '-45.88']]),
        ]);
    }

    public function test_o_mesmo_cep_so_sai_para_a_internet_uma_vez(): void
    {
        $this->internetFalsa();

        $this->get('/locais?cep=12210-100')->assertRedirect();
        $this->get('/locais?cep=12210100')->assertRedirect();
        $this->get('/locais?cep=12210-100&raio=5')->assertRedirect();

        Http::assertSentCount(2);   // 1 ViaCEP + 1 Nominatim, só na primeira busca
    }

    public function test_muitos_ceps_seguidos_pedem_para_esperar_sem_sair_para_a_internet(): void
    {
        $this->internetFalsa();

        for ($i = 0; $i < 20; $i++) {
            $this->get('/locais?cep=' . (12200000 + $i))->assertRedirect();
        }
        $pedidos = count(Http::recorded());

        $this->get('/locais?cep=12299999')->assertOk()->assertSee('Muitas buscas por CEP em pouco tempo');
        $this->assertCount($pedidos, Http::recorded(), 'passou do limite e ainda saiu para a internet');

        // CEP que já está guardado continua funcionando.
        $this->get('/locais?cep=12200000')->assertRedirect();
    }

    // ------------------------------------------------------------------
    // Cabeçalhos e fotos
    // ------------------------------------------------------------------

    public function test_paginas_saem_com_os_cabecalhos_de_seguranca(): void
    {
        $this->get('/')->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');
    }

    public function test_foto_enviada_perde_os_metadados_do_celular(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            $this->markTestSkipped('Precisa da extensão GD.');
        }

        // Um JPEG de verdade com um bloco EXIF "plantado" com um texto de GPS.
        $imagem = imagecreatetruecolor(1600, 900);
        ob_start();
        imagejpeg($imagem);
        $jpeg = (string) ob_get_clean();
        $exif = "Exif\0\0" . 'GPS-RUA-DA-CASA-DO-MEDICO';
        $comExif = substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($exif) + 2) . $exif . substr($jpeg, 2);

        $caminho = tempnam(sys_get_temp_dir(), 'foto') . '.jpg';
        file_put_contents($caminho, $comExif);

        $this->comoClinica()->post('/minha-foto', ['foto' => new UploadedFile($caminho, 'foto.jpg', 'image/jpeg', null, true)])
            ->assertSessionHasNoErrors();

        $guardada = base64_decode(Foto::latest('id')->firstOrFail()->conteudo);
        $this->assertStringNotContainsString('GPS-RUA-DA-CASA-DO-MEDICO', $guardada);

        // E ficou no máximo com 1024 px no lado maior.
        [$largura, $altura] = getimagesizefromstring($guardada);
        $this->assertSame([1024, 576], [$largura, $altura]);
    }

    public function test_foto_deitada_do_celular_fica_em_pe(): void
    {
        if (! function_exists('imagecreatetruecolor') || ! function_exists('exif_read_data')) {
            $this->markTestSkipped('Precisa das extensões GD e exif.');
        }

        // Foto 400x200 com a anotação EXIF "Orientation = 6" (gire 90° para ver em pé).
        $imagem = imagecreatetruecolor(400, 200);
        ob_start();
        imagejpeg($imagem);
        $jpeg = (string) ob_get_clean();
        $tiff = "MM\x00\x2A\x00\x00\x00\x08" . "\x00\x01" . "\x01\x12\x00\x03\x00\x00\x00\x01\x00\x06\x00\x00" . "\x00\x00\x00\x00";
        $exif = "Exif\0\0" . $tiff;
        $caminho = tempnam(sys_get_temp_dir(), 'foto') . '.jpg';
        file_put_contents($caminho, substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($exif) + 2) . $exif . substr($jpeg, 2));
        $this->assertSame(6, exif_read_data($caminho)['Orientation'] ?? null, 'o arquivo de teste precisa ter a orientação');

        $this->comoClinica()->post('/minha-foto', ['foto' => new UploadedFile($caminho, 'deitada.jpg', 'image/jpeg', null, true)])
            ->assertSessionHasNoErrors();

        [$largura, $altura] = getimagesizefromstring(base64_decode(Foto::latest('id')->firstOrFail()->conteudo));
        $this->assertSame([200, 400], [$largura, $altura], 'a foto devia ter sido girada para ficar em pé');
    }
}
