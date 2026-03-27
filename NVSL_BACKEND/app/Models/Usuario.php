<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Usuario — representa o cidadão autenticado via GOV.BR
 *
 * @property int         $id
 * @property string      $cpf             CPF único (11 dígitos)
 * @property string      $nome            Nome completo
 * @property string|null $telefone        Telefone pessoal
 * @property string      $govbr_sub       Identificador único do SSO GOV.BR
 * @property string|null $email           E-mail
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Usuario extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios';

    protected $fillable = [
        'cpf',
        'nome',
        'telefone',
        'govbr_sub',
        'email',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // -------------------------------------------------------
    // Relações
    // -------------------------------------------------------

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function perfis(): BelongsToMany
    {
        return $this->belongsToMany(Perfil::class, 'perfil_usuario', 'usuario_id', 'perfil_id')
            ->withPivot(['id', 'data_inicio_vigencia', 'data_fim_vigencia', 'ativo'])
            ->withTimestamps();
    }

    public function perfisUsuario(): HasMany
    {
        return $this->hasMany(PerfilUsuario::class, 'usuario_id');
    }

    public function solicitacoesCadastro(): HasMany
    {
        return $this->hasMany(SolicitacaoCadastro::class, 'user_id');
    }

    /**
     * Retorna o perfil_usuario marcado como ativo.
     */
    public function perfilUsuarioAtivo(): ?PerfilUsuario
    {
        return $this->perfisUsuario()
            ->where('ativo', true)
            ->first();
    }

    /**
     * Verifica se o usuário possui perfil vigente.
     */
    public function possuiPerfilVigente(): bool
    {
        return $this->perfisVigentes()->isNotEmpty();
    }

    /**
     * Retorna os perfis vigentes (com data de vigência válida e ativo = true no perfil).
     */
    public function perfisVigentes(): \Illuminate\Support\Collection
    {
        $hoje = now()->toDateString();

        return $this->perfis()
            ->where('perfis.ativo', true)
            ->where(function ($q) use ($hoje) {
                $q->whereNull('perfil_usuario.data_inicio_vigencia')
                    ->orWhere('perfil_usuario.data_inicio_vigencia', '<=', $hoje);
            })
            ->where(function ($q) use ($hoje) {
                $q->whereNull('perfil_usuario.data_fim_vigencia')
                    ->orWhere('perfil_usuario.data_fim_vigencia', '>=', $hoje);
            })
            ->get();
    }

    /**
     * Deriva a esfera de atuação a partir da solicitação aprovada mais recente.
     */
    public function getEsferaAtuacaoAttribute(): string
    {
        $solicitacao = $this->solicitacoesCadastro()
            ->whereHas('statusSolicitacao', fn ($q) => $q->where('nome', 'aprovado'))
            ->latest('id')
            ->with('esfera')
            ->first();

        return $solicitacao?->esfera?->nome ?? 'federal';
    }

    /**
     * Deriva a UF de lotação a partir da solicitação aprovada mais recente.
     */
    public function getUfLotacaoAttribute(): ?string
    {
        $solicitacao = $this->solicitacoesCadastro()
            ->whereHas('statusSolicitacao', fn ($q) => $q->where('nome', 'aprovado'))
            ->latest('id')
            ->with('ufRelacao')
            ->first();

        return $solicitacao?->ufRelacao?->sigla;
    }

    /**
     * Deriva o município de lotação a partir da solicitação aprovada mais recente.
     */
    public function getMunicipioLotacaoAttribute(): ?string
    {
        $solicitacao = $this->solicitacoesCadastro()
            ->whereHas('statusSolicitacao', fn ($q) => $q->where('nome', 'aprovado'))
            ->latest('id')
            ->with('municipioRelacao')
            ->first();

        return $solicitacao?->municipioRelacao?->nome;
    }

    // -------------------------------------------------------
    // API Resource — dados seguros para expor ao frontend
    // -------------------------------------------------------

    public function toSafeArray(): array
    {
        $perfisVigentes = $this->perfisVigentes();

        $perfisArray = $perfisVigentes->map(fn (Perfil $p) => [
            'perfil_usuario_id'     => $p->pivot->id,
            'perfil_id'             => $p->id,
            'nome'                  => $p->nome,
            'data_inicio_vigencia'  => $p->pivot->data_inicio_vigencia,
            'data_fim_vigencia'     => $p->pivot->data_fim_vigencia,
            'ativo'                 => (bool) $p->pivot->ativo,
        ])->values()->toArray();

        $perfilAtivoPivot = collect($perfisArray)->firstWhere('ativo', true)
            ?? collect($perfisArray)->first();

        return [
            'id'                => $this->id,
            'name'              => $this->nome,
            'email'             => $this->email,
            'sub'               => $this->govbr_sub,
            'esfera_atuacao'    => $this->esfera_atuacao,
            'uf_lotacao'        => $this->uf_lotacao,
            'municipio_lotacao' => $this->municipio_lotacao,
            'perfis_vigentes'   => $perfisArray,
            'perfil_ativo_id'   => $perfilAtivoPivot['perfil_usuario_id'] ?? null,
        ];
    }
}
