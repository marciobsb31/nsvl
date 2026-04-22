<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanoAcaoObservacao extends Model
{
    protected $table = 'plano_acao_observacoes';

    protected $fillable = [
        'usuario_id',
        'observacoes',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
