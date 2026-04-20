<?php

namespace App\Filters;

use App\Helpers\Helpers;

class SolicitacaoCadastroFilter extends Filters
{
    protected array $filters = [
        'nome',
        'cpf',
        'uf_id',
        'municipio_id',
        'orgao',
        'esfera_id',
        'status_id',
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
            $query->where('cpf', $param);
        });
    }

    public function esferaId(string $param)
    {
        $param = Helpers::onlyDigits($param);
        $this->builder->where('esfera_id', $param);
    }

    public function ufId(string $param)
    {
        $param = Helpers::onlyDigits($param);
        $this->builder->where('uf_id', $param);
    }

    public function municipioId(string $param)
    {
        $param = Helpers::onlyDigits($param);
        $this->builder->where('municipio_id', $param);
    }

    public function orgao(string $param)
    {
        $this->builder->where('orgao', 'ILIKE', '%'.$param.'%');
    }

    public function statusId(string $param)
    {
        $param = Helpers::onlyDigits($param);
        $this->builder->where('status_id', $param);
    }
}
