<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base de todos os testes do PointMed.
 *
 * RefreshDatabase + $seed: o banco de teste é recriado UMA vez com os dados
 * fictícios (DatabaseSeeder) e cada teste roda dentro de uma transação que é
 * desfeita no fim. Então todo teste começa com os mesmos médicos, clínicas,
 * usuários e bases simuladas do README §3.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();

        // 28/09: nos TESTES, campo que não está no $fillable dá erro em vez de
        // ser descartado em silêncio. Foi assim que o bloqueio de conta ficava
        // sem motivo gravado. Só nos testes: na demonstração, nada muda.
        \Illuminate\Database\Eloquent\Model::preventSilentlyDiscardingAttributes(true);
    }

    /**
     * 12/10/2026: o middleware AuthenticateSession guarda na sessão um resumo
     * da senha de quem entrou e desloga se ele não bater (é o que derruba as
     * outras sessões quando a senha muda). Nos testes a sessão é a mesma do
     * começo ao fim, e trocar de conta com actingAs() - sem login de verdade -
     * parecia "senha mudou". Na vida real a troca de conta passa pelo logout,
     * que apaga a sessão inteira.
     */
    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        $this->app['session']->forget('password_hash_web');

        return parent::actingAs($user, $guard);
    }

    /** Loga com uma das contas do seed pelo e-mail (senha não importa aqui). */
    protected function comoUsuario(string $email): static
    {
        return $this->actingAs(User::where('email', $email)->firstOrFail());
    }

    protected function comoUsuarioFinal(string $email = 'ana@facilmed.test'): static
    {
        return $this->comoUsuario($email);
    }


    protected function comoClinica(string $email = 'contato@vidaplena.test'): static
    {
        return $this->comoUsuario($email);
    }

    protected function comoAdmin(): static
    {
        return $this->comoUsuario('admin@facilmed.test');
    }
}
