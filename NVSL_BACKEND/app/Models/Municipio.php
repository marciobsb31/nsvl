<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Municipio — Município brasileiro (dados replicados do IBGE).
 *
 * @property int    $id
 * @property string $nome
 * @property int    $uf_id   FK → ufs.id
 */
class Municipio extends Model
{
    protected $table = 'municipios';
    public $timestamps = false;

    protected $fillable = [
        'nome',
        'uf_id',
    ];

    protected $casts = [
        'uf_id' => 'integer',
    ];

    public function uf(): BelongsTo
    {
        return $this->belongsTo(Uf::class, 'uf_id');
    }
}
