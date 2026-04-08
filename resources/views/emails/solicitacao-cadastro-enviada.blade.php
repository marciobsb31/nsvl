<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: 'Rawline', Arial, sans-serif; color: #333; line-height: 1.6; margin: 0; padding: 0; background: #f5f5f5; }
        .container { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: #1351b4; color: #fff; padding: 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 700; }
        .content { padding: 24px; }
        .content h2 { font-size: 16px; color: #1351b4; margin-top: 0; }
        .info-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
        .info-table td { padding: 8px 12px; border-bottom: 1px solid #eee; }
        .info-table td:first-child { font-weight: 600; color: #555; width: 40%; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 4px; font-size: 13px; font-weight: 600; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .footer { padding: 16px 24px; background: #f8f8f8; text-align: center; font-size: 12px; color: #888; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Novo Viver Sem Limite — NVSL</h1>
        </div>
        <div class="content">
            <h2>Solicitação de Cadastro Recebida</h2>
            <p>Prezado(a) <strong>{{ $solicitacao->nome }}</strong>,</p>
            <p>Sua solicitação de cadastro no sistema NVSL foi recebida com sucesso e encontra-se <span class="badge badge-warning">Em Análise</span>.</p>

            <table class="info-table">
                <tr><td>Protocolo</td><td>#{{ $solicitacao->id }}</td></tr>
                <tr><td>Data/hora do envio</td><td>{{ $solicitacao->created_at->format('d/m/Y H:i:s') }}</td></tr>
                <tr><td>Esfera de atuação</td><td>{{ ucfirst($solicitacao->esfera_atuacao) }}</td></tr>
                <tr><td>UF</td><td>{{ $solicitacao->uf }}</td></tr>
                <tr><td>Município</td><td>{{ $solicitacao->municipio }}</td></tr>
                <tr><td>Órgão</td><td>{{ $solicitacao->orgao }}</td></tr>
            </table>

            <p>A equipe responsável irá avaliar sua solicitação. Você será notificado sobre o resultado.</p>
            <p><strong>Ciência do termo de uso e privacidade:</strong> registrada em {{ $solicitacao->aceite_termo_at?->format('d/m/Y H:i:s') ?? $solicitacao->created_at->format('d/m/Y H:i:s') }} (momento do envio da solicitação).</p>
        </div>
        <div class="footer">
            <p>Este é um e-mail automático do sistema NVSL. Não responda a esta mensagem.</p>
            <p>Ministério dos Direitos Humanos e da Cidadania — Governo Federal</p>
        </div>
    </div>
</body>
</html>
