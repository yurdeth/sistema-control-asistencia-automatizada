<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref, computed } from 'vue'
import axios from 'axios';

const isLoading = ref(true)
const isAuthenticated = ref(false)
const userRole = ref(null)
const userName = ref('')
const userId = ref(null)
const dashboardData = ref({})
const showDebug = ref(false)

const roleMetrics = {
    1: [ // root
        { key: 'total_usuarios', label: 'Total Usuarios', icon: '👥', endpoint: '/users/get/all', color: 'blue' },
        { key: 'total_departamentos', label: 'Departamentos', icon: '🏢', endpoint: '/departaments/get/all', color: 'green' },
        { key: 'total_aulas', label: 'Aulas', icon: '🚪', endpoint: '/classrooms/get/all', color: 'purple' },
        { key: 'total_materias', label: 'Materias', icon: '📚', endpoint: '/subjects/get/all', color: 'yellow' },
        { key: 'total_grupos', label: 'Grupos Activos', icon: '👨‍🎓', endpoint: '/groups/get/all', color: 'red' },
    ],

    2: [ //administrador_academico
        { key: 'total_docentes', label: 'Docentes', icon: '👨‍🏫', endpoint: '/users/get/professors/all', color: 'blue' },
        { key: 'total_estudiantes', label: 'Estudiantes', icon: '👨‍🎓', endpoint: '/users/get/students/all', color: 'green' },
        { key: 'total_grupos', label: 'Grupos', icon: '📋', endpoint: '/groups/get/all', color: 'purple' },
        { key: 'total_aulas', label: 'Aulas', icon: '🚪', endpoint: '/classrooms/get/all', color: 'yellow' },
        { key: 'coordinadores', label: 'Coordinadores', icon: '👤', endpoint: '/users/get/career-managers/all', color: 'red' },
        { key: 'jefes_depto', label: 'Jefes Depto.', icon: '🎯', endpoint: '/users/get/department-managers/all', color: 'orange' },
    ],

    3: [ //jefe_departamento
        { key: 'total_aulas', label: 'Total Aulas', icon: '🚪', endpoint: '/classrooms/get/all', color: 'blue' },
        { key: 'aulas_disponibles', label: 'Aulas Disponibles', icon: '✅', endpoint: '/classrooms/get/available/all', color: 'green' },
        { key: 'incidencias', label: 'Incidencias', icon: '📅', endpoint: '/classroom-reports/get/all', color: 'yellow' },
        { key: 'mantenimientis', label: 'Mantenimientos', icon: '🛠️', endpoint: '/maintenance/get/all', color: 'red' },
        { key: 'sesiones_clase', label: 'Sesiones de Clase', icon: '🎓', endpoint: '/class-sessions/get/all', color: 'orange' },
    ],

    4: [ //coordinador_carreras
        { key: 'estudiantes', label: 'Estudiantes', icon: '👨‍🎓', endpoint: '/users/get/students/all', color: 'blue' },
        { key: 'docentes', label: 'Docentes', icon: '👨‍🏫', endpoint: '/users/get/professors/all', color: 'green' },
        { key: 'grupos', label: 'Grupos', icon: '📋', endpoint: '/groups/get/all', color: 'purple' },
        { key: 'materias', label: 'Materias', icon: '📚', endpoint: '/subjects/get/all', color: 'yellow' },
        { key: 'solicitudes', label: 'Solicitudes', icon: '📝', endpoint: '/enrollment-requests/get/all', color: 'red' },
        { key: 'horarios', label: 'Horarios', icon: '📅', endpoint: '/schedules/get/all', color: 'orange' },
    ],

    5: [ //docente
        { key: 'sesiones_clase', label: 'Sesiones de Clase', icon: '🎓', endpoint: '/class-sessions/get/all', color: 'green' },
        { key: 'aulas_disponibles', label: 'Aulas Disponibles', icon: '🚪', endpoint: '/classrooms/get/available/all', color: 'purple' },
        { key: 'historial_aulas', label: 'Mi Historial de Aulas', icon: '📋', endpoint: '/classroom-history/get/all', color: 'yellow' },
        { key: 'solicitudes_inscripcion', label: 'Solicitudes Inscripción', icon: '📝', endpoint: '/enrollment-requests/get/all', color: 'red' },
        { key: 'horarios', label: 'Horarios', icon: '📅', endpoint: '/schedules/get/all', color: 'orange' },
    ],

    6: [ //estudiante
        { key: 'aulas_disponibles', label: 'Aulas Disponibles', icon: '🚪', endpoint: '/classrooms/get/available/all', color: 'purple' },
    ],
}

const colorClasses = {
    blue: 'bg-blue-500',
    green: 'bg-green-500',
    purple: 'bg-purple-500',
    yellow: 'bg-yellow-500',
    red: 'bg-red-500',
    orange: 'bg-orange-500',
}

const currentMetrics = computed(() => {
    if (!userRole.value || !roleMetrics[userRole.value]) {
        return []
    }
    return roleMetrics[userRole.value]
})

const loadUserData = async () => {
    try {
        const response = await axios.get('/api/users/get/profile/me', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        })
        const data = response.data.data || response.data

        userRole.value = parseInt(data.rol_id || data.role_id || 1)
        userId.value = data.id
        userName.value = data.nombre_completo || data.nombre || 'Usuario'

    } catch (error) {
        console.error(' Error cargando perfil:', error)
        userRole.value = 1
        userName.value = 'Usuario'
    }
}

