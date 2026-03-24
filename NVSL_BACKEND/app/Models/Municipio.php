<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Municipio — Município brasileiro.
 * Estrutura baseada no IBGE; dados replicados no banco.
 *
 * @property int $id
 * @property int $id_ibge
 * @property string $nome
 * @property int $uf_id
 */
class Municipio extends Model
{
    protected $fillable = [
        'id_ibge',
        'nome',
        'uf_id',
    ];

    protected $casts = [
        'id_ibge' => 'integer',
        'uf_id' => 'integer',
    ];

    public function uf(): BelongsTo
    {
        return $this->belongsTo(Uf::class, 'uf_id');
    }
}
