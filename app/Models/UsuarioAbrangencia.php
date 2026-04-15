<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * UsuarioAbrangencia — armazena contextos geográficos/administrativos do usuário
 * 
 * Um usuário pode ter múltiplas abrangências (ex: Federal, Estadual SP, Municipal SP/Rio de Janeiro)
 * 
 * @property int $id
 * @property int $usuario_id FK → usuarios.id
 * @property int $esfera_id FK → esferas.id
 * @property int|null $uf_id FK → ufs.id (obrigatório para estadual/municipal)
 * @property int|null $municipio_id FK → municipios.id (obrigatório para municipal)
 * @property string $nome Descrição/nome da abrangência
 * @property string $origem_tipo Como foi criada (solicitacao, manual, etc)
 * @property int|null $solicitacao_cadastro_origem_id FK originária
 * @property int|null $criado_por_usuario_id FK → usuarios.id
 * @property bool $ativo Marca qual abrangência está ativa
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class UsuarioAbrangencia extends Model
{
    protected $table = 'usuario_abrangencia';

    protected $fillable = [
        'usuario_id',
        'esfera_id',
        'uf_id',
        'municipio_id',
        'nome',
        'origem_tipo',
        'solicitacao_cadastro_origem_id',
        'criado_por_usuario_id',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function esfera(): BelongsTo
    {
        return $this->belongsTo(Esfera::class, 'esfera_id');
    }

    public function uf(): BelongsTo
    {
        return $this->belongsTo(Uf::class, 'uf_id');
    }

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function solicitacaoCadastro(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoCadastro::class, 'solicitacao_cadastro_origem_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por_usuario_id');
    }
}
