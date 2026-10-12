{{--
    Campos de nome, tipo, endereço e telefone da unidade (12/10/2026).
    Usado em Clínica → Unidades por "Nova unidade" e por "Editar dados",
    para os dois formulários nunca ficarem diferentes (as regras ficam em
    SalvarUnidadeRequest::regrasDosDados).

    Parâmetros:
      $prefixo  — começo dos ids (cada formulário da tela precisa de ids próprios)
      $valores  — valores atuais (edição) ou [] (cadastro)
      $comOld   — true se ESTE formulário acabou de voltar com erro
                  (aí valem o old() e as mensagens de erro)
--}}
@php
    use App\Support\Uf;
    $e = fn (string $campo) => $comOld && $errors->has($campo);
    $v = fn (string $campo, $padrao = '') => $comOld ? old($campo, $valores[$campo] ?? $padrao) : ($valores[$campo] ?? $padrao);
@endphp

<div class="fm-campo fm-campo--duplo {{ $e('nome') ? 'fm-campo--erro' : '' }}">
    <label for="{{ $prefixo }}nome">Nome da unidade *</label>
    <input id="{{ $prefixo }}nome" name="nome" maxlength="150" required value="{{ $v('nome') }}" placeholder="Ex.: Unidade Centro">
    @if ($e('nome')) <span class="fm-campo__erro">{{ $errors->first('nome') }}</span> @endif
</div>

<div class="fm-campo {{ $e('tipo') ? 'fm-campo--erro' : '' }}">
    <label for="{{ $prefixo }}tipo">Tipo *</label>
    <select id="{{ $prefixo }}tipo" name="tipo" required>
        <option value="clinica" @selected($v('tipo', 'clinica') === 'clinica')>Clínica</option>
        <option value="hospital" @selected($v('tipo') === 'hospital')>Hospital</option>
    </select>
    @if ($e('tipo')) <span class="fm-campo__erro">{{ $errors->first('tipo') }}</span> @endif
</div>

@foreach ([
    ['cep', 'CEP *', 'inputmode="numeric" maxlength="9" required placeholder="12345-678"'],
    ['endereco', 'Endereço *', 'maxlength="200" required'],
    ['numero', 'Número *', 'maxlength="20" required'],
    ['complemento', 'Complemento', 'maxlength="100"'],
    ['bairro', 'Bairro *', 'maxlength="100" required'],
    ['cidade', 'Cidade *', 'maxlength="100" required'],
] as [$campo, $rotulo, $attrs])
    <div class="fm-campo {{ $e($campo) ? 'fm-campo--erro' : '' }}">
        <label for="{{ $prefixo }}{{ $campo }}">{{ $rotulo }}</label>
        <input id="{{ $prefixo }}{{ $campo }}" name="{{ $campo }}" value="{{ $v($campo) }}" {!! $attrs !!}>
        @if ($e($campo)) <span class="fm-campo__erro">{{ $errors->first($campo) }}</span> @endif
    </div>
@endforeach

<div class="fm-campo {{ $e('uf') ? 'fm-campo--erro' : '' }}">
    <label for="{{ $prefixo }}uf">UF *</label>
    <select id="{{ $prefixo }}uf" name="uf" required>
        @foreach (Uf::TODAS as $uf)
            <option value="{{ $uf }}" @selected($v('uf', 'SP') === $uf)>{{ $uf }}</option>
        @endforeach
    </select>
    @if ($e('uf')) <span class="fm-campo__erro">{{ $errors->first('uf') }}</span> @endif
</div>

<div class="fm-campo {{ $e('telefone') ? 'fm-campo--erro' : '' }}">
    <label for="{{ $prefixo }}telefone">Telefone</label>
    <input id="{{ $prefixo }}telefone" name="telefone" type="tel" inputmode="numeric" maxlength="15" value="{{ $v('telefone') }}">
    @if ($e('telefone')) <span class="fm-campo__erro">{{ $errors->first('telefone') }}</span> @endif
</div>
