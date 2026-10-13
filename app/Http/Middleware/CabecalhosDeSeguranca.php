<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeçalhos de segurança em toda resposta (12/10/2026, revisão de segurança).
 *
 *  - X-Frame-Options: outro site não consegue abrir o PointMed escondido num
 *    quadro (iframe) para enganar a pessoa e fazê-la clicar em "Excluir conta".
 *  - X-Content-Type-Options: o navegador não "adivinha" que um arquivo é outro
 *    tipo (ex.: uma imagem tratada como página).
 *  - Referrer-Policy: ao clicar num link para fora, o outro site não recebe o
 *    endereço completo da página (que pode ter lat/lng da busca).
 *  - Permissions-Policy: câmera e microfone desligados; localização só no
 *    próprio PointMed (o botão "Usar minha localização").
 *  - Strict-Transport-Security: só no site no ar (https), manda o navegador
 *    sempre usar https. No XAMPP (http) não é enviado.
 *
 * Sem Content-Security-Policy de propósito: o Alpine.js precisa avaliar as
 * expressões das telas, e uma política errada quebraria os menus.
 */
class CabecalhosDeSeguranca
{
    public function handle(Request $request, Closure $next): Response
    {
        $resposta = $next($request);

        $resposta->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $resposta->headers->set('X-Content-Type-Options', 'nosniff');
        $resposta->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $resposta->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self)');

        if (app()->isProduction() && $request->isSecure()) {
            $resposta->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $resposta;
    }
}
