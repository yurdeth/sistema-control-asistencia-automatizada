<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
            background-color: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px 20px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
            font-weight: 700;
        }
        .content {
            padding: 30px 20px;
            line-height: 1.6;
            color: #333333;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
            color: #666666;
        }
        .message {
            font-size: 14px;
            margin-bottom: 30px;
            color: #666666;
            line-height: 1.8;
        }
        .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s;
        }
        .button:hover {
            transform: translateY(-2px);
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            color: #856404;
            font-size: 13px;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 20px;
            text-align: center;
            color: #999999;
            font-size: 12px;
            border-top: 1px solid #eeeeee;
        }
        .link-text {
            color: #667eea;
            word-break: break-all;
            font-size: 12px;
            margin-top: 15px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Restablecer Contraseña</h1>
        </div>

        <div class="content">
            <div class="greeting">
                @if($userName)
                    ¡Hola {{ $userName }}!
                @else
                    ¡Hola!
                @endif
            </div>

            <div class="message">
                Hemos recibido una solicitud para restablecer la contraseña de tu cuenta en el <strong>Sistema de Control de Asistencia</strong>.
                <br><br>
                Haz clic en el botón de abajo para restablecer tu contraseña. Este enlace expirará en <strong>1 hora</strong>.
            </div>

            <div class="button-container">
                <a href="{{ $resetUrl }}" class="button">Restablecer Contraseña</a>
            </div>

            <div class="warning">
                <strong>⚠️ Seguridad:</strong> Si no solicitaste restablecer tu contraseña, ignora este correo. Tu cuenta está segura.
            </div>

            <div class="message" style="margin-top: 30px; font-size: 13px; color: #999999;">
                Si el botón anterior no funciona, copia y pega esta URL en tu navegador:
                <div class="link-text">{{ $resetUrl }}</div>
            </div>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Sistema de Control de Asistencia. Todos los derechos reservados.</p>
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
        </div>
    </div>
</body>
</html>
