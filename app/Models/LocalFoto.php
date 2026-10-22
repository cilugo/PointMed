<?php

namespace App\Models;

use App\Support\FotoDePerfil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma foto da galeria de uma unidade (13/10/2026). A imagem em si fica na
 * tabela `fotos`; aqui fica a referência ("foto:<chave>" ou "imgs/...") —
 * ver a migration galeria_e_site_dos_locais.
 *
 * Quem cria: Clinica\FotoDaUnidadeController (até Local::MAX_FOTOS).
 */
class LocalFoto extends Model
{
    protected $table = 'local_fotos';

    protected $fillable = ['foto', 'ordem'];

    /**
     * Apagou a foto da galeria → apaga a imagem da tabela `fotos` junto
     * (static::deleted = "depois de apagar", evento do Eloquent). Foto de
     * demonstração ("imgs/...") é arquivo do projeto e nunca é apagada.
     */
    protected static function booted(): void
    {
        static::deleted(fn (LocalFoto $foto) => FotoDePerfil::apagar($foto->foto));
    }

    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class);
    }

    public function getUrlAttribute(): ?string
    {
        return FotoDePerfil::url($this->foto);
    }
}
