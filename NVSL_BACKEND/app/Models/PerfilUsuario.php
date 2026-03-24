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
 * @property \Carbon\Carbon|null $data_inicio_vigencia
 * @property \Carbon\Carbon|null $data_fim_vigencia
 */
class PerfilUsuario extends Model
{
    protected $table = 'perfil_usuario';

    protected $fillable = [
        'usuario_id',
        'perfil_id',
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

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(Perfil::class);
    }
}
