<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\BuscaController;
use App\Http\Controllers\CadastroController;
use App\Http\Controllers\Clinica;
use App\Http\Controllers\FotoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Usuario;
use App\Http\Controllers\PerfilPublicoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas do PointMed
|--------------------------------------------------------------------------
|
| Os NOMES das rotas aqui são contrato com config/navegacao.php. Se você
| renomear uma rota, o item some da sidebar silenciosamente (ela usa
| Route::has() para não quebrar a página). Renomeou? Ajuste os dois.
|
| Dois middlewares em cima de 'auth':
|   'ativa'  — conta bloqueada não entra (já está no grupo 'web')
|   'tipo:x' — só aquele tipo de usuário acessa
|
| ATENÇÃO: 'tipo' responde "que tipo de usuário é", NÃO "é dono disto".
| A segunda pergunta é da Policy, dentro do controller. Rota sem as duas
| é o furo clássico: a clínica A editando o médico da clínica B.
|
| 01/10/2026 (documento "Modificações"): o PointMed não agenda mais
| consultas. Saíram as rotas de agendamento, de consultas e a área do
| médico inteira (médico agora é perfil cadastrado pela clínica, sem login).
|
*/

// ---------------------------------------------------------------------
// Público
// ---------------------------------------------------------------------

Route::get('/', [HomeController::class, 'index'])->name('home');

// Busca de médicos. Só mostra médico verificado — o controller usa o
// scope Medico::visivel().
Route::get('/buscar', [BuscaController::class, 'index'])->name('busca.index');

// 29/09/2026 (plano do app): clínicas e hospitais do mais perto para o mais
// longe, e a página de cada local com os médicos disponíveis.
// 01/10: a busca de locais também filtra por convênio.
Route::get('/locais', [BuscaController::class, 'locais'])->name('busca.locais');
Route::get('/local/{local}', [PerfilPublicoController::class, 'local'])
    ->whereNumber('local')->name('publico.local');

// Perfis públicos
Route::get('/medico/{medico}', [PerfilPublicoController::class, 'medico'])
    ->whereNumber('medico')->name('publico.medico');
Route::get('/clinica/{clinica}', [PerfilPublicoController::class, 'clinica'])
    ->whereNumber('clinica')->name('publico.clinica');

// Cadastro de usuário e de clínica/hospital. O Breeze cuida de login, logout e senha.
// O médico não se cadastra: é a clínica que cadastra o perfil dele
// (Meus médicos → Cadastrar médico), conferindo o CRM na base simulada.
Route::middleware('guest')->group(function () {
    Route::get('/cadastro', [CadastroController::class, 'escolher'])->name('cadastro.escolher');

    Route::get('/cadastro/usuario', [CadastroController::class, 'formUsuario'])->name('cadastro.usuario');
    // 12/10/2026: no máximo 10 cadastros por minuto do mesmo endereço (IP).
    // Sem limite, um robô criava contas à vontade e testava em massa quais
    // CPFs já têm conta (a tela avisa "Já existe um cadastro com esse CPF").
    Route::post('/cadastro/usuario', [CadastroController::class, 'salvarUsuario'])->middleware('throttle:10,1');

    Route::get('/cadastro/clinica', [CadastroController::class, 'formClinica'])->name('cadastro.clinica');
    Route::post('/cadastro/clinica', [CadastroController::class, 'salvarClinica'])->middleware('throttle:10,1');
});

// Endereço antigo do cadastro de médico (link salvo, protótipo): volta para a escolha.
// redirect()->route() e não Route::redirect(): este último monta o destino SEM a
// subpasta /PointMed do XAMPP e mandava para http://localhost/cadastro (404).
Route::any('/cadastro/medico', fn () => redirect()->route('cadastro.escolher'));

// ---------------------------------------------------------------------
// Foto de perfil (01/10) — usuário, clínica e admin, cada um a sua.
// ---------------------------------------------------------------------

// 07/10: a imagem fica no banco; esta rota a devolve (FotoController::mostrar).
Route::get('/foto/{chave}', [FotoController::class, 'mostrar'])
    ->where('chave', '[A-Za-z0-9]{40}')->name('foto.mostrar');

Route::middleware('auth')->group(function () {
    // 12/10: cada foto é gravada no banco; sem limite, dava para encher o banco.
    Route::post('/minha-foto', [FotoController::class, 'atualizar'])->middleware('throttle:10,1')->name('foto.atualizar');
    Route::delete('/minha-foto', [FotoController::class, 'remover'])->name('foto.remover');
});

// ---------------------------------------------------------------------
// Área do usuário
// ---------------------------------------------------------------------

// 12/10: até 20 avaliações por minuto por usuário (cada uma recalcula médias).
Route::middleware(['auth', 'tipo:usuario', 'throttle:20,1'])->group(function () {

    // 01/10: avaliação direto no local ou no médico (sem consulta).
    // Uma por usuário em cada um; avaliar de novo edita (UNIQUE no banco).
    Route::post('/local/{local}/avaliar', [Usuario\AvaliacaoController::class, 'avaliarLocal'])
        ->whereNumber('local')->name('avaliacoes.local');
    Route::post('/medico/{medico}/avaliar', [Usuario\AvaliacaoController::class, 'avaliarMedico'])
        ->whereNumber('medico')->name('avaliacoes.medico');
});

