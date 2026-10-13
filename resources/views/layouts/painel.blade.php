{{--
    Layout dos painéis (usuário, clínica e admin). Quem usa faz:

        @extends('layouts.painel')
        @section('titulo', 'Início')
        @section('conteudo')  ...  @endsection

    Estrutura: menu lateral no computador e "gaveta" no celular, barra de
    topo com o usuário, e a área de conteúdo.

    12/10/2026: o SÍMBOLO do PointMed é o botão do menu, sempre do lado
    esquerdo. No computador ele recolhe/abre o menu lateral (fechado: só o
    símbolo e os ícones; aberto: símbolo, nome e os nomes dos itens). No
    celular ele fica no topo, no lugar do ☰, e abre a gaveta; o símbolo da
    gaveta fecha. Comportamento em public/javas/menu.js.

    Visual: public/css/painel.css (CSS simples, sem build).
    Comportamento: Alpine.js, que já vem no resources/js/app.js do Breeze.
    Gráficos: public/javas/graficos.js (SVG puro, sem biblioteca).
--}}

@php
    $usuario = auth()->user();
    $nomeUsuario = $usuario->name ?? '';
    $papel = \App\Support\Formatador::PAPEIS[$usuario->tipo ?? ''] ?? '';
    $rotaPerfil = ($usuario && \Illuminate\Support\Facades\Route::has($usuario->tipo . '.perfil'))
        ? route($usuario->tipo . '.perfil')
        : null;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('titulo', 'Painel') — PointMed</title>

    {{-- 12/10/2026: simbolo-icone.png = só o símbolo, recortado do logo.png. --}}
    <link rel="icon" href="{{ asset('imgs/marca/simbolo-icone.png') }}" type="image/png">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=open-sans:400,600,700|rosario:600,700&display=swap" rel="stylesheet">
    {{-- Sem Vite/Node desde 24/09: reset em base.css e Alpine local. --}}
    <link rel="stylesheet" href="{{ asset('css/base.css') }}">
    {{-- Sem "defer" e antes do Alpine: aplica o menu recolhido antes de desenhar (ver o arquivo). --}}
    <script src="{{ asset('javas/menu.js') }}"></script>
    <script src="{{ asset('javas/alpine.min.js') }}" defer></script>
    <link rel="stylesheet" href="{{ asset('css/painel.css') }}">

    @stack('head')
</head>

<body class="fm-app" x-data="fmMenu()" @keydown.escape.window="menu = false; perfil = false">

    <x-sidebar />

    {{-- Fundo escuro atrás da gaveta, só no celular --}}
    <div class="fm-fundo-menu" x-show="menu" x-cloak @click="menu = false"></div>

    <div class="fm-principal">

        <header class="fm-topo">
            {{-- Celular: o símbolo no lugar do ☰ (12/10/2026). Some no computador. --}}
            <button type="button" class="fm-topo__menu" @click="menu = true" :aria-expanded="menu" aria-label="Abrir menu">
                <img src="{{ asset('imgs/marca/simbolo-icone.png') }}" alt="" width="36" height="36">
            </button>

            <div class="fm-usuario" @click.outside="perfil = false">
                <button
                    type="button"
                    class="fm-usuario__botao"
                    @click="perfil = !perfil"
                    :aria-expanded="perfil"
                    aria-haspopup="menu"
                >
                    <x-avatar :nome="$nomeUsuario" :foto="$usuario?->foto_url" />
                    <span class="fm-usuario__texto">
                        <strong>{{ $nomeUsuario }}</strong>
                        <small>{{ $papel }}</small>
                    </span>
                    <x-icone nome="chevron-down" class="fm-usuario__seta" />
                </button>

                <div class="fm-usuario__menu" x-show="perfil" x-cloak role="menu">
                    @if ($rotaPerfil)
                        <a href="{{ $rotaPerfil }}" role="menuitem">Meu perfil</a>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" role="menuitem">Sair</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="fm-conteudo">
            @if (session('sucesso'))
                <div class="fm-flash fm-flash--ok" role="status">{{ session('sucesso') }}</div>
            @endif

            @if (session('erro'))
                <div class="fm-flash fm-flash--erro" role="alert">{{ session('erro') }}</div>
            @endif

            @yield('conteudo')
        </main>

    </div>

    {{-- 24/09: o arquivo real esta em public/javas/, nao em public/js/. --}}
    <script src="{{ asset('javas/graficos.js') }}" defer></script>
    @stack('scripts')
</body>
</html>
