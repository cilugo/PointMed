<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // No Render o site fica atrás de um proxy que cuida do HTTPS. Sem
        // isto o Laravel acha que a visita é http:// e gera links de CSS/JS
        // em http, que o navegador bloqueia. No XAMPP não muda nada.
        //
        // 12/10/2026 (revisão de segurança): só os cabeçalhos de IP, porta e
        // protocolo. O X-Forwarded-Host ficou de FORA: qualquer pessoa pode
        // mandá-lo, e com ele o Laravel montava o link do "Esqueci minha senha"
        // com o endereço que ela quisesse - o e-mail de verdade do PointMed
        // levava o código secreto para o site de um golpista. No site no ar o
        // endereço vem do APP_URL (AppServiceProvider::boot).
        $middleware->trustProxies(at: '*', headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR
            | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT
            | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO);

        $middleware->alias([
            'ativa' => \App\Http\Middleware\GarantirContaAtiva::class,
            'tipo'  => \App\Http\Middleware\GarantirTipoUsuario::class,
        ]);

        // Conta bloqueada/inativa é deslogada em QUALQUER página, não só nas
        // rotas que lembram de pedir 'ativa' (routes/web.php conta com isto).
        $middleware->appendToGroup('web', \App\Http\Middleware\GarantirContaAtiva::class);

        // 12/10/2026: trocou a senha (ou recuperou pelo "Esqueci minha senha")?
        // As OUTRAS sessões abertas com a senha antiga caem fora na próxima
        // página, inclusive o "lembrar de mim". É o middleware do próprio Laravel
        // que compara a senha guardada na sessão com a atual.
        $middleware->appendToGroup('web', \Illuminate\Session\Middleware\AuthenticateSession::class);

        // 12/10/2026: cabeçalhos que pedem ao navegador para não abrir o site
        // dentro de outro site, não "adivinhar" tipo de arquivo etc.
        $middleware->append(\App\Http\Middleware\CabecalhosDeSeguranca::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Formulário "velho" (419): a página ficou aberta muito tempo, foi
        // reaberta pelo botão Voltar ou a pessoa entrou/saiu de outra conta
        // em outra aba. O token do formulário não bate mais com a sessão.
        // Em vez da tela crua "419 PAGE EXPIRED", volta para a mesma página
        // (que já carrega um token novo) com um aviso e os campos preenchidos.
        // O Laravel converte o TokenMismatchException em HttpException 419
        // antes deste ponto; por isso a checagem é pelo código.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, \Illuminate\Http\Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            return redirect()->back()
                ->withInput($request->except(['password', 'password_confirmation', 'current_password', '_token']))
                ->with('erro', 'A página ficou desatualizada. Confira os dados e envie de novo.');
        });
    })->create();
