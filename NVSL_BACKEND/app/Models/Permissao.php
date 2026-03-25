<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int         $id
 * @property string      $modulo
 * @property string      $acao
 * @property string|null $descricao
 */
class Permissao extends Model
{
    use HasFactory;
    protected $table = 'permissoes';

    protected $fillable = [
        'modulo',
        'acao',
        'descricao',
    ];

    public function perfis(): BelongsToMany
    {
        return $this->belongsToMany(Perfil::class, 'perfil_permissao', 'permissao_id', 'perfil_id')
            ->withTimestamps();
    }
}
