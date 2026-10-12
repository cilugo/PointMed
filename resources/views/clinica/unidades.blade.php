{{--
    Clínica → Unidades. Dados: Clinica\UnidadeController@index (README §7.2).

    Lista as unidades com o horário de FUNCIONAMENTO e permite editar esse
    horário, a faixa de preço, os dados (nome, tipo, endereço, telefone —
    12/10/2026), desativar/reativar a unidade e cadastrar unidade nova.

    Desativar (12/10/2026, decisão do grupo): a unidade some da busca e da
    página pública, mas os médicos continuam ligados a ela e voltam ao
    reativar. Os campos dos dados ficam em clinica/parciais/unidade-campos,
    o mesmo do "Nova unidade".

    Várias unidades = vários formulários de horário na mesma tela. Para o
    old() e os erros voltarem só no formulário certo, cada um manda um campo
    escondido "_form" (o back-end ignora).
--}}
@extends('layouts.painel')

@section('titulo', 'Unidades')

@push('head')
    <link rel="stylesheet" href="{{ asset('css/crud.css') }}">
@endpush

@php
    use App\Support\Formatador;
    $tipos = ['clinica' => 'Clínica', 'hospital' => 'Hospital', 'consultorio' => 'Consultório'];
    $formVolta = old('_form');
    $nomeDia = fn (string $dia) => Formatador::DIAS_CURTOS[array_search($dia, $dias, true)] ?? $dia;
    $ordem = array_merge(array_slice($dias, 1), [$dias[0]]);
@endphp

