<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Model SolicitacaoCadastro — solicitações de acesso ao sistema
 *
 * CPF armazenado apenas como hash (HMAC-SHA256) por segurança e LGPD.
 *
 * @property int    $id
 * @property string $cpf_hash
 * @property string $nome
 * @property string $email_institucional
 * @property string|null $telefone_institucional
 * @property string|null $telefone_pessoal
 * @property string $esfera_atuacao  federal|estadual|municipal
 * @property string $uf
 * @property string $municipio
 * @property string $orgao
 * @property string|null $cargo
 * @property string $status  em_analise|aprovado|reprovado
 */
class SolicitacaoCadastro extends Model
{
    protected $table = 'solicitacoes_cadastro';

    protected $fillable = [
        'cpf_hash',
        'cpf_exibicao',
        'nome',
        'email_institucional',
        'telefone_institucional',
        'telefone_pessoal',
        'esfera_atuacao',
        'uf',
        'municipio',
        'orgao',
        'cargo',
        'perfil_id_solicitado',
        'vigencia_inicio_solicitada',
        'vigencia_fim_solicitada',
        'status',
        'aceite_termo_at',
        'justificativa_reprovacao',
    ];

    protected $casts = [
        'vigencia_inicio_solicitada' => 'date',
        'vigencia_fim_solicitada' => 'date',
        'aceite_termo_at' => 'datetime',
    ];

    public const STATUS_EM_ANALISE = 'em_analise';
    public const STATUS_APROVADO = 'aprovado';
    public const STATUS_REPROVADO = 'reprovado';

    /**
     * Retorna CPF para exibição.
     * Usa cpf_exibicao quando preenchido (ambiente dev/teste), senão mascarado.
     */
    public function getCpfMascaradoAttribute(): string
    {
        $exibicao = $this->getAttribute('cpf_exibicao');

        return $exibicao ?: '***.***.***-**';
    }

    /**
     * Scope: filtra solicitações conforme esfera de atuação do usuário.
     *  - Federal: acesso irrestrito
     *  - Estadual: apenas esfera_atuacao=estadual e mesma UF
     *  - Municipal: apenas esfera_atuacao=municipal, mesma UF e mesmo município
     *
     * @param Builder<self> $query
     */
    public function scopeVisivelPara(Builder $query, User $user): void
    {
        $esfera = $user->esfera_atuacao ?? 'federal';

        if ($esfera === 'federal') {
            return;
        }

        if ($esfera === 'estadual') {
            $query->where('esfera_atuacao', 'estadual')
                ->where('uf', $user->uf_lotacao ?? '');
            return;
        }

        if ($esfera === 'municipal') {
            $query->where('esfera_atuacao', 'municipal')
                ->where('uf', $user->uf_lotacao ?? '')
                ->where('municipio', $user->municipio_lotacao ?? '');
        }
    }
}
