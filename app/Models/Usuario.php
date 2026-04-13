<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

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

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'ativo'      => 'boolean',
    ];

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'usuario_id');
    }

    public function perfis(): BelongsToMany
    {
        return $this->belongsToMany(Perfil::class, 'perfil_usuario')
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
     * Retorna os perfis vigentes (com data de vigência válida e ativo = true no perfil).
     */
    public function perfisVigentes(): Collection
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

    public function getAllPermissions(): array
    {
        return $this->perfisUsuario()
            ->with('perfil.permissoes')
            ->get()
            ->pluck('perfil.permissoes')
            ->flatten()
            ->pluck('codigo')
            ->unique()
            ->values()
            ->toArray();
    }

    public function hasPermissao(string $codigo):bool
    {
        return $this->perfisUsuario()
            ->with('perfil.permissoes')
            ->get()
            ->flatMap(fn ($pu) => $pu->perfil?->permissoes ?? [])
            ->pluck('codigo')
            ->contains($codigo);
    }
}
