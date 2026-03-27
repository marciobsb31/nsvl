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

/**
 * Usuários e solicitações de demonstração alinhados ao catálogo em {@see PerfilSeeder}
 * ({@see \App\Models\Perfil::CATALOGO_OFICIAL}).
 */
class UsuarioExemploSeeder extends Seeder
{
    /** Perfis usados neste seeder (todos existentes em PerfilSeeder). */
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
        $this->call(PerfilSeeder::class);
        $this->call(EsferaSeeder::class);
        $this->call(StatusSolicitacaoSeeder::class);

        $perfis = $this->perfisPorNome();

        $statusEmAnalise = StatusSolicitacao::where('nome', 'em_analise')->first();
        $statusAprovado = StatusSolicitacao::where('nome', 'aprovado')->first();
        $statusReprovado = StatusSolicitacao::where('nome', 'reprovado')->first();

        $esferaFederal = Esfera::where('nome', 'Federal')->first();
        $esferaEstadual = Esfera::where('nome', 'Estadual')->first();
        $esferaMunicipal = Esfera::where('nome', 'Municipal')->first();

        $hoje = now()->toDateString();

        $ufDF = Uf::where('sigla', 'DF')->first();
        $ufGO = Uf::where('sigla', 'GO')->first();
        $munBrasilia = $ufDF
            ? Municipio::where('nome', 'Brasília')->where('uf_id', $ufDF->id)->first()
            : null;
        $munAlexaniaMaria = ($ufGO)
            ? Municipio::where('nome', 'Alexânia')->where('uf_id', $ufGO->id)->first()
            : null;

