<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Uf — Unidade Federativa (estado).
 *
 * @property int $id
 * @property string $sigla
 * @property string $nome
 */
class Uf extends Model
{
    protected $table = 'ufs';

    public $timestamps = false;

    protected $fillable = [
        'sigla',
        'nome',
        'sigla',
        'codigo_ibge',
    ];

    public function municipios(): HasMany
    {
        return $this->hasMany(Municipio::class, 'uf_id');
    }

    public function solicitacoesCadastro(): HasMany
    {
        return $this->hasMany(SolicitacaoCadastro::class, 'uf_id');
    }
}
