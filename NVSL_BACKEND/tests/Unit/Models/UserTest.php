<?php

namespace Tests\Unit\Models;

use App\Models\AuditLog;
use App\Models\Perfil;
use App\Models\PerfilUsuario;
use App\Models\Usuario;
use Tests\TestCase;
use Tests\Traits\ActingAsUserTrait;
use PHPUnit\Framework\Attributes\Test;

class UserTest extends TestCase
{
    use ActingAsUserTrait;

    #[Test]
    public function pertence_a_muitos_perfis(): void
    {
        $user = Usuario::factory()->create();
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
        $user = Usuario::factory()->create();

        AuditLog::create([
            'user_id'       => $user->id,
            'acao'          => 'test.action',
            'tipo_operacao' => AuditLog::TIPO_VIEW,
            'contexto'      => [],
        ]);

        $this->assertCount(1, $user->auditLogs);
    }

    #[Test]
    public function perfil_usuario_ativo_retorna_perfil_com_flag_ativo(): void
    {
        $user = Usuario::factory()->create();
        $perfil = Perfil::factory()->create();
        $pu = PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfil->id,
            'ativo'      => true,
        ]);

        $ativo = $user->perfilUsuarioAtivo();

        $this->assertNotNull($ativo);
        $this->assertEquals($pu->id, $ativo->id);
    }

    #[Test]
    public function to_safe_array_retorna_campos_seguros(): void
    {
        $user = $this->criarUsuarioFederal();

        $safe = $user->toSafeArray();

        $this->assertArrayHasKey('id', $safe);
        $this->assertArrayHasKey('name', $safe);
        $this->assertArrayHasKey('esfera_atuacao', $safe);
        $this->assertArrayHasKey('perfis_vigentes', $safe);
    }

    #[Test]
    public function perfis_vigentes_retorna_apenas_perfis_ativos_em_vigencia(): void
    {
        $user = Usuario::factory()->create();
        $perfilAtivo = Perfil::factory()->create(['ativo' => true]);
        $perfilInativo = Perfil::factory()->create(['ativo' => false]);

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
        $user = Usuario::factory()->create();
        $perfil = Perfil::factory()->create(['ativo' => true]);

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
        $user = Usuario::factory()->create();
        $perfil = Perfil::factory()->create(['ativo' => true]);

        PerfilUsuario::create([
            'usuario_id' => $user->id,
            'perfil_id'  => $perfil->id,
        ]);

        $this->assertTrue($user->possuiPerfilVigente());
    }

    #[Test]
    public function esfera_atuacao_derivada_da_solicitacao_aprovada(): void
    {
        $federal = $this->criarUsuarioFederal();
        $estadual = $this->criarUsuarioEstadual();
        $municipal = $this->criarUsuarioMunicipal();

        $this->assertEquals('Federal', $federal->esfera_atuacao);
        $this->assertEquals('Estadual', $estadual->esfera_atuacao);
        $this->assertEquals('GO', $estadual->uf_lotacao);
        $this->assertEquals('Municipal', $municipal->esfera_atuacao);
        $this->assertEquals('Alexânia', $municipal->municipio_lotacao);
    }
}
