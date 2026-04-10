<?php

namespace Tests\Integracao\Autenticacao;

use App\DTOs\Auth\GovBrUserDTO;
use App\Models\Perfil;
use App\Models\PerfilUsuario;
use App\Models\SolicitacaoCadastro;
use App\Models\StatusSolicitacao;
use App\Models\Usuario;
use App\Services\Auth\AuthValidationService;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\Integracao\TestCase;

class AuthValidationServiceTest extends TestCase
{
    #[Test]
    public function rejeita_quando_nao_consegue_identificar_um_cpf_valido(): void
    {
        $service = app(AuthValidationService::class);

        $this->assertAuthValidationMessage(
            'NÃ£o foi possÃ­vel identificar o CPF retornado pelo GOV.BR.',
            fn () => $service->validarOuFalhar(new GovBrUserDTO(sub: 'abc', name: 'Sem CPF')),
        );
    }

    #[Test]
    public function rejeita_quando_o_sub_ja_existe_com_outro_cpf(): void
    {
        $service = app(AuthValidationService::class);

        Usuario::factory()->create([
            'govbr_sub' => 'sub-existente',
            'cpf' => '40168299307',
        ]);

        $this->assertAuthValidationMessage(
            'Os dados do GOV.BR nÃ£o correspondem ao cadastro existente no sistema.',
            fn () => $service->validarOuFalhar(new GovBrUserDTO(
                sub: 'sub-existente',
                name: 'Maria divergente',
                cpf: '52998224725',
            )),
        );
    }

    #[Test]
    public function sincroniza_identidade_e_retorna_usuario_com_perfil_vigente(): void
    {
        $service = app(AuthValidationService::class);

        $usuario = Usuario::factory()->create([
            'cpf' => '22233344405',
            'nome' => 'Nome Antigo',
            'email' => 'antigo@teste.gov.br',
            'govbr_sub' => 'sub-antigo',
        ]);

        $perfil = Perfil::query()->where('nome', 'Gestor Federal')->firstOrFail();

        PerfilUsuario::create([
            'usuario_id' => $usuario->id,
            'perfil_id' => $perfil->id,
            'data_inicio_vigencia' => now()->subDay()->toDateString(),
            'ativo' => true,
        ]);

        $retornado = $service->validarOuFalhar(new GovBrUserDTO(
            sub: 'sub-novo',
            name: 'Nome Atualizado',
            email: 'novo@teste.gov.br',
            cpf: '22233344405',
        ));

        $this->assertSame($usuario->id, $retornado->id);
        $this->assertDatabaseHas('usuarios', [
            'id' => $usuario->id,
            'govbr_sub' => 'sub-novo',
            'nome' => 'Nome Atualizado',
            'email' => 'novo@teste.gov.br',
        ]);
    }

    #[Test]
    public function rejeita_quando_ha_conflito_de_sub_em_outro_usuario(): void
    {
        $service = app(AuthValidationService::class);

        Usuario::factory()->create([
            'govbr_sub' => 'sub-ja-em-uso',
            'cpf' => '40168299307',
        ]);

        $usuario = Usuario::factory()->create([
            'govbr_sub' => 'sub-antigo',
            'cpf' => '22233344405',
        ]);

        $perfil = Perfil::query()->where('nome', 'Gestor Federal')->firstOrFail();

        PerfilUsuario::create([
            'usuario_id' => $usuario->id,
            'perfil_id' => $perfil->id,
            'data_inicio_vigencia' => now()->subDay()->toDateString(),
            'ativo' => true,
        ]);

        $reflection = new ReflectionMethod($service, 'sincronizarIdentidade');
        $reflection->setAccessible(true);

        $this->assertAuthValidationMessage(
            'JÃ¡ existe outro usuÃ¡rio vinculado a esta conta GOV.BR.',
            fn () => $reflection->invoke(
                $service,
                $usuario,
                new GovBrUserDTO(
                    sub: 'sub-ja-em-uso',
                    name: 'UsuÃ¡rio de Teste',
                    cpf: '22233344405',
                ),
                '22233344405',
            ),
        );
    }

    #[Test]
    public function informa_solicitacao_em_analise_quando_usuario_existe_sem_perfil_vigente(): void
    {
        $service = app(AuthValidationService::class);

        $usuario = Usuario::factory()->create([
            'cpf' => '22233344405',
            'govbr_sub' => 'sub-analise',
        ]);

        SolicitacaoCadastro::factory()->create([
            'user_id' => $usuario->id,
            'status_id' => StatusSolicitacao::idPorNome(StatusSolicitacao::EM_ANALISE),
        ]);

        $this->assertAuthValidationMessage(
            'SolicitaÃ§Ã£o de acesso em anÃ¡lise.',
            fn () => $service->validarOuFalhar(new GovBrUserDTO(
                sub: 'sub-analise',
                name: 'UsuÃ¡rio em anÃ¡lise',
                cpf: '22233344405',
            )),
        );
    }

