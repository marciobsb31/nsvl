<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $nome Federal|Estadual|Municipal
 */
class Esfera extends Model
{
    protected $table = 'esferas';

    protected $fillable = [
        'codigo',
        'nome',
    ];

    public function perfis(): HasMany
    {
        return $this->hasMany(Perfil::class, 'esfera_id');
    }

    public function usuarioAbrangencias(): HasMany
    {
        return $this->hasMany(UsuarioAbrangencia::class, 'esfera_id');
    }

    public function solicitacoesCadastro(): HasMany
    {
        return $this->hasMany(SolicitacaoCadastro::class, 'esfera_id_solicitada');
    }
}
