<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property string      $nome
 * @property string|null $descricao
 * @property string      $esfera   federal|estadual|municipal
 * @property string      $status   ativo|inativo
 */
class Perfil extends Model
{
    use HasFactory;
    protected $table = 'perfis';

    protected $fillable = [
        'nome',
        'descricao',
        'esfera',
        'status',
    ];

    public function perfisUsuario(): HasMany
    {
        return $this->hasMany(PerfilUsuario::class, 'perfil_id');
    }

    public function permissoes(): BelongsToMany
    {
        return $this->belongsToMany(Permissao::class, 'perfil_permissao', 'perfil_id', 'permissao_id')
            ->withTimestamps();
    }
}
