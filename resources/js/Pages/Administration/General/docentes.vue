<template>
    <Head title="Docentes" />

    <!-- Loader mientras verifica -->
    <div v-if="isLoading" class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-gray-900 mx-auto"></div>
            <p class="mt-4 text-gray-600 text-lg">Verificando sesión...</p>
        </div>
    </div>

    <MainLayoutDashboard>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-1" :style="{color:colorText}">Docentes</h1>
                <p class="text-gray-600 text-sm">Listado de los docentes dentro de la facultad</p>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <input
                        v-model="searchTerm"
                        type="text"
                        placeholder="Buscar por nombre o email"
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <button
                        @click="openCreateModal"
                        class="text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 whitespace-nowrap"
                        :style="{background: '#ff9966'}"
                    >
                        <span class="text-xl">+</span>
                        Agregar
                    </button>

                    <label
                        for="fileUpload"
                        class="cursor-pointer bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors whitespace-nowrap"
                    >
                        <i class="fa-solid fa-file-excel"></i>
                        Subir Excel
                    </label>
                    <input id="fileUpload" type="file" accept=".xlsx, .xls" class="hidden" @change="handleExcelUpload" />
                </div>
                <br>

                <div v-if="loading" class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600">Cargando docentes...</p>
                </div>

                <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-6">
                    <p class="font-bold">Error al cargar</p>
                    <p>{{ error }}</p>
                </div>
                <br>

                <div v-if="!loading && docentesPaginados.length" class="bg-white rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full" :style="{ border: '1px solid #d93f3f' }">
                            <thead class="bg-gray-50 border-b-2 border-gray-200 text-center" :style="{background: '#d93f3f', height: '40px'}">
                                <tr>
                                    <th class="text-white px-4 py-2">Id</th>
                                    <th class="text-white px-4 py-2">Nombre</th>
                                    <th class="text-white px-4 py-2">Email</th>
                                    <th class="text-white px-4 py-2">Teléfono</th>
                                    <th class="text-white px-4 py-2">Estado</th>
                                    <th class="text-white px-4 py-2">Opciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-center align-middle">
                                <tr v-for="docente in docentesPaginados" :key="docente.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ docente.id }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ docente.nombre_completo }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ docente.email }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ docente.telefono }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ docente.estado }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex justify-center gap-2">
                                            <button
                                                @click="openEditModal(docente)"
                                                class="bg-green-500 hover:bg-green-800 text-white px-4 py-2 rounded-lg transition-colors"
                                                :disabled="loading"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                @click="deleteItem(docente.id)"
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

                    <div class="flex justify-center gap-2 mt-4 pb-4" v-if="totalPaginas >= 1">
                        <button
                            @click="paginaAnterior"
                            :disabled="paginaActual === 1"
                            class="px-3 py-1 border rounded disabled:opacity-50 hover:bg-gray-100 transition-colors"
                        >
                            ←
                        </button>
                        <button
                            class="px-3 py-1 border rounded transition-colors bg-red-500 text-white hover:bg-red-600"
                        >
                            {{ paginaActual }}
                        </button>
                        <button
                            @click="paginaSiguiente"
                            :disabled="paginaActual === totalPaginas"
                            class="px-3 py-1 border rounded disabled:opacity-50 hover:bg-gray-100 transition-colors"
                        >
                            →
                        </button>
                    </div>
                </div>

                <div v-else-if="!loading && !docentesFiltrados.length" class="bg-gray-100 border border-gray-400 text-gray-700 px-6 py-4 rounded-lg mb-6 text-center">
                    <p v-if="searchTerm === ''">No hay docentes registrados en el sistema.</p>
                    <p v-else>No se encontraron docentes que coincidan con la búsqueda: **"{{ searchTerm }}"**.</p>
                </div>
                <div v-else-if="!loading && !allDocentes.length" class="bg-gray-100 border border-gray-400 text-gray-700 px-6 py-4 rounded-lg mb-6 text-center">
                    <p>No hay docentes registrados en el sistema.</p>
                </div>
            </div>
        </div>
    </MainLayoutDashboard>
</template>

<script setup>
    import { Head } from '@inertiajs/vue3';
    import { ref, computed, onMounted, watch } from 'vue';
    import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
    import { getDocentesAll } from '@/Services/docentesService.js';
    import {authService} from "@/Services/authService.js";

    const isLoading = ref(true);
    // --- Estado General ---
    const colorText = ref('#1F2937');
    const searchTerm = ref('');
    const loading = ref(false);
    const error = ref(null);
    const allDocentes = ref([]);
    const openCreateModal = () => { console.log('Abrir Crear'); };
    const openEditModal = (docente) => { console.log('Abrir Editar', docente.id); };
    const deleteItem = (id) => { console.log('Eliminar', id); };
    const handleExcelUpload = (event) => { console.log('Subir Excel', event.target.files[0]); };

    // --- Filtrado ---
    const docentesFiltrados = computed(() => {
        const data = Array.isArray(allDocentes.value) ? allDocentes.value : [];
        if (!searchTerm.value) return data;
        const term = searchTerm.value.toLowerCase();
        return data.filter(docente =>
            docente.nombre_completo.toLowerCase().includes(term) ||
            docente.email.toLowerCase().includes(term)
        );
    });

    // --- Paginación ---
    const paginaActual = ref(1);
    const itemsPorPagina = ref(5);
    const totalPaginas = computed(() => Math.ceil(docentesFiltrados.value.length / itemsPorPagina.value));
    const indiceInicio = computed(() => (paginaActual.value - 1) * itemsPorPagina.value);
    const indiceFin = computed(() => paginaActual.value * itemsPorPagina.value);
    const docentesPaginados = computed(() => docentesFiltrados.value.slice(indiceInicio.value, indiceFin.value));

    // Funciones de control de paginación
    const irAPagina = (num) => {
        if (num >= 1 && num <= totalPaginas.value) {
            paginaActual.value = num;
            console.log('Navigated to page:', paginaActual.value);
        }
    };

    const paginaAnterior = () => { irAPagina(paginaActual.value - 1); };
    const paginaSiguiente = () => { irAPagina(paginaActual.value + 1); };

    // Observa cambios en la lista filtrada y resetea la página a 1
    watch(docentesFiltrados, () => {
        console.log('docentesFiltrados.length:', docentesFiltrados.value.length);
        console.log('itemsPorPagina:', itemsPorPagina.value);
        console.log('totalPaginas:', totalPaginas.value);
        paginaActual.value = 1;
    });

    // --- Carga de Datos ---
    async function fetchDocentes() {
        try {
            loading.value = true;
            const response = await getDocentesAll();
            console.log('API Response:', response);

            const data = response && response.data
                ? Object.values(response.data)
                : [];

            console.log('Processed Data:', data);
            allDocentes.value = data.map(docente => ({
                id: docente.id || 'N/A',
                nombre_completo: docente.nombre_completo || docente.name || 'Unknown',
                email: docente.email || 'N/A',
                telefono: docente.telefono || docente.phone || 'N/A',
                estado: docente.estado || docente.status || 'N/A'
            }));
            console.log('Mapped allDocentes:', allDocentes.value);
        } catch (err) {
            console.error('Error al obtener los docentes:', err);
            error.value = 'Error al cargar los docentes';
            allDocentes.value = [];
        } finally {
            loading.value = false;
        }
    }

    onMounted(async () => {
        await authService.verifyToken(localStorage.getItem("token"));

        searchTerm.value = '';
        await fetchDocentes();
        isLoading.value = false;
    });

</script>
