<template>
    <Head title="Materias" />

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
                <h1 class="text-2xl font-bold text-gray-900 mb-1" :style="{color:colorText}">Materias</h1>
                <p class="text-gray-600 text-sm">Listado de materias del sistema</p>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <input
                        v-model="searchTerm"
                        type="text"
                        placeholder="Buscar por código o nombre"
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <select
                        v-model="filterCarrera"
                        class="px-8 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">Todas las carreras</option>
                        <option v-for="carrera in carreras" :key="carrera.id" :value="carrera.id">
                            {{ carrera.nombre }}
                        </option>
                    </select>
                    <select
                        v-model="filterEstado"
                        class="px-8 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    >
                        <option value="">Todos los estados</option>
                        <option value="activa">Activas</option>
                        <option value="inactiva">Inactivas</option>
                    </select>
                    <button
                        @click="openCreateModal"
                        class="text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 whitespace-nowrap"
                        :style="{background: '#D93F3F'}"
                    >
                        <span class="text-xl">+</span>
                        Agregar Materia
                    </button>
                </div>
                <br>

                <div v-if="loading" class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600">Cargando materias...</p>
                </div>

                <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-6">
                    <p class="font-bold">Error al cargar</p>
                    <p>{{ error }}</p>
                </div>
                <br>

                <div v-if="!loading && materiasFiltradas.length" class="bg-white rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full" :style="{ border: '1px solid #d93f3f' }">
                            <thead class="bg-gray-50 border-b-2 border-gray-200 text-center" :style="{background: '#d93f3f', height: '40px'}">
                            <tr>
                                <th class="text-white px-4 py-2">Código</th>
                                <th class="text-white px-4 py-2">Nombre</th>
                                <th class="text-white px-4 py-2">Descripción</th>
                                <th class="text-white px-4 py-2">Carrera</th>
                                <th class="text-white px-4 py-2">Estado</th>
                                <th class="text-white px-4 py-2">Opciones</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-center align-middle">
                            <tr v-for="materia in paginatedMaterias" :key="materia.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ materia.codigo }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ materia.nombre }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">{{ materia.descripcion }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">{{ materia.carrera_nombre || 'N/A' }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span
                                        class="px-3 py-1 rounded-full text-xs font-semibold"
                                        :class="materia.estado === 'activa' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                    >
                                        {{ materia.estado }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex justify-center gap-2">
                                        <button
                                            @click="openEditModal(materia)"
                                            class="bg-green-500 hover:bg-green-800 text-white px-4 py-2 rounded-lg transition-colors"
                                            :disabled="loading"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            @click="deleteItem(materia.id)"
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

                <div v-else-if="!loading && !materiasFiltradas.length" class="bg-gray-100 border border-gray-400 text-gray-700 px-6 py-4 rounded-lg mb-6 text-center">
                    <p v-if="searchTerm === '' && filterEstado === '' && filterCarrera === ''">No hay materias registradas en el sistema.</p>
                    <p v-else>No se encontraron materias que coincidan con los filtros aplicados.</p>
                </div>
            </div>
        </div>
    </MainLayoutDashboard>

    <!-- Modal Crear/Editar -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">
                    {{ isEditMode ? 'Editar Materia' : 'Agregar Nueva Materia' }}
                </h2>
                <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Código <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="formData.codigo"
                            type="text"
                            maxlength="10"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.codigo}"
                            required
                        />
                        <p v-if="formErrors.codigo" class="text-red-500 text-sm mt-1">
                            {{ formErrors.codigo[0] }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="formData.nombre"
                            type="text"
                            maxlength="100"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.nombre}"
                            required
                        />
                        <p v-if="formErrors.nombre" class="text-red-500 text-sm mt-1">
                            {{ formErrors.nombre[0] }}
                        </p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Descripción
                    </label>
                    <textarea
                        v-model="formData.descripcion"
                        rows="3"
                        maxlength="255"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.descripcion}"
                    ></textarea>
                    <p v-if="formErrors.descripcion" class="text-red-500 text-sm mt-1">
                        {{ formErrors.descripcion[0] }}
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
                            <option value="activa">Activa</option>
                            <option value="inactiva">Inactiva</option>
                        </select>
                        <p v-if="formErrors.estado" class="text-red-500 text-sm mt-1">
                            {{ formErrors.estado[0] }}
                        </p>
                    </div>
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
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted, watch } from 'vue';
import axios from 'axios';
import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
import { authService } from "@/Services/authService.js";

const isLoading = ref(true);
const colorText = ref('#1F2937');
const searchTerm = ref('');
const filterEstado = ref('');
const filterCarrera = ref('');
const loading = ref(false);
const error = ref(null);
const allMaterias = ref([]);
const carreras = ref([]);
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
const currentMateriaId = ref(null);

// Datos del formulario
const formData = ref({
    codigo: '',
    nombre: '',
    descripcion: '',
    carrera_id: '',
    estado: 'activa',
});

// Filtrado
const materiasFiltradas = computed(() => {
    const data = Array.isArray(allMaterias.value) ? allMaterias.value : [];
    let filtered = data;

    // Filtrar por término de búsqueda
    if (searchTerm.value) {
        const term = searchTerm.value.toLowerCase();
        filtered = filtered.filter(materia =>
            materia.codigo.toLowerCase().includes(term) ||
            materia.nombre.toLowerCase().includes(term)
        );
    }

    // Filtrar por carrera
    if (filterCarrera.value) {
        filtered = filtered.filter(materia => materia.carrera_id == filterCarrera.value);
    }

    // Filtrar por estado
    if (filterEstado.value) {
        filtered = filtered.filter(materia => materia.estado === filterEstado.value);
    }

    return filtered;
});

