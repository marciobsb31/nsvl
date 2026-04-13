<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function solicitacaoCadastroOrigem(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoCadastro::class, 'solicitacao_cadastro_origem_id');
    }

    public function criadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'criado_por_usuario_id');
    }

    public function contextos(): HasMany
    {
        return $this->hasMany(UsuarioContexto::class, 'usuario_abrangencia_id');
    }

    public function contatos(): UsuarioAbrangencia|HasMany
    {
        return $this->hasMany(UsuarioContato::class, 'usuario_abrangencia_id');
    }

    public function scopeAtivas($query)
    {
        return $query->where('ativo', true);
    }

    public function scopeFederal($query)
    {
        return $query->whereHas('esfera', fn ($q) => $q->where('codigo', 'federal'));
    }

    public function scopeEstadual($query)
    {
        return $query->whereHas('esfera', fn ($q) => $q->where('codigo', 'estadual'));
    }

    public function scopeMunicipal($query)
    {
        return $query->whereHas('esfera', fn ($q) => $q->where('codigo', 'municipal'));
    }
}
