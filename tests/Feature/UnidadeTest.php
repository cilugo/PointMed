<?php

namespace Tests\Feature;

use App\Models\Local;
use App\Models\Vinculo;
use App\Support\Localizacao;
use Tests\TestCase;

/**
 * Clínica → Unidades: editar os dados e desativar/reativar (12/10/2026).
 *
 * Vida Plena (contato@vidaplena.test) tem uma unidade só: local 2,
 * "Vida Plena - Centro", onde a Dra. Helena atende (vínculo 1).
 * O local 1 (SpSaúde) é de outra clínica.
 *
 * Decisões do grupo: ao desativar, os médicos continuam ligados à unidade
 * (voltam ao reativar); a clínica pode desativar a última unidade, com aviso.
 */
class UnidadeTest extends TestCase
{
    private function dados(array $extra = []): array
    {
        return array_merge([
            'nome' => 'Vida Plena - Centro Novo', 'tipo' => 'hospital', 'cep' => '12210-100',
            'endereco' => 'Rua Nova', 'numero' => '99', 'complemento' => 'Sala 2', 'bairro' => 'Centro',
            'cidade' => 'São José dos Campos', 'uf' => 'SP', 'telefone' => '(12) 3999-0000',
        ], $extra);
    }

    public function test_clinica_edita_os_dados_da_unidade(): void
    {
        $antes = Local::with('horarios')->find(2);

        $this->comoClinica()->put('/clinica/unidades/2', $this->dados())
            ->assertSessionHasNoErrors()->assertSessionHas('sucesso');

        $local = Local::with('horarios')->find(2);
        $this->assertSame('Vida Plena - Centro Novo', $local->nome);
        $this->assertSame('hospital', $local->tipo);
        $this->assertSame('12210100', $local->cep);
        $this->assertSame('Rua Nova', $local->endereco);
        $this->assertSame('1239990000', $local->telefone);

        // Faixa de preço e horário têm formulário próprio: não mudam aqui.
        $this->assertSame($antes->faixa_preco, $local->faixa_preco);
        $this->assertSame($antes->horarios->count(), $local->horarios->count());
    }

    public function test_mudar_a_cidade_recalcula_a_coordenada(): void
    {
        $this->comoClinica()->put('/clinica/unidades/2', $this->dados(['cidade' => 'Jacareí']))
            ->assertSessionHasNoErrors();

        $esperado = Localizacao::coordenadas('Jacareí', 'SP', 'Centro');
        $local = Local::find(2);
        $this->assertEqualsWithDelta($esperado[0], $local->latitude, 0.0001);
        $this->assertEqualsWithDelta($esperado[1], $local->longitude, 0.0001);
    }

    public function test_sem_mudar_o_endereco_a_coordenada_fica(): void
    {
        // Coordenada exata (como a do Geocodificador no cadastro): trocar só nome e telefone não mexe nela.
        Local::find(2)->forceFill(['latitude' => -23.111111, 'longitude' => -45.222222])->save();
        $atual = Local::find(2);

        $this->comoClinica()->put('/clinica/unidades/2', $this->dados([
            'cep' => $atual->cep, 'endereco' => $atual->endereco, 'numero' => $atual->numero,
            'complemento' => $atual->complemento, 'bairro' => $atual->bairro, 'cidade' => $atual->cidade,
        ]))->assertSessionHasNoErrors();

        $local = Local::find(2);
        $this->assertSame('Vida Plena - Centro Novo', $local->nome);
        $this->assertEqualsWithDelta(-23.111111, $local->latitude, 0.000001);
        $this->assertEqualsWithDelta(-45.222222, $local->longitude, 0.000001);
    }

    public function test_dados_invalidos_nao_salvam(): void
    {
        $this->comoClinica()->put('/clinica/unidades/2', $this->dados(['cep' => '123', 'nome' => '']))
            ->assertSessionHasErrors(['cep', 'nome']);

        $this->assertSame('Vida Plena - Centro', Local::find(2)->nome);
    }

    public function test_nao_mexe_em_unidade_de_outra_clinica(): void
    {
        $this->comoClinica()->put('/clinica/unidades/1', $this->dados())->assertForbidden();
        $this->comoClinica()->patch('/clinica/unidades/1/status', ['ativo' => 0])->assertForbidden();

        $local = Local::find(1);
        $this->assertSame('SpSaúde - Jardim Satélite', $local->nome);
        $this->assertTrue($local->ativo);
    }

    public function test_usuario_admin_e_visitante_nao_mexem_em_unidade(): void
    {
        $this->patch('/clinica/unidades/2/status', ['ativo' => 0])->assertRedirect();
        $this->comoUsuarioFinal()->patch('/clinica/unidades/2/status', ['ativo' => 0])->assertForbidden();
        $this->comoAdmin()->patch('/clinica/unidades/2/status', ['ativo' => 0])->assertForbidden();
        $this->comoAdmin()->put('/clinica/unidades/2', $this->dados())->assertForbidden();
        $this->comoClinica()->patch('/clinica/unidades/2/status', ['ativo' => 'talvez'])->assertSessionHasErrors('ativo');

        $this->assertTrue(Local::find(2)->ativo);
    }

    public function test_desativar_tira_da_busca_e_guarda_os_medicos(): void
    {
        $this->comoClinica()->patch('/clinica/unidades/2/status', ['ativo' => 0])
            ->assertSessionHas('sucesso');

        $this->assertFalse(Local::find(2)->ativo);
        $this->assertTrue(Vinculo::find(1)->ativo, 'O médico continua ligado à unidade desativada.');

        $this->get('/local/2')->assertNotFound();
        $this->get('/locais')->assertOk()->assertDontSee('Vida Plena - Centro');
    }

    public function test_reativar_volta_tudo_como_estava(): void
    {
        $this->comoClinica()->patch('/clinica/unidades/2/status', ['ativo' => 0]);
        $this->comoClinica()->patch('/clinica/unidades/2/status', ['ativo' => 1])
            ->assertSessionHas('sucesso');

        $this->assertTrue(Local::find(2)->ativo);
        $this->get('/local/2')->assertOk()->assertSee('Helena Navarro');
    }

    public function test_desativar_a_ultima_unidade_avisa_que_a_clinica_saiu_da_busca(): void
    {
        $resposta = $this->comoClinica()->patch('/clinica/unidades/2/status', ['ativo' => 0]);

        $this->assertStringContainsString('nenhuma unidade ativa', $resposta->getSession()->get('sucesso'));
    }

    public function test_unidade_desativada_nao_recebe_medico_novo(): void
    {
        $this->comoClinica()->patch('/clinica/unidades/2/status', ['ativo' => 0]);

        $this->comoClinica()->post('/clinica/medicos', [
            'name' => 'Paulo Yamada', 'crm' => '445566', 'uf' => 'SP', 'local_id' => 2, 'especialidades' => [1],
        ])->assertSessionHasErrors('local_id');
    }

    public function test_tela_mostra_editar_e_desativar(): void
    {
        $this->comoClinica()->get('/clinica/unidades')->assertOk()
            ->assertSee('Editar dados')
            ->assertSee('Desativar unidade');

        $this->comoClinica()->patch('/clinica/unidades/2/status', ['ativo' => 0]);

        $this->comoClinica()->get('/clinica/unidades')->assertOk()
            ->assertSee('Desativada')
            ->assertSee('Reativar unidade');
    }
}
