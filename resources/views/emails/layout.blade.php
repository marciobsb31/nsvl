<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('email-title', 'NVSL')</title>
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
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .footer { padding: 16px 24px; background: #f8f8f8; text-align: center; font-size: 12px; color: #888; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 16px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Novo Viver Sem Limite - NVSL</h1>
        </div>

        <div class="content">
            @yield('content')
        </div>

        <div class="footer">
            <p>Este e um e-mail automatico do sistema NVSL. Nao responda a esta mensagem.</p>
            <p>Ministerio dos Direitos Humanos e da Cidadania - Governo Federal</p>
        </div>
    </div>
</body>
</html>