@section('conteudo')

    <div class="fm-pagina-topo">
        <div>
            <h1 class="fm-titulo">Unidades</h1>
            <p class="fm-subtitulo">Os endereços da clínica e quando cada um funciona.</p>
        </div>
    </div>

    @if ($locais->isNotEmpty())
        <div class="fm-locais">
            @foreach ($locais as $local)
                @php
                    $atuais = $local->horarios->mapWithKeys(fn ($h) => [$h->dia_semana => [
                        'abre' => Formatador::hora($h->abre), 'fecha' => Formatador::hora($h->fecha),
                    ]])->all();
                    $esteForm = $formVolta === 'horarios-' . $local->id;
                    $dadosVolta = $formVolta === 'dados-' . $local->id;
                @endphp
                <article class="fm-painel fm-local {{ $local->ativo ? '' : 'is-inativo' }}"
                         x-data="{ editando: {{ $esteForm ? 'true' : 'false' }}, editandoDados: {{ $dadosVolta ? 'true' : 'false' }} }">
                    <header class="fm-painel__topo">
                        <h2 class="fm-painel__titulo"><x-icone nome="building" /> {{ $local->nome }}</h2>
                        @if ($local->ativo)
                            <span class="fm-etiqueta fm-etiqueta--azul">{{ $tipos[$local->tipo] ?? $local->tipo }}</span>
                        @else
                            <span class="fm-etiqueta fm-etiqueta--cinza">Desativada</span>
                        @endif
                    </header>

                    <p class="fm-local__endereco"><x-icone nome="pin" /> {{ $local->endereco_completo }}</p>
                    @if ($local->telefone)
                        <p class="fm-local__endereco"><x-icone nome="phone" /> {{ Formatador::telefone($local->telefone) }}</p>
                    @endif

                    {{-- 05/10/2026: a faixa de preço é escolhida aqui (a Tabela de preços saiu). --}}
                    <form method="POST" action="{{ route('clinica.unidades.faixa', $local) }}" class="fm-filtros" style="margin-top: 10px;">
                        @csrf
                        @method('PUT')
                        <div class="fm-campo">
                            <label for="faixa-{{ $local->id }}">Faixa de preço da consulta particular</label>
                            <select id="faixa-{{ $local->id }}" name="faixa_preco">
                                <option value="">Não informar</option>
                                @foreach ([1, 2, 3, 4] as $n)
                                <option value="{{ $n }}" @selected((int) $local->faixa_preco === $n)>{{ str_repeat('$', $n) }} — {{ \App\Support\FaixaDePreco::descricao($n) }}</option>
                            @endforeach
                            </select>
                        </div>
                        <button type="submit" class="fm-botao fm-botao--suave fm-botao--pequeno">Salvar faixa</button>
                    </form>

                    <p class="fm-campo__ajuda" style="margin-top: 8px;">
                        {{ $local->vinculos_count }} {{ $local->vinculos_count === 1 ? 'médico atende' : 'médicos atendem' }} aqui
                    </p>

                    <ul class="fm-horario-lista" x-show="!editando">
                        @foreach ($ordem as $dia)
                            <li>
                                <span>{{ $nomeDia($dia) }}</span>
                                @if (isset($atuais[$dia]))
                                    <strong>{{ $atuais[$dia]['abre'] }}–{{ $atuais[$dia]['fecha'] }}</strong>
                                @else
                                    <em>Fechado</em>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    @unless ($local->ativo)
                        <p class="fm-campo__ajuda" style="margin-top: 8px;">
                            Desativada: não aparece na busca nem tem página pública. Os médicos continuam ligados a ela.
                        </p>
                    @endunless

                    <div class="fm-acoes-topo" style="margin-top: 12px; flex-wrap: wrap;" x-show="!editando && !editandoDados">
                        <button type="button" class="fm-pilula fm-pilula--pequena" @click="editandoDados = true"><x-icone nome="pencil" /> Editar dados</button>
                        <button type="button" class="fm-pilula fm-pilula--pequena" @click="editando = true"><x-icone nome="pencil" /> Editar horário</button>
                        <a href="{{ route('clinica.medicos', ['unidade' => $local->id]) }}" class="fm-pilula fm-pilula--pequena">Médicos <x-icone nome="chevron-right" /></a>
                        <form method="POST" action="{{ route('clinica.unidades.status', $local) }}" style="display: inline;"
                              onsubmit="return confirm('{{ $local->ativo ? 'Desativar' : 'Reativar' }} a unidade {{ addslashes($local->nome) }}?')">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="ativo" value="{{ $local->ativo ? 0 : 1 }}">
                            <button type="submit" class="fm-pilula fm-pilula--pequena">{{ $local->ativo ? 'Desativar unidade' : 'Reativar unidade' }}</button>
                        </form>
                    </div>

                    {{-- 12/10/2026: editar nome, tipo, endereço e telefone. --}}
                    <form method="POST" action="{{ route('clinica.unidades.atualizar', $local) }}" class="fm-form fm-form--tres" x-show="editandoDados" x-cloak style="margin-top: 12px;">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_form" value="dados-{{ $local->id }}">
                        @include('clinica.parciais.unidade-campos', [
                            'prefixo' => 'd' . $local->id . '-',
                            'valores' => [
                                'nome' => $local->nome, 'tipo' => $local->tipo,
                                'cep' => $local->cep ? \App\Support\Localizacao::formatarCep($local->cep) : '',
                                'endereco' => $local->endereco, 'numero' => $local->numero,
                                'complemento' => $local->complemento, 'bairro' => $local->bairro,
                                'cidade' => $local->cidade, 'uf' => $local->uf,
                                'telefone' => $local->telefone ? Formatador::telefone($local->telefone) : '',
                            ],
                            'comOld' => $dadosVolta,
                        ])
                        <div class="fm-form__acoes">
                            <button type="button" class="fm-botao fm-botao--suave fm-botao--pequeno" @click="editandoDados = false">Cancelar</button>
                            <button type="submit" class="fm-botao fm-botao--pequeno">Salvar dados</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('clinica.unidades.horarios', $local) }}" class="fm-form" x-show="editando" x-cloak style="margin-top: 12px;">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="_form" value="horarios-{{ $local->id }}">
                        @include('painel.parciais.horarios-funcionamento', ['atuais' => $atuais, 'comOld' => $esteForm])
                        <p class="fm-campo__ajuda">Dia em branco = fechado. É o horário que aparece na página da unidade.</p>
                        <div class="fm-form__acoes">
                            <button type="button" class="fm-botao fm-botao--suave fm-botao--pequeno" @click="editando = false">Cancelar</button>
                            <button type="submit" class="fm-botao fm-botao--pequeno">Salvar horário</button>
                        </div>
                    </form>
                </article>
            @endforeach
        </div>
    @endif

    {{-- ===================== NOVA UNIDADE ===================== --}}
    @php $novaVolta = $formVolta === 'nova-unidade'; @endphp
    <section class="fm-painel" style="margin-top: 18px;" x-data="{ aberto: {{ $novaVolta || $locais->isEmpty() ? 'true' : 'false' }} }">
        <header class="fm-painel__topo">
            <h2 class="fm-painel__titulo"><x-icone nome="plus" /> Nova unidade</h2>
            <button type="button" class="fm-pilula fm-pilula--pequena" @click="aberto = !aberto" x-text="aberto ? 'Fechar' : 'Abrir'" :aria-expanded="aberto">Abrir</button>
        </header>

        <form method="POST" action="{{ route('clinica.unidades.salvar') }}" class="fm-form fm-form--tres" x-show="aberto" x-cloak>
            @csrf
            <input type="hidden" name="_form" value="nova-unidade">
            @include('clinica.parciais.unidade-campos', ['prefixo' => 'u-', 'valores' => [], 'comOld' => $novaVolta])

            @php $erroFaixa = $novaVolta && $errors->has('faixa_preco'); @endphp
            <div class="fm-campo {{ $erroFaixa ? 'fm-campo--erro' : '' }}">
                <label for="faixa_preco">Faixa de preço da consulta particular</label>
                <select id="faixa_preco" name="faixa_preco">
                    <option value="">Não informar</option>
                    @foreach ([1, 2, 3, 4] as $n)
                        <option value="{{ $n }}" @selected($novaVolta && (int) old('faixa_preco') === $n)>{{ str_repeat('$', $n) }} — {{ \App\Support\FaixaDePreco::descricao($n) }}</option>
                    @endforeach
                </select>
                <span class="fm-campo__ajuda">O usuário vê só os $, nunca um valor exato.</span>
                @if ($erroFaixa) <span class="fm-campo__erro">{{ $errors->first('faixa_preco') }}</span> @endif
            </div>

            <fieldset class="fm-campo--largo fm-funcionamento">
                <legend>Horário de funcionamento</legend>
                <p class="fm-campo__ajuda">Deixe tudo em branco para usar segunda a sexta, 08:00–18:00. Dia em branco = fechado.</p>
                @include('painel.parciais.horarios-funcionamento', ['comOld' => $novaVolta])
            </fieldset>

            <div class="fm-form__acoes">
                <button type="submit" class="fm-botao">Cadastrar unidade</button>
            </div>
        </form>
    </section>

@endsection