Route::middleware(['auth', 'tipo:usuario'])->prefix('usuario')->name('usuario.')->group(function () {

    Route::get('/', [Usuario\DashboardController::class, 'index'])->name('dashboard');

    // Histórico das avaliações que o usuário fez (plano do app, tela 6).
    Route::get('/avaliacoes', [Usuario\AvaliacaoController::class, 'index'])->name('avaliacoes');
    Route::delete('/avaliacoes/{avaliacao}', [Usuario\AvaliacaoController::class, 'excluir'])
        ->whereNumber('avaliacao')->name('avaliacoes.excluir');

    // Carteirinha. Conferida NA HORA na base simulada (base_carteirinhas,
    // decisão de 24/09): bateu -> 'ativa'; não bateu -> erro com o motivo.
    // 01/10: serve de atalho no filtro "aceita meu plano" da busca de locais.
    Route::get('/planos', [Usuario\PlanoController::class, 'index'])->name('planos');
    Route::post('/planos', [Usuario\PlanoController::class, 'salvar'])->name('planos.salvar');
    Route::delete('/planos/{usuarioPlano}', [Usuario\PlanoController::class, 'remover'])->name('planos.remover');

    Route::get('/perfil', [Usuario\PerfilController::class, 'edit'])->name('perfil');
    Route::put('/perfil', [Usuario\PerfilController::class, 'update'])->name('perfil.atualizar');

    // 30/09: exclusão de conta (LGPD). Anonimiza, não apaga — ver
    // Usuario::excluirConta(). Pede a senha atual e uma confirmação.
    Route::delete('/perfil', [Usuario\PerfilController::class, 'excluir'])->middleware('throttle:6,1')->name('perfil.excluir');   // 12/10: pede a senha atual
});

// ---------------------------------------------------------------------
// Área da clínica / hospital
// ---------------------------------------------------------------------

Route::middleware(['auth', 'tipo:clinica'])->prefix('clinica')->name('clinica.')->group(function () {

    Route::get('/', [Clinica\DashboardController::class, 'index'])->name('dashboard');

    // A clínica cadastra e mantém o perfil dos médicos (01/10: o médico não
    // tem conta). Editar passa pela MedicoPolicy: só médico que atende numa
    // unidade DESTA clínica.
    Route::get('/medicos', [Clinica\MedicoController::class, 'index'])->name('medicos');
    Route::get('/medicos/novo', [Clinica\MedicoController::class, 'form'])->name('medicos.novo');
    // 12/10: até 10 envios por minuto por clínica. Vincular médico que já
    // existe pede o CPF dele; sem limite, dava para ficar chutando CPFs.
    Route::post('/medicos', [Clinica\MedicoController::class, 'salvar'])
        ->middleware('throttle:10,1')->name('medicos.salvar');
    Route::get('/medicos/{medico}/editar', [Clinica\MedicoController::class, 'editar'])
        ->whereNumber('medico')->name('medicos.editar');
    Route::put('/medicos/{medico}', [Clinica\MedicoController::class, 'atualizar'])
        ->whereNumber('medico')->name('medicos.atualizar');
    Route::delete('/medicos/vinculo/{vinculo}', [Clinica\MedicoController::class, 'desvincular'])
        ->whereNumber('vinculo')->name('medicos.desvincular');

    // 01/10: a clínica também cria especialidade nova (antes só o admin).
    Route::get('/especialidades', [Clinica\EspecialidadeController::class, 'index'])->name('especialidades');
    // 12/10: a especialidade criada vale para a plataforma inteira; limite contra lixo em massa.
    Route::post('/especialidades', [Clinica\EspecialidadeController::class, 'salvar'])
        ->middleware('throttle:10,1')->name('especialidades.salvar');

    Route::get('/unidades', [Clinica\UnidadeController::class, 'index'])->name('unidades');
    Route::post('/unidades', [Clinica\UnidadeController::class, 'salvar'])->name('unidades.salvar');
    Route::put('/unidades/{local}/horarios', [Clinica\UnidadeController::class, 'salvarHorarios'])
        ->name('unidades.horarios');

    // 12/10: editar os dados da unidade (nome, tipo, endereço, telefone) e
    // desativar/reativar. Desativada, some da busca e da página pública, mas
    // os médicos continuam ligados a ela e voltam ao reativar.
    Route::put('/unidades/{local}', [Clinica\UnidadeController::class, 'atualizar'])
        ->whereNumber('local')->name('unidades.atualizar');
    Route::patch('/unidades/{local}/status', [Clinica\UnidadeController::class, 'alterarStatus'])
        ->whereNumber('local')->name('unidades.status');

    // 05/10: a "Tabela de preços" saiu. A clínica escolhe a faixa ($ a $$$$)
    // de cada unidade (App\Support\FaixaDePreco).
    Route::put('/unidades/{local}/faixa', [Clinica\UnidadeController::class, 'salvarFaixa'])
        ->whereNumber('local')->name('unidades.faixa');
    // 13/10/2026: galeria da unidade (até 6 fotos). scopeBindings: a foto
    // precisa ser DESTA unidade; o limite de envios é o mesmo da foto de perfil.
    Route::scopeBindings()->group(function () {
        Route::post('/unidades/{local}/fotos', [Clinica\FotoDaUnidadeController::class, 'salvar'])
            ->whereNumber('local')->middleware('throttle:10,1')->name('unidades.fotos.salvar');
        Route::delete('/unidades/{local}/fotos/{foto}', [Clinica\FotoDaUnidadeController::class, 'remover'])
            ->whereNumber(['local', 'foto'])->name('unidades.fotos.remover');
    });

    // Cobertura de convênios da clínica. O convênio é aceito pelo MÉDICO;
    // aqui a clínica só liga/desliga "aceita convênio" por médico e unidade.
    Route::get('/convenios', [Clinica\ConvenioController::class, 'index'])->name('convenios');
    Route::post('/convenios', [Clinica\ConvenioController::class, 'salvar'])->name('convenios.salvar');

    Route::get('/avaliacoes', [Clinica\AvaliacaoController::class, 'index'])->name('avaliacoes');

    Route::get('/perfil', [Clinica\PerfilController::class, 'edit'])->name('perfil');
    Route::put('/perfil', [Clinica\PerfilController::class, 'update'])->name('perfil.atualizar');
});

