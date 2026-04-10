<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int         $id
 * @property string      $nome
 * @property string|null $descricao
 * @property bool        $ativo
 */
class Perfil extends Model
{
    use HasFactory;
    protected $table = 'perfis';

    /**
     * Catálogo fixo de perfis (ordem de exibição em listagens administrativas).
     *
     * @var list<string>
     */
    public const CATALOGO_OFICIAL = [
        'Gestor Federal',
        'Gestor Estadual',
        'Gestor Municipal',
        'Administrador Estadual',
        'Administrador Municipal',
        'Visitante Federal',
        'Visitante Estadual',
        'Visitante Municipal',
    ];

    public static function indiceNoCatalogo(string $nome): int
    {
        $i = array_search($nome, self::CATALOGO_OFICIAL, true);

        return $i === false ? PHP_INT_MAX : $i;
    }

    protected $fillable = [
        'nome',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function perfisUsuario(): HasMany
    {
        return $this->hasMany(PerfilUsuario::class, 'perfil_id');
    }
}
