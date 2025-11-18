<template>
    <Head title="Estudiantes"/>

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
                <h1 class="text-2xl font-bold text-gray-900 mb-1" :style="{color:colorText}">Estudiantes</h1>
                <p class="text-gray-600 text-sm">Listado de los estudiantes dentro de la facultad</p>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <div class="flex flex-col lg:flex-row gap-4">
                    <div class="flex flex-col sm:flex-row gap-4 flex-1">
                        <input
                            v-model="searchTerm"
                            type="text"
                            placeholder="Buscar por nombre o email"
                            class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        />
                        <select
                            v-model="filterCarrera"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm appearance-none bg-white cursor-pointer"
                        >
                            <option value="">Todas las carreras</option>
                            <option v-for="carrera in carreras" :key="carrera.id" :value="carrera.id">
                                {{ carrera.nombre }}
                            </option>
                        </select>
                        <select
                            v-model="filterEstado"
                            class="px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        >
                            <option value="">Todos los estados</option>
                            <option value="activo">Activos</option>
                            <option value="inactivo">Inactivos</option>
                            <option value="suspendido">Suspendidos</option>
                        </select>
                    </div>
                    <div class="flex gap-4 items-center">
                        <!-- Selector de registros por página -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600 whitespace-nowrap">Mostrar:</label>
                            <select
                                v-model="perPage"
                                class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm appearance-none bg-white cursor-pointer"
                            >
                                <option v-for="option in perPageOptions" :key="option" :value="option">
                                    {{ option }}
                                </option>
                            </select>
                            <span class="text-sm text-gray-600 whitespace-nowrap">registros</span>
                        </div>
                        <button
                            @click="openCreateModal"
                            class="text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 whitespace-nowrap"
                            :style="{background: '#D93F3F'}"
                        >
                            <span class="text-xl">+</span>
                            Agregar Estudiante
                        </button>
                        <label
                            for="fileUpload"
                            class="cursor-pointer bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors whitespace-nowrap"
                        >
                            <i class="fa-solid fa-file-excel"></i>
                            Subir Excel
                        </label>
                        <input id="fileUpload" type="file" accept=".xlsx, .xls" class="hidden" @change="handleExcelUpload"/>
                    </div>
                </div>
                <br>

                <div v-if="loading" class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600">Cargando estudiantes...</p>
                </div>

                <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-6">
                    <p class="font-bold">Error al cargar</p>
                    <p>{{ error }}</p>
                </div>
                <br>

                <div v-if="!loading && estudiantesFiltrados.length" class="bg-white rounded-lg overflow-hidden">
                    <!-- Información de paginación -->
                    <div v-if="paginationData" class="px-6 py-3 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            <span v-if="paginationData.from && paginationData.to">
                                Mostrando {{ paginationData.from }} al {{ paginationData.to }}
                                de {{ paginationData.total }} registros
                            </span>
                            <span v-else>
                                No hay registros que mostrar
                            </span>
                        </div>
                        <div class="text-sm text-gray-600">
                            Página {{ paginationData.current_page }} de {{ paginationData.last_page }}
                        </div>
                    </div>

                    <!-- Contenedor con altura fija para tabla con scroll -->
                    <div class="relative" style="height: 500px;">
                        <div class="overflow-x-auto h-full flex flex-col">
                            <!-- Cabecera fija -->
                            <div class="sticky top-0 z-10">
                                <table class="w-full" :style="{ border: '1px solid #d93f3f' }">
                                    <thead class="bg-gray-50 border-b-2 border-gray-200 text-center"
                                           :style="{background: '#d93f3f', height: '40px'}">
                                        <tr>
                                            <th class="text-white px-4 py-2">Id</th>
                                            <th class="text-white px-4 py-2">Nombre</th>
                                            <th class="text-white px-4 py-2">Email</th>
                                            <th class="text-white px-4 py-2">Teléfono</th>
                                            <th class="text-white px-4 py-2">Estado</th>
                                            <th class="text-white px-4 py-2">Opciones</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>

                            <!-- Cuerpo con scroll -->
                            <div class="overflow-y-auto flex-1" style="max-height: calc(500px - 40px - 80px);">
                                <table class="w-full" :style="{ border: '1px solid #d93f3f', borderTop: 'none' }">
                                    <tbody class="divide-y divide-gray-200 text-center align-middle">
                                        <tr v-for="estudiante in paginatedEstudiantes" :key="estudiante.id"
                                            class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 text-sm text-gray-900">{{ estudiante.id }}</td>
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{
                                                    estudiante.nombre_completo
                                                }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-600">{{ estudiante.email }}</td>
                                            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ estudiante.telefono }}</td>
                                            <td class="px-6 py-4 text-sm">
                                                <span
                                                    class="px-3 py-1 rounded-full text-xs font-semibold"
                                                    :class="estudiante.estado === 'activo' ? 'bg-green-100 text-green-800' : estudiante.estado === 'inactivo' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'"
                                                >
                                                    {{ estudiante.estado }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                <div class="flex justify-center gap-2">
                                                    <button
                                                        @click="openEditModal(estudiante)"
                                                        class="bg-green-500 hover:bg-green-800 text-white px-4 py-2 rounded-lg transition-colors"
                                                        :disabled="loading"
                                                    >
                                                        Editar
                                                    </button>
                                                    <button
                                                        @click="deleteItem(estudiante.id)"
                                                        class="hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors"
                                                        :style="{ background: '#9b3b3e' }"
                                                        :disabled="loading"
                                                    >
                                                        Eliminar
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Controles de paginación fijos en la parte inferior -->
                    <div v-if="paginationData && paginationData.last_page > 1" class="px-6 py-4 border-t border-gray-200 bg-white">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <!-- Información de paginación -->
                            <div class="text-sm text-gray-600">
                                <span v-if="paginationData.total > 0">
                                    Página {{ paginationData.current_page }} de {{ paginationData.last_page }}
                                    ({{ paginationData.total }} registros en total)
                                </span>
                            </div>

                            <!-- Controles de navegación -->
                            <div class="flex items-center space-x-1">
                                <!-- Botón Primera página -->
                                <button
                                    @click="goToPage(1)"
                                    :disabled="currentPage === 1"
                                    class="p-2 border rounded-lg transition-colors text-xs"
                                    :class="{ 'bg-gray-200 cursor-not-allowed': currentPage === 1, 'hover:bg-gray-100': currentPage > 1 }"
                                    title="Primera página">
                                    <i class="fas fa-angle-double-left"></i>
                                </button>

                                <!-- Botón Anterior -->
                                <button
                                    @click="prevPage"
                                    :disabled="currentPage === 1"
                                    class="p-2 border rounded-lg transition-colors"
                                    :class="{ 'bg-gray-200 cursor-not-allowed': currentPage === 1, 'hover:bg-gray-100': currentPage > 1 }"
                                    title="Página anterior">
                                    <i class="fas fa-chevron-left"></i>
                                </button>

                                <!-- Números de página (limitado a mostrar) -->
                                <div class="flex items-center space-x-1">
                                    <!-- Lógica para mostrar páginas alrededor de la actual -->
                                    <template v-for="page in getVisiblePages()" :key="page">
                                        <span v-if="page === '...'" class="px-2 py-1 text-gray-500">...</span>
                                        <button
                                            v-else
                                            @click="goToPage(page)"
                                            class="px-3 py-1 border rounded-lg transition-colors text-sm"
                                            :class="{
                                                'bg-blue-600 text-white': page === currentPage,
                                                'hover:bg-gray-100': page !== currentPage
                                            }">
                                            {{ page }}
                                        </button>
                                    </template>
                                </div>

                                <!-- Botón Siguiente -->
                                <button
                                    @click="nextPage"
                                    :disabled="currentPage === totalPages"
                                    class="p-2 border rounded-lg transition-colors"
                                    :class="{ 'bg-gray-200 cursor-not-allowed': currentPage === totalPages, 'hover:bg-gray-100': currentPage < totalPages }"
                                    title="Página siguiente">
                                    <i class="fas fa-chevron-right"></i>
                                </button>

                                <!-- Botón Última página -->
                                <button
                                    @click="goToPage(totalPages)"
                                    :disabled="currentPage === totalPages"
                                    class="p-2 border rounded-lg transition-colors text-xs"
                                    :class="{ 'bg-gray-200 cursor-not-allowed': currentPage === totalPages, 'hover:bg-gray-100': currentPage < totalPages }"
                                    title="Última página">
                                    <i class="fas fa-angle-double-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-else-if="!loading && !estudiantesFiltrados.length"
                     class="bg-gray-100 border border-gray-400 text-gray-700 px-6 py-4 rounded-lg mb-6 text-center">
                    <p v-if="searchTerm === ''">No hay estudiantes registrados en el sistema.</p>
                    <p v-else>No se encontraron estudiantes que coincidan con la búsqueda: <span class="text-red-500">"{{
                            searchTerm
                        }}"</span></p>
                </div>
            </div>
        </div>
    </MainLayoutDashboard>

    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">
                    {{ isEditMode ? 'Editar Estudiante' : 'Agregar Nuevo Estudiante' }}
                </h2>
                <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre Completo <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="formData.nombre_completo"
                        type="text"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.nombre_completo}"
                        required
                    />
                    <p v-if="formErrors.nombre_completo" class="text-red-500 text-sm mt-1">
                        {{ formErrors.nombre_completo[0] }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="formData.email"
                        type="email"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.email}"
                        required
                    />
                    <p v-if="formErrors.email" class="text-red-500 text-sm mt-1">
                        {{ formErrors.email[0] }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Teléfono
                    </label>
                    <input
                        v-model="formData.telefono"
                        type="text"
                        placeholder="+503 1234-5678"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.telefono}"
                    />
                    <p v-if="formErrors.telefono" class="text-red-500 text-sm mt-1">
                        {{ formErrors.telefono[0] }}
                    </p>
                </div>

                <div v-if="!isEditMode">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Contraseña <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="formData.password"
                        type="password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.password}"
                        required
                    />
                    <p v-if="formErrors.password" class="text-red-500 text-sm mt-1">
                        {{ formErrors.password[0] }}
                    </p>
                </div>

                <div v-if="!isEditMode">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Confirmar Contraseña <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="formData.password_confirmation"
                        type="password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.password_confirmation}"
                        required
                    />
                    <p v-if="formErrors.password_confirmation" class="text-red-500 text-sm mt-1">
                        {{ formErrors.password_confirmation[0] }}
                    </p>
                </div>

                <div v-if="isEditMode" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-800 mb-3">
                        <strong>Nota:</strong> Deja estos campos vacíos si no deseas cambiar la contraseña
                    </p>

                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nueva Contraseña (opcional)
                        </label>
                        <input
                            v-model="formData.password"
                            type="password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.password}"
                        />
                        <p v-if="formErrors.password" class="text-red-500 text-sm mt-1">
                            {{ formErrors.password[0] }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Confirmar Nueva Contraseña
                        </label>
                        <input
                            v-model="formData.password_confirmation"
                            type="password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.password_confirmation}"
                        />
                        <p v-if="formErrors.password_confirmation" class="text-red-500 text-sm mt-1">
                            {{ formErrors.password_confirmation[0] }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Carrera <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="formData.carrera_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.carrera_id}"
                        required
                    >
                        <option value="">Seleccione una carrera</option>
                        <option v-for="carrera in carreras" :key="carrera.id" :value="carrera.id">
                            {{ carrera.nombre }}
                        </option>
                    </select>
                    <p v-if="formErrors.carrera_id" class="text-red-500 text-sm mt-1">
                        {{ formErrors.carrera_id[0] }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Estado <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="formData.estado"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.estado}"
                        required
                    >
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="suspendido">Suspendido</option>
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
</template>

<script setup>
import {Head} from '@inertiajs/vue3';
import {ref, computed, onMounted, watch} from 'vue';
import axios from 'axios';
import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
import {authService} from "@/Services/authService.js";

const isLoading = ref(true);
const colorText = ref('#1F2937');
const searchTerm = ref('');
const loading = ref(false);
const error = ref(null);
const allEstudiantes = ref([]);
const isAuthenticated = localStorage.getItem('isAuthenticated');

// Paginación server-side
const currentPage = ref(1);
const perPage = ref(15); // Default a 15 registros por página
const perPageOptions = ref([10, 15, 25, 50, 100]);
const paginationData = ref(null);

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
const currentEstudianteId = ref(null);
let errorTimeout = null;
const selectedOption = ref('view-all');
const filterEstado = ref('');
const filterCarrera = ref('');

// Datos del formulario
const formData = ref({
    nombre_completo: '',
    email: '',
    telefono: '',
    password: '',
    password_confirmation: '',
    carrera_id: '',
    estado: 'activo'
});

// Lista para selects (solo carreras)
const carreras = ref([]);

// Los datos ya vienen filtrados y paginados del servidor
const estudiantesFiltrados = computed(() => {
    return Array.isArray(allEstudiantes.value) ? allEstudiantes.value : [];
});

// Paginación server-side (datos vienen del backend)
const totalPages = computed(() => {
    return paginationData.value ? paginationData.value.last_page : 1;
});

const paginatedEstudiantes = computed(() => {
    return estudiantesFiltrados.value; // Ya viene paginado del backend
});

// Mapa de carreras para búsqueda rápida
const carrerasMap = computed(() => {
    const map = {};
    carreras.value.forEach(carrera => {
        map[carrera.id] = carrera.nombre;
    });
    return map;
});

const getCarreraNombre = (carrera_id) => {
    return carrerasMap.value[carrera_id] || 'N/A';
}

// Funciones del Modal
const resetForm = () => {
    formData.value = {
        nombre_completo: '',
        email: '',
        telefono: '',
        password: '',
        password_confirmation: '',
        carrera_id: '',
        estado: 'activo'
    };
    formErrors.value = {};
    currentEstudianteId.value = null;
};

const openCreateModal = async () => {
    resetForm();
    isEditMode.value = false;
    await fetchCarreras();
    showModal.value = true;
};

const openEditModal = async (estudiante) => {
    console.log('Abriendo modal para editar:', estudiante);

    resetForm();
    isEditMode.value = true;
    currentEstudianteId.value = estudiante.id;

    // Cargar datos
    formData.value = {
        nombre_completo: estudiante.nombre_completo || '',
        email: estudiante.email || '',
        telefono: estudiante.telefono || '',
        password: '',
        password_confirmation: '',
        carrera_id: estudiante.carrera_id || '',
        estado: estudiante.estado || 'activo'
    };

    console.log('Form data cargado:', formData.value);

    await fetchCarreras();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    resetForm();
};

// Funciones CRUD
const submitForm = async () => {
    formErrors.value = {};
    submitting.value = true;

    try {
        const toIntOrNull = (v) =>
            v === '' || v === null || v === undefined ? null : Number(v);

        const payload = {
            nombre_completo: formData.value.nombre_completo,
            email: formData.value.email,
            telefono: formData.value.telefono,
            rol_id: 6, // Rol de Estudiante
            estado: formData.value.estado,
            departamento_id: null, // Siempre null para estudiantes
            carrera_id: toIntOrNull(formData.value.carrera_id),
        };

        // Solo agrega la contraseña si no estamos en modo edición o si se proporcionó una nueva
        if (!isEditMode.value || (isEditMode.value && formData.value.password)) {
            if (formData.value.password) {
                payload.password = formData.value.password;
                payload.password_confirmation = formData.value.password_confirmation;
            }
        }

        console.log("📦 Payload enviado:", JSON.stringify(payload, null, 2));

        const url = isEditMode.value
            ? `${API_URL}/users/edit/${currentEstudianteId.value}` : `${API_URL}/users/new`;

        const response = isEditMode.value
            ? await axios.patch(url, payload, getAuthHeaders())
            : await axios.post(url, payload, getAuthHeaders());

        if (response.data.success) {
            closeModal();
            await fetchEstudiantes();
            alert(
                isEditMode.value
                    ? "Estudiante actualizado exitosamente"
                    : "Estudiante creado exitosamente"
            );
        }
    } catch (err) {
        console.error("❌ Error al guardar estudiante:", err);

        const data = err.response?.data || {};

        if (data.errors) {
            formErrors.value = data.errors; // errores de validación
        } else {
            formErrors.value.general =
                data.message || data.error || "Error al guardar el estudiante";
        }
    } finally {
        submitting.value = false;
    }
};

const deleteItem = async (id) => {
    if (!confirm('¿Está seguro de eliminar este estudiante? Esta acción no se puede deshacer.')) return;

    try {
        loading.value = true;
        console.log('Eliminando estudiante ID:', id);

        const response = await axios.delete(`${API_URL}/users/delete/${id}`, getAuthHeaders());

        console.log('Respuesta eliminación:', response.data);

        if (response.data.success) {
            alert('Estudiante eliminado exitosamente');
            await fetchEstudiantes();
        }
    } catch (err) {
        console.error('Error al eliminar estudiante:', err);
        alert(err.response?.data?.message || 'Error al eliminar el estudiante');
    } finally {
        loading.value = false;
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

// Función para calcular qué páginas mostrar en la paginación
const getVisiblePages = () => {
    if (!paginationData.value) return [];

    const current = paginationData.value.current_page;
    const last = paginationData.value.last_page;
    const delta = 2; // Cuántas páginas mostrar antes y después de la actual

    const range = [];
    const rangeWithDots = [];
    let l;

    // Si hay pocas páginas, mostrar todas
    if (last <= 7) {
        for (let i = 1; i <= last; i++) {
            range.push(i);
        }
        return range;
    }

    // Agregar primera página
    range.push(1);

    // Calcular rango alrededor de la página actual
    for (let i = Math.max(2, current - delta); i <= Math.min(last - 1, current + delta); i++) {
        range.push(i);
    }

    // Agregar última página
    range.push(last);

    // Eliminar duplicados y ordenar
    const uniqueRange = [...new Set(range)].sort((a, b) => a - b);

    // Agregar puntos suspensivos donde haya saltos
    for (let i = 0; i < uniqueRange.length; i++) {
        if (l) {
            if (uniqueRange[i] - l === 2) {
                rangeWithDots.push(l + 1);
            } else if (uniqueRange[i] - l !== 1) {
                rangeWithDots.push('...');
            }
        }
        rangeWithDots.push(uniqueRange[i]);
        l = uniqueRange[i];
    }

    return rangeWithDots;
};

// Watchers reactivos para paginación server-side
// Observa los cambios en filtros (resetea página y carga datos)
watch([searchTerm, filterEstado, filterCarrera], () => {
    currentPage.value = 1; // Resetear a página 1 cuando cambian filtros
    fetchEstudiantes(); // Cargar datos del servidor
}, { deep: false });

// Observa cambios de tamaño de página (resetea a página 1)
watch(perPage, () => {
    currentPage.value = 1; // Resetear a página 1 cuando cambia tamaño de página
    fetchEstudiantes(); // Cargar datos del servidor
}, { deep: false });

// Observa cambios de página actual (solo carga datos)
watch(currentPage, () => {
    fetchEstudiantes(); // Cargar datos del servidor
}, { deep: false });

// Cargar datos con paginación y filtros del servidor
async function fetchEstudiantes() {
    loading.value = true;
    error.value = null;

    try {
        // Construir parámetros de consulta
        const params = new URLSearchParams({
            page: currentPage.value.toString(),
            per_page: perPage.value.toString(),
        });

        // Agregar filtros si tienen valores
        if (searchTerm.value.trim()) {
            params.append('search', searchTerm.value.trim());
        }
        if (filterCarrera.value) {
            params.append('carrera_id', filterCarrera.value);
        }
        if (filterEstado.value) {
            params.append('estado', filterEstado.value);
        }

        const res = await axios.get(`${API_URL}/users/get/students/paginated?${params.toString()}`, getAuthHeaders());

        if (res.data.success) {
            const payload = res.data?.data;
            const raw = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);

            allEstudiantes.value = raw.map(estudiante => ({
                id: estudiante.id ?? 'N/A',
                nombre_completo: estudiante.nombre_completo ?? estudiante.name ?? 'Sin nombre',
                email: estudiante.email ?? 'N/A',
                telefono: estudiante.telefono ?? estudiante.phone ?? 'N/A',
                estado: estudiante.estado ?? estudiante.status ?? 'N/A',
                carrera_id: estudiante.carrera_id ?? null,
                carrera_nombre: estudiante.carrera?.nombre || carrerasMap.value[estudiante.carrera_id] || 'N/A',
            }));

            // Guardar metadata de paginación
            paginationData.value = res.data?.pagination || {
                current_page: 1,
                last_page: 1,
                per_page: perPage.value,
                total: 0,
                from: null,
                to: null
            };

            error.value = null;
        } else {
            // Manejar caso cuando no hay resultados con los filtros actuales
            allEstudiantes.value = [];
            paginationData.value = {
                current_page: 1,
                last_page: 1,
                per_page: perPage.value,
                total: 0,
                from: null,
                to: null
            };
            error.value = null; // No es error, solo no hay resultados
        }

    } catch (err) {
        const status = err.response?.status;

        if (status === 404) {
            allEstudiantes.value = [];
            paginationData.value = {
                current_page: 1,
                last_page: 1,
                per_page: perPage.value,
                total: 0,
                from: null,
                to: null
            };
            error.value = null; // No hay resultados, no es error
        } else if (status === 401 || status === 403) {
            error.value = err.response?.data?.message || 'Acceso no autorizado. Verifica tu sesión/rol.';
            allEstudiantes.value = [];
            paginationData.value = null;
        } else {
            error.value = err.response?.data?.message || 'Error al cargar los estudiantes';
            allEstudiantes.value = [];
            paginationData.value = null;
        }
    } finally {
        loading.value = false;
    }
}

// Cargar datos (Carreras)
async function fetchCarreras() {
    try {
        const carResponse = await axios.get(`${API_URL}/careers/get/all`, getAuthHeaders());

        console.log('Respuesta Carreras:', carResponse.data);

        // Asegurar que solo se asignan las carreras
        carreras.value = carResponse.data.data || carResponse.data || [];

        console.log('Carreras procesadas:', carreras.value);
    } catch (err) {
        console.error('Error al cargar carreras:', err);
        console.error('Error completo:', err.response?.data);
        formErrors.value.general = 'Error al cargar carreras';
    }
}

const handleExcelUpload = (event) => {
    console.log('Subir Excel', event.target.files[0]);
};

const performApiSearch = async () => {
    loading.value = true;
    error.value = null;

    // Limpiar cualquier timeout previo
    if (errorTimeout) {
        clearTimeout(errorTimeout);
        errorTimeout = null;
    }

    if (searchTerm.value === '') {
        loading.value = true;
        error.value = 'El término de búsqueda no puede estar vacío';
        errorTimeout = setTimeout(() => {
            error.value = null;
            fetchEstudiantes();
            loading.value = false;
        }, 3000);
        return;
    }

    try {
        const response = await axios.post(
            `${API_URL}/users/get/name`,
            {
                nombre: searchTerm.value,
                rol_nombre: 'estudiante'
            },
            {
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            }
        );

        const data = response.data;

        if (!data.success) {
            error.value = 'Error al obtener los estudiantes';
            allEstudiantes.value = [];
            errorTimeout = setTimeout(() => {
                error.value = null;
                fetchEstudiantes();
            }, 3000);
            return;
        }

        // Manejar la respuesta según la estructura (objeto indexado o array)
        const payload = data.data || data;
        const estudiantes = Array.isArray(payload)
            ? payload
            : (payload && typeof payload === 'object' ? Object.values(payload) : []);

        allEstudiantes.value = estudiantes.map(estudiante => ({
            id: estudiante.id ?? 'N/A',
            nombre_completo: estudiante.nombre_completo ?? estudiante.name ?? 'Unknown',
            email: estudiante.email ?? 'N/A',
            telefono: estudiante.telefono ?? estudiante.phone ?? 'N/A',
            estado: estudiante.estado ?? estudiante.status ?? 'N/A',
            carrera_id: estudiante.carrera_id ?? null,
        }));

        error.value = null;

    } catch (err) {
        const status = err.response?.status;
        allEstudiantes.value = [];

        if (status === 404) {
            error.value = 'No se encontraron estudiantess con ese criterio de búsqueda';
        } else if (status === 401 || status === 403) {
            error.value = err.response?.data?.message || 'Acceso no autorizado. Verifica tu sesión.';
        } else {
            error.value = err.response?.data?.errors.nombre || 'Error al buscar estudiantess';
        }

        // Programar limpieza y recarga después de mostrar el error
        errorTimeout = setTimeout(() => {
            error.value = null;
            fetchEstudiantes();
        }, 3000);

    } finally {
        loading.value = false;
    }
};

const performCleanSearch = async () => {
    searchTerm.value = '';
    await fetchEstudiantes();
};

const fetchEnabledStudents = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await axios.post(
            `${API_URL}/users/get/status-role`,
            {
                rol_nombre: 'estudiante',
                estado: 'activo'
            },
            {
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            }
        );

        const data = response.data;

        if (!data.success) {
            error.value = 'Error al obtener los estudiantess activos';
            allEstudiantes.value = [];
            return;
        }

        // Manejar la respuesta según la estructura
        const payload = data.data || data;
        const raw = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);

        allEstudiantes.value = raw.map(estudiantes => ({
            id: estudiantes.id ?? 'N/A',
            nombre_completo: estudiantes.nombre_completo ?? estudiantes.name ?? 'Unknown',
            email: estudiantes.email ?? 'N/A',
            telefono: estudiantes.telefono ?? estudiantes.phone ?? 'N/A',
            estado: estudiantes.estado ?? estudiantes.status ?? 'activo',
            departamento_id: estudiantes.departamento_id ?? null,
            carrera_id: estudiantes.carrera_id ?? null,
        }));

        error.value = null;

    } catch (err) {
        const status = err.response?.status;

        if (status === 404) {
            // No hay estudiantess activos, trátalo como lista vacía
            allEstudiantes.value = [];
            error.value = null;
        } else if (status === 401 || status === 403) {
            error.value = err.response?.data?.message || 'Acceso no autorizado. Verifica tu sesión.';
            allEstudiantes.value = [];
        } else {
            error.value = err.response?.data?.message || 'Error al obtener los estudiantess activos';
            allEstudiantes.value = [];
        }
    } finally {
        loading.value = false;
    }
};

