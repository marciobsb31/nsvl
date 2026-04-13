<?php

namespace Tests\Integracao\Perfis;

use App\Models\AuditLog;
use PHPUnit\Framework\Attributes\Test;
use Tests\Integracao\TestCase;

class GerenciarPerfisEndpointsTest extends TestCase
{
    #[Test]
    public function lista_perfis_para_gestao(): void
    {
        $this->autenticarComoFederal();

        $response = $this->getJson('/api/gerenciar-perfis');

        $response
            ->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonFragment(['nome' => 'Gestor Federal']);
    }

    #[Test]
    public function cadastra_perfil_com_sucesso_quando_o_nome_oficial_ainda_nao_existe(): void
    {
        $this->autenticarComoFederal();

        $perfil = $this->perfilPorNome('Administrador Municipal');
        $perfil->perfisUsuario()->delete();
        $perfil->delete();

        $response = $this->postJson('/api/gerenciar-perfis', [
            'nome'      => 'Administrador Municipal',
            'descricao' => 'Perfil recriado em teste.',
            'ativo'     => false,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.nome', 'Administrador Municipal')
            ->assertJsonPath('data.status', 'inativo');
    }

    #[Test]
    public function retorna_hierarquia_da_esfera_do_usuario(): void
    {
        $this->autenticarComoEstadual();

        $response = $this->getJson('/api/gerenciar-perfis/hierarquia');

        $response
            ->assertOk()
            ->assertJsonPath('esfera_usuario', 'Estadual')
            ->assertJsonPath('esferas_permitidas.0', 'estadual');
    }

    #[Test]
    public function retorna_hierarquia_para_usuario_municipal(): void
    {
        $this->autenticarComoMunicipal();

        $response = $this->getJson('/api/gerenciar-perfis/hierarquia');

        $response
            ->assertOk()
            ->assertJsonPath('esfera_usuario', 'Municipal')
            ->assertJsonPath('esferas_permitidas.0', 'municipal');
    }

    #[Test]
    public function retorna_catalogo_legado_de_permissoes_vazio(): void
    {
        $this->autenticarComoFederal();

        $response = $this->getJson('/api/gerenciar-perfis/permissoes');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'CatÃ¡logo de permissÃµes nÃ£o disponÃ­vel nesta versÃ£o do sistema.')
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function detalha_e_atualiza_um_perfil_existente(): void
    {
        $this->autenticarComoFederal();
        $perfil = $this->perfilPorNome('Gestor Federal');

        $show = $this->getJson("/api/gerenciar-perfis/{$perfil->id}");
        $show
            ->assertOk()
            ->assertJsonPath('data.id', $perfil->id)
            ->assertJsonPath('data.nome', 'Gestor Federal');

        $update = $this->putJson("/api/gerenciar-perfis/{$perfil->id}", [
            'nome'      => 'Gestor Federal',
            'descricao' => 'Descricao atualizada em teste de integracao.',
            'ativo'     => true,
        ]);

        $update
            ->assertOk()
            ->assertJsonPath('data.descricao', 'Descricao atualizada em teste de integracao.');

        $historico = $this->getJson("/api/gerenciar-perfis/{$perfil->id}/historico");
        $historico
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    #[Test]
    public function retorna_erros_quando_perfil_nao_existe_ou_nome_do_update_e_invalido(): void
    {
        $this->autenticarComoFederal();

        $show = $this->getJson('/api/gerenciar-perfis/999999');
        $show
            ->assertNotFound()
            ->assertJsonPath('message', 'Perfil nÃ£o encontrado.');

        $historico = $this->getJson('/api/gerenciar-perfis/999999/historico');
        $historico
            ->assertNotFound()
            ->assertJsonPath('message', 'Perfil nÃ£o encontrado.');

        $perfil = $this->perfilPorNome('Gestor Federal');
        $update = $this->putJson("/api/gerenciar-perfis/{$perfil->id}", [
            'nome'      => 'Perfil Inventado',
            'descricao' => 'Descricao invalida.',
            'ativo'     => false,
        ]);

        $update
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome']);
    }

    #[Test]
    public function valida_regra_de_nome_unico_ao_tentar_cadastrar_perfil_duplicado(): void
    {
        $this->autenticarComoFederal();

        $response = $this->postJson('/api/gerenciar-perfis', [
            'nome'      => 'Gestor Federal',
            'descricao' => 'Nao deve ser aceito em duplicidade.',
            'ativo'     => true,
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nome']);
    }

    #[Test]
    public function aplica_filtros_por_nome_e_status_na_listagem(): void
    {
        $this->autenticarComoFederal();
        $perfil = $this->perfilPorNome('Administrador Municipal');
        $perfil->update(['ativo' => false]);

        $response = $this->getJson('/api/gerenciar-perfis?nome=Administrador&status=inativo');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nome', 'Administrador Municipal')
            ->assertJsonPath('data.0.status', 'inativo');
    }

    #[Test]
    public function retorna_nao_encontrado_para_perfil_inexistente_e_historico_mapeia_acoes(): void
    {
        $usuario = $this->autenticarComoFederal();
        $perfil = $this->perfilPorNome('Gestor Federal');

        AuditLog::create([
            'user_id'        => $usuario->id,
            'acao'           => 'gerenciar_perfis.cadastrar',
            'tipo_operacao'  => AuditLog::TIPO_INSERT,
            'tabela_afetada' => 'perfis',
            'registro_id'    => $perfil->id,
            'contexto'       => [],
        ]);

        $historico = $this->getJson("/api/gerenciar-perfis/{$perfil->id}/historico");

        $historico
            ->assertOk()
            ->assertJsonFragment(['atualizacao' => 'Perfil cadastrado']);

        $naoEncontrado = $this->getJson('/api/gerenciar-perfis/999999');

        $naoEncontrado
            ->assertNotFound()
            ->assertJsonPath('message', 'Perfil nÃ£o encontrado.');
    }
}
