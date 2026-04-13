<?php

namespace App\Filters;

use App\Helpers\Helpers;

class SolicitacaoCadastroFilter extends Filters
{
    protected array $filters = [
        'nome', 'cpf', 'esfera_id', 'estado_id', 'municipio_id', 'cargo', 'orgao', 'status_solicitacao_id',
    ];

    public function nome(string $param)
    {
        $this->builder->whereHas('usuario', function ($query) use ($param) {
            $query->where('nome', 'ILIKE', '%'.$param.'%');
        });
    }

    public function cpf(string $param)
    {
        $param = Helpers::onlyDigits($param);

        $this->builder->whereHas('usuario', function ($query) use ($param) {
            $this->builder->where('cpf', $param);
        });
    }

    public function esferaId(string $param)
    {
        $param = Helpers::onlyDigits($param);
        $this->builder->where('esfera_id', $param);
    }

    public function estadoId(string $param)
    {
        $param = Helpers::onlyDigits($param);
        $this->builder->where('estado_id', $param);
    }

    public function municipioId(string $param)
    {
        $param = Helpers::onlyDigits($param);
        $this->builder->where('municipio_id', $param);
    }

    public function cargo(string $param)
    {
        $this->builder->where('cargo', 'ILIKE', '%'.$param.'%');
    }

    public function orgao(string $param)
    {
        $this->builder->where('orgao', 'ILIKE', '%'.$param.'%');
    }

    public function statusSolicitacaoId(string $param)
    {
        $param = Helpers::onlyDigits($param);
        $this->builder->where('status_solicitacao_id', $param);
    }
}
