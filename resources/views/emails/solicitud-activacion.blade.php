<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Bienvenido al Sistema</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif, ubuntu;
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
            background: linear-gradient(135deg, #5B0B0B 0%, #7a1010 100%);
            padding: 30px 20px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 5px;
            font-weight: 700;
        }
        .header p {
            font-size: 14px;
            opacity: 0.9;
        }

        .content {
            padding: 30px 20px;
            line-height: 1.6;
            color: #333333;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333333;
            font-weight: 600;
        }
        .message {
            font-size: 14px;
            margin-bottom: 30px;
            color: #666666;
            line-height: 1.8;
        }

        .credentials-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            border: 2px solid #dee2e6;
        }
        .credentials-box h3 {
            color: #5B0B0B;
            margin-bottom: 20px;
            font-size: 16px;
            text-align: center;
            font-weight: 700;
        }
        .credential-item {
            background-color: #ffffff;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 6px;
            border-left: 4px solid #5B0B0B;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }
        .credential-item:last-child {
            margin-bottom: 0;
        }
        .credential-label {
            font-size: 12px;
            color: #999999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .credential-value {
            font-size: 16px;
            color: #333333;
            font-weight: 600;
            font-family: 'Courier New', monospace;
            word-break: break-all;
        }

        .footer {
            background-color: #f9f9f9;
            padding: 20px;
            text-align: center;
            color: #999999;
            font-size: 12px;
            border-top: 1px solid #eeeeee;
        }
            .button-container {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            padding: 14px 40px;
            background: linear-gradient(135deg, #5B0B0B 0%, #7a1010 100%);
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s;
            box-shadow: 0 4px 12px rgba(91, 11, 11, 0.3);
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(91, 11, 11, 0.4);
        }
        .warning {
            background-color: #fff5f5;
            border-left: 4px solid #fc8181;
            padding: 15px;
            margin: 25px 0;
            border-radius: 8px;
            color: #c53030;
            font-size: 13px;
            line-height: 1.6;
        }
        .info-box {
            background-color: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin: 25px 0;
            border-radius: 8px;
            color: #1e40af;
            font-size: 13px;
            line-height: 1.6;
        }
        .steps {
            margin: 25px 0;
        }
        .step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .step-number {
            background: linear-gradient(135deg, #5B0B0B 0%, #7a1010 100%);
            color: #ffffff;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
            margin-right: 12px;
        }
        .step-text {
            font-size: 14px;
            color: #666666;
            line-height: 1.6;
            padding-top: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Bienvenido al SICA!</h1>
            <p>Sistema de Control de Asistencia Automatizada</p>
        </div>

        <div class="content">
            <div class="greeting">
                ¡Hola {{ $userName }}!
            </div>

            <div class="message">
                Has solicitado activar tu cuenta en el  <strong>SICA</strong>.
                A continuación, encontrarás tus credenciales de acceso para iniciar sesión por primera vez.
            </div>

            <div class="credentials-box">
                <h3>🔐 Tus Credenciales de Acceso son:</h3>

                <div class="credential-item">
                    <div class="credential-label">Usuario / Correo Electrónico</div>
                    <div class="credential-value">{{ $email }}</div>
                </div>

                <div class="credential-item">
                    <div class="credential-label">Contraseña Temporal</div>
                    <div class="credential-value">{{ $password }}</div>
                </div>
            </div>

            <div class="warning">
                <strong>⚠️ Importante:</strong> Esta es una contraseña temporal. Por tu seguridad, te recomendamos cambiarla después de tu primer inicio de sesión.
            </div>

            <div class="message" style="margin-bottom: 15px;">
                <strong>Pasos para acceder al sistema:</strong>
            </div>

            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <div class="step-text">Haz clic en el botón "Acceder al Sistema" o copia el enlace proporcionado</div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-text">Ingresa tu correo electrónico y contraseña temporal</div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-text">Cambia tu contraseña por una nueva y segura</div>
                </div>
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-text">¡Comienza a usar el sistema!</div>
                </div>
            </div>

            <div class="button-container">
                <a href="{{ $loginUrl }}" class="button">Acceder al Sistema</a>
            </div>

            <div class="message" style="margin-top: 30px; font-size: 13px; color: #999999;">
                Si el botón anterior no funciona, copia y pega esta URL en tu navegador:
                <div style="color: #5B0B0B; word-break: break-all; font-size: 12px; margin-top: 10px; font-family: monospace;">
                    {{ $loginUrl }}
                </div>
            </div>

        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Sistema de Control de Asistencia. Todos los derechos reservados.</p>
            <p>Este es un correo automático, por favor no respondas a este mensaje.</p>
        </div>

    </div>
</body>
</html>
