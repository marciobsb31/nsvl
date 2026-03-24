<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model AuditLog — registro imutável de ações do sistema (tabela auditoria_log)
 *
 * Registra: quem (user_id), data/hora (created_at), tipo de operação,
 * tabela afetada e registro. Usado para rastrear login, inserções e alterações.
 *
 * @property int         $id
 * @property int|null    $user_id
 * @property string      $action
 * @property string      $tipo_operacao  login|logout|insert|update|delete|view
 * @property string|null $tabela_afetada
 * @property int|null    $registro_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array|null $context
 * @property \Carbon\Carbon $created_at
 */
class AuditLog extends Model
{
    protected $table = 'auditoria_log';

    const UPDATED_AT = null;

    const TIPO_LOGIN = 'login';
    const TIPO_LOGOUT = 'logout';
    const TIPO_INSERT = 'insert';
    const TIPO_UPDATE = 'update';
    const TIPO_DELETE = 'delete';
    const TIPO_VIEW = 'view';

    protected $fillable = [
        'user_id',
        'action',
        'tipo_operacao',
        'tabela_afetada',
        'registro_id',
        'ip_address',
        'user_agent',
        'context',
    ];

    protected $casts = [
        'context'    => 'array',
        'created_at' => 'datetime',
    ];

    // -------------------------------------------------------
    // Relações
    // -------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
