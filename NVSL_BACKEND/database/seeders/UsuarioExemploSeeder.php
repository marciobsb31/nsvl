<?php

namespace Database\Seeders;

use App\Models\AuditLog;
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
 * UsuÃ¡rios e solicitaÃ§Ãµes de demonstraÃ§Ã£o alinhados ao catÃ¡logo em {@see PerfilSeeder}
 * ({@see \App\Models\Perfil::CATALOGO_OFICIAL}).
 */
class UsuarioExemploSeeder extends Seeder
{
    private const CPF_FEDERAL = '11144477735';
    private const CPF_ESTADUAL = '52998224725';
    private const CPF_MUNICIPAL = '98765432100';
    private const CPF_WALYSON = '73583278100';

    /** Perfis usados neste seeder (todos existentes em PerfilSeeder). */
    private const NOMES_PERFIL = [
        'Gestor Federal',
        'Gestor Estadual',
        'Gestor Municipal',
        'Visitante Federal',
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
            ? Municipio::where('nome', 'BrasÃ­lia')->where('uf_id', $ufDF->id)->first()
            : null;
        $munAlexaniaMaria = ($ufGO)
            ? Municipio::where('nome', 'AlexÃ¢nia')->where('uf_id', $ufGO->id)->first()
            : null;

        // --- 1. Maria: perfis federal, estadual e municipal + troca de contexto ---
        $federal = Usuario::updateOrCreate(
            ['cpf' => self::CPF_FEDERAL],
            [
                'govbr_sub' => self::CPF_FEDERAL,
                'nome'      => 'Maria Silva Federal',
                'email'     => 'maria.federal@ministerio.gov.br',
                'telefone'  => null,
            ]
        );
        $this->substituirVinculosPerfil($federal->id, [
            ['perfil' => 'Gestor Federal', 'ativo' => true, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Gestor Estadual', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Gestor Municipal', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Visitante Federal', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Administrador Estadual', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Administrador Municipal', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
        ], $perfis);

        // TrÃªs solicitaÃ§Ãµes aprovadas (uma por esfera). Inserir estadual â†’ municipal â†’ federal para que
        // o Ãºltimo id (federal) seja o contexto exibido em esfera_atuacao (latest).
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
                'perfil_id_solicitado'   => $perfis->get('Gestor Federal')->id,
                'status_id'              => $statusAprovado->id,
                'aceite_termo_at'        => now(),
            ]);
        }

        // --- 2. Walyson: usuÃ¡rio federal adicional para homologaÃ§Ã£o local ---
        $walyson = Usuario::updateOrCreate(
            ['cpf' => self::CPF_WALYSON],
            [
                'govbr_sub' => self::CPF_WALYSON,
                'nome'      => 'Walyson Maxwel',
                'email'     => 'walysonmaxwel@hotmail.com',
                'telefone'  => null,
            ]
        );
        $this->substituirVinculosPerfil($walyson->id, [
            ['perfil' => 'Gestor Federal', 'ativo' => true, 'inicio' => $hoje, 'fim' => null],
        ], $perfis);

        if ($ufDF && $esferaFederal && $statusAprovado) {
            SolicitacaoCadastro::updateOrCreate(
                ['user_id' => $walyson->id, 'status_id' => $statusAprovado->id, 'esfera_id' => $esferaFederal->id],
                [
                    'email_institucional'    => 'walysonmaxwel@hotmail.com',
                    'telefone_institucional' => '61999999999',
                    'uf_id'                  => $ufDF->id,
                    'municipio_id'           => $munBrasilia?->id,
                    'orgao'                  => 'MinistÃ©rio dos Direitos Humanos e da Cidadania',
                    'cargo'                  => 'Gestor Federal',
                    'perfil_id_solicitado'   => $perfis->get('Gestor Federal')->id,
                    'aceite_termo_at'        => now(),
                ]
            );
        }

        // --- 3. Estadual GO ---
        $estadual = Usuario::updateOrCreate(
            ['cpf' => self::CPF_ESTADUAL],
            [
                'govbr_sub' => self::CPF_ESTADUAL,
                'nome'      => 'JoÃ£o Santos Estadual',
                'email'     => 'joao.estadual@go.gov.br',
                'telefone'  => null,
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
                    'orgao'                  => 'Secretaria de EducaÃ§Ã£o de GoiÃ¡s',
                    'cargo'                  => 'Gestor estadual',
                    'perfil_id_solicitado'   => $perfis->get('Gestor Estadual')->id,
                    'aceite_termo_at'        => now(),
                ]
            );
        }

        // --- 4. Municipal AlexÃ¢nia/GO ---
        $municipal = Usuario::updateOrCreate(
            ['cpf' => self::CPF_MUNICIPAL],
            [
                'govbr_sub' => self::CPF_MUNICIPAL,
                'nome'      => 'Ana Costa Municipal',
                'email'     => 'ana.municipal@alexania.go.gov.br',
                'telefone'  => null,
            ]
        );
        $this->substituirVinculosPerfil($municipal->id, [
            ['perfil' => 'Gestor Municipal', 'ativo' => true, 'inicio' => $hoje, 'fim' => null],
        ], $perfis);

        if ($ufGO && $esferaMunicipal && $statusAprovado) {
            $munAlexania = Municipio::where('nome', 'AlexÃ¢nia')->where('uf_id', $ufGO->id)->first();
            SolicitacaoCadastro::updateOrCreate(
                ['user_id' => $municipal->id, 'status_id' => $statusAprovado->id, 'esfera_id' => $esferaMunicipal->id],
                [
                    'email_institucional'    => 'ana.municipal@alexania.go.gov.br',
                    'telefone_institucional' => '62965432109',
                    'uf_id'                  => $ufGO->id,
                    'municipio_id'           => $munAlexania?->id,
                    'orgao'                  => 'Prefeitura Municipal de AlexÃ¢nia',
                    'cargo'                  => 'Gestora municipal',
                    'perfil_id_solicitado'   => $perfis->get('Gestor Municipal')->id,
                    'aceite_termo_at'        => now(),
                ]
            );
        }

        // --- 5. Carlos Souza: vÃ¡rios vÃ­nculos (troca de contexto / histÃ³rico) ---
        $carlos = Usuario::updateOrCreate(
            ['cpf' => '11122233344'],
            [
                'govbr_sub' => '11122233344',
                'nome'      => 'Carlos Souza',
                'email'     => 'carlos.souza@ministerio.gov.br',
                'telefone'  => null,
            ]
        );
        $this->substituirVinculosPerfil($carlos->id, [
            ['perfil' => 'Gestor Estadual', 'ativo' => true, 'inicio' => '2024-01-15', 'fim' => null],
            ['perfil' => 'Gestor Municipal', 'ativo' => false, 'inicio' => '2025-12-01', 'fim' => '2025-12-31'],
            ['perfil' => 'Administrador Municipal', 'ativo' => false, 'inicio' => '2024-01-01', 'fim' => null],
            ['perfil' => 'Visitante Federal', 'ativo' => false, 'inicio' => '2024-01-01', 'fim' => null],
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

        // --- 6. MÃ¡rcio Pereira da Silva (GOV.BR) â€” mesmos vÃ­nculos federais de Maria + contexto federal (Ãºltima solicitaÃ§Ã£o) ---
        if ($legado = Usuario::query()->where('cpf', '47385199806')->first()) {
            PerfilUsuario::query()->where('usuario_id', $legado->id)->delete();
            SolicitacaoCadastro::query()->where('user_id', $legado->id)->delete();
            AuditLog::query()->where('user_id', $legado->id)->delete();
            $legado->tokens()->delete();
            $legado->delete();
        }

        $marcio = Usuario::updateOrCreate(
            ['cpf' => '00012482196'],
            [
                'govbr_sub' => '00012482196',
                'nome'      => 'Marcio Pereira da Silva',
                'email'     => 'marcio.pereira@ministerio.gov.br',
                'telefone'  => null,
            ]
        );
        $this->substituirVinculosPerfil($marcio->id, [
            ['perfil' => 'Gestor Federal', 'ativo' => true, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Gestor Estadual', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Gestor Municipal', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Visitante Federal', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Administrador Estadual', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Administrador Municipal', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
        ], $perfis);

        if ($statusAprovado) {
            SolicitacaoCadastro::query()
                ->where('user_id', $marcio->id)
                ->where('status_id', $statusAprovado->id)
                ->delete();
        }
        if ($ufGO && $esferaEstadual && $statusAprovado) {
            SolicitacaoCadastro::create([
                'user_id'                => $marcio->id,
                'email_institucional'    => 'marcio.pereira@ministerio.gov.br',
                'telefone_institucional' => '61999887766',
                'esfera_id'              => $esferaEstadual->id,
                'uf_id'                  => $ufGO->id,
                'municipio_id'           => null,
                'orgao'                  => 'Secretaria de EducaÃ§Ã£o de GoiÃ¡s (vÃ­nculo estadual de teste)',
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
                'municipio_id'           => $munAlexaniaMaria?->id,
                'orgao'                  => 'Prefeitura de AlexÃ¢nia (vÃ­nculo municipal de teste)',
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
                'orgao'                  => 'MinistÃ©rio dos Direitos Humanos e da Cidadania',
                'cargo'                  => 'Gestor Federal',
                'perfil_id_solicitado'   => $perfis->get('Gestor Federal')->id,
                'status_id'              => $statusAprovado->id,
                'aceite_termo_at'        => now(),
            ]);
        }

        // --- 7. Roberto Alves (GO) ---
        $roberto = Usuario::updateOrCreate(
            ['cpf' => '99988877766'],
            [
                'govbr_sub' => '99988877766',
                'nome'      => 'Roberto Alves',
                'email'     => 'roberto.alves@go.gov.br',
                'telefone'  => null,
            ]
        );
        $this->substituirVinculosPerfil($roberto->id, [
            ['perfil' => 'Gestor Estadual', 'ativo' => true, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Gestor Federal', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
            ['perfil' => 'Administrador Estadual', 'ativo' => false, 'inicio' => $hoje, 'fim' => null],
        ], $perfis);

        if ($ufGO && $esferaEstadual && $statusAprovado) {
            $munAnapolis = Municipio::where('nome', 'AnÃ¡polis')->where('uf_id', $ufGO->id)->first();
            SolicitacaoCadastro::updateOrCreate(
                ['user_id' => $roberto->id, 'status_id' => $statusAprovado->id, 'esfera_id' => $esferaEstadual->id],
                [
                    'email_institucional'    => 'roberto.alves@go.gov.br',
                    'telefone_institucional' => '62976543210',
                    'uf_id'                  => $ufGO->id,
                    'municipio_id'           => $munAnapolis?->id,
                    'orgao'                  => 'Secretaria de Estado da SaÃºde de GoiÃ¡s',
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

        if ($this->command) {
            $this->command->info('UsuÃ¡rios e solicitaÃ§Ãµes de exemplo criados (perfis alinhados ao banco).');
            $this->command->table(
                ['Contexto', 'Nome', 'E-mail', 'govbr_sub', 'Perfil ativo'],
                [
                    ['Federal (+ est./mun.)', $federal->nome, $federal->email, $federal->govbr_sub, 'Gestor Federal (8 perfis)'],
                    ['Federal adicional', $walyson->nome, $walyson->email, $walyson->govbr_sub, 'Gestor Federal'],
                    ['Estadual GO', $estadual->nome, $estadual->email, $estadual->govbr_sub, 'Gestor Estadual'],
                    ['Municipal AlexÃ¢nia/GO', $municipal->nome, $municipal->email, $municipal->govbr_sub, 'Gestor Municipal'],
                    ['GOV.BR (espelho Maria federal)', $marcio->nome, $marcio->email, $marcio->govbr_sub, 'Gestor Federal (8 perfis)'],
                    ['4 vÃ­nculos', $carlos->nome, $carlos->email, $carlos->govbr_sub, 'Gestor Estadual'],
                    ['3 vÃ­nculos', $roberto->nome, $roberto->email, $roberto->govbr_sub, 'Gestor Estadual'],
                ]
            );
        }
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
                    "UsuarioExemploSeeder: perfil \"{$nome}\" nÃ£o encontrado. Execute PerfilSeeder antes."
                );
            }
        }

        return $colecao;
    }

    /**
     * Remove vÃ­nculos anteriores e recria (evita perfis genÃ©ricos obsoletos apÃ³s mudanÃ§as no catÃ¡logo).
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
                'esfera_id' => $esferaEstadual->id, 'uf_id' => $ufGO->id, 'municipio_nome' => 'GoiÃ¢nia',
                'orgao' => 'SEDUC-GO', 'cargo' => 'Gestora estadual', 'status_id' => $statusEmAnalise->id,
                'perfil_nome' => 'Gestor Estadual',
            ],
            [
                'cpf' => '12345678901', 'nome' => 'PatrÃ­cia Mendes', 'email' => 'patricia.mendes@alexania.go.gov.br',
                'esfera_id' => $esferaMunicipal->id, 'uf_id' => $ufGO->id, 'municipio_nome' => 'AlexÃ¢nia',
                'orgao' => 'Prefeitura de AlexÃ¢nia', 'cargo' => 'Servidora municipal', 'status_id' => $statusEmAnalise->id,
                'perfil_nome' => 'Gestor Municipal',
            ],
            [
                'cpf' => '11223344556', 'nome' => 'Lucas Ferreira', 'email' => 'lucas.ferreira@alexania.go.gov.br',
                'esfera_id' => $esferaMunicipal->id, 'uf_id' => $ufGO->id, 'municipio_nome' => 'AlexÃ¢nia',
                'orgao' => 'Secretaria Municipal de SaÃºde', 'cargo' => 'Coordenador', 'status_id' => $statusReprovado->id,
                'perfil_nome' => 'Gestor Municipal',
            ],
        ];

        $ufSP = Uf::where('sigla', 'SP')->first();
        if ($ufSP) {
            $extras[] = [
                'cpf' => '77889900112', 'nome' => 'Mariana Santos', 'email' => 'mariana.santos@sp.gov.br',
                'esfera_id' => $esferaEstadual->id, 'uf_id' => $ufSP->id, 'municipio_nome' => 'SÃ£o Paulo',
                'orgao' => 'Secretaria do Estado de SÃ£o Paulo', 'cargo' => 'Analista', 'status_id' => $statusEmAnalise->id,
                'perfil_nome' => 'Administrador Estadual',
            ];
        }

        foreach ($extras as $e) {
            $usuario = Usuario::updateOrCreate(
                ['cpf' => $e['cpf']],
                [
                    'nome'      => $e['nome'],
                    'email'     => $e['email'],
                    'govbr_sub' => $e['cpf'],
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

