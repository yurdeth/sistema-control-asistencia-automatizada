<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cambio de Contraseña - Primer Ingreso</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
            max-width: 480px;
            width: 100%;
            overflow: hidden;
        }

        /* ESTILO DEL ENCABEZADO */
        .header {
            background: linear-gradient(135deg, #5B0B0B 0%, #882317 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .header-icon {
            width: 70px;
            height: 70px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 32px;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .header p {
            font-size: 14px;
            opacity: 0.95;
            line-height: 1.5;
        }

        /*  CONTENEDOR DEL FORMULARIO */
        .form-container {
            padding: 40px 30px;
        }

        /* Estilos de las alertas */
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-info {
            background: #fdf9e3;
            color: #e47c49;
            border-left: 4px solid #EB9733;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border-left: 4px solid #c62828;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }

        /* estilo del grupo de formularios */
        .form-group {
            margin-bottom: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        /* Estilos para inputs de tipo password Y text */
        /* IMPORTANTE: Se aplica a ambos tipos para mantener consistencia */
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 12px 40px 12px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s ease;
            font-family: inherit;
        }

        input[type="password"]:focus,
        input[type="text"]:focus {
            outline: none;
            border-color: #5B0B0B;
            box-shadow: 0 0 0 3px rgba(91, 11, 11, 0.1);
        }

        /* Estado de error */
        input[type="password"].error,
        input[type="text"].error {
            border-color: #c62828;
        }

        /* BOTÓN PARA MOSTRAR/OCULTAR CONTRASEÑA */
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            font-size: 18px;
            padding: 4px;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #5B0B0B;
        }

        /*  MENSAJES DE ERROR */
        .error-message {
            color: #c62828;
            font-size: 13px;
            margin-top: 6px;
            display: none;
        }

        /* Mostrar mensaje de error cuando tiene la clase 'show' */
        .error-message.show {
            display: block;
        }

        /* Parte del estilo de los requisitos para la contraseña */
        .password-requirements {
            background: #f5f5f5;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .password-requirements h3 {
            font-size: 14px;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }

        /* Cada requisito individual */
        .requirement {
            font-size: 13px;
            color: #666;
            padding: 4px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .requirement::before {
            content: "○";
            color: #999;
            font-weight: bold;
        }

        .requirement.valid::before {
            content: "✓";
            color: #4caf50;
        }

        .requirement.invalid::before {
            content: "✗";
            color: #f44336;
        }

        /* estilo del button de envio */
        .btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #5B0B0B 0%, #882317 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px); /* Levanta el botón */
            box-shadow: 0 8px 20px rgba(91, 11, 11, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        /*RESPONSIVE - DISEÑO MÓVIL= */
        @media (max-width: 480px) {
            .container {
                border-radius: 0; /* Sin bordes redondeados en móvil */
            }

            .form-container {
                padding: 30px 20px; /* Menos padding en móvil */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-icon">🔐</div>
            <h1>Bienvenido</h1>
            <p>Por seguridad, debes cambiar tu contraseña en el primer ingreso</p>
        </div>

        <div class="form-container">
            @if(session('error'))
                <div class="alert alert-error">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Mensaje de éxito -->
            @if(session('success'))
                <div class="alert alert-success">
                    <i class="fa-solid fa-circle-check"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Mensaje informativo -->
            <div class="alert alert-info">
                <i class="fa-solid fa-circle-info"></i>
                <span>Tu contraseña debe cumplir con los requisitos de seguridad</span>
            </div>

            <!-- FORMULARIO DE CAMBIO DE CONTRASEÑA -->
            <form action="" method="POST" id="passwordForm">
                @csrf

                <!-- Campo: Contraseña Actual -->
                <div class="form-group">
                    <label for="current_password">Contraseña Actual</label>
                    <div class="input-wrapper">
                        <input type="password"
                               id="current_password"
                               name="current_password"
                               required
                               class="@error('current_password') error @enderror">
                        <button type="button"
                                class="toggle-password"
                                onclick="togglePassword('current_password', this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <!-- Mensaje de error de Laravel -->
                    @error('current_password')
                        <div class="error-message show">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Campo: Nueva Contraseña -->
                <div class="form-group">
                    <label for="new_password">Nueva Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password"
                               id="new_password"
                               name="new_password"
                               required
                               class="@error('new_password') error @enderror">
                        <button type="button"
                                class="toggle-password"
                                onclick="togglePassword('new_password', this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    @error('new_password')
                        <div class="error-message show">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Campo: Confirmar Nueva Contraseña -->
                <div class="form-group">
                    <label for="new_password_confirmation">Confirmar Nueva Contraseña</label>
                    <div class="input-wrapper">
                        <input type="password"
                               id="new_password_confirmation"
                               name="new_password_confirmation"
                               required>
                        <button type="button"
                                class="toggle-password"
                                onclick="togglePassword('new_password_confirmation', this)">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                    <!-- Mensaje de error personalizado para confirmación -->
                    <div class="error-message" id="confirmError">Las contraseñas no coinciden</div>
                </div>

                <div class="password-requirements">
                    <h3>Requisitos de la contraseña:</h3>
                    <div class="requirement" id="req-length">Mínimo 8 caracteres</div>
                    <div class="requirement" id="req-uppercase">Al menos una letra mayúscula</div>
                    <div class="requirement" id="req-lowercase">Al menos una letra minúscula</div>
                    <div class="requirement" id="req-number">Al menos un número</div>
                    <div class="requirement" id="req-special">Al menos un carácter especial (@$!%*?&#)</div>
                </div>

                <button type="submit" class="btn" id="submitBtn">
                    Cambiar Contraseña
                </button>
            </form>
        </div>
    </div>

    <script>
        /* FUNCIÓN: Mostrar/Ocultar Contraseña */
        function togglePassword(fieldId, button) {
            // Obtener el campo de input
            const field = document.getElementById(fieldId);

            // Obtener el icono dentro del botón
            const icon = button.querySelector('i');

            // Verificar el tipo actual y cambiarlo
            if (field.getAttribute('type') === 'password') {
                // Mostrar contraseña
                field.setAttribute('type', 'text');
                // Cambiar icono a "ojo tachado"
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                // Ocultar contraseña
                field.setAttribute('type', 'password');
                // Cambiar icono a "ojo normal"
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        /* Variables Globales */
        const newPasswordInput = document.getElementById('new_password');
        const confirmPasswordInput = document.getElementById('new_password_confirmation');
        const submitBtn = document.getElementById('submitBtn');
        const confirmError = document.getElementById('confirmError');

        /* VALIDACIÓN EN TIEMPO REAL */
        newPasswordInput.addEventListener('input', function() {
            const password = this.value;

            // Verificar cada requisito con expresiones regulares
            const hasLength = password.length >= 8; // Mínimo 8 caracteres
            const hasUppercase = /[A-Z]/.test(password); // Al menos una mayúscula
            const hasLowercase = /[a-z]/.test(password); // Al menos una minúscula
            const hasNumber = /[0-9]/.test(password); // Al menos un número
            const hasSpecial = /[@$!%*?&#]/.test(password); // Al menos un carácter especial

            // Actualizar los indicadores visuales de cada requisito
            updateRequirement('req-length', hasLength);
            updateRequirement('req-uppercase', hasUppercase);
            updateRequirement('req-lowercase', hasLowercase);
            updateRequirement('req-number', hasNumber);
            updateRequirement('req-special', hasSpecial);

            // Validar que las contraseñas coincidan
            validatePasswordMatch();
        });

        /* Valida coincidencia al escribir en el campo de confirmación */
        confirmPasswordInput.addEventListener('input', validatePasswordMatch);

        function updateRequirement(id, isValid) {
            const element = document.getElementById(id);

            // Remover clases anteriores
            element.classList.remove('valid', 'invalid');

            // Agregar la clase correspondiente
            if (isValid) {
                element.classList.add('valid'); // Check verde
            } else {
                element.classList.add('invalid'); // X roja
            }
        }

        /*  Verifica que ambas contraseñas sean iguales */
        function validatePasswordMatch() {
            const password = newPasswordInput.value;
            const confirm = confirmPasswordInput.value;

            // Solo validar si el usuario ha escrito algo en confirmación
            if (confirm.length > 0) {
                if (password !== confirm) {
                    // No coinciden - mostrar error
                    confirmError.classList.add('show');
                    confirmPasswordInput.classList.add('error');
                } else {
                    // Coinciden - ocultar error
                    confirmError.classList.remove('show');
                    confirmPasswordInput.classList.remove('error');
                }
            }
        }
    </script>
</body>
</html>
