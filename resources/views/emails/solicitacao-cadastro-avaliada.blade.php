@extends('emails.layout')

@section('email-title', 'Atualização da solicitação de cadastro')

@section('content')
    <h2>Solicitação de Cadastro Atualizada</h2>

    <p>Olá, {{ $solicitacao->usuario?->nome ?? 'usuário(a)' }}.</p>

    @if ($status === 'aprovado')
        <p>Sua solicitação de cadastro no NVSL foi <strong>aprovada</strong>.</p>
        <p><span class="badge badge-success">Aprovada</span></p>
    @else
        <p>Sua solicitação de cadastro no NVSL foi <strong>reprovada</strong>.</p>
        <p><span class="badge badge-danger">Reprovada</span></p>
        @if (!empty($justificativa))
            <p><strong>Justificativa:</strong> {{ $justificativa }}</p>
        @endif
    @endif

    <hr class="divider">

    <p>Se necessário, entre em contato com a equipe responsável para mais informações.</p>
@endsection
