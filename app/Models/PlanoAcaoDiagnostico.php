<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanoAcaoDiagnostico extends Model
{
    protected $table = 'plano_acao_diagnosticos';

    protected $fillable = [
        'usuario_id',
        'caracterizacao_populacao',
        'barreiras_urbanisticas',
        'barreiras_transportes',
        'barreiras_atitudinais',
        'barreiras_arquitetonicas',
        'barreiras_comunicacoes',
        'barreiras_tecnologicas',
        'outras_barreiras',
    ];

    protected $casts = [
        'barreiras_urbanisticas'  => 'array',
        'barreiras_transportes'   => 'array',
        'barreiras_atitudinais'   => 'array',
        'barreiras_arquitetonicas' => 'array',
        'barreiras_comunicacoes'  => 'array',
        'barreiras_tecnologicas'  => 'array',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
