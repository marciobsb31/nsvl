<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Model PerfilUsuario — associação usuário-perfil com vigência
 *
 * @property int $id
 * @property int $usuario_id FK → usuarios.id
 * @property int $perfil_id FK → perfis.id
 * @property Carbon|null $data_inicio_vigencia
 * @property Carbon|null $data_fim_vigencia
 * @property bool $ativo
 */
class PerfilUsuario extends Model
{
    protected $table = 'perfil_usuario';

    protected $fillable = [
        'usuario_id',
        'perfil_id',
        'data_inicio_vigencia',
        'data_fim_vigencia',
        'origem_tipo',
        'solicitacao_cadastro_origem_id',
        'atribuido_por_usuario_id',
        'ativo',
    ];

    protected $casts = [
        'data_inicio_vigencia' => 'date',
        'data_fim_vigencia'    => 'date',
        'ativo'                => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(Perfil::class, 'perfil_id');
    }

    public function solicitacaoCadastroOrigem(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoCadastro::class, 'solicitacao_cadastro_origem_id');
    }

    public function atribuidoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'atribuido_por_usuario_id');
    }

    public function contexto(): HasOne
    {
        return $this->hasOne(UsuarioContexto::class, 'perfil_usuario_id');
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeVigentes($query)
    {
        return $query->where('ativo', true)
            ->where(fn ($q) => $q
                ->whereNull('data_inicio_vigencia')
                ->orWhere('data_inicio_vigencia', '<=', now())
            )
            ->where(fn ($q) => $q
                ->whereNull('data_fim_vigencia')
                ->orWhere('data_fim_vigencia', '>=', now())
            );
    }
}
