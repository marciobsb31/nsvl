<?php

namespace App\Http\Controllers;

use App\Http\Resources\StatusSolicitacaoResource;
use App\Models\StatusSolicitacao;

class StatusSolicitacaoController extends Controller
{
    public function __invoke()
    {
     return StatusSolicitacaoResource::collection(StatusSolicitacao::all());
    }
}
