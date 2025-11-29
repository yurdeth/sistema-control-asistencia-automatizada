<template>
    <Head title="Sesiones de Clase" />

    <div v-if="!isAuthenticated">
        <div v-if="isLoading" class="flex items-center justify-center min-h-screen bg-gray-100">
            <div class="text-center">
                <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-gray-900 mx-auto"></div>
                <p class="mt-4 text-gray-600 text-lg">Verificando sesión...</p>
            </div>
        </div>
    </div>

    <MainLayoutDashboard>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-1" :style="{color:colorText}">Sesiones de Clase</h1>
                <p class="text-gray-600 text-sm">Gestión y control de sesiones de clase</p>
            </div>

            <!-- Sección: Mis Sesiones de Hoy (solo para docentes) -->
            <div v-if="misSesionesHoy.length > 0" class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 mb-6 text-white">
                <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <i class="fas fa-calendar-day"></i>
                    Mis Sesiones de Hoy
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="sesion in misSesionesHoy" :key="sesion.id"
                         class="bg-white bg-opacity-20 backdrop-blur rounded-lg p-4 hover:bg-opacity-30 transition-all">
                        <div class="flex justify-between items-start mb-2">
                            <span class="font-semibold">{{ sesion.grupo_nombre }}</span>
                            <span class="text-xs px-2 py-1 bg-white bg-opacity-30 rounded">
                                {{ getEstadoLabel(sesion.estado) }}
                            </span>
                        </div>
                        <p class="text-sm mb-2">Aula: {{ sesion.aula_codigo }}</p>
                        <p class="text-sm mb-3">
                            {{ formatTime(sesion.hora_inicio_real) }} - {{ formatTime(sesion.hora_fin_real) }}
                        </p>
                        <button
                            v-if="sesion.estado === 'programada'"
                            @click="iniciarSesion(sesion)"
                            class="w-full bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                        >
                            Iniciar Sesión
                        </button>
                        <button
                            v-if="sesion.estado === 'en_curso'"
                            @click="finalizarSesion(sesion.id)"
                            class="w-full bg-white text-blue-600 hover:bg-gray-100 px-4 py-2 rounded-lg text-sm font-medium transition-colors"
                        >
                            Finalizar Sesión
                        </button>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <!-- Filtros -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-7 gap-4 mb-4">
                    <input
                        v-model="searchTerm"
                        type="text"
                        placeholder="Buscar..."
                        class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent lg:col-span-2"
                    />

                    <input
                        v-model="filterFecha"
                        type="date"
                        class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />

                    <select
                        v-model="filterEstado"
                        class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">Todos los estados</option>
                        <option value="programada">Programada</option>
                        <option value="en_curso">En curso</option>
                        <option value="finalizada">Finalizada</option>
                        <option value="cancelada">Cancelada</option>
                        <option value="sin_marcar_salida">Sin marcar salida</option>
                    </select>

                    <select
                        v-model="filterGrupo"
                        class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">Todos los grupos</option>
                        <option v-for="grupo in grupos" :key="grupo.id" :value="grupo.id">
                            {{ grupo.numero_grupo }}
                        </option>
                    </select>

                    <select
                        v-model="filterProfesor"
                        class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">Todos los profesores</option>
                        <option v-for="profesor in profesores" :key="profesor.id" :value="profesor.id">
                            {{ profesor.nombre_completo }}
                        </option>
                    </select>

                    <button
                        @click="openCreateModal"
                        class="text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
                        :style="{background: colors.btn_agregar}"
                    >
                        <span class="text-xl">+</span>
                        Nueva
                    </button>
                </div>

                <div v-if="loading" class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600">Cargando sesiones...</p>
                </div>

                <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-6">
                    <p class="font-bold">Error al cargar</p>
                    <p>{{ error }}</p>
                </div>

                <div v-if="!loading && sesionesFiltradas.length" class="bg-white rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full" :style="{ border: '1px solid #d93f3f' }">
                            <thead class="bg-gray-50 border-b-2 border-gray-200 text-center" :style="{background: '#d93f3f', height: '40px'}">
                            <tr>
                                <th class="text-white px-4 py-2">Fecha</th>
                                <th class="text-white px-4 py-2">Grupo</th>
                                <th class="text-white px-4 py-2">Aula</th>
                                <th class="text-white px-4 py-2">Inicio</th>
                                <th class="text-white px-4 py-2">Fin</th>
                                <th class="text-white px-4 py-2">Duración</th>
                                <th class="text-white px-4 py-2">Estado</th>
                                <th class="text-white px-4 py-2">Opciones</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-center align-middle">
                            <tr v-for="sesion in paginatedSesiones" :key="sesion.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ formatDate(sesion.fecha_clase) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ sesion.grupo_nombre || 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900">{{ sesion.aula_codigo || 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ formatTime(sesion.hora_inicio_real) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ formatTime(sesion.hora_fin_real) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ sesion.duracion_minutos ? sesion.duracion_minutos + ' min' : '-' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <select
                                        v-model="sesion.estado"
                                        @change="cambiarEstado(sesion.id, sesion.estado)"
                                        class="px-2 py-1 rounded text-xs font-semibold border-0 cursor-pointer"
                                        :class="getEstadoBadgeClass(sesion.estado)"
                                        :style="{background: colors.hover_seleccion, color: colors.text_color_light}"
                                    >
                                        <option value="programada">Programada</option>
                                        <option value="en_curso">En Curso</option>
                                        <option value="finalizada">Finalizada</option>
                                        <option value="cancelada">Cancelada</option>
                                        <option value="sin_marcar_salida">Sin Marcar Salida</option>
                                    </select>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex justify-center gap-2 flex-wrap">
                                        <button
                                            @click="verDetalles(sesion.id)"
                                            class="text-white px-3 py-2 rounded-lg transition-colors text-xs"
                                            :style="{ background: colors.btn_ver_detalle }"
                                            :disabled="loading"
                                        >
                                            Ver
                                        </button>
                                        <button
                                            v-if="sesion.estado === 'programada'"
                                            @click="iniciarSesion(sesion)"
                                            class="text-white px-3 py-2 rounded-lg transition-colors text-xs"
                                            :style="{ background: colors.btn_ver_mas }"
                                            :disabled="loading"
                                        >
                                            Iniciar
                                        </button>
                                        <button
                                            v-if="sesion.estado === 'en_curso'"
                                            @click="finalizarSesion(sesion.id)"
                                            class="bg-purple-500 hover:bg-purple-600 text-white px-3 py-2 rounded-lg transition-colors text-xs"
                                            :disabled="loading"
                                        >
                                            Finalizar
                                        </button>
                                        <button
                                            @click="openEditModal(sesion)"
                                            class=" text-white px-3 py-2 rounded-lg transition-colors text-xs"
                                            :style="{background: colors.btn_editar}"
                                            :disabled="loading"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            @click="deleteItem(sesion.id)"
                                            class="hover:bg-red-600 text-white px-3 py-2 rounded-lg transition-colors text-xs"
                                            :style="{ background: colors.btn_eliminar }"
                                            :disabled="loading"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <div class="flex justify-center items-center space-x-2 p-4 border-t border-gray-200">
                            <button
                                @click="prevPage"
                                :disabled="currentPage === 1"
                                class="p-2 border rounded-lg transition-colors"
                                :class="{ 'bg-gray-200 cursor-not-allowed': currentPage === 1, 'hover:bg-gray-100': currentPage > 1 }">
                                <i class="fas fa-chevron-left"></i>
                            </button>

                            <button
                                @click="goToPage(currentPage)"
                                class="px-4 py-2 border rounded-lg font-bold text-white transition-colors"
                                :style="{ background: '#d93f3f' }">
                                {{ currentPage }}
                            </button>

                            <button
                                @click="nextPage"
                                :disabled="currentPage === totalPages"
                                class="p-2 border rounded-lg transition-colors"
                                :class="{ 'bg-gray-200 cursor-not-allowed': currentPage === totalPages, 'hover:bg-gray-100': currentPage < totalPages }">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else-if="!loading && !sesionesFiltradas.length" class="bg-gray-100 border border-gray-400 text-gray-700 px-6 py-4 rounded-lg mb-6 text-center">
                    <p v-if="!hayFiltrosActivos">No hay sesiones registradas en el sistema.</p>
                    <p v-else>No se encontraron sesiones que coincidan con los filtros aplicados.</p>
                </div>
            </div>
        </div>
    </MainLayoutDashboard>

    <!-- Modal Crear/Editar -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">
                    {{ isEditMode ? 'Editar Sesión de Clase' : 'Crear Nueva Sesión' }}
                </h2>
                <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="p-6 space-y-4">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Horario <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="formData.horario_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.horario_id}"
                            required
                            :disabled="isEditMode"
                        >
                            <option value="">Seleccione un horario</option>
                            <option v-for="horario in horarios" :key="horario.id" :value="horario.id">
                                {{ horario.dia_semana }} | {{ horario.hora_inicio }} - {{ horario.hora_fin }} | Grupo: {{ horario.numero_grupo }} | Aula: {{ horario.aula_nombre }}
                            </option>
                        </select>
                        <p v-if="formErrors.horario_id" class="text-red-500 text-sm mt-1">
                            {{ formErrors.horario_id[0] }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Fecha de Clase <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="formData.fecha_clase"
                            type="date"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.fecha_clase}"
                            :min="new Date().toISOString().split('T')[0]"
                            required
                        />
                        <p v-if="formErrors.fecha_clase" class="text-red-500 text-sm mt-1">
                            {{ formErrors.fecha_clase[0] }}
                        </p>
                    </div>
                </div>

                <div v-if="isEditMode" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Hora Inicio Real
                        </label>
                        <input
                            v-model="formData.hora_inicio_real"
                            type="datetime-local"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.hora_inicio_real}"
                        />
                        <p v-if="formErrors.hora_inicio_real" class="text-red-500 text-sm mt-1">
                            {{ formErrors.hora_inicio_real[0] }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Hora Fin Real
                        </label>
                        <input
                            v-model="formData.hora_fin_real"
                            type="datetime-local"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.hora_fin_real}"
                        />
                        <p v-if="formErrors.hora_fin_real" class="text-red-500 text-sm mt-1">
                            {{ formErrors.hora_fin_real[0] }}
                        </p>
                    </div>
                </div>

                <div v-if="isEditMode">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Estado <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="formData.estado"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.estado}"
                    >
                        <option value="programada">Programada</option>
                        <option value="en_curso">En Curso</option>
                        <option value="finalizada">Finalizada</option>
                        <option value="cancelada">Cancelada</option>
                        <option value="sin_marcar_salida">Sin Marcar Salida</option>
                    </select>
                    <p v-if="formErrors.estado" class="text-red-500 text-sm mt-1">
                        {{ formErrors.estado[0] }}
                    </p>
                </div>

                <div v-if="formErrors.general" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <p class="font-bold">Error</p>
                    <p>{{ formErrors.general }}</p>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                        :disabled="submitting"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50"
                        :disabled="submitting"
                    >
                        {{ submitting ? 'Guardando...' : (isEditMode ? 'Actualizar' : 'Crear') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Ver Detalles -->
    <div v-if="showDetallesModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">Detalles de la Sesión</h2>
                <button @click="closeDetallesModal" class="text-gray-500 hover:text-gray-700">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>

            <div v-if="sesionDetalle" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Información General -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Información General</h3>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Fecha de Clase</label>
                            <p class="text-gray-900">{{ formatDate(sesionDetalle.fecha_clase) }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Grupo</label>
                            <p class="text-gray-900">{{ sesionDetalle.horario?.grupo?.numero_grupo || 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Aula</label>
                            <p class="text-gray-900">{{ sesionDetalle.horario?.aula?.codigo || 'N/A' }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Estado</label>
                            <span
                                class="inline-block px-3 py-1 rounded-full text-sm font-semibold mt-1"
                                :class="getEstadoBadgeClass(sesionDetalle.estado)"
                            >
                                {{ getEstadoLabel(sesionDetalle.estado) }}
                            </span>
                        </div>
                    </div>

                    <!-- Horarios -->
                    <div class="space-y-4">
                        <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Horarios</h3>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Hora Inicio Real</label>
                            <p class="text-gray-900">{{ formatDateTime(sesionDetalle.hora_inicio_real) }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Hora Fin Real</label>
                            <p class="text-gray-900">{{ formatDateTime(sesionDetalle.hora_fin_real) }}</p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Duración</label>
                            <p class="text-gray-900">
                                {{ sesionDetalle.duracion_minutos ? sesionDetalle.duracion_minutos + ' minutos' : 'N/A' }}
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-600">Retraso</label>
                            <p class="text-gray-900">
                                {{ sesionDetalle.retraso_minutos ? sesionDetalle.retraso_minutos + ' minutos' : 'Sin retraso' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Información del Horario -->
                <div v-if="sesionDetalle.horario" class="mt-6 pt-6 border-t">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Información del Horario</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-sm font-medium text-gray-600">Día de la Semana</label>
                            <p class="text-gray-900">{{ sesionDetalle.horario.dia_semana || 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Hora Inicio Programada</label>
                            <p class="text-gray-900">{{ sesionDetalle.horario.hora_inicio || 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-gray-600">Hora Fin Programada</label>
                            <p class="text-gray-900">{{ sesionDetalle.horario.hora_fin || 'N/A' }}</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 mt-6 pt-4 border-t">
                    <button
                        @click="closeDetallesModal"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                    >
                        Cerrar
                    </button>
                </div>
            </div>

            <div v-else class="p-6 text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <p class="mt-4 text-gray-600">Cargando detalles...</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
import { authService } from "@/Services/authService.js";
import { colors } from '@/UI/color';

const isLoading = ref(true);
const colorText = ref('#1F2937');
const searchTerm = ref('');
const filterEstado = ref('');
const filterFecha = ref('');
const filterGrupo = ref('');
const filterProfesor = ref('');
const loading = ref(false);
const error = ref(null);
const allSesiones = ref([]);
const misSesionesHoy = ref([]);
const horarios = ref([]);
const grupos = ref([]);
const profesores = ref([]);
const isAuthenticated = localStorage.getItem('isAuthenticated');

// Paginación
const currentPage = ref(1);
const perPage = ref(10);

// Configuración de axios
const API_URL = '/api';
const getAuthHeaders = () => ({
    headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Estados del Modal
const showModal = ref(false);
const isEditMode = ref(false);
const submitting = ref(false);
const formErrors = ref({});
const currentSesionId = ref(null);

// Modal de detalles
const showDetallesModal = ref(false);
const sesionDetalle = ref(null);

// Datos del formulario
const formData = ref({
    horario_id: '',
    fecha_clase: '',
    hora_inicio_real: '',
    hora_fin_real: '',
    estado: 'programada',
});

// Computed para verificar si hay filtros activos
const hayFiltrosActivos = computed(() => {
    return searchTerm.value !== '' ||
           filterEstado.value !== '' ||
           filterFecha.value !== '' ||
           filterGrupo.value !== '' ||
           filterProfesor.value !== '';
});

// Filtrado
const sesionesFiltradas = computed(() => {
    const data = Array.isArray(allSesiones.value) ? allSesiones.value : [];
    let filtered = data;

    // Filtrar por término de búsqueda
    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();
        filtered = filtered.filter(sesion =>
            sesion.grupo_nombre?.toLowerCase().includes(term) ||
            sesion.aula_codigo?.toLowerCase().includes(term)
        );
    }

    // Filtrar por fecha
    if (filterFecha.value) {
        filtered = filtered.filter(sesion => sesion.fecha_clase === filterFecha.value);
    }

    // Filtrar por estado
    if (filterEstado.value) {
        filtered = filtered.filter(sesion => sesion.estado === filterEstado.value);
    }

    // Filtrar por grupo
    if (filterGrupo.value) {
        filtered = filtered.filter(sesion => sesion.grupo_id == filterGrupo.value);
    }

    // Filtrar por profesor
    if (filterProfesor.value) {
        filtered = filtered.filter(sesion => sesion.profesor_id == filterProfesor.value);
    }

    return filtered;
});

// Paginación
const totalPages = computed(() => {
    return Math.ceil(sesionesFiltradas.value.length / perPage.value);
});

const paginatedSesiones = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return sesionesFiltradas.value.slice(start, end);
});

// Observa los cambios en filtros
watch([searchTerm, filterEstado, filterFecha, filterGrupo, filterProfesor], () => {
    currentPage.value = 1;
});

// Funciones de utilidad
const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit'
    });
};

const formatTime = (datetime) => {
    if (!datetime) return '-';
    return new Date(datetime).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatDateTime = (datetime) => {
    if (!datetime) return '-';
    const date = new Date(datetime);
    return date.toLocaleDateString('es-ES', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getEstadoLabel = (estado) => {
    const labels = {
        'programada': 'Programada',
        'en_curso': 'En Curso',
        'finalizada': 'Finalizada',
        'cancelada': 'Cancelada',
        'sin_marcar_salida': 'Sin Marcar Salida'
    };
    return labels[estado] || estado;
};

const getEstadoBadgeClass = (estado) => {
    const classes = {
        'programada': 'bg-blue-100 text-blue-800',
        'en_curso': 'bg-green-100 text-green-800',
        'finalizada': 'bg-gray-100 text-gray-800',
        'cancelada': 'bg-red-100 text-red-800',
        'sin_marcar_salida': 'bg-yellow-100 text-yellow-800'
    };
    return classes[estado] || 'bg-gray-100 text-gray-800';
};

// Funciones del Modal
const resetForm = () => {
    formData.value = {
        horario_id: '',
        fecha_clase: '',
        hora_inicio_real: '',
        hora_fin_real: '',
        estado: 'programada',
    };
    formErrors.value = {};
    currentSesionId.value = null;
};

const openCreateModal = async () => {
    resetForm();
    isEditMode.value = false;
    await fetchHorarios();
    showModal.value = true;
};

const openEditModal = async (sesion) => {
    console.log('Abriendo modal para editar:', sesion);

    resetForm();
    isEditMode.value = true;
    currentSesionId.value = sesion.id;

    await fetchHorarios();

    // Cargar datos
    formData.value = {
        horario_id: sesion.horario_id || '',
        fecha_clase: sesion.fecha_clase || '',
        hora_inicio_real: sesion.hora_inicio_real ? formatDatetimeLocal(sesion.hora_inicio_real) : '',
        hora_fin_real: sesion.hora_fin_real ? formatDatetimeLocal(sesion.hora_fin_real) : '',
        estado: sesion.estado || 'programada',
    };

    console.log('Form data cargado:', formData.value);

    showModal.value = true;
};

const formatDatetimeLocal = (datetime) => {
    if (!datetime) return '';
    const date = new Date(datetime);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

const closeDetallesModal = () => {
    showDetallesModal.value = false;
    sesionDetalle.value = null;
};

// Funciones CRUD
// Función helper para convertir a zona horaria de El Salvador
const toElSalvadorTime = (dateString) => {
    if (!dateString) return null;

    const date = new Date(dateString);
    const svTime = new Date(date.toLocaleString('en-US', {
        timeZone: 'America/El_Salvador'
    }));

    const year = svTime.getFullYear();
    const month = String(svTime.getMonth() + 1).padStart(2, '0');
    const day = String(svTime.getDate()).padStart(2, '0');
    const hours = String(svTime.getHours()).padStart(2, '0');
    const minutes = String(svTime.getMinutes()).padStart(2, '0');
    const seconds = String(svTime.getSeconds()).padStart(2, '0');

    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
};

const submitForm = async () => {
    formErrors.value = {};
    submitting.value = true;

    try {
        const payload = {
            horario_id: parseInt(formData.value.horario_id),
            fecha_clase: formData.value.fecha_clase,
        };

        if (isEditMode.value) {
            if (formData.value.hora_inicio_real) {
                // Usar zona horaria de El Salvador en lugar de UTC
                payload.hora_inicio_real = toElSalvadorTime(formData.value.hora_inicio_real);
            }
            if (formData.value.hora_fin_real) {
                // Usar zona horaria de El Salvador en lugar de UTC
                payload.hora_fin_real = toElSalvadorTime(formData.value.hora_fin_real);
            }
            payload.estado = formData.value.estado;
        }

        console.log("📦 Payload enviado:", JSON.stringify(payload, null, 2));

        const url = isEditMode.value
            ? `${API_URL}/class-sessions/edit/${currentSesionId.value}`
            : `${API_URL}/class-sessions/new`;

        const response = isEditMode.value
            ? await axios.patch(url, payload, getAuthHeaders())
            : await axios.post(url, payload, getAuthHeaders());

        if (response.data.success || response.status === 200 || response.status === 201) {
            closeModal();
            await fetchSesiones();
            await loadMisSesionesHoy();
            alert(
                isEditMode.value
                    ? "Sesión actualizada exitosamente"
                    : "Sesión creada exitosamente"
            );
        }
    } catch (err) {
        console.error("❌ Error al guardar sesión:", err);

        const data = err.response?.data || {};

        if (data.errors) {
            formErrors.value = data.errors;
        } else {
            formErrors.value.general =
                data.message || data.error || "Error al guardar la sesión";
        }
    } finally {
        submitting.value = false;
    }
};

const deleteItem = async (id) => {
    if (!confirm('¿Está seguro de eliminar esta sesión? Esta acción no se puede deshacer.')) return;

    try {
        loading.value = true;
        console.log('Eliminando sesión ID:', id);

        const response = await axios.delete(`${API_URL}/class-sessions/delete/${id}`, getAuthHeaders());

        console.log('Respuesta eliminación:', response.data);

        if (response.data.success || response.status === 200) {
            alert('Sesión eliminada exitosamente');
            await fetchSesiones();
            await loadMisSesionesHoy();
        }
    } catch (err) {
        console.error('Error al eliminar sesión:', err);
        alert(err.response?.data?.message || 'Error al eliminar la sesión');
    } finally {
        loading.value = false;
    }
};

// Funciones especiales de sesiones
const iniciarSesion = async (sesion) => {
    if (!confirm('¿Desea iniciar esta sesión de clase?')) return;

    try {
        loading.value = true;
        const payload = {
            horario_id: sesion.horario_id,
            fecha_clase: sesion.fecha_clase
        };

        const response = await axios.post(`${API_URL}/class-sessions/start`, payload, getAuthHeaders());

        if (response.data.success) {
            alert('Sesión iniciada exitosamente');
            await fetchSesiones();
            await loadMisSesionesHoy();
        }
    } catch (err) {
        console.error('Error al iniciar sesión:', err);
        alert(err.response?.data?.message || 'Error al iniciar la sesión');
    } finally {
        loading.value = false;
    }
};

const finalizarSesion = async (id) => {
    if (!confirm('¿Desea finalizar esta sesión de clase?')) return;

    try {
        loading.value = true;
        const response = await axios.post(`${API_URL}/class-sessions/finish/${id}`, {}, getAuthHeaders());

        if (response.data.success) {
            alert('Sesión finalizada exitosamente');
            await fetchSesiones();
            await loadMisSesionesHoy();
        }
    } catch (err) {
        console.error('Error al finalizar sesión:', err);
        alert(err.response?.data?.message || 'Error al finalizar la sesión');
    } finally {
        loading.value = false;
    }
};

const cambiarEstado = async (id, nuevoEstado) => {
    if (!confirm(`¿Desea cambiar el estado a "${getEstadoLabel(nuevoEstado)}"?`)) {
        await fetchSesiones();
        return;
    }

    try {
        loading.value = true;
        const payload = { estado: nuevoEstado };

        const response = await axios.patch(
            `${API_URL}/class-sessions/change-status/${id}`,
            payload,
            getAuthHeaders()
        );

        if (response.data.success) {
            alert('Estado actualizado exitosamente');
            await fetchSesiones();
            await loadMisSesionesHoy();
        }
    } catch (err) {
        console.error('Error al cambiar estado:', err);
        alert(err.response?.data?.message || 'Error al cambiar el estado');
        await fetchSesiones();
    } finally {
        loading.value = false;
    }
};

const verDetalles = async (id) => {
    try {
        showDetallesModal.value = true;
        sesionDetalle.value = null;

        const response = await axios.get(`${API_URL}/class-sessions/get/${id}`, getAuthHeaders());

        if (response.data.success) {
            sesionDetalle.value = response.data.data;
            console.log(sesionDetalle.value);
        }
    } catch (err) {
        console.error('Error al cargar detalles:', err);
        alert(err.response?.data?.message || 'Error al cargar los detalles de la sesión');
        closeDetallesModal();
    }
};

// Paginación
const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
};

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

// Cargar datos
async function fetchSesiones() {
    loading.value = true;
    error.value = null;

    try {
        const res = await axios.get(`${API_URL}/class-sessions/get/all`, getAuthHeaders());

        const payload = res.data?.data;
        const raw = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);

        console.log(raw);

        allSesiones.value = raw.map(sesion => ({
            id: sesion.id ?? 'N/A',
            horario_id: sesion.horario_id ?? null,
            fecha_clase: sesion.fecha_clase ?? null,
            hora_inicio_real: sesion.hora_inicio_real ?? null,
            hora_fin_real: sesion.hora_fin_real ?? null,
            duracion_minutos: sesion.duracion_minutos ?? null,
            estado: sesion.estado ?? 'programada',
            retraso_minutos: sesion.retraso_minutos ?? null,
            grupo_nombre: sesion.horario?.grupo?.numero_grupo ?? 'N/A',
            grupo_id: sesion.horario?.grupo?.id ?? null,
            aula_codigo: sesion.horario?.aula?.codigo ?? 'N/A',
            profesor_id: sesion.horario?.grupo?.docente_id ?? null,
        }));

        error.value = null;

    } catch (err) {
        const status = err.response?.status;

        if (status === 404) {
            allSesiones.value = [];
            error.value = null;
        } else if (status === 401 || status === 403) {
            error.value = err.response?.data?.message || 'Acceso no autorizado. Verifica tu sesión/rol.';
            allSesiones.value = [];
        } else {
            error.value = err.response?.data?.message || 'Error al cargar las sesiones';
            allSesiones.value = [];
        }
    } finally {
        loading.value = false;
    }
}

async function fetchHorarios() {
    try {
        const res = await axios.get(`${API_URL}/schedules/get/all`, getAuthHeaders());
        const payload = res.data?.data;
        horarios.value = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);
    } catch (err) {
        console.error('Error al cargar horarios:', err);
        horarios.value = [];
    }
}

async function fetchGrupos() {
    try {
        const res = await axios.get(`${API_URL}/groups/get/all`, getAuthHeaders());
        const payload = res.data?.data;
        grupos.value = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);
    } catch (err) {
        console.error('Error al cargar grupos:', err);
        grupos.value = [];
    }
}

async function fetchProfesores() {
    try {
        const res = await axios.get(`${API_URL}/users/get/professors/all`, getAuthHeaders());
        const payload = res.data?.data;
        profesores.value = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);
    } catch (err) {
        console.error('Error al cargar profesores:', err);
        profesores.value = [];
    }
}

async function loadMisSesionesHoy() {
    try {
        const userId = localStorage.getItem('user_id');
        if (!userId) return;

        const response = await axios.get(
            `${API_URL}/class-sessions/get/professor/${userId}/today`,
            getAuthHeaders()
        );

        if (response.data.success) {
            const payload = response.data.data;
            const raw = Array.isArray(payload) ? payload : [];

            misSesionesHoy.value = raw.map(sesion => ({
                id: sesion.id,
                horario_id: sesion.horario_id,
                fecha_clase: sesion.fecha_clase,
                hora_inicio_real: sesion.hora_inicio_real,
                hora_fin_real: sesion.hora_fin_real,
                estado: sesion.estado,
                grupo_nombre: sesion.horario?.grupo?.nombre ?? 'N/A',
                aula_codigo: sesion.horario?.aula?.codigo ?? 'N/A',
            }));
        }
    } catch (err) {
        if (err.response?.status !== 404) {
            console.error('Error al cargar mis sesiones de hoy:', err);
        }
        misSesionesHoy.value = [];
    }
}

onMounted(async () => {
    await authService.verifyToken(localStorage.getItem("token"));
    searchTerm.value = '';
    filterEstado.value = '';
    filterFecha.value = '';
    filterGrupo.value = '';
    filterProfesor.value = '';

    await Promise.all([
        fetchSesiones(),
        fetchGrupos(),
        fetchProfesores(),
        loadMisSesionesHoy()
    ]);

    isLoading.value = false;
});
</script>
