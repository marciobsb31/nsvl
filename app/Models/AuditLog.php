<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model AuditLog — registro imutável de ações do sistema (tabela auditoria_log)
 *
 * @property int         $id
 * @property int|null    $usuario_id       FK → usuarios.id
 * @property string      $acao
 * @property string      $tipo_operacao    login|logout|insert|update|delete|view
 * @property string|null $tabela_afetada
 * @property int|null    $registro_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array|null  $contexto
 * @property \Carbon\Carbon $created_at
 */
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
}
