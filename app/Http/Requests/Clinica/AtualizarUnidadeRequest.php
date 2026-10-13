<?php

namespace App\Http\Requests\Clinica;

use App\Models\Local;

/**
 * Editar os dados de uma unidade (12/10/2026): nome, tipo, endereço e
 * telefone. Faixa de preço e horário de funcionamento têm formulário
 * próprio na mesma tela, por isso ficam de fora daqui.
 *
 * Herda do cadastro (SalvarUnidadeRequest) a limpeza do CEP/telefone e as
 * regras dos campos — assim cadastro e edição nunca ficam diferentes.
 *
 * authorize(): só a clínica DONA da unidade (LocalPolicy::update). O
 * middleware 'tipo:clinica' garante que é uma clínica; a Policy garante
 * que é ESTA clínica.
 */
class AtualizarUnidadeRequest extends SalvarUnidadeRequest
{
    public function authorize(): bool
    {
        $local = $this->route('local');

        return $local instanceof Local && ($this->user()?->can('update', $local) ?? false);
    }

    public function rules(): array
    {
        return $this->regrasDosDados();
    }
}
