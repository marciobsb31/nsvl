<?php

namespace Tests\Integracao;

use App\Models\AuditLog;
use App\Models\PerfilUsuario;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

/**
 * Teste completo da HU: Troca de Contexto
 * Valida 100% dos critérios de aceite
 */
class TrocaContextoComplexoTest extends TestCase
{
    use DatabaseMigrations;

    private Usuario $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = Usuario::where('govbr_sub', '11144477735')->firstOrFail(); // Maria Silva Federal
    }

    // -------------------------------------------------------
    // Cenário 1: Exibir botão apenas com múltiplos perfis
    // -------------------------------------------------------

    public function test_criterio_1_usuario_com_multiplos_perfis_ativos_recebe_lista(): void
    {
        $this->actingAs($this->user);

        $perfisAtivos = $this->user->perfisVigentes();
        $this->assertGreaterThanOrEqual(2, $perfisAtivos->count(), 'Usuário deve ter 2+ perfis vigentes');

        $response = $this->getJson('/api/user');
        $response->assertOk();
        $response->assertJsonStructure([
            'perfis_vigentes' => [
                0 => [
                    'perfil_usuario_id',
                    'perfil_id',
                    'nome',
                    'ativo',
                ]
            ]
        ]);

        $this->assertGreaterThanOrEqual(2, count($response->json('perfis_vigentes')));
    }

    public function test_criterio_2_listar_perfis_ativos_com_detalhes_completos(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/user/perfis-ativos');
        $response->assertOk();

        // Validar estrutura da resposta
        $response->assertJsonStructure([
            'data' => [
                0 => [
                    'perfil_usuario_id',
                    'perfil_id',
                    'nome',
                    'data_inicio_vigencia',
                    'data_fim_vigencia',
                    'ativo',
                ]
            ]
        ]);

        $perfis = $response->json('data');
        $this->assertGreaterThanOrEqual(2, count($perfis), 'Deve listar múltiplos perfis');

        // Validar cada perfil
        foreach ($perfis as $perfil) {
            $this->assertIsInt($perfil['perfil_usuario_id']);
            $this->assertIsInt($perfil['perfil_id']);
            $this->assertNotEmpty($perfil['nome'], 'Nome do perfil não pode ser vazio');
            $this->assertIsBool($perfil['ativo']);
        }
    }

    // -------------------------------------------------------
    // Cenário 3: Alternar perfil ativo
    // -------------------------------------------------------

    public function test_criterio_3_selecionar_novo_perfil_ativo(): void
    {
        $this->actingAs($this->user);

        $perfisAtivos = $this->user->perfisVigentes();
        $this->assertGreaterThanOrEqual(2, $perfisAtivos->count());

        $perfilAnterior = $perfisAtivos->firstWhere('pivot.ativo', true);
        $novoPerfilId = $perfisAtivos
            ->where('pivot.id', '!=', $perfilAnterior->pivot->id)
            ->first()
            ->pivot->id;

        $response = $this->postJson('/api/user/trocar-contexto', [
            'perfil_usuario_id' => $novoPerfilId,
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'user' => [
                'perfis_vigentes'
            ]
        ]);

        // Validar que o perfil foi alterado
        $userAtualizado = $response->json('user');
        $novoPerfilAtivo = collect($userAtualizado['perfis_vigentes'])
            ->firstWhere('ativo', true);
        $this->assertEquals($novoPerfilId, $novoPerfilAtivo['perfil_usuario_id']);
    }

    // -------------------------------------------------------
    // Cenário 4: Validações
    // -------------------------------------------------------

    public function test_criterio_4_rejeita_perfil_invalido(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/user/trocar-contexto', [
            'perfil_usuario_id' => 99999,
        ]);

        $response->assertForbidden();
        $response->assertJsonPath('message', 'O perfil selecionado não está ativo ou não pertence ao seu cadastro.');
    }

    public function test_criterio_4_exige_campo_obrigatorio(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/user/trocar-contexto', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['perfil_usuario_id']);
    }

    public function test_criterio_4_apenas_autenticados_podem_trocar(): void
    {
        $response = $this->postJson('/api/user/trocar-contexto', [
            'perfil_usuario_id' => 1,
        ]);

        $response->assertUnauthorized();
    }

    // -------------------------------------------------------
    // Cenário 5: Auditoria registrada
    // -------------------------------------------------------

    public function test_criterio_5_registra_auditoria_contexto_alterado(): void
    {
        $this->actingAs($this->user);

        $perfisAtivos = $this->user->perfisVigentes();
        $perfilAnterior = $perfisAtivos->firstWhere('pivot.ativo', true);
        $novoPerfilId = $perfisAtivos
            ->where('pivot.id', '!=', $perfilAnterior->pivot->id)
            ->first()
            ->pivot->id;

        $response = $this->postJson('/api/user/trocar-contexto', [
            'perfil_usuario_id' => $novoPerfilId,
        ]);

        $response->assertOk();

        // Validar registro de auditoria
        $audit = AuditLog::where('user_id', $this->user->id)
            ->where('tipo_acao', 'contexto.troca')
            ->latest()
            ->first();

        $this->assertNotNull($audit, 'Deve registrar auditoria de troca de contexto');
        $this->assertEquals('contexto.troca', $audit->tipo_acao);
        $this->assertEquals($this->user->id, $audit->user_id);
        $this->assertEquals('UPDATE', $audit->tipo_alteracao);

        // Validar dados da auditoria
        $dados = $audit->alteracoes;
        $this->assertArrayHasKey('perfil_anterior_id', $dados);
        $this->assertArrayHasKey('novo_perfil_id', $dados);
        $this->assertArrayHasKey('perfil_anterior_nome', $dados);
        $this->assertArrayHasKey('novo_perfil_nome', $dados);
    }

    // -------------------------------------------------------
    // Cenário 6: Sessão mantida após troca
    // -------------------------------------------------------

    public function test_criterio_6_sessao_mantida_apos_troca(): void
    {
        $this->actingAs($this->user);

        $tokenAnterior = auth('sanctum')->user()->currentAccessToken();

        $perfisAtivos = $this->user->perfisVigentes();
        $novoPerfilId = $perfisAtivos
            ->where('pivot.id', '!=', $perfisAtivos->firstWhere('pivot.ativo', true)->pivot->id)
            ->first()
            ->pivot->id;

        $this->postJson('/api/user/trocar-contexto', [
            'perfil_usuario_id' => $novoPerfilId,
        ])->assertOk();

        // Validar que o token ainda é válido
        $response = $this->getJson('/api/user');
        $response->assertOk();
        $this->assertEquals($this->user->id, $response->json('id'));
    }

    // -------------------------------------------------------
    // Cenário 7: Indicação visual do perfil ativo
    // -------------------------------------------------------

    public function test_criterio_7_perfil_ativo_marcado_na_lista(): void
    {
        $this->actingAs($this->user);

        $response = $this->getJson('/api/user/perfis-ativos');
        $response->assertOk();

        $perfis = $response->json('data');
        $ativos = collect($perfis)->where('ativo', true);

        // Deve haver exatamente 1 perfil ativo
        $this->assertEquals(1, $ativos->count(), 'Deve haver exatamente um perfil ativo');
    }

    // -------------------------------------------------------
    // Cenário 8: Perfil anterior é desativado
    // -------------------------------------------------------

    public function test_criterio_8_perfil_anterior_desativado_apos_troca(): void
    {
        $this->actingAs($this->user);

        $perfisAtivos = $this->user->perfisVigentes();
        $perfilAnterior = $perfisAtivos->firstWhere('pivot.ativo', true);
        $novoPerfilId = $perfisAtivos
            ->where('pivot.id', '!=', $perfilAnterior->pivot->id)
            ->first()
            ->pivot->id;

        $this->postJson('/api/user/trocar-contexto', [
            'perfil_usuario_id' => $novoPerfilId,
        ]);

        $this->user->refresh();

        // Validar que apenas um perfil está ativo
        $perfisAtivos = PerfilUsuario::where('usuario_id', $this->user->id)
            ->where('ativo', true)
            ->get();

        $this->assertEquals(1, $perfisAtivos->count());
        $this->assertEquals($novoPerfilId, $perfisAtivos->first()->id);
    }

    // -------------------------------------------------------
    // Cenário 9: Dados atualizados no retorno
    // -------------------------------------------------------

    public function test_criterio_9_resposta_contem_usuario_atualizado(): void
    {
        $this->actingAs($this->user);

        $perfisAtivos = $this->user->perfisVigentes();
        $novoPerfilId = $perfisAtivos
            ->where('pivot.id', '!=', $perfisAtivos->firstWhere('pivot.ativo', true)->pivot->id)
            ->first()
            ->pivot->id;

        $response = $this->postJson('/api/user/trocar-contexto', [
            'perfil_usuario_id' => $novoPerfilId,
        ]);

        $response->assertOk();

        $user = $response->json('user');
        $this->assertArrayHasKey('perfis_vigentes', $user);
        $this->assertArrayHasKey('perfil_ativo_id', $user);

        // O novo perfil deve estar marcado como ativo
        $novoAtivo = collect($user['perfis_vigentes'])
            ->firstWhere('ativo', true);
        $this->assertEquals($novoPerfilId, $novoAtivo['perfil_usuario_id']);
    }
}
