<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanoAcaoIdentificacao extends Model
{
    protected $table = 'plano_acao_identificacoes';

    protected $fillable = [
        'usuario_id',
        'orgao_gestor',
        'secretarias_envolvidas',
        'vigencia_inicio',
        'vigencia_fim',
    ];

    protected $casts = [
        'secretarias_envolvidas' => 'array',
        'vigencia_inicio'        => 'date',
        'vigencia_fim'           => 'date',
        'created_at'             => 'datetime',
        'updated_at'             => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
