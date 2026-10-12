{{--
    Sidebar do PointMed, no estilo dos mockups (fundo claro, item ativo
    em azul-claro, marca no topo).

    Lê os itens de config/navegacao.php pelo tipo do usuário logado. Não
    escreva item de menu direto aqui: se estiver só no Blade, ninguém acha
    quando precisar mudar, e o menu de um papel sai do ar sem ninguém
    perceber.

    Só mostra o item se a ROTA EXISTE (Route::has). Assim o menu nunca
    aponta para uma tela que ninguém criou ainda.

    Precisa estar dentro de x-data="fmMenu()" (public/javas/menu.js) — o
    layout layouts/painel.blade.php já faz isso. 12/10/2026: o símbolo no
    topo do menu é o botão — no computador recolhe/abre, no celular fecha a
    gaveta. Recolhido, os nomes dos itens ficam escondidos só para a vista
    (o leitor de tela continua lendo) e aparecem ao passar o mouse (title).

    Uso:  <x-sidebar />
--}}

@php
    $usuario = auth()->user();

    $itens = collect($usuario ? config('navegacao.' . $usuario->tipo, []) : [])
        ->filter(fn ($item) => \Illuminate\Support\Facades\Route::has($item['rota']))
        ->map(fn ($item) => $item + [
            'url'   => route($item['rota']),
            'ativo' => request()->routeIs($item['rota'] . '*'),
        ])
        ->values()
        ->all();

@endphp

<aside class="fm-sidebar" :class="{ 'is-open': menu }" aria-label="Menu principal">

    <div class="fm-sidebar__marca">
        <button type="button" class="fm-marca" @click="alternarMarca()"
                :aria-label="rotuloMarca()" :aria-expanded="menu || !recolhido" aria-label="Recolher menu">
            <img class="fm-marca__simbolo" src="{{ asset('imgs/marca/simbolo-icone.png') }}" alt="" width="40" height="40">
            <img class="fm-marca__nome" src="{{ asset('imgs/marca/nome.png') }}" alt="PointMed" width="759" height="147">
        </button>
    </div>

    <nav class="fm-sidebar__nav">
        <ul>
            @foreach ($itens as $item)
                <li>
                    <a
                        href="{{ $item['url'] }}"
                        class="fm-nav {{ $item['ativo'] ? 'is-ativo' : '' }}"
                        title="{{ $item['label'] }}"
                        @if ($item['ativo']) aria-current="page" @endif
                    >
                        <x-icone :nome="$item['icone']" />
                        <span>{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="fm-sidebar__rodape">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="fm-nav fm-nav--sair" title="Sair">
                <x-icone nome="logout" />
                <span>Sair</span>
            </button>
        </form>
    </div>
</aside>
