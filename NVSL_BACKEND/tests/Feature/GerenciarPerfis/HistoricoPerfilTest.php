<?php

namespace Tests\Feature\GerenciarPerfis;

use App\Models\AuditLog;
use App\Models\Perfil;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class HistoricoPerfilTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function usuario_nao_autenticado_recebe_401(): void
    {
        $perfil = Perfil::factory()->create();

        $this->getJson("/api/gerenciar-perfis/{$perfil->id}/historico")
            ->assertStatus(401);
    }

    #[Test]
    public function usuario_federal_acessa_historico(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil = Perfil::factory()->create();

        AuditLog::create([
            'user_id'        => $user->id,
            'action'         => 'gerenciar_perfis.cadastrar',
            'tipo_operacao'  => AuditLog::TIPO_INSERT,
            'tabela_afetada' => 'perfis',
            'registro_id'    => $perfil->id,
            'context'        => ['perfil_nome' => $perfil->nome],
        ]);

        $this->autenticar($user)
            ->getJson("/api/gerenciar-perfis/{$perfil->id}/historico")
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'data_hora', 'usuario', 'atualizacao', 'action']]]);
    }

    #[Test]
    public function historico_de_perfil_inexistente_retorna_404(): void
    {
        $user = $this->criarUsuarioFederal();

        $this->autenticar($user)
            ->getJson('/api/gerenciar-perfis/99999/historico')
            ->assertStatus(404);
    }

    #[Test]
    public function historico_retorna_apenas_logs_do_perfil_especifico(): void
    {
        $user = $this->criarUsuarioFederal();
        $perfil1 = Perfil::factory()->create();
        $perfil2 = Perfil::factory()->create();

        AuditLog::create([
            'user_id' => $user->id, 'action' => 'gerenciar_perfis.cadastrar',
            'tipo_operacao' => 'insert', 'tabela_afetada' => 'perfis', 'registro_id' => $perfil1->id,
        ]);
        AuditLog::create([
            'user_id' => $user->id, 'action' => 'gerenciar_perfis.cadastrar',
            'tipo_operacao' => 'insert', 'tabela_afetada' => 'perfis', 'registro_id' => $perfil2->id,
        ]);

        $response = $this->autenticar($user)
            ->getJson("/api/gerenciar-perfis/{$perfil1->id}/historico")
            ->assertOk();

        $this->assertCount(1, $response->json('data'));
    }
}
