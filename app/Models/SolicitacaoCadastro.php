<?php

namespace App\Models;

use App\Models\Scopes\AbrangenciaScope;
use App\Traits\FilterScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitacaoCadastro extends Model
{
    use FilterScope;
    use HasFactory;

    protected $table = 'solicitacoes_cadastro';

    protected static function booted(): void
    {
        static::addGlobalScope(new AbrangenciaScope);
    }

    protected $fillable = [
        'usuario_id',
        'email_institucional',
        'telefone_institucional',
        'telefone_pessoal',
        'esfera_id',
        'uf_id',
        'municipio_id',
        'orgao',
        'cargo',
        'perfil_id',
        'vigencia_inicio',
        'vigencia_fim',
        'status_id',
        'aceite_termo_at',
        'justificativa_reprovacao',
    ];

    protected $casts = [
        'aceite_termo_at' => 'datetime',
        'vigencia_inicio' => 'date',
        'vigencia_fim'    => 'date',
        'esfera_id'       => 'integer',
        'uf_id'           => 'integer',
        'municipio_id'    => 'integer',
        'status_id'       => 'integer',
        'usuario_id'      => 'integer',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function esfera(): BelongsTo
    {
        return $this->belongsTo(Esfera::class, 'esfera_id');
    }

    public function ufRelacao(): BelongsTo
    {
        return $this->belongsTo(Uf::class, 'uf_id');
    }

    public function municipioRelacao(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'municipio_id');
    }

    public function perfilSolicitado(): BelongsTo
    {
        return $this->belongsTo(Perfil::class, 'perfil_id_solicitado');
    }

    public function statusSolicitacao(): BelongsTo
    {
        return $this->belongsTo(StatusSolicitacao::class, 'status_id');
    }

    // -------------------------------------------------------
    // Acessores de conveniência (compatibilidade)
    // -------------------------------------------------------

    public function getStatusAttribute(): string
    {
        return $this->statusSolicitacao?->nome ?? StatusSolicitacao::EM_ANALISE;
    }

    public function getNomeAttribute(): string
    {
        return $this->usuario?->nome ?? '';
    }

    public function getEsferaAtuacaoAttribute(): string
    {
        return $this->esfera?->nome ?? '';
    }

    public function getUfAttribute(): string
    {
        return $this->ufRelacao?->sigla ?? '';
    }

    public function getUfSiglaAttribute(): string
    {
        return $this->getUfAttribute();
    }

    public function getMunicipioAttribute(): string
    {
        return $this->municipioRelacao?->nome ?? '';
    }

    public function getMunicipioNomeAttribute(): string
    {
        return $this->getMunicipioAttribute();
    }
}
