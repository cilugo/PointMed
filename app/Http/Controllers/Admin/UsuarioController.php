<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Admin → contas de acesso.
 *
 * 13/10/2026 (decisão do Sidney de 01/10): três telas separadas, no mesmo
 * visual, em vez de um filtro de situação numa tela só:
 *   - Contas ativas     /admin/usuarios             (admin.usuarios)         → Bloquear
 *   - Contas bloqueadas /admin/usuarios/bloqueadas  (admin.contas-bloqueadas) → Desbloquear
 *   - Contas excluídas  /admin/usuarios/excluidas   (admin.contas-excluidas)  → só leitura
 *
 * Os nomes das rotas novas NÃO começam com "admin.usuarios" de propósito: o
 * menu marca o item ativo com routeIs('admin.usuarios*'), e "Contas ativas"
 * ficaria aceso nas outras duas telas.
 *
 * "Excluída" = status `inativo`. Hoje o único jeito de uma conta ficar inativa
 * é o próprio usuário excluí-la (Usuario::excluirConta, que também preenche
 * excluida_em); olhar o status garante que nenhuma conta fique fora das três telas.
 */
class UsuarioController extends Controller
{
    /** situação => [status no banco, rota, título, subtítulo] */
    public const SITUACOES = [
        'ativas' => ['ativo', 'admin.usuarios', 'Contas ativas',
            'Quem pode entrar na plataforma. Bloqueie quem não deve mais acessar.'],
        'bloqueadas' => ['bloqueado', 'admin.contas-bloqueadas', 'Contas bloqueadas',
            'Contas que não entram mais. O motivo é interno: a pessoa só vê que a conta está bloqueada.'],
        'excluidas' => ['inativo', 'admin.contas-excluidas', 'Contas excluídas',
            'Contas que o próprio usuário excluiu (LGPD). Os dados pessoais já foram apagados e a conta não volta.'],
    ];

    public function index(Request $request)
    {
        // Link antigo (?status=bloqueado, ?status=inativo) vai para a tela certa.
        $porStatus = ['bloqueado' => 'admin.contas-bloqueadas', 'inativo' => 'admin.contas-excluidas'];
        if (isset($porStatus[$request->query('status')])) {
            return redirect()->route($porStatus[$request->query('status')], $request->only('tipo', 'busca'));
        }

        return $this->listar($request, 'ativas');
    }

    public function bloqueadas(Request $request)
    {
        return $this->listar($request, 'bloqueadas');
    }

    public function excluidas(Request $request)
    {
        return $this->listar($request, 'excluidas');
    }

    private function listar(Request $request, string $situacao)
    {
        [$status, $rota, $titulo, $subtitulo] = self::SITUACOES[$situacao];

        // Filtro em lista na URL (?tipo[]=x) não derruba a página: só texto conta.
        $tipo = is_string($request->tipo) ? $request->tipo : null;
        $busca = is_string($request->busca) ? trim($request->busca) : null;

        $contagem = User::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.usuarios', [
            'situacao'  => $situacao,
            'rota'      => $rota,
            'titulo'    => $titulo,
            'subtitulo' => $subtitulo,
            'contagem'  => collect(self::SITUACOES)->map(fn ($s) => (int) ($contagem[$s[0]] ?? 0)),
            'usuarios'  => User::query()
                ->where('status', $status)
                ->when($tipo, fn ($q, $t) => $q->where('tipo', $t))
                ->when($busca, fn ($q, $b) => $q->where(fn ($s) => $s
                    ->where('name', 'like', "%{$b}%")
                    ->orWhere('email', 'like', "%{$b}%")))
                ->when($situacao === 'bloqueadas', fn ($q) => $q->orderByDesc('bloqueado_em'))
                ->when($situacao === 'excluidas', fn ($q) => $q->orderByDesc('excluida_em'))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
        ]);
    }

    /**
     * Bloqueio EXIGE motivo. Bloqueio sem registro de quem bloqueou e
     * por que e ingovernavel - ninguem sabe se pode desbloquear.
     *
     * O middleware GarantirContaAtiva roda em toda requisicao, entao
     * quem ja esta logado cai fora na proxima acao.
     */
    public function bloquear(Request $request, User $user)
    {
        $dados = $request->validate([
            'motivo' => ['required', 'string', 'min:10', 'max:255'],
        ], [
            'motivo.required' => 'Informe o motivo do bloqueio.',
            'motivo.min'      => 'Descreva o motivo com pelo menos 10 caracteres.',
        ]);

        abort_if($user->ehAdmin(), 403, 'Não é possível bloquear um administrador.');
        // 30/09: conta excluída pelo usuário fica como está. Bloquear e depois
        // "desbloquear" a colocaria de volta como ativa.
        abort_if($user->foiExcluida(), 422, 'Essa conta foi excluída pelo próprio usuário.');

        // 28/09: forceFill, e não update(). Esses três campos NÃO estão no
        // $fillable do User (de propósito: ninguém deve preenchê-los por
        // formulário), e o update() os descartava EM SILÊNCIO — o status
        // mudava, mas o motivo, quem bloqueou e quando nunca eram gravados.
        $user->forceFill([
            'status'          => 'bloqueado',
            'motivo_bloqueio' => $dados['motivo'],
            'bloqueado_por'   => auth()->id(),
            'bloqueado_em'    => now(),
        ])->save();

        // O motivo do bloqueio NÃO vai para a pessoa (AGENTS.md §3: sem detalhar).
        // A conta sai da tela de ativas: a mensagem diz onde ela foi parar.
        return back()->with('sucesso', "A conta de {$user->name} foi bloqueada e agora está em Contas bloqueadas.");
    }

    public function desbloquear(User $user)
    {
        abort_unless($user->status === 'bloqueado', 422, 'Essa conta não está bloqueada.');

        $user->forceFill([   // forceFill: ver o comentário em bloquear()
            'status'          => 'ativo',
            'motivo_bloqueio' => null,
            'bloqueado_por'   => null,
            'bloqueado_em'    => null,
        ])->save();

        return back()->with('sucesso', "A conta de {$user->name} foi reativada e voltou para Contas ativas.");
    }
}