// ---------------------------------------------------------------------
// Administração
// ---------------------------------------------------------------------

Route::middleware(['auth', 'tipo:admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/usuarios', [Admin\UsuarioController::class, 'index'])->name('usuarios');
    // 13/10/2026: bloqueadas e excluídas em telas próprias (ver o UsuarioController).
    Route::get('/usuarios/bloqueadas', [Admin\UsuarioController::class, 'bloqueadas'])->name('contas-bloqueadas');
    Route::get('/usuarios/excluidas', [Admin\UsuarioController::class, 'excluidas'])->name('contas-excluidas');
    // Bloqueio exige motivo — bloqueio sem registro de quem e por quê
    // é ingovernável.
    Route::post('/usuarios/{user}/bloquear', [Admin\UsuarioController::class, 'bloquear'])
        ->name('usuarios.bloquear');
    Route::post('/usuarios/{user}/desbloquear', [Admin\UsuarioController::class, 'desbloquear'])
        ->name('usuarios.desbloquear');

    // 01/10: "Verificar CRM" virou "Verificar CNPJ" (quem cadastra médico é
    // a clínica). Situação de cada CNPJ na base simulada. Só leitura.
    Route::get('/cnpjs', [Admin\CnpjController::class, 'index'])->name('cnpjs');

    Route::get('/clinicas', [Admin\ClinicaController::class, 'index'])->name('clinicas');

    Route::get('/especialidades', [Admin\EspecialidadeController::class, 'index'])->name('especialidades');
    Route::post('/especialidades', [Admin\EspecialidadeController::class, 'salvar'])->name('especialidades.salvar');
    Route::put('/especialidades/{especialidade}', [Admin\EspecialidadeController::class, 'atualizar'])
        ->name('especialidades.atualizar');

    // Convênios e planos FICTÍCIOS (decisão do grupo, 24/09) — não
    // dependem mais da ANS. Nada se apaga: desativar esconde da busca
    // e preserva as carteirinhas.
    Route::get('/convenios', [Admin\ConvenioController::class, 'index'])->name('convenios');
    Route::post('/convenios', [Admin\ConvenioController::class, 'salvar'])->name('convenios.salvar');
    Route::put('/convenios/{convenio}', [Admin\ConvenioController::class, 'atualizar'])
        ->whereNumber('convenio')->name('convenios.atualizar');
    Route::patch('/convenios/{convenio}/status', [Admin\ConvenioController::class, 'alternar'])
        ->whereNumber('convenio')->name('convenios.status');

    Route::post('/convenios/{convenio}/planos', [Admin\ConvenioController::class, 'salvarPlano'])
        ->whereNumber('convenio')->name('convenios.planos');
    Route::put('/convenios/planos/{plano}', [Admin\ConvenioController::class, 'atualizarPlano'])
        ->whereNumber('plano')->name('convenios.planos.atualizar');
    Route::patch('/convenios/planos/{plano}/status', [Admin\ConvenioController::class, 'alternarPlano'])
        ->whereNumber('plano')->name('convenios.planos.status');

    // 01/10: o admin também tem perfil (foto e senha).
    Route::get('/perfil', [Admin\PerfilController::class, 'edit'])->name('perfil');
});

// ---------------------------------------------------------------------
// /dashboard — adicionado em 24/09.
// O Breeze, depois do login, manda para route('dashboard'). Como cada
// tipo de usuário tem o próprio painel (usuario.dashboard, ...), sem
// esta rota o login dava erro "Route [dashboard] not defined".
// ---------------------------------------------------------------------
Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->tipo . '.dashboard');
})->middleware('auth')->name('dashboard');

require __DIR__ . '/auth.php';
