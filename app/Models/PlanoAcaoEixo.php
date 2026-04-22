<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanoAcaoEixo extends Model
{
    protected $table = 'plano_acao_eixos';

    protected $fillable = [
        'usuario_id',
        'eixo_numero',
        'acoes',
    ];

    protected $casts = [
        'acoes'       => 'array',
        'eixo_numero' => 'integer',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
