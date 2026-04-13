<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsuarioContato extends Model
{
    protected $table = 'usuario_contato';

    protected $fillable = [
        'usuario_id',
        'usuario_abrangencia_id',
        'tipo_contato',
        'classificacao_contato',
        'valor',
        'principal',
        'origem_tipo',
        'solicitacao_cadastro_origem_id',
        'ativo',
    ];

    protected $casts = [
        'principal' => 'boolean',
        'ativo'     => 'boolean',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function abrangencia(): BelongsTo
    {
        return $this->belongsTo(UsuarioAbrangencia::class, 'usuario_abrangencia_id');
    }

    public function solicitacaoCadastroOrigem(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoCadastro::class, 'solicitacao_cadastro_origem_id');
    }

    public function scopeAtivos($query)
    {
        return $query->where('ativo', true);
    }

    public function scopePrincipais($query)
    {
        return $query->where('principal', true);
    }

    public function scopeEmail($query)
    {
        return $query->where('tipo_contato', 'email');
    }

    public function scopeTelefone($query)
    {
        return $query->where('tipo_contato', 'telefone');
    }

    public function scopeInstitucional($query)
    {
        return $query->where('classificacao_contato', 'institucional');
    }

    public function scopePessoal($query)
    {
        return $query->where('classificacao_contato', 'pessoal');
    }
}
