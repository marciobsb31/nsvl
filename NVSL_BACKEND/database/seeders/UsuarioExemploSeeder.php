<?php

namespace Database\Seeders;

use App\Models\Perfil;
use App\Models\PerfilUsuario;
use App\Models\SolicitacaoCadastro;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Cadastra perfis, usuários de exemplo e solicitações para teste de visibilidade.
 *
 * Usuários:
 * - Federal: vê todas as solicitações
 * - Estadual (GO): vê apenas estadual/GO
 * - Municipal (Alexânia/GO): vê apenas municipal/GO/Alexânia
 */
class UsuarioExemploSeeder extends Seeder
{
    private static function cpfExibicao(string $digits): string
    {
        $d = preg_replace('/\D/', '', $digits);

        return strlen($d) === 11
            ? substr($d, 0, 3) . '.' . substr($d, 3, 3) . '.' . substr($d, 6, 3) . '-' . substr($d, 9, 2)
            : '***.***.***-**';
    }
    public function run(): void
    {
        $this->call(PerfilSeeder::class);
        $this->call(EsferaSeeder::class);

        $perfilGestor = Perfil::where('nome', 'Gestor')->first();
        $perfilAnalista = Perfil::where('nome', 'Analista')->first();
        $perfilVisualizador = Perfil::where('nome', 'Visualizador')->first();
        $perfilAdministrador = Perfil::where('nome', 'Administrador')->first();

        $hoje = now()->toDateString();

        // 1. Usuário FEDERAL — acesso irrestrito, múltiplos perfis para testar troca de contexto
        $federal = User::firstOrCreate(
            ['govbr_sub' => 'teste-federal-001'],
            [
                'cpf_hash'           => User::hashCpf('11144477735'),
                'name'               => 'Maria Silva Federal',
                'email'              => 'maria.federal@ministerio.gov.br',
                'picture'            => null,
                'role'                => 'admin',
                'esfera_atuacao'     => 'federal',
                'uf_lotacao'         => null,
                'municipio_lotacao' => null,
            ]
        );

        $perfilGestorNacional = Perfil::where('nome', 'Gestor Nacional')->first();
        $perfilGestorEstadual = Perfil::where('nome', 'Gestor Estadual')->first();
        $perfilAdmNacional = Perfil::where('nome', 'Administrador Nacional')->first();

        $puFederalGestor = PerfilUsuario::firstOrCreate(
            ['usuario_id' => $federal->id, 'perfil_id' => $perfilGestor->id],
            ['data_inicio_vigencia' => $hoje, 'data_fim_vigencia' => null, 'uf' => null, 'municipio' => null, 'orgao' => 'Ministério dos Direitos Humanos e da Cidadania']
        );
        if (!$puFederalGestor->orgao) {
            $puFederalGestor->update(['orgao' => 'Ministério dos Direitos Humanos e da Cidadania']);
        }

        PerfilUsuario::firstOrCreate(
            ['usuario_id' => $federal->id, 'perfil_id' => $perfilAdministrador->id],
            ['data_inicio_vigencia' => $hoje, 'data_fim_vigencia' => null, 'uf' => null, 'municipio' => null, 'orgao' => 'Ministério dos Direitos Humanos e da Cidadania']
        );
        PerfilUsuario::firstOrCreate(
            ['usuario_id' => $federal->id, 'perfil_id' => $perfilGestorEstadual->id],
            ['data_inicio_vigencia' => $hoje, 'data_fim_vigencia' => null, 'uf' => 'SP', 'municipio' => null, 'orgao' => 'Secretaria Estadual de Assistência Social - SP']
        );
        PerfilUsuario::firstOrCreate(
            ['usuario_id' => $federal->id, 'perfil_id' => $perfilAnalista->id],
            ['data_inicio_vigencia' => $hoje, 'data_fim_vigencia' => null, 'uf' => 'GO', 'municipio' => 'Goiânia', 'orgao' => 'Secretaria Municipal de Saúde de Goiânia']
        );

        if (!$federal->perfil_usuario_ativo_id) {
            $federal->update(['perfil_usuario_ativo_id' => $puFederalGestor->id]);
        }

        // 2. Usuário ESTADUAL — UF GO
        $estadual = User::firstOrCreate(
            ['govbr_sub' => 'teste-estadual-go-002'],
            [
                'cpf_hash'           => User::hashCpf('52998224725'),
                'name'               => 'João Santos Estadual',
                'email'              => 'joao.estadual@go.gov.br',
                'picture'            => null,
                'role'                => 'user',
                'esfera_atuacao'     => 'estadual',
                'uf_lotacao'         => 'GO',
                'municipio_lotacao'  => null,
            ]
        );
        PerfilUsuario::firstOrCreate(
            ['usuario_id' => $estadual->id, 'perfil_id' => $perfilAnalista->id],
            ['data_inicio_vigencia' => $hoje, 'data_fim_vigencia' => null, 'uf' => 'GO', 'municipio' => null, 'orgao' => 'SEDUC-GO']
        );

        // 3. Usuário MUNICIPAL — Alexânia/GO
        $municipal = User::firstOrCreate(
            ['govbr_sub' => 'teste-municipal-alexania-003'],
            [
                'cpf_hash'           => User::hashCpf('98765432100'),
                'name'               => 'Ana Costa Municipal',
                'email'              => 'ana.municipal@alexania.go.gov.br',
                'picture'            => null,
                'role'                => 'user',
                'esfera_atuacao'     => 'municipal',
                'uf_lotacao'         => 'GO',
                'municipio_lotacao'   => 'Alexânia',
            ]
        );
        PerfilUsuario::firstOrCreate(
            ['usuario_id' => $municipal->id, 'perfil_id' => $perfilAnalista->id],
            ['data_inicio_vigencia' => $hoje, 'data_fim_vigencia' => null, 'uf' => 'GO', 'municipio' => 'Alexânia', 'orgao' => 'Prefeitura de Alexânia']
        );

        // 4. Carlos Souza — usuário aprovado com múltiplos perfis (vigente + não vigente, conforme anexo)
        $carlosCpfHash = User::hashCpf('11122233344');
        $carlos = User::firstOrCreate(
            ['govbr_sub' => 'teste-carlos-souza-004'],
            [
                'cpf_hash'           => $carlosCpfHash,
                'name'               => 'Carlos Souza',
                'email'              => 'carlos.souza@ministerio.gov.br',
                'picture'            => null,
                'role'               => 'user',
                'esfera_atuacao'     => 'estadual',
                'uf_lotacao'         => 'DF',
                'municipio_lotacao'  => 'Brasília',
            ]
        );
        // Gestor: vigente (início passado, sem fim) — ex.: 15/01/2026 no anexo
        PerfilUsuario::updateOrCreate(
            ['usuario_id' => $carlos->id, 'perfil_id' => $perfilGestor->id],
            ['data_inicio_vigencia' => '2024-01-15', 'data_fim_vigencia' => null]
        );
        // Analista: não vigente (vigência encerrada em 31/12/2025)
        PerfilUsuario::updateOrCreate(
            ['usuario_id' => $carlos->id, 'perfil_id' => $perfilAnalista->id],
            ['data_inicio_vigencia' => '2025-12-01', 'data_fim_vigencia' => '2025-12-31']
        );
        // Visualizador: vigente
        PerfilUsuario::updateOrCreate(
            ['usuario_id' => $carlos->id, 'perfil_id' => $perfilVisualizador->id],
            ['data_inicio_vigencia' => '2024-01-01', 'data_fim_vigencia' => null]
        );
        // Administrador: vigente
        PerfilUsuario::updateOrCreate(
            ['usuario_id' => $carlos->id, 'perfil_id' => $perfilAdministrador->id],
            ['data_inicio_vigencia' => '2024-01-01', 'data_fim_vigencia' => null]
        );

        // 5. Márcio Pereira da Silva — usuário para teste login GOV.BR (CPF 473.851.998-06)
        $marcioCpfHash = User::hashCpf('47385199806');
        $marcio = User::updateOrCreate(
            ['cpf_hash' => $marcioCpfHash],
            [
                'govbr_sub'          => '47385199806',
                'name'               => 'Márcio Pereira da Silva',
                'email'              => 'marcio.silva@ministerio.gov.br',
                'picture'            => null,
                'role'               => 'user',
                'esfera_atuacao'     => 'federal',
                'uf_lotacao'         => null,
                'municipio_lotacao'  => null,
            ]
        );
        PerfilUsuario::firstOrCreate(
            ['usuario_id' => $marcio->id, 'perfil_id' => $perfilGestor->id],
            ['data_inicio_vigencia' => $hoje, 'data_fim_vigencia' => null]
        );

        // 6. Roberto Alves — usuário aprovado com múltiplos perfis vinculados
        $robertoCpfHash = User::hashCpf('99988877766');
        $roberto = User::firstOrCreate(
            ['govbr_sub' => 'teste-roberto-alves-005'],
            [
                'cpf_hash'           => $robertoCpfHash,
                'name'               => 'Roberto Alves',
                'email'              => 'roberto.alves@go.gov.br',
                'picture'            => null,
                'role'               => 'user',
                'esfera_atuacao'     => 'estadual',
                'uf_lotacao'         => 'GO',
                'municipio_lotacao'  => 'Anápolis',
            ]
        );
        PerfilUsuario::firstOrCreate(
            ['usuario_id' => $roberto->id, 'perfil_id' => $perfilGestor->id],
            ['data_inicio_vigencia' => $hoje, 'data_fim_vigencia' => null]
        );
        PerfilUsuario::firstOrCreate(
            ['usuario_id' => $roberto->id, 'perfil_id' => $perfilAnalista->id],
            ['data_inicio_vigencia' => $hoje, 'data_fim_vigencia' => null]
        );
        PerfilUsuario::firstOrCreate(
            ['usuario_id' => $roberto->id, 'perfil_id' => $perfilVisualizador->id],
            ['data_inicio_vigencia' => $hoje, 'data_fim_vigencia' => null]
        );

        // Solicitações de cadastro para teste (CPFs fictícios para exibição em dev/teste)
        $solicitacoes = [
            [
                'cpf_hash'               => $carlosCpfHash,
                'cpf_exibicao'           => self::cpfExibicao('11122233344'),
                'nome'                   => 'Carlos Souza',
                'email_institucional'    => 'carlos.souza@ministerio.gov.br',
                'telefone_institucional' => '61999887766',
                'esfera_atuacao'         => 'estadual',
                'uf'                     => 'DF',
                'municipio'              => 'Brasília',
                'orgao'                  => 'Secretaria X',
                'cargo'                  => 'Coordenadora',
                'status'                 => SolicitacaoCadastro::STATUS_APROVADO,
            ],
            [
                'cpf_hash'               => User::hashCpf('55566677788'),
                'cpf_exibicao'           => self::cpfExibicao('55566677788'),
                'nome'                   => 'Fernanda Lima',
                'email_institucional'    => 'fernanda.lima@go.gov.br',
                'telefone_institucional' => '62987654321',
                'esfera_atuacao'         => 'estadual',
                'uf'                     => 'GO',
                'municipio'              => 'Goiânia',
                'orgao'                  => 'SEDUC-GO',
                'cargo'                  => 'Gestor',
                'status'                 => SolicitacaoCadastro::STATUS_EM_ANALISE,
            ],
            [
                'cpf_hash'               => $robertoCpfHash,
                'cpf_exibicao'           => self::cpfExibicao('99988877766'),
                'nome'                   => 'Roberto Alves',
                'email_institucional'    => 'roberto.alves@go.gov.br',
                'telefone_institucional' => '62976543210',
                'esfera_atuacao'         => 'estadual',
                'uf'                     => 'GO',
                'municipio'              => 'Anápolis',
                'orgao'                  => 'Secretaria de Saúde GO',
                'cargo'                  => 'Analista',
                'status'                 => SolicitacaoCadastro::STATUS_APROVADO,
            ],
            [
                'cpf_hash'               => User::hashCpf('12345678901'),
                'cpf_exibicao'           => self::cpfExibicao('12345678901'),
                'nome'                   => 'Patrícia Mendes',
                'email_institucional'    => 'patricia.mendes@alexania.go.gov.br',
                'telefone_institucional' => '62965432109',
                'esfera_atuacao'         => 'municipal',
                'uf'                     => 'GO',
                'municipio'              => 'Alexânia',
                'orgao'                  => 'Prefeitura de Alexânia',
                'cargo'                  => 'Assistente Social',
                'status'                 => SolicitacaoCadastro::STATUS_EM_ANALISE,
            ],
            [
                'cpf_hash'               => User::hashCpf('11223344556'),
                'cpf_exibicao'           => self::cpfExibicao('11223344556'),
                'nome'                   => 'Lucas Ferreira',
                'email_institucional'    => 'lucas.ferreira@alexania.go.gov.br',
                'telefone_institucional' => '62954321098',
                'esfera_atuacao'         => 'municipal',
                'uf'                     => 'GO',
                'municipio'              => 'Alexânia',
                'orgao'                  => 'Secretaria Municipal de Saúde',
                'cargo'                  => 'Coordenador',
                'status'                 => SolicitacaoCadastro::STATUS_REPROVADO,
            ],
            [
                'cpf_hash'               => User::hashCpf('77889900112'),
                'cpf_exibicao'           => self::cpfExibicao('77889900112'),
                'nome'                   => 'Mariana Santos',
                'email_institucional'    => 'mariana.santos@sp.gov.br',
                'telefone_institucional' => '11987654321',
                'esfera_atuacao'         => 'estadual',
                'uf'                     => 'SP',
                'municipio'              => 'São Paulo',
                'orgao'                  => 'Secretaria SP',
                'cargo'                  => 'Analista',
                'status'                 => SolicitacaoCadastro::STATUS_EM_ANALISE,
            ],
        ];

        foreach ($solicitacoes as $dados) {
            $hash = $dados['cpf_hash'];
            SolicitacaoCadastro::updateOrCreate(
                ['cpf_hash' => $hash],
                $dados
            );
        }

        $this->command->info('Usuários e solicitações de exemplo criados.');
        $this->command->table(
            ['Perfil', 'Nome', 'E-mail', 'govbr_sub'],
            [
                ['Federal (4 perfis)', $federal->name, $federal->email, $federal->govbr_sub],
                ['Estadual (GO)', $estadual->name, $estadual->email, $estadual->govbr_sub],
                ['Municipal (Alexânia/GO)', $municipal->name, $municipal->email, $municipal->govbr_sub],
                ['Márcio Pereira (GOV.BR teste)', $marcio->name, $marcio->email, $marcio->govbr_sub],
                ['Carlos Souza (4 perfis)', $carlos->name, $carlos->email, $carlos->govbr_sub],
                ['Roberto Alves (3 perfis)', $roberto->name, $roberto->email, $roberto->govbr_sub],
            ]
        );
    }
}
