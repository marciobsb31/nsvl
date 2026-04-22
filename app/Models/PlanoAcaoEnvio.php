<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanoAcaoEnvio extends Model
{
    protected $table = 'plano_acao_envios';

    protected $fillable = [
        'usuario_id',
        'responsavel_nome',
        'responsavel_cargo',
        'responsavel_orgao',
        'responsavel_contato',
        'justificativa_eixo_1',
        'justificativa_eixo_2',
        'justificativa_eixo_3',
        'justificativa_eixo_4',
        'status',
        'enviado_em',
    ];

    protected $casts = [
        'enviado_em' => 'datetime',
    ];

    public function isEnviado(): bool
    {
        return $this->status === 'em_analise';
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
