<template>
    <Head title="Mi Historial de Aulas"/>

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
                    Mi Historial de Uso de Aulas
                </h1>
                <p class="text-gray-600 text-xs sm:text-sm">
                    Visualice su historial completo de aulas utilizadas, fechas y actividades impartidas
                </p>
            </div>

            <!-- Estadísticas -->
            <div v-if="estadisticas" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-sm text-gray-600 mb-1">Total Sesiones</div>
                    <div class="text-2xl font-bold text-gray-900">{{ estadisticas.total_sesiones }}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-sm text-gray-600 mb-1">Sesiones Finalizadas</div>
                    <div class="text-2xl font-bold text-green-600">{{ estadisticas.sesiones_finalizadas }}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-sm text-gray-600 mb-1">Aulas Utilizadas</div>
                    <div class="text-2xl font-bold text-blue-600">{{ estadisticas.aulas_utilizadas }}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-sm text-gray-600 mb-1">Horas Impartidas</div>
                    <div class="text-2xl font-bold text-purple-600">
                        {{ Math.round((estadisticas.duracion_total_minutos || 0) / 60) }}h
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                <div class="flex flex-col md:flex-row gap-3">
                    <!-- Búsqueda por texto -->
                    <div class="flex-1">
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input
                                v-model="busqueda"
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Buscar por aula, materia o actividad..."
                                type="text"
                            >
                        </div>
                    </div>

                    <!-- Filtro por estado -->
                    <select
                        v-model="filtroEstado"
                        class="px-8 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">Todos los estados</option>
                        <option value="finalizada">Finalizadas</option>
                        <option value="cancelada">Canceladas</option>
                        <option value="en_curso">En curso</option>
                        <option value="programada">Programadas</option>
                    </select>

                    <!-- Filtro por rango de fechas -->
                    <div class="flex gap-2">
                        <input
                            v-model="fechaInicio"
                            type="date"
                            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            placeholder="Desde"
                        >
                        <input
                            v-model="fechaFin"
                            type="date"
                            class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                            placeholder="Hasta"
                        >
                    </div>

                    <button
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors text-sm"
                        @click="limpiarFiltros"
                    >
                        <i class="fa-solid fa-eraser"></i>
                    </button>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="cargando" class="text-center py-12 bg-white rounded-lg border border-gray-200">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <!-- Tabla de historial -->
            <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-4 sm:px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">Historial de Sesiones</h2>
                    <p class="text-sm text-gray-600 mt-1">{{ historialFiltrado.length }} sesión(es) encontrada(s)</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Fecha
                                </th>
                                <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aula
                                </th>
                                <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Materia
                                </th>
                                <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Horario
                                </th>
                                <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Duración
                                </th>
                                <th scope="col" class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th scope="col" class="px-4 sm:px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="sesion in historialPaginado" :key="sesion.sesion_id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ formatearFecha(sesion.fecha_clase) }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ sesion.dia_semana }}</div>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <i class="fa-solid fa-hotel text-blue-600"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ sesion.aula_nombre }}</div>
                                            <div class="text-xs text-gray-500">{{ sesion.aula_codigo }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="text-sm text-gray-900">{{ sesion.materia_nombre }}</div>
                                    <div class="text-xs text-gray-500">Grupo {{ sesion.numero_grupo }}</div>
                                </td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ sesion.hora_programada_inicio }} - {{ sesion.hora_programada_fin }}
                                    </div>
                                    <div v-if="sesion.retraso_minutos > 0" class="text-xs text-red-500">
                                        Retraso: {{ sesion.retraso_minutos }} min
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        {{ sesion.duracion_minutos ? sesion.duracion_minutos + ' min' : '-' }}
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                    <span 
                                        class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full"
                                        :class="{
                                            'bg-green-100 text-green-800': sesion.estado === 'finalizada',
                                            'bg-red-100 text-red-800': sesion.estado === 'cancelada',
                                            'bg-blue-100 text-blue-800': sesion.estado === 'en_curso',
                                            'bg-gray-100 text-gray-800': sesion.estado === 'programada'
                                        }"
                                    >
                                        {{ sesion.estado }}
                                    </span>
                                </td>
                                <td class="px-4 sm:px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button
                                        :style="{background: colorButton}"
                                        class="text-white px-3 py-1 rounded-lg hover:opacity-90 transition-opacity text-xs"
                                        @click="verDetalle(sesion)"
                                    >
                                        Ver detalle
                                    </button>
                                </td>
                            </tr>

                            <!-- Estado vacío -->
                            <tr v-if="historialFiltrado.length === 0">
                                <td class="px-6 py-12 text-center" colspan="7">
                                    <i :style="{color: colorButton}" class="fa-solid fa-calendar-xmark text-4xl mb-4"></i>
                                    <p class="text-sm text-gray-500">No se encontraron sesiones en su historial</p>
                                    <p class="text-xs text-gray-400 mt-2">Intente ajustar los filtros de búsqueda</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div v-if="historialFiltrado.length > 0"
                    class="bg-white px-4 sm:px-6 py-3 flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 gap-3">
                    <div class="hidden sm:block">
                        <p class="text-sm text-gray-700">
                            Mostrando
                            <span class="font-medium">{{ indiceInicio + 1 }}</span>
                            a
                            <span class="font-medium">{{ Math.min(indiceFin, historialFiltrado.length) }}</span>
                            de
                            <span class="font-medium">{{ historialFiltrado.length }}</span>
                            resultados
                        </p>
                    </div>

                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <button
                            :disabled="paginaActual === 1"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            @click="paginaAnterior"
                        >
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>

                        <button
                            v-for="pagina in paginasVisibles"
                            :key="pagina"
                            :class="[
                                pagina === paginaActual
                                    ? 'z-10 border-blue-500 text-blue-600 bg-blue-50'
                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                            ]"
                            @click="irAPagina(pagina)"
                        >
                            {{ pagina }}
                        </button>

                        <button
                            :disabled="paginaActual === totalPaginas"
                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                            @click="paginaSiguiente"
                        >
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    </MainLayoutDashboard>

    <!-- Modal de detalle -->
    <Modal :show="mostrarDetalle" @close="cerrarDetalle">
        <div v-if="sesionSeleccionada" class="p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Detalle de Sesión</h2>
            
            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Fecha</p>
                        <p class="font-semibold">{{ formatearFecha(sesionSeleccionada.fecha_clase) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Día</p>
                        <p class="font-semibold">{{ sesionSeleccionada.dia_semana }}</p>
                    </div>
                </div>

                <div class="border-t pt-4">
                    <p class="text-sm text-gray-600 mb-2">Aula</p>
                    <p class="font-semibold">{{ sesionSeleccionada.aula_nombre }}</p>
                    <p class="text-sm text-gray-500">{{ sesionSeleccionada.aula_ubicacion }}</p>
                </div>

                <div class="border-t pt-4">
                    <p class="text-sm text-gray-600 mb-2">Materia</p>
                    <p class="font-semibold">{{ sesionSeleccionada.materia_nombre }}</p>
                    <p class="text-sm text-gray-500">Código: {{ sesionSeleccionada.materia_codigo }} - Grupo {{ sesionSeleccionada.numero_grupo }}</p>
                    <p class="text-sm text-gray-500">{{ sesionSeleccionada.ciclo_nombre }}</p>
                </div>

                <div class="border-t pt-4 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Horario Programado</p>
                        <p class="font-semibold">{{ sesionSeleccionada.hora_programada_inicio }} - {{ sesionSeleccionada.hora_programada_fin }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Duración</p>
                        <p class="font-semibold">{{ sesionSeleccionada.duracion_minutos || '-' }} minutos</p>
                    </div>
                </div>

                <div v-if="sesionSeleccionada.hora_inicio_real" class="border-t pt-4 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Hora Inicio Real</p>
                        <p class="font-semibold">{{ formatearHora(sesionSeleccionada.hora_inicio_real) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Hora Fin Real</p>
                        <p class="font-semibold">{{ formatearHora(sesionSeleccionada.hora_fin_real) }}</p>
                    </div>
                </div>

                <div v-if="sesionSeleccionada.retraso_minutos > 0" class="bg-red-50 border border-red-200 rounded-lg p-3">
                    <p class="text-sm text-red-800">
                        <i class="fa-solid fa-clock mr-2"></i>
                        Retraso de {{ sesionSeleccionada.retraso_minutos }} minutos
                    </p>
                </div>

                <div class="border-t pt-4 flex justify-end">
                    <button
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors"
                        @click="cerrarDetalle"
                    >
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
import Modal from '@/Components/Modal.vue';

const isLoading = ref(true);
const isAuthenticated = localStorage.getItem('isAuthenticated');

// Colores
const colorText = ref('#2C2D2F');
const colorButton = ref('#FE6244');

// Estados
const cargando = ref(false);
const historial = ref([]);
const estadisticas = ref(null);
const mostrarDetalle = ref(false);
const sesionSeleccionada = ref(null);

// Filtros
const busqueda = ref('');
const filtroEstado = ref('');
const fechaInicio = ref('');
const fechaFin = ref('');

// Paginación
const paginaActual = ref(1);
const itemsPorPagina = ref(10);

// Historial filtrado
const historialFiltrado = computed(() => {
    let resultado = historial.value;

    // Filtrar por búsqueda de texto
    if (busqueda.value) {
        const busquedaLower = busqueda.value.toLowerCase();
        resultado = resultado.filter(s =>
            s.aula_nombre.toLowerCase().includes(busquedaLower) ||
            s.materia_nombre.toLowerCase().includes(busquedaLower) ||
            s.aula_codigo.toLowerCase().includes(busquedaLower)
        );
    }

    // Filtrar por estado
    if (filtroEstado.value) {
        resultado = resultado.filter(s => s.estado === filtroEstado.value);
    }

    // Filtrar por rango de fechas
    if (fechaInicio.value) {
        resultado = resultado.filter(s => s.fecha_clase >= fechaInicio.value);
    }
    if (fechaFin.value) {
        resultado = resultado.filter(s => s.fecha_clase <= fechaFin.value);
    }

    return resultado;
});

// Cálculos de paginación
const totalPaginas = computed(() => Math.ceil(historialFiltrado.value.length / itemsPorPagina.value));
const indiceInicio = computed(() => (paginaActual.value - 1) * itemsPorPagina.value);
const indiceFin = computed(() => paginaActual.value * itemsPorPagina.value);
const historialPaginado = computed(() => historialFiltrado.value.slice(indiceInicio.value, indiceFin.value));

const paginasVisibles = computed(() => {
    const paginas = [];
    const maxPaginas = 5;
    let inicio = Math.max(1, paginaActual.value - 2);
    let fin = Math.min(totalPaginas.value, inicio + maxPaginas - 1);

    if (fin - inicio < maxPaginas - 1) {
        inicio = Math.max(1, fin - maxPaginas + 1);
    }

    for (let i = inicio; i <= fin; i++) {
        paginas.push(i);
    }
    return paginas;
});

// Funciones de paginación
const irAPagina = (pagina) => { paginaActual.value = pagina; };
const paginaAnterior = () => { if (paginaActual.value > 1) paginaActual.value--; };
const paginaSiguiente = () => { if (paginaActual.value < totalPaginas.value) paginaActual.value++; };

// Cargar historial
const cargarHistorial = async () => {
    cargando.value = true;
    try {
        const response = await axios.get('/api/disponibilidad/mi-historial', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
            }
        });

        const data = response.data;
        if (data.success) {
            historial.value = data.data;
            estadisticas.value = data.estadisticas;
        }
    } catch (error) {
        console.error('Error al cargar historial:', error);
        alert(error.response?.data?.message || 'Error al cargar el historial');
    } finally {
        cargando.value = false;
        isLoading.value = false;
    }
};

// Formatear fecha
const formatearFecha = (fecha) => {
    return new Date(fecha).toLocaleDateString('es-ES', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
};

// Formatear hora
const formatearHora = (timestamp) => {
    if (!timestamp) return '-';
    return new Date(timestamp).toLocaleTimeString('es-ES', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
};

// Ver detalle
const verDetalle = (sesion) => {
    sesionSeleccionada.value = sesion;
    mostrarDetalle.value = true;
};

const cerrarDetalle = () => {
    mostrarDetalle.value = false;
    sesionSeleccionada.value = null;
};

// Limpiar filtros
const limpiarFiltros = () => {
    busqueda.value = '';
    filtroEstado.value = '';
    fechaInicio.value = '';
    fechaFin.value = '';
    paginaActual.value = 1;
};

onMounted(() => {
    cargarHistorial();
});
</script>