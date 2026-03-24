<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * AuditLogService — Registra ações no auditoria_log
 *
 * Registra: quem (user_id), data/hora (created_at), tipo de operação,
 * tabela afetada, registro_id. Append-only.
 */
class AuditLogService
{
    public function __construct(
        private readonly Request $request
    ) {}

    /**
     * Registra uma ação no auditoria_log.
     *
     * @param string     $action         Ex: 'auth.login', 'gerenciar_cadastros.listagem'
     * @param int|null   $userId         ID do usuário (quem fez a ação)
     * @param array      $context        Dados adicionais (sem informações sensíveis)
     * @param string     $tipoOperacao   login|logout|insert|update|delete|view
     * @param string|null $tabelaAfetada Ex: 'solicitacoes_cadastro'
     * @param int|null   $registroId     ID do registro inserido/alterado
     */
    public function log(
        string $action,
        ?int $userId = null,
        array $context = [],
        string $tipoOperacao = AuditLog::TIPO_VIEW,
        ?string $tabelaAfetada = null,
        ?int $registroId = null
    ): void {
        AuditLog::create([
            'user_id'        => $userId,
            'action'         => $action,
            'tipo_operacao'  => $tipoOperacao,
            'tabela_afetada' => $tabelaAfetada,
            'registro_id'    => $registroId,
            'ip_address'     => $this->request->ip(),
            'user_agent'     => $this->request->userAgent(),
            'context'        => $this->maskSensitiveFields($context),
        ]);
    }

    /**
     * Remove campos sensíveis do contexto antes de persistir.
     */
    private function maskSensitiveFields(array $context): array
    {
        $masked = config('audit.masked_fields', []);

        foreach ($masked as $field) {
            if (array_key_exists($field, $context)) {
                $context[$field] = '***';
            }
        }

        return $context;
    }
}