const loadDashboardMetrics = async () => {
    const metrics = currentMetrics.value
    console.log(` Cargando ${metrics.length} métricas para rol ${userRole.value}`)

    for (const metric of metrics) {
        try {
            let endpoint = metric.endpoint

            if (metric.needsUserId && userId.value) {
                endpoint = endpoint.replace('{id}', userId.value)
            }
            const response = await axios.get(`/api${endpoint}`, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            })
            let count = 0

            if (Array.isArray(response.data)) {
                count = response.data.length
            } else if (response.data.data && Array.isArray(response.data.data)) {
                count = response.data.data.length
            } else if (typeof response.data === 'number') {
                count = response.data
            } else if (response.data.total !== undefined) {
                count = response.data.total
            }

            dashboardData.value[metric.key] = count
            console.log(`✔️ ${metric.label}: ${count}`)

        } catch (error) {
            console.error(` Error cargando ${metric.key}:`, error.response?.status, error.response?.data)
            dashboardData.value[metric.key] = 0
        }
    }
}

onMounted(async () => {
    try {
        console.log(' Inicializando dashboard...')
        isAuthenticated.value = true
        await loadUserData()

        if (currentMetrics.value.length > 0) {
            await loadDashboardMetrics()
        }

        console.log(' Dashboard cargado correctamente')
    } catch (error) {
        console.error(' Error en la inicialización:', error)
    } finally {
        isLoading.value = false
    }
})

const greeting = computed(() => {
    const hour = new Date().getHours()
    if (hour < 12) return 'Buenos días'
    if (hour < 18) return 'Buenas tardes'
    return 'Buenas noches'
})

const roleName = computed(() => {
    const roles = {
        1: 'Superusuario',
        2: 'Jefe de Académica',
        3: 'Jefe de Departamento',
        4: 'Coordinador de Carreras',
        5: 'Docente',
        6: 'Estudiante',
        7: 'Invitado'
    }
    return roles[userRole.value] || 'Usuario'
})
</script>

<template>
    <Head title="Dashboard" />

    <div v-if="isLoading" class="flex items-center justify-center min-h-screen bg-gray-50">
        <div class="text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-indigo-600 mx-auto"></div>
            <p class="mt-4 text-gray-600 text-lg font-medium">Cargando dashboard...</p>
        </div>
    </div>

    <AuthenticatedLayout v-else-if="isAuthenticated">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="text-2xl font-bold leading-tight text-gray-800">
                    Dashboard
                </h2>
                <span class="text-sm text-gray-600 bg-gray-100 px-4 py-2 rounded-full font-medium">
                    {{ roleName }}
                </span>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="mb-8 bg-gradient-to-r from-red-900 to-red-700 rounded-lg shadow-lg p-8 text-white text-center">
                    <h1 v-if="userRole !== 1" class="text-4xl md:text-5xl font-bold">{{ greeting }}, {{ userName }}</h1>
                    <h1 v-else class="text-4xl md:text-5xl font-bold">{{ greeting }}</h1>
                    <p class="mt-3 text-lg text-indigo-100">Bienvenido al Sistema de Control de Asistencia Automatizada</p>
                    <p v-if="userRole !== 1" class="mt-2 text-base text-indigo-200">{{ roleName }}</p>
                </div>

                <div v-if="showDebug" class="mb-4 bg-yellow-50 border-2 border-yellow-300 rounded-lg p-4">
                    <details>
                        <summary class="cursor-pointer font-bold text-yellow-800 mb-2">
                            🐛 Información de Depuración (Click para expandir)
                        </summary>
                        <div class="mt-2 space-y-2 text-sm">
                            <p><strong>Rol detectado:</strong> {{ userRole }} ({{ roleName }})</p>
                            <p><strong>User ID:</strong> {{ userId }}</p>
                            <p><strong>Nombre:</strong> {{ userName }}</p>
                            <p><strong>Métricas disponibles:</strong> {{ currentMetrics.length }}</p>
                            <div class="mt-2">
                                <strong>Métricas configuradas para este rol:</strong>
                                <ul class="list-disc list-inside mt-1 text-xs">
                                    <li v-for="metric in currentMetrics" :key="metric.key">
                                        {{ metric.label }} ({{ metric.key }}) = {{ dashboardData[metric.key] }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </details>
                </div>

                <div v-if="currentMetrics.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="metric in currentMetrics"
                        :key="metric.key"
                        class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1"
                    >
                        <div class="p-6">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">
                                        {{ metric.label }}
                                    </p>
                                    <p class="mt-2 text-4xl font-bold text-gray-900">
                                        {{ dashboardData[metric.key] !== undefined ? dashboardData[metric.key] : '...' }}
                                    </p>
                                </div>
                                <div :class="[colorClasses[metric.color], 'p-4 rounded-full shadow-lg']">
                                    <span class="text-3xl">{{ metric.icon }}</span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                    <div
                                        :class="[colorClasses[metric.color], 'h-full transition-all duration-700 ease-out']"
                                        :style="{ width: dashboardData[metric.key] > 0 ? '75%' : '0%' }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else class="bg-white rounded-xl shadow-lg p-12 text-center">
                    <div class="text-gray-400 text-6xl mb-4">📊</div>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">No hay métricas disponibles</h3>
                    <p class="text-gray-500">Tu rol aún no tiene métricas configuradas</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.grid > div {
    animation: fadeIn 0.6s ease-out backwards;
}

.grid > div:nth-child(1) { animation-delay: 0.05s; }
.grid > div:nth-child(2) { animation-delay: 0.1s; }
.grid > div:nth-child(3) { animation-delay: 0.15s; }
.grid > div:nth-child(4) { animation-delay: 0.2s; }
.grid > div:nth-child(5) { animation-delay: 0.25s; }
.grid > div:nth-child(6) { animation-delay: 0.3s; }
</style>
