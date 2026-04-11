<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated Tabela permissoes foi removida na nova modelagem.
 * Este arquivo existe apenas para evitar erros de autoload em referências legadas.
 */
class Permissao extends Model
{
    protected $table = 'permissoes';

    protected $fillable = ['modulo', 'acao', 'descricao'];
}
