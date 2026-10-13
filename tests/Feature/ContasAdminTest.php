<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Admin com três telas de contas (13/10/2026, decisão do Sidney de 01/10):
 * ativas, bloqueadas e excluídas, cada uma com a ação que cabe a ela.
 */
class ContasAdminTest extends TestCase
{
    private function marcos(): User
    {
        return User::where('email', 'marcos@facilmed.test')->first();
    }

    private function bloquearMarcos(): User
    {
        $marcos = $this->marcos();
        $marcos->forceFill(['status' => 'bloqueado', 'motivo_bloqueio' => 'Avaliações falsas repetidas', 'bloqueado_em' => now()])->save();

        return $marcos;
    }

    private function excluirAna(): User
    {
        $this->comoUsuarioFinal()->delete('/usuario/perfil', ['current_password' => 'facilmed2026', 'confirmacao' => '1']);

        return User::where('email', 'like', 'excluida-%')->firstOrFail();
    }

    public function test_menu_do_admin_tem_as_tres_telas_e_marca_so_a_aberta(): void
    {
        $html = $this->comoAdmin()->get('/admin/usuarios/bloqueadas')->assertOk()->getContent();

        foreach (['Contas ativas', 'Contas bloqueadas', 'Contas excluídas'] as $item) {
            $this->assertStringContainsString($item, $html);
        }
        // Só um item do menu aceso (o de bloqueadas), não o de ativas junto.
        $this->assertSame(1, substr_count($html, 'class="fm-nav is-ativo"'));
    }

    public function test_cada_tela_mostra_so_as_contas_daquela_situacao(): void
    {
        $this->bloquearMarcos();
        $excluida = $this->excluirAna();

        $this->comoAdmin()->get('/admin/usuarios')->assertOk()
            ->assertSee('contato@vidaplena.test')
            ->assertDontSee('marcos@facilmed.test')
            ->assertDontSee($excluida->email);

        $this->comoAdmin()->get('/admin/usuarios/bloqueadas')->assertOk()
            ->assertSee('marcos@facilmed.test')
            ->assertSee('Avaliações falsas repetidas')
            ->assertDontSee('contato@vidaplena.test');

        $this->comoAdmin()->get('/admin/usuarios/excluidas')->assertOk()
            ->assertSee('Excluída pelo próprio usuário')
            ->assertDontSee('marcos@facilmed.test')
            ->assertDontSee('contato@vidaplena.test');
    }

    public function test_acoes_certas_em_cada_tela(): void
    {
        $marcos = $this->bloquearMarcos();
        $excluida = $this->excluirAna();
        $vidaPlena = User::where('email', 'contato@vidaplena.test')->first();

        $this->comoAdmin()->get('/admin/usuarios')
            ->assertSee(route('admin.usuarios.bloquear', $vidaPlena), false);

        $this->comoAdmin()->get('/admin/usuarios/bloqueadas')
            ->assertSee(route('admin.usuarios.desbloquear', $marcos), false)
            ->assertDontSee('Confirmar bloqueio');

        // Conta excluída não volta: nenhuma ação.
        $this->comoAdmin()->get('/admin/usuarios/excluidas')
            ->assertDontSee(route('admin.usuarios.bloquear', $excluida), false)
            ->assertDontSee('Desbloquear');
    }

    public function test_bloquear_e_desbloquear_dizem_para_onde_a_conta_foi(): void
    {
        $marcos = $this->marcos();

        $this->comoAdmin()->from('/admin/usuarios')
            ->post(route('admin.usuarios.bloquear', $marcos), ['motivo' => 'Avaliações falsas repetidas'])
            ->assertRedirect('/admin/usuarios')
            ->assertSessionHas('sucesso', fn ($m) => str_contains($m, 'Contas bloqueadas'));

        $this->comoAdmin()->from('/admin/usuarios/bloqueadas')
            ->post(route('admin.usuarios.desbloquear', $marcos))
            ->assertRedirect('/admin/usuarios/bloqueadas')
            ->assertSessionHas('sucesso', fn ($m) => str_contains($m, 'Contas ativas'));

        $this->assertSame('ativo', $marcos->fresh()->status);
    }

    public function test_abas_mostram_o_total_de_cada_situacao(): void
    {
        $this->bloquearMarcos();

        $contagem = $this->comoAdmin()->get('/admin/usuarios')->viewData('contagem');

        $this->assertSame(1, $contagem['bloqueadas']);
        $this->assertSame(0, $contagem['excluidas']);
        $this->assertSame(User::where('status', 'ativo')->count(), $contagem['ativas']);
    }

    public function test_link_antigo_com_status_vai_para_a_tela_certa_e_lista_na_url_nao_quebra(): void
    {
        $this->comoAdmin()->get('/admin/usuarios?status=bloqueado&tipo=usuario')
            ->assertRedirect(route('admin.contas-bloqueadas', ['tipo' => 'usuario']));
        $this->comoAdmin()->get('/admin/usuarios?status=inativo')
            ->assertRedirect(route('admin.contas-excluidas'));

        $this->comoAdmin()->get('/admin/usuarios?tipo[]=x&busca[]=y')->assertOk();
    }

    public function test_so_admin_abre_as_telas_de_contas(): void
    {
        foreach (['/admin/usuarios/bloqueadas', '/admin/usuarios/excluidas'] as $url) {
            $this->get($url)->assertRedirect(route('login'));   // visitante primeiro: sem ninguém logado
        }
        foreach (['/admin/usuarios/bloqueadas', '/admin/usuarios/excluidas'] as $url) {
            $this->comoUsuarioFinal()->get($url)->assertForbidden();
            $this->comoClinica()->get($url)->assertForbidden();
        }
    }
}
