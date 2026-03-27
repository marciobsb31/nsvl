<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int    $id
 * @property string $nome   Federal|Estadual|Municipal
 */
class Esfera extends Model
{
    protected $table = 'esferas';
    public $timestamps = false;

    protected $fillable = ['nome'];

    public function solicitacoesCadastro(): HasMany
    {
        return $this->hasMany(SolicitacaoCadastro::class, 'esfera_id');
    }
}
