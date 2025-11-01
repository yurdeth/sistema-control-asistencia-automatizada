<template>
    <Head title="Tipos de Recursos" />

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
                <h1 class="text-2xl font-bold text-gray-900 mb-1" :style="{color:colorText}">Tipos de Recursos</h1>
                <p class="text-gray-600 text-sm">Listado de los tipos de recursos dentro de la facultad</p>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <input
                        v-model="searchTerm"
                        type="text"
                        placeholder="Buscar por nombre"
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <button
                        @click="openCreateModal"
                        class="text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 whitespace-nowrap"
                        :style="{background: '#ff9966'}"
                    >
                        <span class="text-xl">+</span>
                        Agregar Tipo
                    </button>
                </div>
                <br>

                <div v-if="loading" class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600">Cargando tipos de recursos...</p>
                </div>

                <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-6">
                    <p class="font-bold">Error al cargar</p>
                    <p>{{ error }}</p>
                </div>
                <br>

                <div v-if="!loading && tiposFiltrados.length" class="bg-white rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full" :style="{ border: '1px solid #d93f3f' }">
                            <thead class="bg-gray-50 border-b-2 border-gray-200 text-center" :style="{background: '#d93f3f', height: '40px'}">
                            <tr>
                                <th class="text-white px-4 py-2">Id</th>
                                <th class="text-white px-4 py-2">Nombre</th>
                                <th class="text-white px-4 py-2">Descripción</th>
                                <th class="text-white px-4 py-2">Icono</th>
                                <th class="text-white px-4 py-2">Opciones</th>
                            </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 text-center align-middle">
                            <tr v-for="tipo in paginatedTipos" :key="tipo.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ tipo.id }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ tipo.nombre }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">{{ tipo.descripcion }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <span v-if="!tipo.icono" class="text-gray-500">Sin icono</span>
                                    <img v-else :src="tipo.icono" alt="Icono" class="h-16 w-16 object-contain mx-auto border rounded p-1">
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex justify-center gap-2">
                                        <button
                                            @click="openEditModal(tipo)"
                                            class="bg-green-500 hover:bg-green-800 text-white px-4 py-2 rounded-lg transition-colors"
                                            :disabled="loading"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            @click="deleteItem(tipo.id)"
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
                                <i class="fas fa-chevron-left"></i> </button>

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
                                <i class="fas fa-chevron-right"></i> </button>
                        </div>
                    </div>
                </div>

                <div v-else-if="!loading && !tiposFiltrados.length" class="bg-gray-100 border border-gray-400 text-gray-700 px-6 py-4 rounded-lg mb-6 text-center">
                    <p v-if="searchTerm === ''">No hay tipos de recursos registrados en el sistema.</p>
                    <p v-else>No se encontraron tipos de recursos que coincidan con la búsqueda: <span class="text-red-500">"{{ searchTerm }}"</span></p>
                </div>
            </div>
        </div>
    </MainLayoutDashboard>

    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">
                    {{ isEditMode ? 'Editar Tipo de Recurso' : 'Agregar Nuevo Tipo de Recurso' }}
                </h2>
                <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="formData.nombre"
                        type="text"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.nombre}"
                        required
                    />
                    <p v-if="formErrors.nombre" class="text-red-500 text-sm mt-1">
                        {{ formErrors.nombre[0] }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Descripción <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="formData.descripcion"
                        rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.descripcion}"
                        required
                    ></textarea>
                    <p v-if="formErrors.descripcion" class="text-red-500 text-sm mt-1">
                        {{ formErrors.descripcion[0] }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Icono (URL de Imagen - Opcional)
                    </label>
                    <input
                        v-model="formData.icono"
                        type="text"
                        placeholder="https://www.google.com/imagen.png"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.icono}"
                    />
                    <p v-if="formErrors.icono" class="text-red-500 text-sm mt-1">
                        {{ formErrors.icono[0] }}
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

    import { Head } from '@inertiajs/vue3';
    import {ref, computed, onMounted, watch, nextTick} from 'vue';
    import axios from 'axios';
    import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
    import { authService } from "@/Services/authService.js";

    const isLoading = ref(true);
    const colorText = ref('#1F2937');
    const searchTerm = ref('');
    const loading = ref(false);
    const error = ref(null);
    const allTiposRecurso = ref([]);
    const isAuthenticated = localStorage.getItem('isAuthenticated');

    // Paginación
    const currentPage = ref(1);
    const perPage = ref(5);

    // Configuración de axios
    const API_URL = '/api';
    // const ENDPOINT_BASE = '/resource-types';
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
    const currentTipoId = ref(null);

    // Datos del formulario
    const formData = ref({
        nombre: '',
        descripcion: '',
        icono: null,
    });

    // Lógica de Filtrado y Paginación
    const tiposFiltrados = computed(() => {
        const data = Array.isArray(allTiposRecurso.value) ? allTiposRecurso.value : [];
        if (!searchTerm.value) return data;
        const term = searchTerm.value.toLowerCase();
        return data.filter(tipo =>
            tipo.nombre.toLowerCase().includes(term)
        );
    });

    const totalPages = computed(() => {
        return Math.ceil(tiposFiltrados.value.length / perPage.value);
    });

    const paginatedTipos = computed(() => {
        const start = (currentPage.value - 1) * perPage.value;
        const end = start + perPage.value;
        return tiposFiltrados.value.slice(start, end);
    });

    // Observa los tipos filtrados
    watch(tiposFiltrados, () => {
        currentPage.value = 1;
    });

    // Funciones del Modal
    const resetForm = () => {
        formData.value = {
            nombre: '',
            descripcion: '',
            icono: null,
        };
        formErrors.value = {};
        currentTipoId.value = null;
    };

    const openCreateModal = () => {
        resetForm();
        isEditMode.value = false;
        showModal.value = true;
    };

    const openEditModal = (tipo) => {
        console.log('Abriendo modal para editar:', tipo);

        resetForm();
        isEditMode.value = true;
        currentTipoId.value = tipo.id;

        // Cargar datos
        formData.value = {
            nombre: tipo.nombre || '',
            descripcion: tipo.descripcion || '',
            icono: tipo.icono || null,
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
            // Asegurarse de que el icono sea null si es string vacía
            const iconoValue = formData.value.icono ? formData.value.icono.toString().trim() : null;

            const payload = {
                nombre: formData.value.nombre,
                descripcion: formData.value.descripcion,
                icono: iconoValue,
            };

            console.log("📦 Payload enviado:", JSON.stringify(payload, null, 2));

            const url = isEditMode.value
                ? `${API_URL}/resource-types/edit/${currentTipoId.value}` : `${API_URL}/resource-types/new`;

            const response = isEditMode.value
                ? await axios.patch(url, payload, getAuthHeaders())
                : await axios.post(url, payload, getAuthHeaders());

            if (response.data.success || response.status === 200 || response.status === 201) {
                closeModal();
                await fetchTiposRecurso();

                alert(isEditMode.value
                    ? "Tipo de Recurso actualizado exitosamente"
                    : "Tipo de Recurso creado exitosamente");

                // setTimeout(() => {
                //     alert(isEditMode.value
                //         ? "Tipo de Recurso actualizado exitosamente"
                //         : "Tipo de Recurso creado exitosamente");
                // }, 100);
            }
        } catch (err) {
            console.error("❌ Error al guardar el Tipo de Recurso:", err);

            const data = err.response?.data || {};

            if (data.errors) {
                formErrors.value = data.errors; // errores de validación
            } else if (data.error) {
                formErrors.value.general = data.error;
            } else {
                formErrors.value.general =
                    data.message || data.error || "Error al guardar el Tipo de Recurso";
            }
        } finally {
            submitting.value = false;
        }
    };

    const deleteItem = async (id) => {
        if (!confirm('¿Está seguro de eliminar este Tipo de Recurso? Esta acción no se puede deshacer.')) return;

        try {
            loading.value = true;
            console.log('Eliminando Tipo de Recurso ID:', id);

            const response = await axios.delete(`${API_URL}/resource-types/delete/${id}`, getAuthHeaders());

            console.log('Respuesta eliminación:', response.data);

            if (response.data.success || response.status === 200) {
                alert('Tipo de Recurso eliminado exitosamente');
                await fetchTiposRecurso();
            }
        } catch (err) {
            console.error('Error al eliminar Tipo de Recurso:', err);
            alert(err.response?.data?.message || 'Error al eliminar el Tipo de Recurso');
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

    // Cargar datos (Tipos de Recursos)
    async function fetchTiposRecurso() {
        loading.value = true;
        error.value = null;

        try {
            const res = await axios.get(`${API_URL}/resource-types/get/all`, getAuthHeaders());

            const payload = res.data?.data;
            const raw = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);

            console.log("Datos recibidos: ", res.data);

            allTiposRecurso.value = raw.map(tipo => ({
                id: tipo.id ?? 'N/A',
                nombre: tipo.nombre ?? 'Unknown',
                descripcion: tipo.descripcion ?? 'Sin descripción',
                icono: tipo.icono ?? null,
            }));

            error.value = null;

        } catch (err) {
            const status = err.response?.status;

            if (status === 404) {
                allTiposRecurso.value = [];
                error.value = null;
            } else if (status === 401 || status === 403) {
                error.value = err.response?.data?.message || 'Acceso no autorizado. Verifica tu sesión/rol.';
                allTiposRecurso.value = [];
            } else {
                error.value = err.response?.data?.message || 'Error al cargar los tipos de recurso';
                allTiposRecurso.value = [];
            }
        } finally {
            loading.value = false;
        }
    }

    onMounted(async () => {
        await authService.verifyToken(localStorage.getItem("token"));
        searchTerm.value = '';
        await fetchTiposRecurso();
        isLoading.value = false;
    });

</script>
