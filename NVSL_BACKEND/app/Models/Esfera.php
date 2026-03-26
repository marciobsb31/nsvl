<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $codigo
 * @property string $nome
 * @property int $ordem
 */
class Esfera extends Model
{
    protected $table = 'esferas';

    protected $fillable = ['codigo', 'nome', 'ordem'];

    public $timestamps = true;

    public function perfis(): HasMany
    {
        return $this->hasMany(Perfil::class, 'esfera', 'codigo');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'esfera_atuacao', 'codigo');
    }

    public function solicitacoesCadastro(): HasMany
    {
        return $this->hasMany(SolicitacaoCadastro::class, 'esfera_atuacao', 'codigo');
    }

    /** Vínculos usuário–perfil cuja esfera coincide com este domínio. */
    public function perfilUsuarios(): HasMany
    {
        return $this->hasMany(PerfilUsuario::class, 'esfera', 'codigo');
    }
}