// Paginación
const totalPages = computed(() => {
    return Math.ceil(materiasFiltradas.value.length / perPage.value);
});

const paginatedMaterias = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    const end = start + perPage.value;
    return materiasFiltradas.value.slice(start, end);
});

// Observa los cambios en filtros
watch([searchTerm, filterEstado, filterCarrera], () => {
    currentPage.value = 1;
});

// Funciones del Modal
const resetForm = () => {
    formData.value = {
        codigo: '',
        nombre: '',
        descripcion: '',
        carrera_id: '',
        estado: 'activa',
    };
    formErrors.value = {};
    currentMateriaId.value = null;
};

const openCreateModal = async () => {
    resetForm();
    isEditMode.value = false;
    await fetchCarreras();
    showModal.value = true;
};

const openEditModal = async (materia) => {
    console.log('Abriendo modal para editar:', materia);

    resetForm();
    isEditMode.value = true;
    currentMateriaId.value = materia.id;

    await fetchCarreras();

    // Cargar datos
    formData.value = {
        codigo: materia.codigo || '',
        nombre: materia.nombre || '',
        descripcion: materia.descripcion || '',
        carrera_id: materia.carrera_id || '',
        estado: materia.estado || 'activa',
    };

    console.log('Form data cargado:', formData.value);

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
        const payload = {
            codigo: formData.value.codigo,
            nombre: formData.value.nombre,
            descripcion: formData.value.descripcion || null,
            carrera_id: parseInt(formData.value.carrera_id),
            estado: formData.value.estado,
        };

        console.log("📦 Payload enviado:", JSON.stringify(payload, null, 2));

        const url = isEditMode.value
            ? `${API_URL}/subjects/edit/${currentMateriaId.value}`
            : `${API_URL}/subjects/new`;

        const response = isEditMode.value
            ? await axios.patch(url, payload, getAuthHeaders())
            : await axios.post(url, payload, getAuthHeaders());

        if (response.data.success || response.status === 200 || response.status === 201) {
            closeModal();
            await fetchMaterias();
            alert(
                isEditMode.value
                    ? "Materia actualizada exitosamente"
                    : "Materia creada exitosamente"
            );
        }
    } catch (err) {
        console.error("❌ Error al guardar materia:", err);

        const data = err.response?.data || {};

        if (data.errors) {
            formErrors.value = data.errors;
        } else {
            formErrors.value.general =
                data.message || data.error || "Error al guardar la materia";
        }
    } finally {
        submitting.value = false;
    }
};

const deleteItem = async (id) => {
    if (!confirm('¿Está seguro de eliminar esta materia? Esta acción no se puede deshacer.')) return;

    try {
        loading.value = true;
        console.log('Eliminando materia ID:', id);

        const response = await axios.delete(`${API_URL}/subjects/delete/${id}`, getAuthHeaders());

        console.log('Respuesta eliminación:', response.data);

        if (response.data.success || response.status === 200) {
            alert('Materia eliminada exitosamente');
            await fetchMaterias();
        }
    } catch (err) {
        console.error('Error al eliminar materia:', err);
        alert(err.response?.data?.message || 'Error al eliminar la materia');
    } finally {
        loading.value = false;
    }
};

// Mapa de carreras para búsqueda rápida.
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
async function fetchMaterias() {
    loading.value = true;
    error.value = null;

    try {
        const res = await axios.get(`${API_URL}/subjects/get/all`, getAuthHeaders());

        const payload = res.data?.data;
        const raw = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);

        allMaterias.value = raw.map(materia => ({
            id: materia.id ?? 'N/A',
            codigo: materia.codigo ?? 'N/A',
            nombre: materia.nombre ?? 'Sin nombre',
            descripcion: materia.descripcion ?? 'Sin descripción',
            carrera_id: materia.carrera_id ?? null,
            carrera_nombre: materia.carrera?.nombre || carrerasMap.value[materia.carrera_id] || 'N/A',
            estado: materia.estado ?? 'activa',
        }));

        error.value = null;

    } catch (err) {
        const status = err.response?.status;

        if (status === 404) {
            allMaterias.value = [];
            error.value = null;
        } else if (status === 401 || status === 403) {
            error.value = err.response?.data?.message || 'Acceso no autorizado. Verifica tu sesión/rol.';
            allMaterias.value = [];
        } else {
            error.value = err.response?.data?.message || 'Error al cargar las materias';
            allMaterias.value = [];
        }
    } finally {
        loading.value = false;
    }
}

async function fetchCarreras() {
    try {
        const res = await axios.get(`${API_URL}/careers/get/all`, getAuthHeaders());
        const payload = res.data?.data;
        carreras.value = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);
    } catch (err) {
        console.error('Error al cargar carreras:', err);
        carreras.value = [];
    }
}

onMounted(async () => {
    await authService.verifyToken(localStorage.getItem("token"));
    searchTerm.value = '';
    filterEstado.value = '';
    filterCarrera.value = '';
    await fetchCarreras(); // Cargar carreras primero para los filtros
    await fetchMaterias();
    isLoading.value = false;
});
</script>
