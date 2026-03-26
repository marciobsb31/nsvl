<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model PerfilUsuario — associação usuário-perfil com vigência
 *
 * @property int    $id
 * @property int    $usuario_id
 * @property int    $perfil_id
 * @property string $esfera federal|estadual|municipal (espelha perfis.esfera; FK esferas.codigo)
 * @property \Carbon\Carbon|null $data_inicio_vigencia
 * @property \Carbon\Carbon|null $data_fim_vigencia
 */
class PerfilUsuario extends Model
{
    protected $table = 'perfil_usuario';

    protected $fillable = [
        'usuario_id',
        'perfil_id',
        'esfera',
        'data_inicio_vigencia',
        'data_fim_vigencia',
        'uf',
        'municipio',
        'orgao',
    ];

    protected $casts = [
        'data_inicio_vigencia' => 'date',
        'data_fim_vigencia'    => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (PerfilUsuario $pu): void {
            if (!$pu->perfil_id) {
                return;
            }
            if ($pu->isDirty('perfil_id') || $pu->esfera === null || $pu->esfera === '') {
                $codigo = Perfil::query()->whereKey($pu->perfil_id)->value('esfera');
                if ($codigo !== null && $codigo !== '') {
                    $pu->esfera = $codigo;
                }
            }
        });
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(Perfil::class);
    }

    /** Esfera do tipo de perfil deste vínculo — tabela esferas. */
    public function dominioEsfera(): BelongsTo
    {
        return $this->belongsTo(Esfera::class, 'esfera', 'codigo');
    }

    public function ufVinculo(): BelongsTo
    {
        return $this->belongsTo(Uf::class, 'uf', 'sigla');
    }
}
