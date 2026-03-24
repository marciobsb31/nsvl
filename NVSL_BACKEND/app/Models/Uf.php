<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Uf — Unidade Federativa (estado).
 * Estrutura baseada no IBGE; dados replicados no banco.
 *
 * @property int $id
 * @property int $id_ibge
 * @property string $sigla
 * @property string $nome
 * @property string|null $regiao_sigla
 * @property string|null $regiao_nome
 */
class Uf extends Model
{
    protected $table = 'ufs';

    protected $fillable = [
        'id_ibge',
        'sigla',
        'nome',
        'regiao_sigla',
        'regiao_nome',
    ];

    protected $casts = [
        'id_ibge' => 'integer',
    ];

    public function municipios(): HasMany
    {
        return $this->hasMany(Municipio::class, 'uf_id');
    }
}
