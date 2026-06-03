<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $campanha->titulo }}</title>
</head>
<body style="margin:0; padding:0; background:#f6f7f9; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent;">
        {{ $preheader }}
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f6f7f9; padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px; background:#ffffff; border-radius:14px; overflow:hidden; border:1px solid #eceff3;">
                    <tr>
                        <td style="background:#b91c1c; padding:24px 28px;">
                            <div style="font-size:14px; letter-spacing:.08em; text-transform:uppercase; color:#fee2e2; font-weight:700;">
                                DoaVida
                            </div>
                            <h1 style="margin:10px 0 0; color:#ffffff; font-size:28px; line-height:1.2; font-weight:800;">
                                {{ $campanha->titulo }}
                            </h1>
                            @if($campanha->subtitulo)
                                <p style="margin:10px 0 0; color:#fee2e2; font-size:16px; line-height:1.5;">
                                    {{ $campanha->subtitulo }}
                                </p>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px;">
                            <p style="margin:0 0 16px; font-size:17px; line-height:1.6;">
                                Olá, <strong>{{ $doador->name }}</strong>.
                            </p>

                            <p style="margin:0 0 20px; font-size:16px; line-height:1.7; color:#374151;">
                                {{ $campanha->descricao ?: 'Estamos com uma campanha ativa e sua participação pode fazer a diferença para quem precisa de sangue.' }}
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin:22px 0; background:#fff7ed; border:1px solid #fed7aa; border-radius:10px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <div style="font-size:13px; text-transform:uppercase; letter-spacing:.06em; color:#9a3412; font-weight:700;">
                                            Campanha direcionada
                                        </div>
                                        <div style="margin-top:8px; font-size:15px; line-height:1.6; color:#7c2d12;">
                                            @if($bloodType)
                                                Seu tipo sanguíneo cadastrado é compatível com esta campanha:
                                                <strong style="font-size:18px;">{{ $bloodType }}</strong>.
                                            @else
                                                Esta campanha está aberta para todos os tipos sanguíneos cadastrados.
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin:26px 0;">
                                <tr>
                                    <td style="background:#dc2626; border-radius:8px;">
                                        <a href="{{ $ctaUrl }}" style="display:inline-block; padding:14px 22px; color:#ffffff; text-decoration:none; font-weight:700; font-size:16px;">
                                            Entrar para agendar doação
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0; font-size:13px; line-height:1.6; color:#6b7280;">
                                Antes de doar, confirme se você está bem alimentado, hidratado e levando um documento oficial com foto.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:18px 28px; background:#f9fafb; border-top:1px solid #eef2f7;">
                            <p style="margin:0; font-size:12px; line-height:1.6; color:#6b7280;">
                                Enviado pelo DoaVida.
                                @if($expireDate)
                                    Campanha válida até {{ $expireDate }}.
                                @endif
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
