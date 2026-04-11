<?php

namespace App\Traits;

use App\Filters\Filters;
use Illuminate\Database\Eloquent\Builder;

trait FilterScope
{
    public function scopeFilters(Builder $query, Filters $filters): mixed
    {
        return $filters->apply($query);
    }
}
