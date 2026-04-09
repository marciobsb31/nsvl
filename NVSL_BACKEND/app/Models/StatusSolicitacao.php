<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int    $id
 * @property string $nome   em_analise|aprovado|reprovado
 */
class StatusSolicitacao extends Model
{
    protected $table = 'status_solicitacao';
    public $timestamps = false;

    protected $fillable = ['nome'];

    public const EM_ANALISE = 'em_analise';
    public const APROVADO = 'aprovado';
    public const REPROVADO = 'reprovado';

    public function solicitacoesCadastro(): HasMany
    {
        return $this->hasMany(SolicitacaoCadastro::class, 'status_id');
    }

    public static function idPorNome(string $nome): ?int
    {
        return static::where('nome', $nome)->value('id');
    }
}
