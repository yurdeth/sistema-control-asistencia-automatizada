<template>
    <div v-if="isLoading" class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="text-center">
            <div class="loader"></div>
            <p class="mt-6 text-gray-600 text-lg">{{ message }}</p>
        </div>

    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { authService } from '@/Services/authService';
import axios from 'axios';

const props = defineProps({
    message: {
        type: String,
        default: 'Verificando sesión...'
    },
    redirectDelay: {
        type: Number,
        default: 2000
    },
    loginUrl: {
        type: String,
        default: '/login'
    }
});

const emit = defineEmits(['authenticated', 'loading']);

const isLoading = ref(true);

// Verificación inicial rápida - antes de montar
const token = authService.getToken();
const user = authService.getUser();

// Si hay token y usuario, comenzar sin mostrar loader
if (token && user && user.id) {
    isLoading.value = false;
}

onMounted(async () => {
    emit('loading', isLoading.value);

    // Obteniendo el token guardado en localStorage
    const token = authService.getToken();

    // Si no hay token, redirigir al login
    if (!token) {
        console.log('No hay token, redirigiendo al login...');
        setTimeout(() => {
            window.location.href = props.loginUrl;
        }, props.redirectDelay);
        return;
    }

    // Si hay token, verificar que sea válido con el backend
    try {
        await authService.verifyToken(token);

        // Obtener el usuario guardado en localStorage
        const user = authService.getUser();
        console.log('Usuario de localStorage:', user);

        if (!user || !user.id) {
            throw new Error('No hay datos de usuario');
        }

        // Para usuarios invitados, omitimos la segunda verificación ya que
        // authService.verifyToken() ya validó el token y los invitados no tienen
        // permiso para acceder al endpoint /api/users/get/{id}
        if (user.role_id === 7) {
            console.log('Usuario invitado detectado, omitiendo segunda verificación');

            // Autenticación exitosa para invitado
            isLoading.value = false;
            emit('loading', false);
            emit('authenticated', true);
            console.log('Usuario invitado autenticado correctamente');
        } else {
            // Para usuarios regulares, hacemos la verificación adicional
            const url = `/api/users/get/${user.id}`;
            console.log('Haciendo petición GET...');

            const response = await axios.get(url, {
                headers: {
                    'Authorization': `Bearer ${token}`
                }
            });
            console.log('Status:', response.status);

            // Autenticación exitosa
            isLoading.value = false;
            emit('loading', false);
            emit('authenticated', true);
            console.log('Usuario autenticado correctamente');
        }

    } catch (error) {
        console.error('Error al verificar token:');
        console.error('Status:', error.response?.status);
        console.error('Data:', error.response?.data);
        console.error('Error completo:', error);

        authService.logout();

        setTimeout(() => {
            window.location.href = props.loginUrl;
        }, props.redirectDelay);
        return;
    }
});
</script>

<style scoped>
.loader {
    box-sizing: border-box;
    display: inline-block;
    width: 50px;
    height: 80px;
    border-top: 5px solid #fff;
    border-bottom: 5px solid #fff;
    position: relative;
    background: linear-gradient(#660D04 30px, transparent 0) no-repeat;
    background-size: 2px 40px;
    background-position: 50% 0px;
    animation: spinx 5s linear infinite;
}
.loader:before, .loader:after {
    content: "";
    width: 40px;
    left: 50%;
    height: 35px;
    position: absolute;
    top: 0;
    transform: translatex(-50%);
    background: rgba(255, 255, 255, 0.4);
    border-radius: 0 0 20px 20px;
    background-size: 100% auto;
    background-repeat: no-repeat;
    background-position: 0 0px;
    animation: lqt 5s linear infinite;
}
.loader:after {
    top: auto;
    bottom: 0;
    border-radius: 20px 20px 0 0;
    animation: lqb 5s linear infinite;
}
@keyframes lqt {
    0%, 100% {
        background-image: linear-gradient(#660D04 40px, transparent 0);
        background-position: 0% 0px;
    }
    50% {
        background-image: linear-gradient(#660D04 40px, transparent 0);
        background-position: 0% 40px;
    }
    50.1% {
        background-image: linear-gradient(#660D04 40px, transparent 0);
        background-position: 0% -40px;
    }
}
@keyframes lqb {
    0% {
        background-image: linear-gradient(#660D04 40px, transparent 0);
        background-position: 0 40px;
    }
    100% {
        background-image: linear-gradient(#660D04 40px, transparent 0);
        background-position: 0 -40px;
    }
}
@keyframes spinx {
    0%, 49% {
        transform: rotate(0deg);
        background-position: 50% 36px;
    }
    51%, 98% {
        transform: rotate(180deg);
        background-position: 50% 4px;
    }
    100% {
        transform: rotate(360deg);
        background-position: 50% 36px;
    }
}
</style>
