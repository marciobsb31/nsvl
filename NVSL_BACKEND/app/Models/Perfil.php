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
     * Catálogo fixo de perfis MVP (ordem de exibição em listagens administrativas).
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

    /**
     * Mapa: nome do perfil → esfera de atuação.
     */
    public const ESFERA_POR_PERFIL = [
        'Gestor Federal'           => 'federal',
        'Gestor Estadual'          => 'estadual',
        'Gestor Municipal'         => 'municipal',
        'Administrador Estadual'   => 'estadual',
        'Administrador Municipal'  => 'municipal',
        'Visitante Federal'        => 'federal',
        'Visitante Estadual'       => 'estadual',
        'Visitante Municipal'      => 'municipal',
    ];

    /**
     * RN01: Hierarquia de avaliação — perfis que cada avaliador pode aprovar.
     */
    public const HIERARQUIA_AVALIACAO = [
        'Gestor Federal' => [
            'Gestor Federal', 'Gestor Estadual', 'Gestor Municipal',
            'Administrador Estadual', 'Administrador Municipal',
            'Visitante Federal', 'Visitante Estadual', 'Visitante Municipal',
        ],
        'Gestor Estadual' => [
            'Gestor Estadual', 'Administrador Estadual', 'Visitante Estadual',
        ],
        'Gestor Municipal' => [
            'Gestor Municipal', 'Administrador Municipal', 'Visitante Municipal',
        ],
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
