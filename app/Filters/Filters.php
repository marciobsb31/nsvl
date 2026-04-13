<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

abstract class Filters
{
    protected Request $request;

    protected Builder $builder;

    protected array $filters = [];

    /**
     * Filters constructor.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply($builder): mixed
    {

        $this->builder = $builder;
        foreach ($this->getFilters() as $filter => $value) {
            if ($this->methodExistsFor($filter)) {
                $method = method_exists($this, $filter) ? $filter : Str::camel($filter);
                $this->$method($value);
            }
        }

        return $this->builder;
    }

    public function getFilters(): array
    {
        return $this->request->only($this->filters);
    }

    private function methodExistsFor($filter): bool
    {
        return method_exists($this, $filter) || method_exists($this, Str::camel($filter));
    }
}
