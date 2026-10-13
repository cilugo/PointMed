<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Local;
use App\Models\LocalFoto;
use App\Support\FotoDePerfil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Galeria de fotos da unidade (13/10/2026): Clínica → Unidades → Fotos.
 *
 * Quem pode: só a clínica DONA da unidade (LocalPolicy::update) — a rota já
 * exige uma conta de clínica; a Policy garante que é ESTA clínica.
 * A imagem passa pelo mesmo caminho da foto de perfil (App\Support\FotoDePerfil):
 * só JPG/PNG/WEBP até 2 MB, regravada sem os metadados (GPS do celular) e
 * guardada no banco.
 */
class FotoDaUnidadeController extends Controller
{
    public function salvar(Request $request, Local $local)
    {
        $this->authorize('update', $local);

        $request->validate(
            ['foto' => array_merge(['required'], array_slice(FotoDePerfil::REGRAS, 1))],
            [
                'foto.required' => 'Escolha uma imagem.',
                'foto.image'    => 'O arquivo precisa ser uma imagem.',
                'foto.mimes'    => 'Use uma imagem JPG, PNG ou WEBP.',
                'foto.max'      => 'A imagem pode ter no máximo 2 MB.',
                'foto.uploaded' => 'A imagem não chegou. Tente uma de até 2 MB.',
            ],
        );

        // lockForUpdate na linha da unidade: dois envios ao mesmo tempo (duas
        // abas) esperam um ao outro, e a conta "já tem 6?" não passa de 6.
        $salvou = DB::transaction(function () use ($request, $local) {
            Local::whereKey($local->id)->lockForUpdate()->first();

            $atual = $local->fotos()->count();
            if ($atual >= Local::MAX_FOTOS) {
                return false;
            }

            $local->fotos()->create([
                'foto'  => FotoDePerfil::salvar($request->file('foto')),
                'ordem' => (int) $local->fotos()->max('ordem') + 1,
            ]);

            return true;
        });

        if (! $salvou) {
            return back()->with('erro', "{$local->nome} já tem " . Local::MAX_FOTOS . ' fotos. Remova uma para enviar outra.');
        }

        return back()->with('sucesso', "Foto adicionada à galeria de {$local->nome}.");
    }

    /**
     * {foto} só é encontrada se for DESTA unidade (scopeBindings na rota):
     * /unidades/1/fotos/<foto da unidade 2> dá 404.
     */
    public function remover(Local $local, LocalFoto $foto)
    {
        $this->authorize('update', $local);

        $foto->delete();   // LocalFoto::booted apaga a imagem da tabela `fotos`

        return back()->with('sucesso', "Foto removida da galeria de {$local->nome}.");
    }
}
