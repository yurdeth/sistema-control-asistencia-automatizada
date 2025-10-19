<script setup>
//Imports
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue'
import { authService } from '@/Services/authService'
import axios from 'axios'

//Estados reactivos
const isLoading = ref(true)
const isAuthenticated = ref(false)

//Verificación de autenticación
onMounted(async () => {
    //Obteniendo el token guardado en localStorage
    const token = authService.getToken()

    // Si no hay token, redirigir inmediatamente al login
    if (!token) {
        setTimeout(() => {
            window.location.href = '/login'
        }, 2000)
        return
    }

    // Si hay token, verificar que sea válido con el backend
    try {
        await authService.verifyToken(token);

        // Obtener el usuario guardado en localStorage
        const user = authService.getUser()
        console.log('Usuario de localStorage:', user)

        if (!user || !user.id) {
            throw new Error('No hay datos de usuario')
        }

        // Verificando token con el backend usando el ID del usuario
        const url = `/api/users/get/${user.id}`
        console.log('   Haciendo petición GET...')

        const response = await axios.get(url, {
            headers: {
                'Authorization': `Bearer ${token}`
            }
        })
        console.log('   Status:', response.status)

        isAuthenticated.value = true
        console.log('Usuario autenticado correctamente')

    } catch (error) {
        console.error('Error al verificar token:')
        console.error('Status:', error.response?.status)
        console.error('Data:', error.response?.data)
        console.error('Error completo:', error)

        authService.logout()

        setTimeout(() => {
            window.location.href = '/login'
        }, 2000)
        return

    } finally {
        isLoading.value = false
    }
});
</script>

<template>
    <Head title="Dashboard" />

    <!-- Loader mientras verifica -->
    <div v-if="isLoading" class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-gray-900 mx-auto"></div>
            <p class="mt-4 text-gray-600 text-lg">Verificando sesión...</p>
        </div>
    </div>

    <!-- Contenido del dashboard -->
    <AuthenticatedLayout v-else-if="isAuthenticated">
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-xl sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        <div class="text-center">
                            <h1 class="title">BIENVENIDO AL SICA</h1>
                            <hr>
                            <h3 class="subtitle">Sistema de Control de Asistencia Automatizada</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
    .title{
        font-size: 40px;
        font-weight: 500;
        color: #212020c4;
        margin-top: 10px;
    }

    .subtitle{
        font-size: 20px;
        font-weight: 300;
        color: #212020c4;
        margin-top: 10px;
    }
</style>
