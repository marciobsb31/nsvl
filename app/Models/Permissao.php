<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Permissao extends Model
{
    protected $table = 'permissoes';

    protected $fillable = [
        'recurso_id',
        'acao_id',
        'codigo',
        'nome',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function recurso(): BelongsTo
    {
        return $this->belongsTo(Recurso::class, 'recurso_id');
    }

    public function acao(): BelongsTo
    {
        return $this->belongsTo(Acao::class, 'acao_id');
    }

    public function perfilPermissoes(): HasMany
    {
        return $this->hasMany(PerfilPermissao::class, 'permissao_id');
    }

    public function perfis(): BelongsToMany
    {
        return $this->belongsToMany(Perfil::class, 'perfil_permissao', 'permissao_id', 'perfil_id')
            ->withPivot('ativo')
            ->withTimestamps();
    }
}
