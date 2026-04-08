<?php

namespace Database\Seeders;

use App\Models\Esfera;
use App\Models\Municipio;
use App\Models\Perfil;
use App\Models\PerfilUsuario;
use App\Models\SolicitacaoCadastro;
use App\Models\StatusSolicitacao;
use App\Models\Uf;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Remove todos os usuários, vínculos e solicitações e recria apenas
 * Marcio Pereira da Silva (CPF 00012482196) com perfil federal equivalente ao cenário de teste anterior.
 */
class SomenteMarcioSeeder extends Seeder
{
    private const NOMES_PERFIL = [
        'Gestor Nacional',
        'Gestor Estadual',
        'Gestor Municipal',
        'Administrador Nacional',
        'Administrador Estadual',
        'Administrador Municipal',
    ];

    public function run(): void
    {
        DB::table('personal_access_tokens')->delete();

        DB::table('auditoria_log')->delete();
        DB::table('solicitacoes_cadastro')->delete();
        DB::table('perfil_usuario')->delete();
        DB::table('usuarios')->delete();

        $this->call(PerfilSeeder::class);
        $this->call(EsferaSeeder::class);
        $this->call(StatusSolicitacaoSeeder::class);

        $perfis = $this->perfisPorNome();
        $statusAprovado = StatusSolicitacao::where('nome', 'aprovado')->first();
        $esferaFederal = Esfera::where('nome', 'Federal')->first();
        $esferaEstadual = Esfera::where('nome', 'Estadual')->first();
        $esferaMunicipal = Esfera::where('nome', 'Municipal')->first();

        $hoje = now()->toDateString();
        $ufDF = Uf::where('sigla', 'DF')->first();
        $ufGO = Uf::where('sigla', 'GO')->first();
        $munBrasilia = $ufDF
            ? Municipio::where('nome', 'Brasília')->where('uf_id', $ufDF->id)->first()
            : null;
        $munAlexania = $ufGO
            ? Municipio::where('nome', 'Alexânia')->where('uf_id', $ufGO->id)->first()
            : null;

        $marcio = Usuario::create([
            'cpf'       => '00012482196',
            'govbr_sub' => '00012482196',
            'nome'      => 'Marcio Pereira da Silva',
            'email'     => 'marcio.pereira@ministerio.gov.br',
            'telefone'  => null,
        ]);

        foreach ([
            ['perfil' => 'Gestor Nacional', 'ativo' => true, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Gestor Estadual', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Gestor Municipal', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Administrador Nacional', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Administrador Estadual', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Administrador Municipal', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
        ] as $v) {
            $perfil = $perfis->get($v['perfil']);
            PerfilUsuario::create([
                'usuario_id'             => $marcio->id,
                'perfil_id'              => $perfil->id,
                'data_inicio_vigencia'   => $v['inicio'],
                'data_fim_vigencia'      => $v['fim'],
                'ativo'                  => $v['ativo'],
            ]);
        }

        if ($ufGO && $esferaEstadual && $statusAprovado) {
            SolicitacaoCadastro::create([
                'user_id'                => $marcio->id,
                'email_institucional'    => 'marcio.pereira@ministerio.gov.br',
                'telefone_institucional' => '61999887766',
                'esfera_id'              => $esferaEstadual->id,
                'uf_id'                  => $ufGO->id,
                'municipio_id'           => null,
                'orgao'                  => 'Secretaria de Educação de Goiás (vínculo estadual de teste)',
                'cargo'                  => 'Gestor estadual',
                'perfil_id_solicitado'   => $perfis->get('Gestor Estadual')->id,
                'status_id'              => $statusAprovado->id,
                'aceite_termo_at'        => now(),
            ]);
        }
        if ($ufGO && $esferaMunicipal && $statusAprovado) {
            SolicitacaoCadastro::create([
                'user_id'                => $marcio->id,
                'email_institucional'    => 'marcio.pereira@ministerio.gov.br',
                'telefone_institucional' => '61999887766',
                'esfera_id'              => $esferaMunicipal->id,
                'uf_id'                  => $ufGO->id,
                'municipio_id'           => $munAlexania?->id,
                'orgao'                  => 'Prefeitura de Alexânia (vínculo municipal de teste)',
                'cargo'                  => 'Gestor municipal',
                'perfil_id_solicitado'   => $perfis->get('Gestor Municipal')->id,
                'status_id'              => $statusAprovado->id,
                'aceite_termo_at'        => now(),
            ]);
        }
        if ($ufDF && $esferaFederal && $statusAprovado) {
            SolicitacaoCadastro::create([
                'user_id'                => $marcio->id,
                'email_institucional'    => 'marcio.pereira@ministerio.gov.br',
                'telefone_institucional' => '61999887766',
                'esfera_id'              => $esferaFederal->id,
                'uf_id'                  => $ufDF->id,
                'municipio_id'           => $munBrasilia?->id,
                'orgao'                  => 'Ministério dos Direitos Humanos e da Cidadania',
                'cargo'                  => 'Gestor nacional',
                'perfil_id_solicitado'   => $perfis->get('Gestor Nacional')->id,
                'status_id'              => $statusAprovado->id,
                'aceite_termo_at'        => now(),
            ]);
        }

        $this->command->info('Banco limpo: permanece apenas Marcio Pereira da Silva (CPF 00012482196) com perfis vigentes.');
    }

    /**
     * @return Collection<string, Perfil>
     */
    private function perfisPorNome(): Collection
    {
        $colecao = Perfil::query()
            ->whereIn('nome', self::NOMES_PERFIL)
            ->get()
            ->keyBy('nome');

        foreach (self::NOMES_PERFIL as $nome) {
            if (!$colecao->has($nome)) {
                throw new \RuntimeException(
                    "SomenteMarcioSeeder: perfil \"{$nome}\" não encontrado. Execute PerfilSeeder antes."
                );
            }
        }

        return $colecao;
    }
}
