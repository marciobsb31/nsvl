<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UsuarioContexto — armazena o contexto ativo do usuário
 * 
 * @property int $id
 * @property int $usuario_id FK → usuarios.id (unique)
 * @property int|null $perfil_usuario_id FK → perfil_usuario.id (ativo agora)
 * @property int|null $usuario_abrangencia_id FK → usuario_abrangencia.id (contexto geográfico ativo)
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class UsuarioContexto extends Model
{
    protected $table = 'usuario_contexto';

    protected $fillable = [
        'usuario_id',
        'perfil_usuario_id',
        'usuario_abrangencia_id',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function perfilUsuario(): BelongsTo
    {
        return $this->belongsTo(PerfilUsuario::class, 'perfil_usuario_id');
    }

    public function usuarioAbrangencia(): BelongsTo
    {
        return $this->belongsTo(UsuarioAbrangencia::class, 'usuario_abrangencia_id');
    }
}