    #[Test]
    public function informa_reprovacao_quando_cpf_ja_tem_solicitacao_reprovada_sem_usuario_vigente(): void
    {
        $service = app(AuthValidationService::class);

        $usuario = Usuario::factory()->create([
            'cpf' => '33344455576',
            'govbr_sub' => 'sub-reprovado-antigo',
        ]);

        SolicitacaoCadastro::factory()->reprovado()->create([
            'user_id' => $usuario->id,
            'status_id' => StatusSolicitacao::idPorNome(StatusSolicitacao::REPROVADO),
        ]);

        $usuario->perfisUsuario()->delete();

        $this->assertAuthValidationMessage(
            'Solicitar acesso e aguardar avaliaÃ§Ã£o',
            fn () => $service->validarOuFalhar(new GovBrUserDTO(
                sub: 'sub-novo-sem-acesso',
                name: 'UsuÃ¡rio Reprovado',
                cpf: '33344455576',
            )),
        );
    }

    #[Test]
    public function informa_ausencia_de_perfil_quando_o_usuario_ja_foi_aprovado(): void
    {
        $service = app(AuthValidationService::class);

        $usuario = Usuario::factory()->create([
            'cpf' => '40168299307',
            'govbr_sub' => 'sub-aprovado-sem-perfil',
        ]);

        SolicitacaoCadastro::factory()->aprovado()->create([
            'user_id' => $usuario->id,
            'status_id' => StatusSolicitacao::idPorNome(StatusSolicitacao::APROVADO),
        ]);

        $this->assertAuthValidationMessage(
            'Seu cadastro foi aprovado, mas nenhum perfil de acesso foi configurado. Procure o administrador do sistema.',
            fn () => $service->validarOuFalhar(new GovBrUserDTO(
                sub: 'sub-aprovado-sem-perfil',
                name: 'UsuÃ¡rio sem perfil',
                cpf: '40168299307',
            )),
        );
    }

    #[Test]
    public function informa_que_deve_solicitar_acesso_quando_usuario_existe_sem_solicitacao_e_sem_perfil(): void
    {
        $service = app(AuthValidationService::class);
        $cpf = Usuario::factory()->make()->cpf;

        Usuario::factory()->create([
            'cpf' => $cpf,
            'govbr_sub' => 'sub-aprovado-sem-vinculo',
        ]);

        $this->assertAuthValidationMessage(
            'Solicitar acesso e aguardar avaliaÃ§Ã£o',
            fn () => $service->validarOuFalhar(new GovBrUserDTO(
                sub: 'sub-aprovado-sem-vinculo',
                name: 'UsuÃ¡rio sem solicitaÃ§Ã£o',
                cpf: $cpf,
            )),
        );
    }

    #[Test]
    public function informa_mensagem_generica_quando_ultima_solicitacao_tem_status_desconhecido(): void
    {
        $service = app(AuthValidationService::class);
        $cpf = Usuario::factory()->make()->cpf;
        $usuario = Usuario::factory()->create([
            'cpf' => $cpf,
            'govbr_sub' => 'sub-status-desconhecido',
        ]);

        $statusCustom = StatusSolicitacao::query()->create([
            'nome' => 'pendencia_operacional',
        ]);

        SolicitacaoCadastro::factory()->create([
            'user_id' => $usuario->id,
            'status_id' => $statusCustom->id,
        ]);

        $this->assertAuthValidationMessage(
            'Seu cadastro foi aprovado, mas nenhum perfil de acesso foi configurado. Procure o administrador do sistema.',
            fn () => $service->validarOuFalhar(new GovBrUserDTO(
                sub: 'sub-status-desconhecido',
                name: 'UsuÃ¡rio com pendÃªncia',
                cpf: $cpf,
            )),
        );
    }

    #[Test]
    public function informa_que_deve_solicitar_acesso_quando_nao_ha_usuario_nem_solicitacao(): void
    {
        $service = app(AuthValidationService::class);
        $cpf = Usuario::factory()->make()->cpf;

        $this->assertAuthValidationMessage(
            'Solicitar acesso e aguardar avaliaÃ§Ã£o',
            fn () => $service->validarOuFalhar(new GovBrUserDTO(
                sub: $cpf,
                name: 'Novo usuÃ¡rio',
                cpf: $cpf,
            )),
        );
    }

    private function assertAuthValidationMessage(string $mensagemEsperada, callable $callback): void
    {
        try {
            $callback();
            self::fail('Era esperada uma ValidationException.');
        } catch (ValidationException $exception) {
            $this->assertSame($mensagemEsperada, $exception->errors()['auth'][0] ?? null);
        }
    }
}

