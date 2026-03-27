<?php

namespace Tests\Traits;

use App\Models\Esfera;
use App\Models\Municipio;
use App\Models\Perfil;
use App\Models\PerfilUsuario;
use App\Models\SolicitacaoCadastro;
use App\Models\StatusSolicitacao;
use App\Models\Uf;
use App\Models\Usuario;

trait ActingAsUserTrait
{
    protected function criarUsuarioFederal(array $attrs = []): Usuario
    {
        $user = Usuario::factory()->create($attrs);
        $perfil = Perfil::factory()->create(['nome' => 'Federal Test ' . $user->id]);

        $esfera = Esfera::firstOrCreate(['nome' => 'Federal']);
        $uf = Uf::firstOrCreate(['sigla' => 'DF', 'nome' => 'Distrito Federal']);
        $statusAprovado = StatusSolicitacao::idPorNome(StatusSolicitacao::APROVADO);

        SolicitacaoCadastro::create([
            'user_id'              => $user->id,
            'email_institucional'  => $user->email,
            'telefone_institucional' => '6132151000',
            'esfera_id'            => $esfera->id,
            'uf_id'                => $uf->id,
            'orgao'                => 'Órgão Federal Teste',
            'cargo'                => 'Analista',
            'perfil_id_solicitado' => $perfil->id,
            'status_id'            => $statusAprovado,
            'aceite_termo_at'      => now(),
        ]);

        PerfilUsuario::create([
            'usuario_id'           => $user->id,
            'perfil_id'            => $perfil->id,
            'data_inicio_vigencia' => now()->subDay()->toDateString(),
            'ativo'                => true,
        ]);

        return $user->fresh();
    }

    protected function criarUsuarioEstadual(array $attrs = []): Usuario
    {
        $user = Usuario::factory()->create($attrs);
        $perfil = Perfil::factory()->create(['nome' => 'Estadual Test ' . $user->id]);

        $esfera = Esfera::firstOrCreate(['nome' => 'Estadual']);
        $uf = Uf::firstOrCreate(['sigla' => 'GO', 'nome' => 'Goiás']);
        $statusAprovado = StatusSolicitacao::idPorNome(StatusSolicitacao::APROVADO);

        SolicitacaoCadastro::create([
            'user_id'              => $user->id,
            'email_institucional'  => $user->email,
            'telefone_institucional' => '6232151000',
            'esfera_id'            => $esfera->id,
            'uf_id'                => $uf->id,
            'orgao'                => 'Órgão Estadual Teste',
            'cargo'                => 'Analista',
            'perfil_id_solicitado' => $perfil->id,
            'status_id'            => $statusAprovado,
            'aceite_termo_at'      => now(),
        ]);

        PerfilUsuario::create([
            'usuario_id'           => $user->id,
            'perfil_id'            => $perfil->id,
            'data_inicio_vigencia' => now()->subDay()->toDateString(),
            'ativo'                => true,
        ]);

        return $user->fresh();
    }

    protected function criarUsuarioMunicipal(array $attrs = []): Usuario
    {
        $user = Usuario::factory()->create($attrs);
        $perfil = Perfil::factory()->create(['nome' => 'Municipal Test ' . $user->id]);

        $esfera = Esfera::firstOrCreate(['nome' => 'Municipal']);
        $uf = Uf::firstOrCreate(['sigla' => 'GO', 'nome' => 'Goiás']);
        $municipio = Municipio::firstOrCreate(['nome' => 'Alexânia', 'uf_id' => $uf->id]);
        $statusAprovado = StatusSolicitacao::idPorNome(StatusSolicitacao::APROVADO);

        SolicitacaoCadastro::create([
            'user_id'              => $user->id,
            'email_institucional'  => $user->email,
            'telefone_institucional' => '6236001000',
            'esfera_id'            => $esfera->id,
            'uf_id'                => $uf->id,
            'municipio_id'         => $municipio->id,
            'orgao'                => 'Órgão Municipal Teste',
            'cargo'                => 'Analista',
            'perfil_id_solicitado' => $perfil->id,
            'status_id'            => $statusAprovado,
            'aceite_termo_at'      => now(),
        ]);

        PerfilUsuario::create([
            'usuario_id'           => $user->id,
            'perfil_id'            => $perfil->id,
            'data_inicio_vigencia' => now()->subDay()->toDateString(),
            'ativo'                => true,
        ]);

        return $user->fresh();
    }

    protected function autenticar(Usuario $user): self
    {
        return $this->actingAs($user, 'sanctum');
    }
}
