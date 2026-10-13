<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DadosFicticios;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * 12/10/2026 — a senha "facilmed2026" está no README, e o repositório é
 * público. No site no ar (APP_ENV=production) ela não pode abrir nenhuma
 * conta de demonstração. Ver facilmed:proteger-demonstracao.
 *
 * Para simular o site no ar, o teste troca o ambiente para 'production'.
 */
class SenhaDemonstracaoTest extends TestCase
{
    private const SENHA_DO_RENDER = 'senha-so-do-painel-do-render';

    private function noSiteNoAr(?string $demoPassword): void
    {
        $this->app['env'] = 'production';
        config(['app.senha_demonstracao' => $demoPassword]);
    }

    private function senhaFunciona(string $email, string $senha): bool
    {
        return Hash::check($senha, User::where('email', $email)->firstOrFail()->password);
    }

    public function test_fora_do_site_no_ar_a_senha_do_readme_continua_valendo(): void
    {
        $this->artisan('facilmed:proteger-demonstracao')->assertSuccessful();

        foreach (DadosFicticios::emailsDasContas() as $email) {
            $this->assertTrue($this->senhaFunciona($email, 'facilmed2026'), $email);
        }
        $this->post('/login', ['email' => 'admin@facilmed.test', 'password' => 'facilmed2026'])->assertRedirect('/dashboard');
    }

    public function test_no_site_no_ar_as_contas_de_demonstracao_passam_para_a_demo_password(): void
    {
        $this->noSiteNoAr(self::SENHA_DO_RENDER);

        $this->artisan('facilmed:proteger-demonstracao')
            ->doesntExpectOutputToContain(self::SENHA_DO_RENDER)   // a senha nunca vai para o log
            ->assertSuccessful();

        $this->assertCount(9, DadosFicticios::emailsDasContas());
        foreach (DadosFicticios::emailsDasContas() as $email) {
            $this->assertFalse($this->senhaFunciona($email, 'facilmed2026'), "$email ainda abre com a senha do README");
            $this->assertTrue($this->senhaFunciona($email, self::SENHA_DO_RENDER), $email);
        }

        // De volta ao ambiente de teste só para o POST (no 'production' o Laravel cobra o token do formulário).
        $this->app['env'] = 'testing';
        $this->post('/login', ['email' => 'admin@facilmed.test', 'password' => 'facilmed2026'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->post('/login', ['email' => 'admin@facilmed.test', 'password' => self::SENHA_DO_RENDER])->assertRedirect('/dashboard');
    }

    public function test_sem_demo_password_ninguem_entra_com_a_senha_do_readme(): void
    {
        $this->noSiteNoAr(null);

        $this->artisan('facilmed:proteger-demonstracao')
            ->expectsOutputToContain('DEMO_PASSWORD vazia')
            ->assertSuccessful();

        foreach (DadosFicticios::emailsDasContas() as $email) {
            $this->assertFalse($this->senhaFunciona($email, 'facilmed2026'), $email);
        }
    }

    public function test_quem_ja_estava_logado_com_a_senha_antiga_cai_fora(): void
    {
        $admin = User::where('email', 'admin@facilmed.test')->firstOrFail();
        $admin->forceFill(['remember_token' => 'token-antigo'])->save();
        DB::table('sessions')->insert(['id' => 'sessao-do-invasor', 'user_id' => $admin->id, 'payload' => '', 'last_activity' => time()]);
        DB::table('password_reset_tokens')->insert(['email' => $admin->email, 'token' => 'x', 'created_at' => now()]);

        $this->noSiteNoAr(self::SENHA_DO_RENDER);
        $this->artisan('facilmed:proteger-demonstracao')->assertSuccessful();

        $this->assertDatabaseMissing('sessions', ['id' => 'sessao-do-invasor']);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $admin->email]);
        $this->assertNull($admin->fresh()->remember_token);
    }

    public function test_conta_criada_pelo_site_nao_e_mexida(): void
    {
        $real = User::create([
            'name' => 'Admin de Verdade', 'email' => 'integrante@exemplo.test', 'password' => 'facilmed2026',
            'tipo' => User::TIPO_ADMIN, 'status' => 'ativo',
        ]);

        $this->noSiteNoAr(self::SENHA_DO_RENDER);
        $this->artisan('facilmed:proteger-demonstracao')->assertSuccessful();

        $this->assertTrue(Hash::check('facilmed2026', $real->fresh()->password));
    }

    public function test_rodar_de_novo_nao_troca_nada_e_nao_derruba_ninguem(): void
    {
        $this->noSiteNoAr(self::SENHA_DO_RENDER);
        $this->artisan('facilmed:proteger-demonstracao')->assertSuccessful();

        $admin = User::where('email', 'admin@facilmed.test')->firstOrFail();
        DB::table('sessions')->insert(['id' => 'sessao-do-grupo', 'user_id' => $admin->id, 'payload' => '', 'last_activity' => time()]);

        $this->artisan('facilmed:proteger-demonstracao')
            ->expectsOutputToContain('0 com a senha trocada agora')
            ->assertSuccessful();

        $this->assertDatabaseHas('sessions', ['id' => 'sessao-do-grupo']);
    }

    public function test_seeder_no_site_no_ar_ja_grava_a_demo_password(): void
    {
        // README §2.7: "migrate:fresh --seed" pela aba Shell do Render.
        $this->noSiteNoAr(self::SENHA_DO_RENDER);

        $this->artisan('db:seed', ['--class' => AdminSeeder::class, '--force' => true])
            ->doesntExpectOutputToContain(self::SENHA_DO_RENDER)
            ->assertSuccessful();

        $this->assertTrue($this->senhaFunciona('admin@facilmed.test', self::SENHA_DO_RENDER));
        $this->assertFalse($this->senhaFunciona('admin@facilmed.test', 'facilmed2026'));
    }
}
