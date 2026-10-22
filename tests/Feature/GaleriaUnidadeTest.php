<?php

namespace Tests\Feature;

use App\Models\Foto;
use App\Models\Local;
use App\Models\LocalFoto;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Galeria de fotos e site da unidade (13/10/2026).
 *
 * Vida Plena (contato@vidaplena.test) é dona do local 2 ("Vida Plena -
 * Centro"); o local 1 (SpSaúde) é de outra clínica. O seeder já põe a foto
 * da vitrine da home (imgs/inst) como 1ª foto de cada unidade.
 */
class GaleriaUnidadeTest extends TestCase
{
    private function enviar(int $local = 2, string $nome = 'fachada.jpg')
    {
        return $this->comoClinica()->from('/clinica/unidades')
            ->post("/clinica/unidades/{$local}/fotos", ['foto' => UploadedFile::fake()->image($nome, 800, 600)]);
    }

    public function test_unidade_de_demonstracao_ja_tem_a_foto_da_vitrine(): void
    {
        $this->assertSame('imgs/inst/vidaplena.jpg', Local::find(2)->fotos->first()->foto);
    }

    public function test_clinica_envia_foto_e_ela_aparece_na_pagina_publica(): void
    {
        $this->enviar()->assertRedirect('/clinica/unidades')->assertSessionHas('sucesso');

        $nova = Local::find(2)->fotos->last();
        $this->assertStringStartsWith('foto:', $nova->foto);
        $this->assertTrue(Foto::where('chave', substr($nova->foto, 5))->exists());

        $this->get('/local/2')->assertOk()
            ->assertSee($nova->url, false)
            ->assertSee('1 / 2', false);   // contador da galeria (começa na 1ª)

        $this->get($nova->url)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    }

    public function test_no_maximo_seis_fotos(): void
    {
        for ($i = Local::find(2)->fotos()->count(); $i < Local::MAX_FOTOS; $i++) {
            $this->enviar()->assertSessionHas('sucesso');
        }

        $this->enviar()->assertSessionHas('erro');
        $this->assertSame(Local::MAX_FOTOS, Local::find(2)->fotos()->count());

        // Cheia, a tela não oferece mais o envio.
        $this->comoClinica()->get('/clinica/unidades')->assertOk()
            ->assertDontSee('Enviar foto')->assertSee('6 de 6');
    }

    public function test_so_imagem_ate_2_mb(): void
    {
        $this->comoClinica()->from('/clinica/unidades')
            ->post('/clinica/unidades/2/fotos', ['foto' => UploadedFile::fake()->create('laudo.pdf', 50, 'application/pdf')])
            ->assertSessionHasErrors('foto');

        $this->comoClinica()->from('/clinica/unidades')
            ->post('/clinica/unidades/2/fotos', ['foto' => UploadedFile::fake()->image('grande.jpg')->size(3000)])
            ->assertSessionHasErrors('foto');

        $this->assertSame(1, Local::find(2)->fotos()->count());
    }

    public function test_remover_apaga_a_imagem_do_banco_e_foto_de_demonstracao_fica_no_projeto(): void
    {
        $this->enviar();
        $nova = Local::find(2)->fotos->last();
        $chave = substr($nova->foto, 5);

        $this->comoClinica()->delete("/clinica/unidades/2/fotos/{$nova->id}")->assertSessionHas('sucesso');
        $this->assertModelMissing($nova);
        $this->assertFalse(Foto::where('chave', $chave)->exists());

        $demo = Local::find(2)->fotos->first();
        $this->comoClinica()->delete("/clinica/unidades/2/fotos/{$demo->id}")->assertSessionHas('sucesso');
        $this->assertFileExists(public_path('imgs/inst/vidaplena.jpg'));
    }

    public function test_clinica_nao_mexe_na_galeria_de_outra(): void
    {
        $this->enviar(1)->assertForbidden();

        $daSpSaude = Local::find(1)->fotos->first();
        $this->comoClinica()->delete("/clinica/unidades/1/fotos/{$daSpSaude->id}")->assertForbidden();
        // Foto de outra unidade pela URL da própria unidade: 404 (scopeBindings).
        $this->comoClinica()->delete("/clinica/unidades/2/fotos/{$daSpSaude->id}")->assertNotFound();

        $this->assertModelExists($daSpSaude);
    }

    public function test_usuario_admin_e_visitante_nao_enviam_foto_de_unidade(): void
    {
        $arquivo = fn () => ['foto' => UploadedFile::fake()->image('x.jpg')];

        $this->post('/clinica/unidades/2/fotos', $arquivo())->assertRedirect(route('login'));
        $this->comoUsuarioFinal()->post('/clinica/unidades/2/fotos', $arquivo())->assertForbidden();
        $this->comoAdmin()->post('/clinica/unidades/2/fotos', $arquivo())->assertForbidden();
        $this->assertSame(1, LocalFoto::where('local_id', 2)->count());
    }

    public function test_site_ganha_https_e_aparece_na_pagina_com_link_seguro(): void
    {
        $local = Local::find(2);
        $dados = [
            'nome' => $local->nome, 'tipo' => $local->tipo, 'cep' => $local->cep, 'endereco' => $local->endereco,
            'numero' => $local->numero, 'bairro' => $local->bairro, 'cidade' => $local->cidade, 'uf' => $local->uf,
        ];

        $this->comoClinica()->put('/clinica/unidades/2', $dados + ['site' => 'www.vidaplena.com.br/contato'])
            ->assertSessionHasNoErrors();
        $this->assertSame('https://www.vidaplena.com.br/contato', $local->fresh()->site);

        $this->get('/local/2')->assertOk()
            ->assertSee('Visitar o site')
            ->assertSee('href="https://www.vidaplena.com.br/contato"', false)
            ->assertSee('rel="noopener noreferrer nofollow"', false)
            ->assertSee('vidaplena.com.br');

        // Vazio apaga o site.
        $this->comoClinica()->put('/clinica/unidades/2', $dados + ['site' => ''])->assertSessionHasNoErrors();
        $this->assertNull($local->fresh()->site);
        $this->get('/local/2')->assertDontSee('Visitar o site');
    }

    public function test_site_so_aceita_http_e_https(): void
    {
        $local = Local::find(2);
        $dados = [
            'nome' => $local->nome, 'tipo' => $local->tipo, 'cep' => $local->cep, 'endereco' => $local->endereco,
            'numero' => $local->numero, 'bairro' => $local->bairro, 'cidade' => $local->cidade, 'uf' => $local->uf,
        ];

        foreach (['javascript:alert(1)', 'ftp://arquivos.exemplo.com', 'não é site'] as $ruim) {
            $this->comoClinica()->from('/clinica/unidades')->put('/clinica/unidades/2', $dados + ['site' => $ruim])
                ->assertSessionHasErrors('site');
        }
        $this->assertNull($local->fresh()->site);
    }

    public function test_nova_unidade_ja_pode_ter_site(): void
    {
        $this->comoClinica()->post('/clinica/unidades', [
            'nome' => 'Vida Plena - Norte', 'tipo' => 'clinica', 'cep' => '12230-000', 'endereco' => 'Av. Norte',
            'numero' => '10', 'bairro' => 'Centro', 'cidade' => 'São José dos Campos', 'uf' => 'SP',
            'site' => 'https://vidaplena.com.br', 'horarios' => ['segunda' => ['abre' => '08:00', 'fecha' => '18:00']],
        ])->assertSessionHasNoErrors();

        $this->assertSame('https://vidaplena.com.br', Local::where('nome', 'Vida Plena - Norte')->value('site'));
    }

    public function test_home_usa_a_primeira_foto_da_galeria(): void
    {
        $this->enviar();
        $local = Local::find(2);
        // A foto nova passa a ser a primeira.
        $local->fotos->last()->update(['ordem' => 0]);

        $vitrine = $this->get('/')->assertOk()->viewData('locaisDestaque')->firstWhere('id', 2);
        $this->assertSame($local->fresh()->fotos->first()->url, $vitrine->imagem_vitrine);
    }
}
