<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sistema de Control de Asistencia')</title>
    <style>
        /* Reset y estilos base */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f4f4f7;
            color: #333333;
            line-height: 1.6;
        }

        /* Contenedor principal */
        .email-wrapper {
            width: 100%;
            background-color: #f4f4f7;
            padding: 40px 20px;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* Header corporativo con gradiente burgundy */
        .email-header {
            background: linear-gradient(135deg, #5B0B0B 0%, #7a1010 100%);
            color: #ffffff;
            padding: 30px 40px;
            text-align: center;
        }

        .email-header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .email-header p {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Cuerpo del email */
        .email-body {
            padding: 40px;
        }

        .email-body h2 {
            color: #5B0B0B;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .email-body p {
            color: #555555;
            font-size: 15px;
            margin-bottom: 12px;
        }

        .email-body strong {
            color: #333333;
        }

        /* Saludo personalizado */
        .greeting {
            font-size: 16px;
            color: #333333;
            margin-bottom: 20px;
        }

        /* Mensaje principal */
        .message {
            background-color: #f9f9fb;
            border-left: 4px solid #5B0B0B;
            padding: 16px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }

        /* Tabla de información */
        .info-table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }

        .info-table tr {
            border-bottom: 1px solid #e0e0e0;
        }

        .info-table tr:last-child {
            border-bottom: none;
        }

        .info-table td {
            padding: 12px 0;
            font-size: 15px;
        }

        .info-table td:first-child {
            font-weight: 600;
            color: #5B0B0B;
            width: 40%;
        }

        .info-table td:last-child {
            color: #555555;
        }

        /* Botón de acción */
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #5B0B0B 0%, #7a1010 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            margin: 20px 0;
            text-align: center;
            transition: transform 0.2s;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(91, 11, 11, 0.3);
        }

        /* Lista con viñetas */
        .info-list {
            margin: 16px 0;
            padding-left: 20px;
        }

        .info-list li {
            margin-bottom: 8px;
            color: #555555;
            font-size: 15px;
        }

        /* Alerta/Advertencia */
        .alert {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 16px 20px;
            margin: 20px 0;
            border-radius: 4px;
        }

        .alert.error {
            background-color: #f8d7da;
            border-left-color: #dc3545;
        }

        .alert.success {
            background-color: #d1ecf1;
            border-left-color: #0c5460;
        }

        /* Footer */
        .email-footer {
            background-color: #f9f9fb;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
        }

        .email-footer p {
            font-size: 13px;
            color: #888888;
            margin-bottom: 8px;
        }

        .email-footer a {
            color: #5B0B0B;
            text-decoration: none;
        }

        .email-footer a:hover {
            text-decoration: underline;
        }

        /* Divisor */
        .divider {
            height: 1px;
            background-color: #e0e0e0;
            margin: 30px 0;
        }

        /* Responsive */
        @media only screen and (max-width: 600px) {
            .email-header,
            .email-body,
            .email-footer {
                padding: 20px !important;
            }

            .email-header h1 {
                font-size: 20px;
            }

            .button {
                display: block;
                padding: 12px 24px;
            }

            .info-table td:first-child {
                width: 35%;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <!-- Header -->
            <div class="email-header">
                <h1>@yield('header-title', 'Sistema de Control de Asistencia')</h1>
                <p>@yield('header-subtitle', 'Universidad XYZ')</p>
            </div>

            <!-- Body -->
            <div class="email-body">
                @yield('content')
            </div>

            <!-- Footer -->
            <div class="email-footer">
                <p><strong>Sistema de Control de Asistencia Automatizada</strong></p>
                <p>Este es un correo automático, por favor no responder a este mensaje.</p>
                <p>
                    Si tienes alguna pregunta, contacta a
                    <a href="mailto:soporte@ues.edu.sv">soporte@ues.edu.sv</a>
                </p>
                <p style="margin-top: 16px; font-size: 12px; color: #aaaaaa;">
                    &copy; {{ date('Y') }} Universidad de El Salvador, FMO UES. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