        // --- 1. Maria (token teste-federal-001): perfis federal, estadual e municipal + troca de contexto ---
        $federal = Usuario::firstOrCreate(
            ['govbr_sub' => 'teste-federal-001'],
            [
                'cpf'      => '11144477735',
                'nome'     => 'Maria Silva Federal',
                'email'    => 'maria.federal@ministerio.gov.br',
                'telefone' => null,
            ]
        );
        $this->substituirVinculosPerfil($federal->id, [
            ['perfil' => 'Gestor Nacional', 'ativo' => true, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Gestor Estadual', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Gestor Municipal', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Administrador Nacional', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Administrador Estadual', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Administrador Municipal', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
        ], $perfis);

        // Três solicitações aprovadas (uma por esfera). Inserir estadual → municipal → federal para que
        // o último id (federal) seja o contexto exibido em esfera_atuacao (latest).
        if ($statusAprovado) {
            SolicitacaoCadastro::query()
                ->where('user_id', $federal->id)
                ->where('status_id', $statusAprovado->id)
                ->delete();
        }
        if ($ufGO && $esferaEstadual && $statusAprovado) {
            SolicitacaoCadastro::create([
                'user_id'                => $federal->id,
                'email_institucional'    => 'maria.federal@ministerio.gov.br',
                'telefone_institucional' => '61999887766',
                'esfera_id'              => $esferaEstadual->id,
                'uf_id'                  => $ufGO->id,
                'municipio_id'           => null,
                'orgao'                  => 'Secretaria de Educação de Goiás (vínculo estadual de teste)',
                'cargo'                  => 'Gestora estadual',
                'perfil_id_solicitado'   => $perfis->get('Gestor Estadual')->id,
                'status_id'              => $statusAprovado->id,
                'aceite_termo_at'        => now(),
            ]);
        }
        if ($ufGO && $esferaMunicipal && $statusAprovado) {
            SolicitacaoCadastro::create([
                'user_id'                => $federal->id,
                'email_institucional'    => 'maria.federal@ministerio.gov.br',
                'telefone_institucional' => '61999887766',
                'esfera_id'              => $esferaMunicipal->id,
                'uf_id'                  => $ufGO->id,
                'municipio_id'           => $munAlexaniaMaria?->id,
                'orgao'                  => 'Prefeitura de Alexânia (vínculo municipal de teste)',
                'cargo'                  => 'Gestora municipal',
                'perfil_id_solicitado'   => $perfis->get('Gestor Municipal')->id,
                'status_id'              => $statusAprovado->id,
                'aceite_termo_at'        => now(),
            ]);
        }
        if ($ufDF && $esferaFederal && $statusAprovado) {
            SolicitacaoCadastro::create([
                'user_id'                => $federal->id,
                'email_institucional'    => 'maria.federal@ministerio.gov.br',
                'telefone_institucional' => '61999887766',
                'esfera_id'              => $esferaFederal->id,
                'uf_id'                  => $ufDF->id,
                'municipio_id'           => $munBrasilia?->id,
                'orgao'                  => 'Ministério dos Direitos Humanos e da Cidadania',
                'cargo'                  => 'Gestora nacional',
                'perfil_id_solicitado'   => $perfis->get('Gestor Nacional')->id,
                'status_id'              => $statusAprovado->id,
                'aceite_termo_at'        => now(),
            ]);
        }

        // --- 2. Estadual GO (teste-estadual-go-002) ---
        $estadual = Usuario::firstOrCreate(
            ['govbr_sub' => 'teste-estadual-go-002'],
            [
                'cpf'      => '52998224725',
                'nome'     => 'João Santos Estadual',
                'email'    => 'joao.estadual@go.gov.br',
                'telefone' => null,
            ]
        );
        $this->substituirVinculosPerfil($estadual->id, [
            ['perfil' => 'Gestor Estadual', 'ativo' => true, 'inicio' => $hoje, 'fim' => null],
        ], $perfis);

        if ($ufGO && $esferaEstadual && $statusAprovado) {
            SolicitacaoCadastro::updateOrCreate(
                ['user_id' => $estadual->id, 'status_id' => $statusAprovado->id, 'esfera_id' => $esferaEstadual->id],
                [
                    'email_institucional'    => 'joao.estadual@go.gov.br',
                    'telefone_institucional' => '62987654321',
                    'uf_id'                  => $ufGO->id,
                    'municipio_id'           => null,
                    'orgao'                  => 'Secretaria de Educação de Goiás',
                    'cargo'                  => 'Gestor estadual',
                    'perfil_id_solicitado'   => $perfis->get('Gestor Estadual')->id,
                    'aceite_termo_at'        => now(),
                ]
            );
        }

        // --- 3. Municipal Alexânia/GO (teste-municipal-alexania-003) ---
        $municipal = Usuario::firstOrCreate(
            ['govbr_sub' => 'teste-municipal-alexania-003'],
            [
                'cpf'      => '98765432100',
                'nome'     => 'Ana Costa Municipal',
                'email'    => 'ana.municipal@alexania.go.gov.br',
                'telefone' => null,
            ]
        );
        $this->substituirVinculosPerfil($municipal->id, [
            ['perfil' => 'Gestor Municipal', 'ativo' => true, 'inicio' => $hoje, 'fim' => null],
        ], $perfis);

        if ($ufGO && $esferaMunicipal && $statusAprovado) {
            $munAlexania = Municipio::where('nome', 'Alexânia')->where('uf_id', $ufGO->id)->first();
            SolicitacaoCadastro::updateOrCreate(
                ['user_id' => $municipal->id, 'status_id' => $statusAprovado->id, 'esfera_id' => $esferaMunicipal->id],
                [
                    'email_institucional'    => 'ana.municipal@alexania.go.gov.br',
                    'telefone_institucional' => '62965432109',
                    'uf_id'                  => $ufGO->id,
                    'municipio_id'           => $munAlexania?->id,
                    'orgao'                  => 'Prefeitura Municipal de Alexânia',
                    'cargo'                  => 'Gestora municipal',
                    'perfil_id_solicitado'   => $perfis->get('Gestor Municipal')->id,
                    'aceite_termo_at'        => now(),
                ]
            );
        }

        // --- 4. Carlos Souza: vários vínculos (troca de contexto / histórico) ---
        $carlos = Usuario::firstOrCreate(
            ['govbr_sub' => 'teste-carlos-souza-004'],
            [
                'cpf'      => '11122233344',
                'nome'     => 'Carlos Souza',
                'email'    => 'carlos.souza@ministerio.gov.br',
                'telefone' => null,
            ]
        );
        $this->substituirVinculosPerfil($carlos->id, [
            ['perfil' => 'Gestor Estadual', 'ativo' => true, 'inicio' => '2024-01-15', 'fim' => null],
            ['perfil' => 'Gestor Municipal', 'ativo' => false, 'inicio' => '2025-12-01', 'fim' => '2025-12-31'],
            ['perfil' => 'Administrador Municipal', 'ativo' => false, 'inicio' => '2024-01-01', 'fim' => null],
            ['perfil' => 'Administrador Nacional', 'ativo' => false, 'inicio' => '2024-01-01', 'fim' => null],
        ], $perfis);

        if ($ufDF && $esferaEstadual && $statusAprovado) {
            SolicitacaoCadastro::updateOrCreate(
                ['user_id' => $carlos->id, 'status_id' => $statusAprovado->id, 'esfera_id' => $esferaEstadual->id],
                [
                    'email_institucional'    => 'carlos.souza@ministerio.gov.br',
                    'telefone_institucional' => '61999887766',
                    'uf_id'                  => $ufDF->id,
                    'municipio_id'           => $munBrasilia?->id,
                    'orgao'                  => 'Secretaria de Estado do Distrito Federal',
                    'cargo'                  => 'Gestor estadual',
                    'perfil_id_solicitado'   => $perfis->get('Gestor Estadual')->id,
                    'aceite_termo_at'        => now(),
                ]
            );
        }

        // --- 5. Márcio Pereira (GOV.BR) ---
        $marcio = Usuario::updateOrCreate(
            ['cpf' => '47385199806'],
            [
                'govbr_sub' => '47385199806',
                'nome'      => 'Márcio Pereira da Silva',
                'email'     => 'marcio.silva@ministerio.gov.br',
                'telefone'  => null,
            ]
        );
        $this->substituirVinculosPerfil($marcio->id, [
            ['perfil' => 'Gestor Nacional', 'ativo' => true, 'inicio' => $hoje, 'fim' => null],
        ], $perfis);

        if ($esferaFederal && $ufDF && $statusAprovado) {
            SolicitacaoCadastro::updateOrCreate(
                ['user_id' => $marcio->id, 'status_id' => $statusAprovado->id, 'esfera_id' => $esferaFederal->id],
                [
                    'email_institucional'    => 'marcio.silva@ministerio.gov.br',
                    'telefone_institucional' => '61999999999',
                    'uf_id'                  => $ufDF->id,
                    'municipio_id'           => $munBrasilia?->id,
                    'orgao'                  => 'Ministério dos Direitos Humanos e da Cidadania',
                    'cargo'                  => 'Gestor nacional',
                    'perfil_id_solicitado'   => $perfis->get('Gestor Nacional')->id,
                    'aceite_termo_at'        => now(),
                ]
            );
        }

        // --- 6. Roberto Alves (GO) ---
        $roberto = Usuario::firstOrCreate(
            ['govbr_sub' => 'teste-roberto-alves-005'],
            [
                'cpf'      => '99988877766',
                'nome'     => 'Roberto Alves',
                'email'    => 'roberto.alves@go.gov.br',
                'telefone' => null,
            ]
        );
        $this->substituirVinculosPerfil($roberto->id, [
            ['perfil' => 'Gestor Estadual', 'ativo' => true, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Gestor Nacional', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Administrador Estadual', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
        ], $perfis);

        if ($ufGO && $esferaEstadual && $statusAprovado) {
            $munAnapolis = Municipio::where('nome', 'Anápolis')->where('uf_id', $ufGO->id)->first();
            SolicitacaoCadastro::updateOrCreate(
                ['user_id' => $roberto->id, 'status_id' => $statusAprovado->id, 'esfera_id' => $esferaEstadual->id],
                [
                    'email_institucional'    => 'roberto.alves@go.gov.br',
                    'telefone_institucional' => '62976543210',
                    'uf_id'                  => $ufGO->id,
                    'municipio_id'           => $munAnapolis?->id,
                    'orgao'                  => 'Secretaria de Estado da Saúde de Goiás',
                    'cargo'                  => 'Gestor estadual',
                    'perfil_id_solicitado'   => $perfis->get('Gestor Estadual')->id,
                    'aceite_termo_at'        => now(),
                ]
            );
        }

        $this->criarSolicitacoesExtra(
            $esferaEstadual,
            $esferaMunicipal,
            $statusEmAnalise,
            $statusReprovado,
            $ufGO,
            $perfis
        );

        $this->command->info('Usuários e solicitações de exemplo criados (perfis alinhados ao banco).');
        $this->command->table(
            ['Contexto', 'Nome', 'E-mail', 'govbr_sub', 'Perfil ativo'],
            [
                ['Federal (+ est./mun.)', $federal->nome, $federal->email, $federal->govbr_sub, 'Gestor Nacional (6 perfis)'],
                ['Estadual GO', $estadual->nome, $estadual->email, $estadual->govbr_sub, 'Gestor Estadual'],
                ['Municipal Alexânia/GO', $municipal->nome, $municipal->email, $municipal->govbr_sub, 'Gestor Municipal'],
                ['GOV.BR teste', $marcio->nome, $marcio->email, $marcio->govbr_sub, 'Gestor Nacional'],
                ['4 vínculos', $carlos->nome, $carlos->email, $carlos->govbr_sub, 'Gestor Estadual'],
                ['3 vínculos', $roberto->nome, $roberto->email, $roberto->govbr_sub, 'Gestor Estadual'],
            ]
        );
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
                    "UsuarioExemploSeeder: perfil \"{$nome}\" não encontrado. Execute PerfilSeeder antes."
                );
            }
        }

        return $colecao;
    }

    /**
     * Remove vínculos anteriores e recria (evita perfis genéricos obsoletos após mudanças no catálogo).
     *
     * @param  list<array{perfil: string, ativo: bool, inicio: string, fim: ?string}>  $vinculos
     * @param  Collection<string, Perfil>  $perfis
     */
    private function substituirVinculosPerfil(int $usuarioId, array $vinculos, Collection $perfis): void
    {
        PerfilUsuario::query()->where('usuario_id', $usuarioId)->delete();

        foreach ($vinculos as $v) {
            $perfil = $perfis->get($v['perfil']);
            PerfilUsuario::create([
                'usuario_id'             => $usuarioId,
                'perfil_id'              => $perfil->id,
                'data_inicio_vigencia'   => $v['inicio'],
                'data_fim_vigencia'      => $v['fim'],
                'ativo'                  => $v['ativo'],
            ]);
        }
    }

    private function criarSolicitacoesExtra(
        ?Esfera $esferaEstadual,
        ?Esfera $esferaMunicipal,
        ?StatusSolicitacao $statusEmAnalise,
        ?StatusSolicitacao $statusReprovado,
        ?Uf $ufGO,
        Collection $perfis
    ): void {
        if (!$ufGO || !$esferaEstadual || !$esferaMunicipal || !$statusEmAnalise || !$statusReprovado) {
            return;
        }

        $extras = [
            [
                'cpf' => '55566677788', 'nome' => 'Fernanda Lima', 'email' => 'fernanda.lima@go.gov.br',
                'esfera_id' => $esferaEstadual->id, 'uf_id' => $ufGO->id, 'municipio_nome' => 'Goiânia',
                'orgao' => 'SEDUC-GO', 'cargo' => 'Gestora estadual', 'status_id' => $statusEmAnalise->id,
                'perfil_nome' => 'Gestor Estadual',
            ],
            [
                'cpf' => '12345678901', 'nome' => 'Patrícia Mendes', 'email' => 'patricia.mendes@alexania.go.gov.br',
                'esfera_id' => $esferaMunicipal->id, 'uf_id' => $ufGO->id, 'municipio_nome' => 'Alexânia',
                'orgao' => 'Prefeitura de Alexânia', 'cargo' => 'Servidora municipal', 'status_id' => $statusEmAnalise->id,
                'perfil_nome' => 'Gestor Municipal',
            ],
            [
                'cpf' => '11223344556', 'nome' => 'Lucas Ferreira', 'email' => 'lucas.ferreira@alexania.go.gov.br',
                'esfera_id' => $esferaMunicipal->id, 'uf_id' => $ufGO->id, 'municipio_nome' => 'Alexânia',
                'orgao' => 'Secretaria Municipal de Saúde', 'cargo' => 'Coordenador', 'status_id' => $statusReprovado->id,
                'perfil_nome' => 'Gestor Municipal',
            ],
        ];

        $ufSP = Uf::where('sigla', 'SP')->first();
        if ($ufSP) {
            $extras[] = [
                'cpf' => '77889900112', 'nome' => 'Mariana Santos', 'email' => 'mariana.santos@sp.gov.br',
                'esfera_id' => $esferaEstadual->id, 'uf_id' => $ufSP->id, 'municipio_nome' => 'São Paulo',
                'orgao' => 'Secretaria do Estado de São Paulo', 'cargo' => 'Analista', 'status_id' => $statusEmAnalise->id,
                'perfil_nome' => 'Administrador Estadual',
            ];
        }

        foreach ($extras as $e) {
            $usuario = Usuario::firstOrCreate(
                ['cpf' => $e['cpf']],
                [
                    'nome'      => $e['nome'],
                    'email'     => $e['email'],
                    'govbr_sub' => 'pending-' . $e['cpf'],
                    'telefone'  => null,
                ]
            );

            $municipioId = Municipio::where('nome', $e['municipio_nome'])->where('uf_id', $e['uf_id'])->value('id');
            $perfilId = $perfis->get($e['perfil_nome'])->id;

            SolicitacaoCadastro::updateOrCreate(
                ['user_id' => $usuario->id, 'esfera_id' => $e['esfera_id'], 'status_id' => $e['status_id']],
                [
                    'email_institucional'    => $e['email'],
                    'telefone_institucional' => '00000000000',
                    'uf_id'                  => $e['uf_id'],
                    'municipio_id'           => $municipioId,
                    'orgao'                  => $e['orgao'],
                    'cargo'                  => $e['cargo'],
                    'perfil_id_solicitado'   => $perfilId,
                    'aceite_termo_at'        => now(),
                ]
            );
        }
    }
}
