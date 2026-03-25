<?php

namespace Tests\Unit\Models;

use App\Models\AuditLog;
use App\Models\User;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AuditLogTest extends TestCase
{
    #[Test]
    public function pode_ser_criado(): void
    {
        $log = AuditLog::create([
            'action'        => 'test.action',
            'tipo_operacao' => AuditLog::TIPO_VIEW,
            'context'       => ['key' => 'value'],
        ]);

        $this->assertDatabaseHas('auditoria_log', ['action' => 'test.action']);
    }

    #[Test]
    public function pertence_a_um_user(): void
    {
        $user = User::factory()->create();
        $log = AuditLog::create([
            'user_id'       => $user->id,
            'action'        => 'test',
            'tipo_operacao' => AuditLog::TIPO_LOGIN,
        ]);

        $this->assertEquals($user->id, $log->user->id);
    }

    #[Test]
    public function context_e_castado_como_array(): void
    {
        $log = AuditLog::create([
            'action'        => 'test',
            'tipo_operacao' => AuditLog::TIPO_VIEW,
            'context'       => ['perfil' => 'Admin', 'total' => 5],
        ]);

        $this->assertIsArray($log->fresh()->context);
        $this->assertEquals('Admin', $log->fresh()->context['perfil']);
    }

    #[Test]
    public function constantes_de_tipo_estao_corretas(): void
    {
        $this->assertEquals('login', AuditLog::TIPO_LOGIN);
        $this->assertEquals('logout', AuditLog::TIPO_LOGOUT);
        $this->assertEquals('insert', AuditLog::TIPO_INSERT);
        $this->assertEquals('update', AuditLog::TIPO_UPDATE);
        $this->assertEquals('delete', AuditLog::TIPO_DELETE);
        $this->assertEquals('view', AuditLog::TIPO_VIEW);
    }

    #[Test]
    public function tabela_customizada_esta_correta(): void
    {
        $this->assertEquals('auditoria_log', (new AuditLog())->getTable());
    }

    #[Test]
    public function nao_usa_updated_at(): void
    {
        $this->assertNull(AuditLog::UPDATED_AT);
    }
}
