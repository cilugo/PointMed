<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\HorarioFuncionamento;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Clinica\AtualizarUnidadeRequest;
use App\Http\Requests\Clinica\SalvarUnidadeRequest;
use App\Http\Requests\Clinica\HorariosDeFuncionamento;
use App\Models\Local;
use App\Support\Geocodificador;
use Illuminate\Http\Request;

class UnidadeController extends Controller
{
    public function index()
    {
        return view('clinica.unidades', [
            'locais' => auth()->user()->clinica->locais()->with(['horarios', 'fotos'])->withCount(['vinculos' => fn ($q) => $q->where('ativo', true)])->get(),
            'dias'   => HorarioFuncionamento::DIAS,
        ]);
    }

    public function salvar(SalvarUnidadeRequest $request)
    {
        $dados = $request->validated();

        $local = DB::transaction(function () use ($request, $dados) {
            $local = Local::create([
                'clinica_id'  => $request->user()->clinica->id,
                'nome'        => $dados['nome'],
                'tipo'        => $dados['tipo'],
                'cep'         => $dados['cep'],
                'endereco'    => $dados['endereco'],
                'numero'      => $dados['numero'],
                'complemento' => $dados['complemento'] ?? null,
                'bairro'      => $dados['bairro'],
                'cidade'      => $dados['cidade'],
                'uf'          => $dados['uf'],
                'telefone'    => ($dados['telefone'] ?? '') ?: null,
                'site'        => $dados['site'] ?? null,   // 13/10
                'faixa_preco' => $dados['faixa_preco'] ?? null,
                'ativo'       => true,
            ]);

            $local->definirHorarios(HorariosDeFuncionamento::preenchidos($dados['horarios'] ?? []));

            return $local;
        });

        // 07/10/2026 (trazido da main): coordenada exata do endereço (Nominatim),
        // FORA da transação - é um pedido pela internet e não pode segurar o banco.
        // Sem internet, fica a aproximada do bairro/cidade (Local::booted).
        Geocodificador::atualizarLocal($local);

        return back()->with('sucesso', 'Unidade cadastrada. Agora vincule os médicos que atendem nela.');
    }

    /**
     * Editar nome, tipo, endereço e telefone da unidade (12/10/2026).
     * Quem pode: só a clínica dona (AtualizarUnidadeRequest + LocalPolicy).
     *
     * Endereço mudou? A coordenada aproximada é refeita sozinha ao salvar
     * (Local::booted) e, com internet, o Geocodificador troca pela exata -
     * fora da transação, como no cadastro.
     */
    public function atualizar(AtualizarUnidadeRequest $request, Local $local)
    {
        $dados = $request->validated();

        $local->update([
            'nome'        => $dados['nome'],
            'tipo'        => $dados['tipo'],
            'cep'         => $dados['cep'],
            'endereco'    => $dados['endereco'],
            'numero'      => $dados['numero'],
            'complemento' => $dados['complemento'] ?? null,
            'bairro'      => $dados['bairro'],
            'cidade'      => $dados['cidade'],
            'uf'          => $dados['uf'],
            'telefone'    => ($dados['telefone'] ?? '') ?: null,
            'site'        => $dados['site'] ?? null,   // 13/10
        ]);

        if ($local->wasChanged(Local::CAMPOS_DO_ENDERECO)) {
            Geocodificador::atualizarLocal($local);
        }

        return back()->with('sucesso', "Dados de {$local->nome} salvos.");
    }

    /**
     * Desativar ou reativar a unidade (12/10/2026, decisões do grupo):
     *  - desativada, some da busca, da home e da página pública
     *    (Local::publicos e estaPublico() já olham locais.ativo);
     *  - os médicos CONTINUAM ligados a ela (o vínculo não muda) e voltam a
     *    aparecer ao reativar;
     *  - pode desativar a última unidade, mas a mensagem avisa que a clínica
     *    saiu da busca.
     * Recebe ativo=0/1 (e não "inverter") para que um clique duplo não desfaça.
     */
    public function alterarStatus(Request $request, Local $local)
    {
        $this->authorize('update', $local);

        $ativo = $request->validate(['ativo' => ['required', 'boolean']])['ativo'];
        $local->update(['ativo' => (bool) $ativo]);

        if ($local->ativo) {
            return back()->with('sucesso', "{$local->nome} foi reativada e voltou a aparecer na busca, com os médicos que atendem nela.");
        }

        $msg = "{$local->nome} foi desativada e não aparece mais na busca. Os médicos continuam ligados a ela e voltam a aparecer se você reativar.";

        if (! $local->clinica->locais()->where('ativo', true)->exists()) {
            $msg .= ' Atenção: a clínica ficou sem nenhuma unidade ativa e não aparece na busca até você reativar uma.';
        }

        return back()->with('sucesso', $msg);
    }

    /**
     * Faixa de preço da consulta particular desta unidade, de $ a $$$$
     * (05/10/2026: a clínica escolhe; a Tabela de preços saiu). Vazio =
     * não informar. O usuário vê só os $, nunca valor.
     */
    public function salvarFaixa(Request $request, Local $local)
    {
        $this->authorize('update', $local);

        $dados = $request->validate(
            ['faixa_preco' => ['nullable', 'integer', 'between:1,4']],
            ['faixa_preco.between' => 'Escolha uma faixa de $ a $$$$.'],
        );

        $local->update(['faixa_preco' => $dados['faixa_preco'] ?? null]);

        return back()->with('sucesso', "Faixa de preço de {$local->nome} salva.");
    }

    /**
     * Horario de FUNCIONAMENTO do lugar, mostrado na pagina do local.
     */
    public function salvarHorarios(Request $request, Local $local)
    {
        $this->authorize('update', $local);

        $dados = $request->validate(HorariosDeFuncionamento::regras() + ['horarios' => ['nullable', 'array']], HorariosDeFuncionamento::mensagens());
        $horarios = HorariosDeFuncionamento::preenchidos($dados['horarios'] ?? []);

        if ($horarios === []) {
            return back()->with('erro', 'A unidade precisa abrir pelo menos um dia. Para parar de atender, use "Desativar unidade".');
        }

        DB::transaction(fn () => $local->definirHorarios($horarios));

        $msg = "Horário de funcionamento de {$local->nome} salvo.";

        return back()->with('sucesso', $msg);
    }
}
