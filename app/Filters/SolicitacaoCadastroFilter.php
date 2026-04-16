<?php

namespace App\Filters;

use App\Helpers\Helpers;

class SolicitacaoCadastroFilter extends Filters
{
    protected array $filters = [
        'nome',
        'cpf',
        'esfera_id',
        'estado_id',
        'municipio_id',
        'cargo',
        'orgao',
        'status_solicitacao_id',
        'uf',
        'municipio',
        'esfera',
        'status',
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

    public function estadoId(string $param)
    {
        $param = Helpers::onlyDigits($param);
        $this->builder->where('uf_id', $param);
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
        $this->builder->where('status_id', $param);
    }

    public function uf(string $param)
    {
        $digits = Helpers::onlyDigits($param);
        if ($digits !== '') {
            $this->builder->where('uf_id', $digits);

            return;
        }

        $uf = strtoupper(trim($param));
        $this->builder->whereHas('ufRelacao', function ($query) use ($uf) {
            $query->where('sigla', $uf);
        });
    }

    public function municipio(string $param)
    {
        $digits = Helpers::onlyDigits($param);
        if ($digits !== '') {
            $this->builder->where('municipio_id', $digits);

            return;
        }

        $this->builder->whereHas('municipioRelacao', function ($query) use ($param) {
            $query->where('nome', 'ILIKE', '%'.$param.'%');
        });
    }

    public function esfera(string $param)
    {
        $digits = Helpers::onlyDigits($param);
        if ($digits !== '') {
            $this->builder->where('esfera_id', $digits);

            return;
        }

        $this->builder->whereHas('esfera', function ($query) use ($param) {
            $query->whereRaw('LOWER(nome) = ?', [mb_strtolower(trim($param))]);
        });
    }

    public function status(string $param)
    {
        $digits = Helpers::onlyDigits($param);
        if ($digits !== '') {
            $this->builder->where('status_id', $digits);

            return;
        }

        $this->builder->whereHas('statusSolicitacao', function ($query) use ($param) {
            $query->whereRaw('LOWER(nome) = ?', [mb_strtolower(trim($param))]);
        });
    }
}
