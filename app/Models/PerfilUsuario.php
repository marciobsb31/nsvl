<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PerfilUsuario extends Model
{
    protected $table = 'perfil_usuario';

    protected $fillable = [
        'usuario_id',
        'perfil_id',
        'usuario_abrangencia_id',
        'data_inicio_vigencia',
        'data_fim_vigencia',
        'origem_tipo',
        'solicitacao_cadastro_origem_id',
        'atribuido_por_usuario_id',
        'ativo',
    ];

    protected $casts = [
        'data_inicio_vigencia' => 'date',
        'data_fim_vigencia'    => 'date',
        'ativo'                => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function perfil(): BelongsTo
    {
        return $this->belongsTo(Perfil::class, 'perfil_id');
    }

    public function solicitacaoCadastroOrigem(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoCadastro::class, 'solicitacao_cadastro_origem_id');
    }

    public function atribuidoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'atribuido_por_usuario_id');
    }

    public function contexto(): HasOne
    {
        return $this->hasOne(UsuarioContexto::class, 'perfil_usuario_id');
    }

    public function abrangencia()
    {
        return $this->belongsTo(UsuarioAbrangencia::class, 'usuario_abrangencia_id');
    }
}
