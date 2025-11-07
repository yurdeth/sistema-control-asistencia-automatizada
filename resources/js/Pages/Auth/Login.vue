<script setup>
    import InputError from '@/Components/InputError.vue';
    import InputLabel from '@/Components/InputLabel.vue';
    import TextInput from '@/Components/TextInput.vue';
    import { Head, Link, useForm, router} from '@inertiajs/vue3';

    //Imports para el funcionamiento del login
    import { ref, onMounted } from 'vue'
    import { authService } from '@/Services/authService';

    defineProps({
        canResetPassword: {
            type: Boolean,
        },
        status: {
            type: String,
        },
    });

    // =================| Estados |=================
    // Formulario como objeto reactivo simple
    const form = ref({
        email: '',
        password: '',
    })

    // Estados de procesamiento y errores
    const processing = ref(false)
    const errors = ref({})
    const errorMessage = ref(null)
    const showPassword = ref(false)
    const checking = ref(true) // Estado para el loader inicial

    //recupera el token del localStorage y lo configura en authService si existe
    onMounted(async () => {
        const token = localStorage.getItem('token')
        const user = localStorage.getItem('user')

        // Si ya está autenticado, redirigir al dashboard INMEDIATAMENTE
        if (token && user) {
            try {
                // Verificar que el token sea válido
                await authService.verifyToken(token)
                // Si el token es válido, redirigir al dashboard
                router.visit('/dashboard')
                return // No mostrar el formulario
            } catch (error) {
                localStorage.removeItem('token')
                localStorage.removeItem('user')
            }
        }

        if (token) {
            authService.setAxiosToken(token)
        }

        // Después de verificar, mostrar el formulario
        checking.value = false
    });

    // Función para alternar visibilidad de contraseña
    const togglePasswordVisibility = () => {
        showPassword.value = !showPassword.value
    }

    // Función de inicio de sesión usando authService
    const submit = async () => {
        processing.value = true
        errorMessage.value = null
        errors.value = {}

        try {
            const user = await authService.login(form.value.email, form.value.password)

            // verificando que el token se guardó
            const tokenGuardado = authService.getToken()

            if (tokenGuardado) {
                //Almacenamos el rol del usuario
                const roleId = user.role_id

                //Verificando el rol del usuario para la redirección
                if (roleId === 1) {
                    router.visit('/dashboard');
                } else if (roleId === 2){
                    router.visit('#');
                }
            } else {
                console.error('ERROR: El token NO se guardó')
                errorMessage.value = 'Error al guardar la sesión'
            }

        } catch (error) {
            console.log('Error en login:', error)

            if (error.response?.status === 401) {
                errorMessage.value = 'Credenciales incorrectas'
            } else if (error.response?.status === 422) {
                errors.value = error.response.data.errors || {}
                errorMessage.value = 'Por favor verifica los datos ingresados'
            } else {
                errorMessage.value = 'Error inesperado, inténtalo más tarde'
            }
        } finally {
            processing.value = false
        }
    }
</script>

<template>

        <Head title="Log in" />

        <!-- Loader mientras verifica autenticación -->
        <div v-if="checking" class="flex items-center justify-center min-h-screen bg-gray-100">
            <div class="text-center">
                <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-gray-900 mx-auto" style="border-bottom-color: #5B0B0B;"></div>
                <p class="mt-4 text-gray-600 text-lg">Verificando sesión...</p>
            </div>
        </div>

        <!--Contenedor principal - solo se muestra después de verificar-->
        <div v-else class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
            <div class="log-container">
                <div class="left_panel">
                    <h1 class="title">SICA</h1>
                </div>

                <div class="right_panel">
                    <h2 class="mensaje">Iniciar sesión</h2>
                    <p class="submensaje">Ingresa tus credenciales para continuar</p>
                    <br>

                    <!-- Mensaje de error global -->
                    <div v-if="errorMessage" class="alert-error">
                        <i class="fa-solid fa-circle-xmark"></i>
                        <span>{{ errorMessage }}</span>
                    </div>

                    <form @submit.prevent="submit">
                        <div class="input-group">
                            <InputLabel for="email" value="Email" />
                            <div class="input-form">
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full input-field"
                                    :class="{ 'input-error': errors.email }"
                                    v-model="form.email"
                                    required
                                    autofocus
                                    autocomplete="username"
                                    placeholder="example@gmail.com"
                                />

                                <InputError class="mt-2" :message="errors.email?.[0]" />
                            </div>
                        </div>

                        <div class="input-group mt-4">
                            <InputLabel for="password" value="Password" />
                            <div class="input-form password-form">
                                <TextInput
                                    id="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    class="mt-1 block w-full input-field pr-10"
                                    :class="{ 'input-error': errors.password }"
                                    v-model="form.password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                />

                                <button
                                    type="button"
                                    @click="togglePasswordVisibility"
                                    class="password-toggle"
                                    tabindex="-1"
                                >
                                    <!-- Icono de ojo abierto -->
                                    <i v-if="!showPassword" class="fa-solid fa-eye"></i>
                                    <!-- Icono de no ver -->
                                    <i v-else class="fa-solid fa-eye-slash"></i>
                                </button>
                                <InputError class="mt-2" :message="errors.password?.[0]" />
                            </div>


                            </div>

                                <!-- Button de inicio -->
                                <div class="btn_inicio">
                                    <button
                                        type="submit"
                                        class="btn-ini"
                                        :disabled="processing"
                                    >
                                        {{ processing ? 'Ingresando...' : 'Ingresar' }}
                                    </button>
                                </div>

                                <!-- Link de recuperación de contraseña -->
                                <div class="mt-3 flex items-center justify-end">
                                    <Link
                                        v-if="canResetPassword"
                                        :href="route('password.request')"
                                        class="forgot-password-link"
                                    >
                                        ¿Olvidaste tu contraseña?
                                    </Link>
                                </div>
                    </form>
                </div>
            </div>
            <Link
            href="/"
            class="btn-home-float">
                <i class="fa-solid fa-house"></i>
            </Link>
        </div>