const fetchDisabledStudents = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await axios.post(
            `${API_URL}/users/get/status-role`,
            {
                rol_nombre: 'estudiante',
                estado: 'inactivo'
            },
            {
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            }
        );

        const data = response.data;

        if (!data.success) {
            error.value = 'Error al obtener los estudiantess activos';
            allEstudiantes.value = [];
            return;
        }

        // Manejar la respuesta según la estructura
        const payload = data.data || data;
        const raw = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);

        allEstudiantes.value = raw.map(estudiantes => ({
            id: estudiantes.id ?? 'N/A',
            nombre_completo: estudiantes.nombre_completo ?? estudiantes.name ?? 'Unknown',
            email: estudiantes.email ?? 'N/A',
            telefono: estudiantes.telefono ?? estudiantes.phone ?? 'N/A',
            estado: estudiantes.estado ?? estudiantes.status ?? 'activo',
            departamento_id: estudiantes.departamento_id ?? null,
            carrera_id: estudiantes.carrera_id ?? null,
        }));

        error.value = null;

    } catch (err) {
        const status = err.response?.status;

        if (status === 404) {
            // No hay estudiantes activos, trátalo como lista vacía
            allEstudiantes.value = [];
            error.value = null;
        } else if (status === 401 || status === 403) {
            error.value = err.response?.data?.message || 'Acceso no autorizado. Verifica tu sesión.';
            allEstudiantes.value = [];
        } else {
            error.value = err.response?.data?.message || 'Error al obtener los estudiantes inactivos';
            allEstudiantes.value = [];
        }
    } finally {
        loading.value = false;
    }
};

