<?php

namespace App\Models;

use App\Models\Scopes\AbrangenciaScope;
use App\Traits\FilterScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model SolicitacaoCadastro — solicitações de acesso ao sistema
 *
 * @property int         $id
 * @property int|null    $usuario_id            FK → usuarios.id
 * @property string      $email_institucional
 * @property string|null $telefone_institucional
 * @property string|null $telefone_pessoal
 * @property int|null    $esfera_id             FK → esferas.id
 * @property int|null    $uf_id                 FK → ufs.id
 * @property int|null    $municipio_id          FK → municipios.id
 * @property string      $orgao
 * @property string|null $cargo
 * @property int|null    $perfil_id             FK → perfis.id
 * @property \Carbon\Carbon|null $vigencia_inicio
 * @property \Carbon\Carbon|null $vigencia_fim
 * @property int|null    $status_id             FK → status_solicitacao.id
 * @property \Carbon\Carbon|null $aceite_termo_at
 * @property string|null $justificativa_reprovacao
 */
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
        'aceite_termo_at'            => 'datetime',
        'vigencia_inicio'            => 'date',
        'vigencia_fim'               => 'date',
        'esfera_id'                  => 'integer',
        'uf_id'           => 'integer',
        'municipio_id'    => 'integer',
        'status_id'       => 'integer',
        'usuario_id'      => 'integer',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function getUserIdAttribute(): ?int
    {
        return $this->attributes['usuario_id'] ?? null;
    }

    public function setUserIdAttribute(?int $value): void
    {
        $this->attributes['usuario_id'] = $value;
    }

    public function getPerfilIdSolicitadoAttribute(): ?int
    {
        return $this->attributes['perfil_id'] ?? null;
    }

    public function setPerfilIdSolicitadoAttribute(?int $value): void
    {
        $this->attributes['perfil_id'] = $value;
    }

    public function getVigenciaInicioSolicitadaAttribute(): mixed
    {
        return $this->vigencia_inicio;
    }

    public function setVigenciaInicioSolicitadaAttribute(mixed $value): void
    {
        $this->attributes['vigencia_inicio'] = $value;
    }

    public function getVigenciaFimSolicitadaAttribute(): mixed
    {
        return $this->vigencia_fim;
    }

    public function setVigenciaFimSolicitadaAttribute(mixed $value): void
    {
        $this->attributes['vigencia_fim'] = $value;
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
        return $this->belongsTo(Perfil::class, 'perfil_id');
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

    /**
     * Scope: filtra solicitações conforme esfera de atuação do usuário.
     *  - Federal: acesso irrestrito
     *  - Estadual: apenas mesma esfera + mesma UF
     *  - Municipal: apenas mesma esfera + mesma UF + mesmo município
     *
     * @param  Builder<self>  $query
     */
    public function scopeVisivelPara(Builder $query, Usuario $user): void
    {
        $esfera = mb_strtolower(trim((string) $user->esfera_atuacao), 'UTF-8');

        // Perfil ativo federal sempre possui acesso irrestrito à listagem.
        if ($user->isPerfilFederalAtivo() || $esfera === 'federal') {
            return;
        }

        $ufSigla = strtoupper(trim((string) $user->uf_lotacao));
        $municipioNome = trim((string) $user->municipio_lotacao);

        if ($esfera === 'estadual') {
            $esferaId = Esfera::where('nome', 'Estadual')->value('id');
            $ufId = Uf::where('sigla', $ufSigla)->value('id');
            $query->where('esfera_id', $esferaId)
                ->where('uf_id', $ufId);

            return;
        }

        if ($esfera === 'municipal') {
            $esferaId = Esfera::where('nome', 'Municipal')->value('id');
            $ufId = Uf::where('sigla', $ufSigla)->value('id');
            $municipioId = Municipio::where('nome', $municipioNome)->where('uf_id', $ufId)->value('id');
            $query->where('esfera_id', $esferaId)
                ->where('uf_id', $ufId)
                ->where('municipio_id', $municipioId);
        }
    }
}
