<?php

namespace Tests\Unit\Services;

use App\Models\AuditLog;
use App\Services\Audit\AuditLogService;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class AuditLogServiceTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function registra_log_basico(): void
    {
        $service = app(AuditLogService::class);

        $service->log('test.action', null, ['key' => 'value']);

        $this->assertDatabaseHas('auditoria_log', [
            'action'        => 'test.action',
            'tipo_operacao' => 'view',
        ]);
    }

    #[Test]
    public function registra_log_com_todos_os_campos(): void
    {
        $user = $this->criarUsuarioFederal();
        $service = app(AuditLogService::class);

        $service->log(
            'gerenciar_perfis.cadastrar',
            $user->id,
            ['perfil' => 'Admin'],
            AuditLog::TIPO_INSERT,
            'perfis',
            42
        );

        $this->assertDatabaseHas('auditoria_log', [
            'user_id'        => $user->id,
            'action'         => 'gerenciar_perfis.cadastrar',
            'tipo_operacao'  => 'insert',
            'tabela_afetada' => 'perfis',
            'registro_id'    => 42,
        ]);
    }

    #[Test]
    public function mascara_campos_sensiveis_configurados(): void
    {
        config(['audit.masked_fields' => ['cpf', 'senha']]);
        $service = app(AuditLogService::class);

        $service->log('test', null, ['cpf' => '12345678901', 'nome' => 'João', 'senha' => 'abc']);

        $log = AuditLog::latest('id')->first();
        $this->assertEquals('***', $log->context['cpf']);
        $this->assertEquals('***', $log->context['senha']);
        $this->assertEquals('João', $log->context['nome']);
    }

    #[Test]
    public function aceita_user_id_nulo(): void
    {
        $service = app(AuditLogService::class);

        $service->log('auth.redirect', null, ['state' => 'xyz']);

        $log = AuditLog::latest('id')->first();
        $this->assertNull($log->user_id);
    }
}
