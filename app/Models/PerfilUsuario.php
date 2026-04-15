<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model PerfilUsuario — associação usuário-perfil com vigência
 *
 * @property int    $id
 * @property int    $usuario_id       FK → usuarios.id
 * @property int    $perfil_id        FK → perfis.id
 * @property \Carbon\Carbon|null $data_inicio_vigencia
 * @property \Carbon\Carbon|null $data_fim_vigencia
 * @property bool   $ativo
 */
class PerfilUsuario extends Model
{
    protected $table = 'perfil_usuario';

    protected $fillable = [
        'usuario_id',
        'perfil_id',
        'usuario_abrangencia_id',
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
        return $this->belongsTo(Perfil::class);
    }

    public function usuarioAbrangencia(): BelongsTo
    {
        return $this->belongsTo(UsuarioAbrangencia::class, 'usuario_abrangencia_id');
    }
}
