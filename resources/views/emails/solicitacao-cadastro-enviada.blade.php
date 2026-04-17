@extends('emails.layout')

@section('email-title', 'Solicitação de Cadastro Recebida')

@section('content')
    @php
        $protocoloSequencial = 2026000 + (int) ($solicitacao->id ?? 0);
        $ufEmail = $solicitacao->ufRelacao?->sigla
            ?? $solicitacao->ufRelacao?->nome
            ?? $solicitacao->uf
            ?? '-';
    @endphp

    <h2>Solicitação de Cadastro Recebida</h2>
    <p>Prezado(a) <strong>{{ $solicitacao->nome }}</strong>,</p>
    <p>Sua solicitação de cadastro no sistema NVSL foi recebida com sucesso e encontra-se <span class="badge badge-warning">Em Análise</span>.</p>

    <table class="info-table">
        <tr><td>Protocolo</td><td>{{ $protocoloSequencial }}</td></tr>
        <tr><td>Data/hora do envio</td><td>{{ $solicitacao->created_at->format('d/m/Y H:i:s') }}</td></tr>
        <tr><td>Esfera de atuação</td><td>{{ ucfirst($solicitacao->esfera_atuacao) }}</td></tr>
        <tr><td>UF</td><td>{{ $ufEmail }}</td></tr>
        <tr><td>Município</td><td>{{ $solicitacao->municipio }}</td></tr>
        <tr><td>Órgão</td><td>{{ $solicitacao->orgao }}</td></tr>
    </table>

    <p>A equipe responsável irá avaliar sua solicitação. Você será notificado sobre o resultado.</p>
    <p><strong>Ciência do termo de uso e privacidade:</strong> registrada em {{ $solicitacao->aceite_termo_at?->format('d/m/Y H:i:s') ?? $solicitacao->created_at->format('d/m/Y H:i:s') }} (momento do envio da solicitação).</p>
@endsection