const fetchSuspendedStudents = async () => {
    loading.value = true;
    error.value = null;

    try {
        const response = await axios.post(
            `${API_URL}/users/get/status-role`,
            {
                rol_nombre: 'estudiante',
                estado: 'suspendido'
            },
            {
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            }
        );

        const data = response.data;

        if (!data.success) {
            error.value = 'Error al obtener los estudiantes suspendidos';
            allEstudiantes.value = [];
            return;
        }

        // Manejar la respuesta según la estructura
        const payload = data.data || data;
        const raw = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);

        allEstudiantes.value = raw.map(estudiantes => ({
            id: estudiantes.id ?? 'N/A',
            nombre_completo: estudiantes.nombre_completo ?? estudiantes.name ?? 'Unknown',
            email: estudiantes.email ?? 'N/A',
            telefono: estudiantes.telefono ?? estudiantes.phone ?? 'N/A',
            estado: estudiantes.estado ?? estudiantes.status ?? 'activo',
            departamento_id: estudiantes.departamento_id ?? null,
            carrera_id: estudiantes.carrera_id ?? null,
        }));

        error.value = null;

    } catch (err) {
        const status = err.response?.status;

        if (status === 404) {
            // No hay estudiantess activos, trátalo como lista vacía
            allEstudiantes.value = [];
            error.value = null;
        } else if (status === 401 || status === 403) {
            error.value = err.response?.data?.message || 'Acceso no autorizado. Verifica tu sesión.';
            allEstudiantes.value = [];
        } else {
            error.value = err.response?.data?.message || 'Error al obtener los estudiantes suspendidos';
            allEstudiantes.value = [];
        }
    } finally {
        loading.value = false;
    }
};