</template>

<!--Estilos del login-->
<style scoped>
    .log-container{
        width: 600px;
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: row;
        box-shadow: 0 10px 40px rgba(91, 11, 11, 0.15);
        border: 1px solid rgba(91, 11, 11, 0.1);
        background: white;
    }

    .left_panel {
        flex: 1.2;
        background: linear-gradient(135deg, #5B0B0B 0%, #7a1010 100%);
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        padding: 40px 20px;
        position: relative;
    }

    .right_panel {
        flex: 1.4;
        background: #F4F4F4;
        padding: 50px 45px;
        display: flex;
        flex-direction: column;
    }

    .title {
        color: white;
        font-size: 3.5rem;
        font-weight: 700;
        text-align: center;
        letter-spacing: 2px;
        margin-bottom: 8px;
        position: relative;
        z-index: 1;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    }

    .logo-container {
        position: relative;
        z-index: 1;
        margin-bottom: 20px;
    }

    .mensaje{
        font-size: 19px;
        font-weight: 500;
        text-align: center;
    }

    .submensaje {
        font-size: 0.9rem;
        color: #718096;
        text-align: center;
        font-weight: 400;
    }

    /* Parte donde se trabajan los input */
    .input-group {
        margin-bottom: 20px;
    }

    .input-form {
        position: relative;
    }

    .input-field {
        border-radius: 8px;
        border: 2px solid #e2e8f0;
        padding: 12px 16px;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: white;
    }

    .input-field:focus {
        border-color: #5B0B0B;
        box-shadow: 0 0 0 3px rgba(91, 11, 11, 0.1);
        outline: none;
    }

    .input-error {
        border-color: #e53e3e !important;
    }

    .input-error:focus {
        box-shadow: 0 0 0 3px rgba(229, 62, 62, 0.1) !important;
    }

    .password-form {
        position: relative;
    }

    .forgot-password-link {
        font-size: 0.875rem;
        color: #5B0B0B;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s ease;
    }

    .forgot-password-link:hover {
        color: #7a1010;
        text-decoration: underline;
    }

    /* Parte donde se trabajan los buttons */
        .password-toggle {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #718096;
        transition: color 0.2s ease;
    }

    .password-toggle:hover {
        color: #5B0B0B;
    }

    .btn_inicio{
        background: #5B0B0B;
        padding: 6px;
        text-align: center;
        border-radius: 6px;
        margin-top: 14px;
    }

    .btn-ini{
        color: white;
    }

    /* Parte donde se trabajan las alertas en caso de error */
    .alert-error {
        background: #fff5f5;
        border: 1px solid #fc8181;
        border-radius: 8px;
        padding: 12px 16px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #c53030;
        font-size: 0.9rem;
        animation: slideDown 0.3s ease;
    }

    .btn_inicio{
        background: linear-gradient(135deg, #5B0B0B 0%, #7a1010 100%);
        padding: 0;
        text-align: center;
        border-radius: 8px;
        margin-top: 24px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(91, 11, 11, 0.3);
        transition: all 0.3s ease;
    }

    .btn_inicio:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(91, 11, 11, 0.4);
    }

    .btn_inicio:active {
        transform: translateY(0);
    }

    .btn-ini{
        color: white;
        width: 100%;
        padding: 14px 24px;
        font-size: 1rem;
        font-weight: 600;
        border: none;
        background: transparent;
        cursor: pointer;
        transition: all 0.3s ease;
        letter-spacing: 0.5px;
    }

    .btn-ini:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .log-container {
            flex-direction: column;
            width: 90%;
            max-width: 400px;
        }
        .left_panel {
            padding: 30px 20px;
        }
        .title {
            font-size: 2.5rem;
        }
        .right_panel {
            padding: 40px 30px;
        }
    }

    .btn-home-float {
        position: fixed;
        top: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
        z-index: 1000;
        text-decoration: none;
        color: #333;
    }

    .btn-home-float:hover {
        background: #fff;
        transform: scale(1.1);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
    }

    .btn-home-float svg {
        width: 24px;
        height: 24px;
    }
</style>
