<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model User — representa o cidadão autenticado via GOV.BR
 *
 * ANONIMIZAÇÃO:
 *   - CPF nunca é armazenado em texto claro.
 *     É salvo como SHA-256(cpf + APP_KEY) para garantir unicidade
 *     sem possibilidade de reversão.
 *   - O govbr_sub (identificador SSO) é o id único do usuário.
 *   - govbr_access_token JAMAIS é persistido.
 *
 * @property int    $id
 * @property string $govbr_sub       Identificador único do SSO GOV.BR
 * @property string $cpf_hash        SHA-256 anonimizado do CPF
 * @property string $name            Nome completo
 * @property string|null $email      E-mail (pode não estar disponível)
 * @property string|null $picture    URL foto de perfil
 * @property string $role            Papel no sistema (user, admin)
 * @property string $esfera_atuacao  federal|estadual|municipal (perfil institucional)
 * @property string|null $uf_lotacao       UF de lotação (estadual/municipal)
 * @property string|null $municipio_lotacao Município de lotação (municipal)
 * @property int|null $perfil_usuario_ativo_id  FK para perfil_usuario (contexto ativo)
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'govbr_sub',
        'cpf_hash',
        'name',
        'email',
        'picture',
        'role',
        'esfera_atuacao',
        'uf_lotacao',
        'municipio_lotacao',
        'perfil_usuario_ativo_id',
    ];

    protected $hidden = [
        'cpf_hash',      // Nunca expor hash do CPF
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    // -------------------------------------------------------
    // Relações
    // -------------------------------------------------------

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function perfis(): BelongsToMany
    {
        return $this->belongsToMany(Perfil::class, 'perfil_usuario', 'usuario_id', 'perfil_id')
            ->withPivot(['id', 'data_inicio_vigencia', 'data_fim_vigencia', 'uf', 'municipio', 'orgao'])
            ->withTimestamps();
    }

    public function perfilUsuarioAtivo(): BelongsTo
    {
        return $this->belongsTo(PerfilUsuario::class, 'perfil_usuario_ativo_id');
    }

    /**
     * Verifica se o usuário possui perfil vigente.
     */
    public function possuiPerfilVigente(): bool
    {
        return $this->perfisVigentes()->isNotEmpty();
    }

    /**
     * Retorna os perfis vigentes (com data de vigência válida e status ativo).
     */
    public function perfisVigentes(): \Illuminate\Support\Collection
    {
        $hoje = now()->toDateString();

        return $this->perfis()
            ->where('perfis.status', 'ativo')
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

    // -------------------------------------------------------
    // Helper: anonimizar CPF
    // -------------------------------------------------------

    /**
     * Gera o hash irreversível do CPF.
     * Usa HMAC-SHA256 com a APP_KEY como salt.
     */
    public static function hashCpf(string $cpf): string
    {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/\D/', '', $cpf);

        return hash_hmac('sha256', $cpf, config('app.key'));
    }

    // -------------------------------------------------------
    // API Resource — dados seguros para expor ao frontend
    // -------------------------------------------------------

    /**
     * Retorna apenas os campos seguros para o frontend.
     * CPF hash e dados internos são omitidos.
     */
    public function toSafeArray(): array
    {
        $perfisVigentes = $this->perfisVigentes();

        $perfisArray = $perfisVigentes->map(fn (Perfil $p) => [
            'perfil_usuario_id'     => $p->pivot->id,
            'perfil_id'             => $p->id,
            'nome'                  => $p->nome,
            'esfera'                => $p->esfera,
            'uf'                    => $p->pivot->uf,
            'municipio'             => $p->pivot->municipio,
            'orgao'                 => $p->pivot->orgao,
            'data_inicio_vigencia'  => $p->pivot->data_inicio_vigencia,
            'data_fim_vigencia'     => $p->pivot->data_fim_vigencia,
        ])->values()->toArray();

        $permissoesAtivas = [];
        $perfilAtivoId = $this->perfil_usuario_ativo_id;
        $perfilAtivo = $perfilAtivoId
            ? $perfisVigentes->first(fn (Perfil $p) => $p->pivot->id == $perfilAtivoId)
            : $perfisVigentes->first();

        if ($perfilAtivo) {
            $perfilAtivo->load('permissoes');
            $permissoesAtivas = $perfilAtivo->permissoes->map(fn (Permissao $perm) => [
                'id'     => $perm->id,
                'modulo' => $perm->modulo,
                'acao'   => $perm->acao,
            ])->values()->toArray();
        }

        return [
            'id'                        => $this->id,
            'name'                      => $this->name,
            'email'                     => $this->email,
            'picture'                   => $this->picture,
            'role'                      => $this->role,
            'sub'                       => $this->govbr_sub,
            'esfera_atuacao'            => $this->esfera_atuacao ?? 'federal',
            'uf_lotacao'                => $this->uf_lotacao,
            'municipio_lotacao'         => $this->municipio_lotacao,
            'perfil_usuario_ativo_id'   => $this->perfil_usuario_ativo_id,
            'perfis_vigentes'           => $perfisArray,
            'permissoes'                => $permissoesAtivas,
        ];
    }
}
