<?php

namespace Tests\Feature;

use App\Models\Medico;
use App\Models\Vinculo;
use Tests\TestCase;

/**
 * 12/10/2026 (revisão de segurança) — vincular um médico que JÁ EXISTE no
 * PointMed pede o CPF dele.
 *
 * Antes bastava o CRM/UF, que aparece na página pública do médico: a clínica
 * Aurora digitava o CRM da Dra. Helena (que atende na Vida Plena e no Santa
 * Clara), ganhava o vínculo e passava a editar a foto, a apresentação, as
 * especialidades e os convênios dela — em todas as clínicas.
 *
 * Aurora = contato@aurora.test, unidade (local) 3. Dra. Helena = médico 1,
 * CRM 112233/SP, CPF 701.201.301-06 (DadosFicticios).
 */
class VinculoMedicoTest extends TestCase
{
    private const HELENA = ['crm' => '112233', 'uf' => 'SP'];

    private function auroraVincula(array $dados)
    {
        return $this->comoClinica('contato@aurora.test')
            ->post('/clinica/medicos', self::HELENA + ['local_id' => 3, 'aceita_particular' => '1'] + $dados);
    }

    private function auroraTemVinculoComHelena(): bool
    {
        return Vinculo::where('medico_id', 1)->where('local_id', 3)->exists();
    }

    public function test_so_com_o_crm_publico_nao_vincula(): void
    {
        $this->auroraVincula([])->assertSessionHasErrors(['cpf' => 'Digite o CPF do médico.']);

        $this->assertFalse($this->auroraTemVinculoComHelena());
    }

    public function test_com_o_cpf_de_outra_pessoa_nao_vincula(): void
    {
        // 529.982.247-25 é um CPF válido, mas não é o da Dra. Helena.
        $this->auroraVincula(['cpf' => '529.982.247-25'])
            ->assertSessionHasErrors(['cpf' => 'Esse CPF não é o do médico com esse CRM. Confira com o médico.']);

        $this->assertFalse($this->auroraTemVinculoComHelena());
    }

    public function test_sem_vinculo_a_clinica_continua_sem_poder_editar_o_perfil(): void
    {
        $this->auroraVincula(['cpf' => '529.982.247-25']);

        $this->comoClinica('contato@aurora.test')->put('/clinica/medicos/1', [
            'bio' => 'TEXTO TROCADO POR OUTRA CLINICA', 'especialidades' => [1],
        ])->assertForbidden();

        $this->assertNotSame('TEXTO TROCADO POR OUTRA CLINICA', Medico::find(1)->bio);
    }

    public function test_com_o_cpf_certo_vincula_com_ou_sem_mascara(): void
    {
        $this->auroraVincula(['cpf' => '701.201.301-06'])->assertSessionHasNoErrors();
        $this->assertTrue($this->auroraTemVinculoComHelena());

        Vinculo::where('medico_id', 1)->where('local_id', 3)->delete();

        $this->auroraVincula(['cpf' => '70120130106'])->assertSessionHasNoErrors();
        $this->assertTrue($this->auroraTemVinculoComHelena());
    }

    public function test_cpf_errado_nao_revela_a_situacao_do_medico(): void
    {
        Medico::whereKey(1)->update(['status_verificacao' => 'rejeitado']);

        $this->auroraVincula(['cpf' => '529.982.247-25'])->assertSessionHasErrors('cpf')->assertSessionDoesntHaveErrors('crm');
    }

    public function test_nao_da_para_ficar_chutando_cpf(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->auroraVincula(['cpf' => '529.982.247-25'])->assertSessionHasErrors('cpf');
        }

        $this->auroraVincula(['cpf' => '701.201.301-06'])->assertStatus(429);
        $this->assertFalse($this->auroraTemVinculoComHelena());
    }
}
