<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function abrangencia(): BelongsTo
    {
        return $this->belongsTo(UsuarioAbrangencia::class, 'usuario_abrangencia_id');
    }

    /**
     * Atalho para o perfil efetivo do contexto atual.
     */
    public function perfil(): ?Perfil
    {
        return $this->perfilUsuario?->perfil;
    }

    /**
     * Atalho para a esfera do contexto atual.
     */
    public function esfera(): ?Esfera
    {
        return $this->abrangencia?->esfera;
    }
}
