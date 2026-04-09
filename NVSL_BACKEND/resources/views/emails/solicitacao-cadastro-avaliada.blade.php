<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Atualizacao da solicitacao de cadastro</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2 style="margin-bottom: 12px;">Solicitacao de cadastro atualizada</h2>

    <p>
        Ola, {{ $solicitacao->usuario?->nome ?? 'usuario(a)' }}.
    </p>

    @if ($status === 'aprovado')
        <p>
            Sua solicitacao de cadastro no NVSL foi <strong>aprovada</strong>.
        </p>
    @else
        <p>
            Sua solicitacao de cadastro no NVSL foi <strong>reprovada</strong>.
        </p>
        @if (!empty($justificativa))
            <p>
                <strong>Justificativa:</strong> {{ $justificativa }}
            </p>
        @endif
    @endif

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 16px 0;">

    <p style="font-size: 14px; color: #4b5563;">
        Esta e uma mensagem automatica do sistema NVSL.
    </p>
</body>
</html>
