<template>
    <Head title="Consultar Disponibilidad"/>

    <!-- Loader mientras verifica -->
    <div v-if="!isAuthenticated">
        <div v-if="isLoading" class="flex items-center justify-center min-h-screen bg-gray-100">
            <div class="text-center">
                <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-gray-900 mx-auto"></div>
                <p class="mt-4 text-gray-600 text-lg">Verificando sesión...</p>
            </div>
        </div>
    </div>

    <MainLayoutDashboard>
        <div class="p-3 sm:p-4 md:p-6">
            <!-- Header de la vista-->
            <div class="mb-4 sm:mb-6">
                <h1 :style="{color:colorText}" class="text-xl sm:text-2xl font-bold text-gray-900 mb-1">
                    Consultar Disponibilidad de Aulas
                </h1>
                <p class="text-gray-600 text-xs sm:text-sm">
                    Busque aulas disponibles según el día y horario que necesita
                </p>
            </div>

            <!-- Formulario de búsqueda -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6 mb-4 sm:mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Criterios de Búsqueda</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Día de la semana -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Día de la Semana
                        </label>
                        <select
                            v-model="filtros.dia_semana"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Seleccione un día</option>
                            <option value="Lunes">Lunes</option>
                            <option value="Martes">Martes</option>
                            <option value="Miercoles">Miércoles</option>
                            <option value="Jueves">Jueves</option>
                            <option value="Viernes">Viernes</option>
                            <option value="Sabado">Sábado</option>
                            <option value="Domingo">Domingo</option>
                        </select>
                    </div>

                    <!-- Hora inicio -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Hora de Inicio
                        </label>
                        <input
                            v-model="filtros.hora_inicio"
                            type="time"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>

                    <!-- Hora fin -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Hora de Fin
                        </label>
                        <input
                            v-model="filtros.hora_fin"
                            type="time"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                    </div>
                </div>

                <!-- Botón de búsqueda -->
                <div class="mt-4 flex gap-3">
                    <button
                        :style="{background: colorButton}"
                        :disabled="!formularioValido || cargando"
                        class="px-6 py-2 text-white rounded-lg transition-colors text-sm font-medium hover:opacity-90 disabled:opacity-50 disabled:cursor-not-allowed"
                        @click="buscarDisponibilidad"
                    >
                        <i class="fa-solid fa-search mr-2"></i>
                        {{ cargando ? 'Buscando...' : 'Buscar Aulas Disponibles' }}
                    </button>

                    <button
                        class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors text-sm font-medium"
                        @click="limpiarFiltros"
                    >
                        <i class="fa-solid fa-eraser mr-2"></i>
                        Limpiar
                    </button>
                </div>
            </div>

            <!-- Resultados -->
            <div v-if="busquedaRealizada" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <!-- Header de resultados -->
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Resultados de Búsqueda
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ aulasDisponibles.length }} aula(s) disponible(s) para 
                        <span class="font-medium">{{ filtros.dia_semana }}</span> 
                        de <span class="font-medium">{{ filtros.hora_inicio }}</span> 
                        a <span class="font-medium">{{ filtros.hora_fin }}</span>
                    </p>
                </div>

                <!-- Loading -->
                <div v-if="cargando" class="text-center py-12">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>

                <!-- Tabla de resultados -->
                <div v-else-if="aulasDisponibles.length > 0" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aula
                                </th>
                                <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ubicación
                                </th>
                                <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Capacidad
                                </th>
                                <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden lg:table-cell">
                                    Recursos
                                </th>
                                <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="aula in aulasDisponibles" :key="aula.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-lg flex items-center justify-center">
                                            <i class="fa-solid fa-hotel text-green-600"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ aula.nombre }}</div>
                                            <div class="text-xs text-gray-500">{{ aula.codigo }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ aula.ubicacion }}</div>
                                </td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1">
                                        <i class="fa-solid fa-people-group text-sm text-gray-600"></i>
                                        <span class="text-sm text-gray-900">{{ aula.capacidad_pupitres }}</span>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4 hidden lg:table-cell">
                                    <div v-if="aula.recursos && aula.recursos.length > 0" class="text-xs text-gray-600">
                                        <div v-for="recurso in aula.recursos" :key="recurso.nombre" class="mb-1">
                                            • {{ recurso.nombre }} ({{ recurso.cantidad }})
                                        </div>
                                    </div>
                                    <span v-else class="text-xs text-gray-400 italic">Sin recursos</span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <span 
                                        class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800': aula.estado === 'disponible',
                                            'bg-yellow-100 text-yellow-800': aula.estado === 'mantenimiento',
                                            'bg-red-100 text-red-800': aula.estado === 'ocupada'
                                        }"
                                    >
                                        {{ aula.estado }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Estado vacío -->
                <div v-else class="px-6 py-12 text-center">
                    <i :style="{color: colorButton}" class="fa-solid fa-calendar-xmark text-4xl mb-4"></i>
                    <p class="text-sm text-gray-500">No se encontraron aulas disponibles en el horario seleccionado</p>
                    <p class="text-xs text-gray-400 mt-2">Intente buscar en otro horario o día</p>
                </div>
            </div>

            <!-- Mensaje inicial -->
            <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                <i class="fa-solid fa-search text-4xl text-gray-300 mb-4"></i>
                <p class="text-gray-500">Seleccione un día y horario para buscar aulas disponibles</p>
            </div>
        </div>
    </MainLayoutDashboard>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';

const isLoading = ref(false);
const isAuthenticated = localStorage.getItem('isAuthenticated');

// Colores
const colorText = ref('#2C2D2F');
const colorButton = ref('#FE6244');

// Estados
const cargando = ref(false);
const busquedaRealizada = ref(false);
const aulasDisponibles = ref([]);

// Filtros
const filtros = ref({
    dia_semana: '',
    hora_inicio: '',
    hora_fin: ''
});

// Validación del formulario
const formularioValido = computed(() => {
    return filtros.value.dia_semana && 
           filtros.value.hora_inicio && 
           filtros.value.hora_fin;
});

// Buscar disponibilidad
const buscarDisponibilidad = async () => {
    if (!formularioValido.value) {
        alert('Por favor complete todos los campos');
        return;
    }

    cargando.value = true;
    busquedaRealizada.value = true;

    try {
        const response = await axios.post('/api/disponibilidad/consultar', {
            dia_semana: filtros.value.dia_semana,
            hora_inicio: filtros.value.hora_inicio,
            hora_fin: filtros.value.hora_fin
        }, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
            }
        });

        const data = response.data;

        if (data.success) {
            aulasDisponibles.value = data.data;
        } else {
            alert(data.message || 'Error al buscar disponibilidad');
            aulasDisponibles.value = [];
        }
    } catch (error) {
        console.error('Error al buscar disponibilidad:', error);
        alert(error.response?.data?.message || 'Error al buscar disponibilidad');
        aulasDisponibles.value = [];
    } finally {
        cargando.value = false;
    }
};

// Limpiar filtros
const limpiarFiltros = () => {
    filtros.value = {
        dia_semana: '',
        hora_inicio: '',
        hora_fin: ''
    };
    busquedaRealizada.value = false;
    aulasDisponibles.value = [];
};
</script>