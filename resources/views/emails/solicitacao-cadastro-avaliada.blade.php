<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Atualização da solicitação de cadastro</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f2937;">
    <h2 style="margin-bottom: 12px;">Solicitação de cadastro atualizada</h2>

    <p>
        Olá, {{ $solicitacao->usuario?->nome ?? 'usuário(a)' }}.
    </p>

    @if ($status === 'aprovado')
        <p>
            Sua solicitação de cadastro no NVSL foi <strong>aprovada</strong>.
        </p>
    @else
        <p>
            Sua solicitação de cadastro no NVSL foi <strong>reprovada</strong>.
        </p>
        @if (!empty($justificativa))
            <p>
                <strong>Justificativa:</strong> {{ $justificativa }}
            </p>
        @endif
    @endif

    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 16px 0;">

    <p style="font-size: 14px; color: #4b5563;">
        Esta é uma mensagem automática do sistema NVSL.
    </p>
</body>
</html>
