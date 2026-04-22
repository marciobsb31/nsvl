<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanoAcaoAnexo extends Model
{
    protected $table = 'plano_acao_anexos';

    protected $fillable = [
        'usuario_id',
        'nome_original',
        'nome_armazenado',
        'tipo_mime',
        'tamanho_bytes',
        'caminho',
    ];

    protected $casts = [
        'tamanho_bytes' => 'integer',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }
}
