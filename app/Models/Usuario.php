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
 * @property bool        $ativo
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
        'ativo',
    ];

    protected $attributes = [
        'ativo' => true,
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $usuario): void {
            if ($usuario->ativo === null) {
                $usuario->ativo = true;
            }
        });
    }

    // -------------------------------------------------------
    // Relações
    // -------------------------------------------------------

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'usuario_id');
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
        return $this->hasMany(SolicitacaoCadastro::class, 'usuario_id');
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
            'nome'              => $this->nome,
            'cpf'               => $this->cpf,
            'email'             => $this->email,
            'sub'               => $this->govbr_sub,
            'esfera_atuacao'    => $this->esfera_atuacao,
            'uf_lotacao'        => $this->uf_lotacao,
            'municipio_lotacao' => $this->municipio_lotacao,
            'perfis_vigentes'   => $perfisArray,
            'perfil_ativo_id'   => $perfilAtivoPivot['perfil_usuario_id'] ?? null,
        ];
    }

    // -------------------------------------------------------
    // Helpers para permissões
    // -------------------------------------------------------

    /**
     * Verifica se o usuário tem um perfil de visitante ativo
     */
    public function isVisitante(): bool
    {
        $perfilAtivo = $this->perfilAtivo();
        if (!$perfilAtivo) {
            return false;
        }

        return str_starts_with(strtolower($perfilAtivo->nome), 'visitante');
    }

    /**
     * Verifica se o usuário tem um perfil de gestor ativo
     * Gestores: Gestor Federal, Gestor Estadual, Gestor Municipal
     */
    public function isGestor(): bool
    {
        $perfilAtivo = $this->perfilAtivo();
        if (!$perfilAtivo) {
            return false;
        }

        return str_starts_with(strtolower($perfilAtivo->nome), 'gestor');
    }

    /**
     * Verifica se o usuário tem perfil ativo de Gestor Nacional.
     */
    public function isGestorNacional(): bool
    {
        $perfilAtivo = $this->perfilAtivo();
        if (!$perfilAtivo) {
            return false;
        }

        return mb_strtolower(trim($perfilAtivo->nome), 'UTF-8') === 'gestor nacional';
    }

    /**
     * Verifica se o perfil ativo pertence a esfera federal.
     */
    public function isPerfilFederalAtivo(): bool
    {
        $perfilAtivo = $this->perfilAtivo();
        if (!$perfilAtivo) {
            return false;
        }

        $nomePerfil = mb_strtolower(trim((string) $perfilAtivo->nome), 'UTF-8');

        return str_contains($nomePerfil, 'federal');
    }

    /**
     * Retorna o perfil ativo do usuário
     */
    public function perfilAtivo(): ?Perfil
    {
        return $this->perfisVigentes()->firstWhere('pivot.ativo', true);
    }
}
