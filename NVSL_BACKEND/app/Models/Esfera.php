<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $codigo
 * @property string $nome
 * @property int $ordem
 */
class Esfera extends Model
{
    protected $table = 'esferas';

    protected $fillable = ['codigo', 'nome', 'ordem'];

    public $timestamps = true;
}
