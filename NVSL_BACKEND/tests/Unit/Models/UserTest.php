<?php

namespace Tests\Unit\Models;

use App\Models\AuditLog;
use App\Models\Perfil;
use App\Models\PerfilUsuario;
use App\Models\Permissao;
use App\Models\User;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UserTest extends TestCase
{
    #[Test]
    public function pertence_a_muitos_perfis(): void
    {
        $user = User::factory()->create();
        $perfil = Perfil::factory()->create();

        PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfil->id,
        ]);

        $this->assertCount(1, $user->perfis);
        $this->assertEquals($perfil->id, $user->perfis->first()->id);
    }

    #[Test]
    public function possui_muitos_audit_logs(): void
    {
        $user = User::factory()->create();

        AuditLog::create([
            'user_id'       => $user->id,
            'action'        => 'test.action',
            'tipo_operacao' => AuditLog::TIPO_VIEW,
            'context'       => [],
        ]);

        $this->assertCount(1, $user->auditLogs);
    }

    #[Test]
    public function pertence_a_perfil_usuario_ativo(): void
    {
        $user = User::factory()->create();
        $perfil = Perfil::factory()->create();
        $pu = PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfil->id,
        ]);

        $user->update(['perfil_usuario_ativo_id' => $pu->id]);

        $this->assertNotNull($user->fresh()->perfilUsuarioAtivo);
        $this->assertEquals($pu->id, $user->fresh()->perfilUsuarioAtivo->id);
    }

    #[Test]
    public function hash_cpf_gera_hash_irreversivel_consistente(): void
    {
        $cpf = '12345678901';
        $hash1 = User::hashCpf($cpf);
        $hash2 = User::hashCpf($cpf);

        $this->assertEquals($hash1, $hash2);
        $this->assertNotEquals($cpf, $hash1);
        $this->assertEquals(64, strlen($hash1));
    }

    #[Test]
    public function hash_cpf_remove_caracteres_nao_numericos(): void
    {
        $hash1 = User::hashCpf('123.456.789-01');
        $hash2 = User::hashCpf('12345678901');

        $this->assertEquals($hash1, $hash2);
    }

    #[Test]
    public function cpf_hash_esta_oculto_na_serializacao(): void
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('cpf_hash', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    #[Test]
    public function to_safe_array_retorna_campos_seguros(): void
    {
        $user = User::factory()->create(['esfera_atuacao' => 'federal']);

        $safe = $user->toSafeArray();

        $this->assertArrayHasKey('id', $safe);
        $this->assertArrayHasKey('name', $safe);
        $this->assertArrayHasKey('esfera_atuacao', $safe);
        $this->assertArrayHasKey('perfis_vigentes', $safe);
        $this->assertArrayHasKey('permissoes', $safe);
        $this->assertArrayNotHasKey('cpf_hash', $safe);
    }

    #[Test]
    public function perfis_vigentes_retorna_apenas_perfis_ativos_em_vigencia(): void
    {
        $user = User::factory()->create();
        $perfilAtivo = Perfil::factory()->create(['status' => 'ativo']);
        $perfilInativo = Perfil::factory()->create(['status' => 'inativo']);

        PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfilAtivo->id,
            'data_inicio_vigencia' => now()->subDay()->toDateString(),
        ]);

        PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfilInativo->id,
            'data_inicio_vigencia' => now()->subDay()->toDateString(),
        ]);

        $vigentes = $user->perfisVigentes();

        $this->assertCount(1, $vigentes);
        $this->assertEquals($perfilAtivo->id, $vigentes->first()->id);
    }

    #[Test]
    public function perfis_vigentes_exclui_perfis_com_vigencia_expirada(): void
    {
        $user = User::factory()->create();
        $perfil = Perfil::factory()->create(['status' => 'ativo']);

        PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfil->id,
            'data_inicio_vigencia' => now()->subMonth()->toDateString(),
            'data_fim_vigencia'    => now()->subDay()->toDateString(),
        ]);

        $this->assertCount(0, $user->perfisVigentes());
        $this->assertFalse($user->possuiPerfilVigente());
    }

    #[Test]
    public function possui_perfil_vigente_retorna_true_quando_existe(): void
    {
        $user = User::factory()->create();
        $perfil = Perfil::factory()->create(['status' => 'ativo']);

        PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfil->id,
        ]);

        $this->assertTrue($user->possuiPerfilVigente());
    }

    #[Test]
    public function to_safe_array_inclui_permissoes_do_perfil_ativo(): void
    {
        $user = User::factory()->create();
        $perfil = Perfil::factory()->create(['status' => 'ativo']);
        $permissao = Permissao::create([
            'modulo' => 'Gerenciar Perfis',
            'acao'   => 'Visualizar',
        ]);
        $perfil->permissoes()->sync([$permissao->id]);

        $pu = PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfil->id,
            'data_inicio_vigencia' => now()->subDay()->toDateString(),
        ]);
        $user->update(['perfil_usuario_ativo_id' => $pu->id]);

        $safe = $user->fresh()->toSafeArray();

        $this->assertNotEmpty($safe['permissoes']);
        $this->assertEquals('Gerenciar Perfis', $safe['permissoes'][0]['modulo']);
    }

    #[Test]
    public function factory_states_funcionam_corretamente(): void
    {
        $federal = User::factory()->federal()->create();
        $estadual = User::factory()->estadual('SP')->create();
        $municipal = User::factory()->municipal('RJ', 'Niterói')->create();

        $this->assertEquals('federal', $federal->esfera_atuacao);
        $this->assertEquals('estadual', $estadual->esfera_atuacao);
        $this->assertEquals('SP', $estadual->uf_lotacao);
        $this->assertEquals('municipal', $municipal->esfera_atuacao);
        $this->assertEquals('Niterói', $municipal->municipio_lotacao);
    }
}
