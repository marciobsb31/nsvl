<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Acao extends Model
{
    protected $table = 'acoes';

    protected $fillable = [
        'codigo',
        'nome',
        'descricao',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function permissoes(): HasMany
    {
        return $this->hasMany(Permissao::class, 'acao_id');
    }
}
