<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

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

    /**
     * Catálogo permitido conforme o perfil ativo do gestor.
     * Federal/Nacional: qualquer perfil do catálogo oficial.
     * Estadual: Gestor Estadual e Gestor Municipal.
     * Municipal: apenas Gestor Municipal.
     * Demais perfis: catálogo oficial completo.
     *
     * @return list<string>
     */
    public static function catalogoPermitidoParaUsuario(?Usuario $usuario): array
    {
        $perfilAtivo = mb_strtolower(trim((string) ($usuario?->perfilAtivo()?->nome ?? '')), 'UTF-8');

        return match ($perfilAtivo) {
            'gestor estadual' => [
                'Gestor Estadual',
                'Gestor Municipal',
            ],
            'gestor municipal' => [
                'Gestor Municipal',
            ],
            default => self::CATALOGO_OFICIAL,
        };
    }

    public static function queryCatalogoPermitidoParaUsuario(?Usuario $usuario)
    {
        return self::query()
            ->whereIn('nome', self::catalogoPermitidoParaUsuario($usuario))
            ->where('ativo', true);
    }

    /**
     * @return Collection<int, self>
     */
    public static function listarCatalogoPermitidoParaUsuario(?Usuario $usuario): Collection
    {
        return self::queryCatalogoPermitidoParaUsuario($usuario)
            ->get(['id', 'nome', 'descricao'])
            ->sortBy(fn (self $perfil): int => self::indiceNoCatalogo($perfil->nome))
            ->values();
    }

    public static function perfilPermitidoParaUsuario(?Usuario $usuario, int $perfilId): bool
    {
        return self::queryCatalogoPermitidoParaUsuario($usuario)
            ->where('id', $perfilId)
            ->exists();
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
