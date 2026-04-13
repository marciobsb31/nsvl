<?php

namespace App\Services\Audit;

use App\Enums\TipoAuditoria;
use App\Models\AuditLog;
use Illuminate\Http\Request;

/**
 * AuditLogService — Registra ações no auditoria_log
 */
class AuditLogService
{
    public function __construct(
        private readonly Request $request
    ) {}

    /**
     * Registra uma ação no auditoria_log.
     *
     * @param  string  $acao  Ex: 'auth.login', 'gerenciar_cadastros.listagem'
     * @param  int|null  $userId  ID do usuário (quem fez a ação)
     * @param  array  $contexto  Dados adicionais (sem informações sensíveis)
     * @param  string  $tipoOperacao  login|logout|insert|update|delete|view
     * @param  string|null  $tabelaAfetada  Ex: 'solicitacoes_cadastro'
     * @param  int|null  $registroId  ID do registro inserido/alterado
     */
    public function log(
        string $acao,
        ?int $userId = null,
        array $contexto = [],
        string $tipoOperacao = TipoAuditoria::VIEW->name,
        ?string $tabelaAfetada = null,
        ?int $registroId = null
    ): void {
        AuditLog::create([
            'user_id'        => $userId,
            'acao'           => $acao,
            'tipo_operacao'  => $tipoOperacao,
            'tabela_afetada' => $tabelaAfetada,
            'registro_id'    => $registroId,
            'ip_address'     => $this->request->ip(),
            'user_agent'     => $this->request->userAgent(),
            'contexto'       => $this->maskSensitiveFields($contexto),
        ]);
    }

    private function maskSensitiveFields(array $contexto): array
    {
        $masked = config('audit.masked_fields', []);

        foreach ($masked as $field) {
            if (array_key_exists($field, $contexto)) {
                $contexto[$field] = '***';
            }
        }

        return $contexto;
    }
}