const fetchByCareer = async (career_id) => {
    loading.value = true;
    error.value = null;

    try {
        const response = await axios.post(
            `/api/users/get/career/by-role/`,
            {
                rol_nombre: 'estudiante',
                carrera_id: career_id
            },
            {
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            }
        );

        const data = response.data;

        if (!data.success) {
            error.value = 'Error al obtener los estudiantes activos';
            allEstudiantes.value = [];
            return;
        }

        // Manejar la respuesta según la estructura
        const payload = data.data || data;
        const raw = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);

        allEstudiantes.value = raw.map(estudiantes => ({
            id: estudiantes.id ?? 'N/A',
            nombre_completo: estudiantes.nombre_completo ?? estudiantes.name ?? 'Unknown',
            email: estudiantes.email ?? 'N/A',
            telefono: estudiantes.telefono ?? estudiantes.phone ?? 'N/A',
            estado: estudiantes.estado ?? estudiantes.status ?? 'activo',
            departamento_id: estudiantes.departamento_id ?? null,
            carrera_id: estudiantes.carrera_id ?? null,
        }));

        error.value = null;

    } catch (err) {
        const status = err.response?.status;

        if (status === 404) {
            // No hay estudiantess activos, trátalo como lista vacía
            allEstudiantes.value = [];
            error.value = null;
        } else if (status === 401 || status === 403) {
            error.value = err.response?.data?.message || 'Acceso no autorizado. Verifica tu sesión.';
            allEstudiantes.value = [];
        } else {
            error.value = err.response?.data?.message || 'Error al obtener los estudiantes activos';
            allEstudiantes.value = [];
        }
    } finally {
        loading.value = false;
    }
};

const handleFetchByCareer = () => {
    // Lógica para manejar el cambio al ver por departamento
    fetchByCareer(formData.value.carrera_id);
};

const handleSelectFilter = () => {
    if (selectedOption.value === 'view-all' || selectedOption.value === '') {
        // Mostrar todos los estudiantess
        fetchEstudiantes();
    } else if (selectedOption.value === 'view-actives') {
        // Filtrar estudiantess activos
        fetchEnabledStudents();
    } else if (selectedOption.value === 'view-inactives') {
        // Filtrar estudiantess inactivos
        fetchDisabledStudents();
    } else if (selectedOption.value === 'view-suspended') {
        // Filtrar estudiantess suspendidos
        fetchSuspendedStudents();
    } else if (selectedOption.value === 'view-by-career') {
        fetchCarreras().then(() => {
            handleFetchByCareer();
        });
    }
};

onMounted(async () => {
    await authService.verifyToken(localStorage.getItem("token"));
    searchTerm.value = '';
    await Promise.all([
        fetchCarreras(),
        fetchEstudiantes()
    ]);
    isLoading.value = false;
});
</script>
