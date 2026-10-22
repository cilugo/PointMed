<?php

namespace App\Http\Requests\Clinica;

use App\Support\Uf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Nova unidade (clínica ou hospital) da clínica logada. */
class SalvarUnidadeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->ehClinica() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cep'      => preg_replace('/\D/', '', (string) $this->input('cep')),
            'telefone' => preg_replace('/\D/', '', (string) $this->input('telefone')),
            'uf'       => mb_strtoupper(trim((string) $this->input('uf'))),
            'site'     => self::normalizarSite($this->input('site')),
        ]);
    }

    /**
     * 13/10/2026: "www.clinica.com.br" vira "https://www.clinica.com.br" (quase
     * ninguém digita o https://). Com outro esquema ("javascript:", "ftp:") fica
     * como veio, e a regra url:http,https recusa.
     */
    public static function normalizarSite($site): ?string
    {
        $site = is_string($site) ? trim($site) : '';

        if ($site === '') {
            return null;
        }

        return preg_match('/^[a-z][a-z0-9+.-]*:/i', $site) ? $site : 'https://' . $site;
    }

    public function rules(): array
    {
        return $this->regrasDosDados() + [
            'faixa_preco' => ['nullable', 'integer', 'between:1,4'],   // 05/10: escolhida pela clínica
            'horarios'    => ['nullable', 'array'],
        ] + HorariosDeFuncionamento::regras();
    }

    /**
     * Nome, tipo, endereço e telefone. As mesmas regras no cadastro e na
     * edição da unidade (AtualizarUnidadeRequest, 12/10/2026).
     */
    protected function regrasDosDados(): array
    {
        return [
            'nome'        => ['required', 'string', 'max:150'],
            'tipo'        => ['required', Rule::in(['clinica', 'hospital'])],
            'cep'         => ['required', 'digits:8'],
            'endereco'    => ['required', 'string', 'max:200'],
            'numero'      => ['required', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro'      => ['required', 'string', 'max:100'],
            'cidade'      => ['required', 'string', 'max:100'],
            'uf'          => ['required', Rule::in(Uf::TODAS)],
            'telefone'    => ['nullable', 'digits_between:10,11'],
            'site'        => ['nullable', 'string', 'max:255', 'url:http,https'],   // 13/10
        ];
    }

    public function messages(): array
    {
        return [
            'cep.digits' => 'O CEP deve ter 8 dígitos.',
            'site.url'   => 'Digite o endereço do site, por exemplo www.suaclinica.com.br.',
        ] + HorariosDeFuncionamento::mensagens();
    }
}
