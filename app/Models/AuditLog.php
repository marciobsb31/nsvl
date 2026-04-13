<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $table = 'auditoria_log';

    protected $fillable = [
        'usuario_id',
        'acao',
        'tipo_operacao',
        'tabela_afetada',
        'registro_id',
        'ip_address',
        'user_agent',
        'contexto',
    ];

    const UPDATED_AT = null;

    protected $casts = [
        'contexto'   => 'array',
        'created_at' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function scopePorTabela($query, string $tabela)
    {
        return $query->where('tabela_afetada', $tabela);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo_operacao', $tipo);
    }
}
