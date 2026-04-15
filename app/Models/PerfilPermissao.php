<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerfilPermissao extends Model
{
    protected $table = 'perfil_permissao';

    public $incrementing = true;

    protected $fillable = [
        'perfil_id',
        'permissao_id',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(Perfil::class, 'perfil_id');
    }

    public function permissao(): BelongsTo
    {
        return $this->belongsTo(Permissao::class, 'permissao_id');
    }
}
