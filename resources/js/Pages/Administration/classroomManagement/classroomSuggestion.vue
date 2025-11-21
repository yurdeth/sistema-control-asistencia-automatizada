<template>
    <Head title="Sugerencias"/>

    <Loader
        v-if="!isAuthenticated"
        @authenticated="handleAuthenticated"
        message="Verificando sesión..."
        :redirectDelay="2000"
    />

    <MainLayoutDashboard>
        <div class="p-6" v-if="isAuthenticated">
            <!-- Header de la vista-->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-1" :style="{color:colorText}">Sugerencia de Aulas</h1>
                <p class="text-gray-600 text-sm">Encuentra el aula perfecta para tus necesidades dentro de la facultad</p>
            </div>
        </div>

            <!-- Mensajes de alerta -->
            <div v-if="mensaje.texto" class="mb-4">
                <div
                    :class="mensaje.tipo === 'success' ? 'bg-green-50 border-green-500 text-green-700' : 'bg-red-50 border-red-500 text-red-700'"
                    class="border-l-4 p-4 rounded"
                >
                    <p class="text-sm">{{ mensaje.texto }}</p>
                </div>
            </div>

            <!-- Indicador de carga de aulas -->
            <div v-if="cargandoAulas" class="mb-4">
                <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 rounded">
                    <p class="text-sm">Cargando aulas disponibles...</p>
                </div>
            </div>

        <!--Formulario de búsqueda-->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="space-y-6">
                <!-- Tamaño del grupo -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fa-solid fa-users"></i>
                        Tamaño del Grupo
                    </label>
                    <input
                        v-model.number="groupSize"
                        type="number"
                        min="1"
                        placeholder="Número de personas"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    />
                </div>

                <!-- Recursos requeridos con select múltiple -->
                <div>
                    <select
                        v-model="selectedResources"
                        multiple
                        :disabled="loading || recursosDisponibles.length === 0"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent min-h-[150px] disabled:bg-gray-100 disabled:cursor-not-allowed"
                    >
                        <option
                            v-for="recurso in recursosDisponibles"
                                :key="recurso"
                                :value="recurso"
                                class="py-2"
                        >
                            {{ recurso }}
                        </option>
                    </select>

                    <p v-if="!loading && recursosDisponibles.length === 0" class="text-xs text-gray-500 mt-2">
                        No hay recursos disponibles
                    </p>
                    <p v-else class="text-xs text-gray-500 mt-2">
                        Mantén presionado Ctrl (Windows) o Cmd (Mac) para seleccionar múltiples opciones
                    </p>

                    <!-- Recursos seleccionados -->
                    <div v-if="selectedResources.length > 0" class="mt-3 flex flex-wrap gap-2">
                        <span
                            v-for="recurso in selectedResources"
                            :key="recurso"
                            class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm"
                        >
                            {{ recurso }}
                            <button
                                @click="removeResource(recurso)"
                                class="hover:text-indigo-900"
                            >
                                <i class="fa-solid fa-xmark"></i>
                                </button>
                        </span>

                    </div>
                </div>

                    <!-- Botón de búsqueda -->
                    <button
                        @click="handleSearch"
                        :disabled="!groupSize || groupSize <= 0 || cargandoAulas || aulas.length === 0"
                        class="w-full py-3 px-6 rounded-lg font-semibold transition-colors flex items-center justify-center gap-2"
                        :class="groupSize && groupSize > 0
                            ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                            : 'bg-gray-300 text-gray-500 cursor-not-allowed'"
                    >
                        {{ cargandoAulas ? 'Cargando aulas...' : 'Buscar Aulas Disponibles' }}
                    </button>
                    <p v-if="aulas.length === 0 && !cargandoAulas" class="text-sm text-amber-600 text-center mt-2">
                        No hay aulas disponibles para buscar
                    </p>
            </div>
        </div>

        <!-- Resultados -->
        <div v-if="showResults" class="space-y-6">
            <!-- Coincidencias perfectas -->
            <div v-if="perfectMatches.length > 0">
                <h2 class="text-2xl font-bold mb-4 flex items-center gap-2">
                    Coincidencias ({{ perfectMatches.length }})
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="aula in visiblePerfectMatches"
                        :key="aula.id"
                        class="relative flex flex-col gap-4 bg-white p-6 rounded-lg shadow-sm border border-gray-200"
                    >

                        <span
                            class="absolute top-2 right-2 bg-green-100 text-green-700  px-3 py-1 rounded-full text-sm font-semibold"
                        >
                            Recomendado
                        </span>

                        <h3 class="text-xl font-bold text-gray-800">
                            {{ aula.nombre }}
                        </h3>

                        <hr>
                        <!-- Información general -->
                        <div class="flex flex-col gap-1 text-sm text-gray-700">
                            <p class="font-medium">
                                <i class="fa-solid fa-users"></i>
                                Capacidad: {{ aula.capacidadReal }}
                            </p>
                            <p class="text-gray-600">
                                <i class="fa-solid fa-users"></i>
                                Tu grupo: {{ groupSize }}
                            </p>
                        </div>

                        <!-- Recursos -->
                        <div>
                            <p class="text-sm font-semibold text-gray-700 mb-2">
                                Recursos:
                            </p>

                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="recurso in aula.recursos"
                                    :key="recurso"
                                    :class="[
                                        'px-2 py-1 rounded text-xs font-medium',
                                        selectedResources.includes(recurso)
                                            ? 'bg-green-100 text-green-700'
                                            : 'bg-gray-100 text-gray-600'
                                    ]"
                                >
                                    {{ recurso }}
                                </span>
                            </div>
                        </div>

                        <!-- Botónes -->
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                @click="abrirModal({ aula, modo: 'ver' })"
                                class="text-white hover:bg-blue-700 px-3 py-2 rounded flex items-center justify-center gap-1 transition-colors text-sm font-medium"
                                :style="{ background: colorButtons.ver }"
                            >
                                <i class="fa-solid fa-eye"></i>
                                Ver
                            </button>
                            <button
                                @click="solicitarAula(aula)"
                                class="text-white hover:bg-yellow-600 px-3 py-2 rounded flex items-center justify-center gap-1 transition-colors text-sm font-medium"
                                :style="{ background: colorButtons.editar }"
                            >
                                Solicitar
                            </button>
                        </div>

                    </div>
                </div>

                <!-- Botón VER MÁS -->
                <div v-if="perfectMatches.length > visiblePerfect" class="text-center mt-4">
                    <button @click="showMorePerfect" class="px-6 py-2 bg-green-600 text-white rounded-lg">
                        Ver más
                    </button>
                </div>

            </div>

            <!-- Opciones alternativas -->
            <div v-if="partialMatches.length > 0">
                <h2 class="text-2xl font-bold text-yellow-700 mb-4 flex items-center gap-2">
                    Opciones Alternativas ({{ partialMatches.length }})
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div
                        v-for="aula in visiblePartialMatches"
                        :key="aula.id"
                        class="bg-white rounded-xl shadow-md p-6 border-2 border-yellow-400"
                    >
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">{{ aula.nombre }}</h3>
                            </div>
                            <div class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-sm font-semibold">
                                Alternativa
                            </div>
                        </div>
                        <div class="flex items-center gap-4 mb-3">
                            <span class="text-sm font-medium text-gray-700">
                                <i class="fa-solid fa-users"></i>
                                Capacidad: {{ aula.capacidadReal }} personas
                            </span>
                        </div>
                        <div v-if="aula.missingResources.length > 0" class="mb-3 p-3 bg-yellow-50 rounded-lg">
                            <p class="text-sm font-semibold text-yellow-800 mb-1">Recursos faltantes:</p>
                            <p class="text-sm text-yellow-700">{{ aula.missingResources.join(', ') }}</p>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-gray-700 mb-2">Recursos disponibles:</p>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="recurso in aula.recursos"
                                    :key="recurso"
                                    class="px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-600"
                                >
                                    {{ recurso }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="partialMatches.length > visiblePartial" class="text-center mt-4">
                <button @click="showMorePartial" class="px-6 py-2 bg-yellow-600 text-white rounded-lg">
                    Ver más
                </button>
            </div>

                <!-- No recomendadas -->
                <div v-if="notRecommended.length > 0">
                    <h2 class="text-2xl font-bold text-red-700 mb-4 flex items-center gap-2">
                        No Recomendadas ({{ notRecommended.length }})
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div
                            v-for="aula in visibleNotRecommendedMatches"
                            :key="aula.id"
                            class="bg-white rounded-xl shadow-md p-6 border-2 border-red-300 opacity-75"
                        >
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-800">{{ aula.nombre }}</h3>

                                </div>
                            </div>

                            <div class="mb-3 p-3 bg-red-50 rounded-lg">
                                <p class="text-sm font-semibold text-red-800">
                                    Capacidad insuficiente: {{ aula.capacidadReal }} personas
                                </p>
                                <p class="text-sm text-red-700">
                                    Necesitas: {{ groupSize }} (faltan {{ groupSize - aula.capacidadReal }})
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botón VER MÁS -->
                <div v-if="notRecommended.length > visibleNotRecommended" class="text-center mt-4">
                    <button @click="showMoreNotRecommended" class="px-6 py-2 bg-red-600 text-white rounded-lg">
                        Ver más
                    </button>
                </div>

                <!-- Sin resultados -->
                <div v-if="suggestions.length === 0" class="bg-white rounded-xl shadow-md p-8 text-center">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <p class="text-xl text-gray-600">No se encontraron aulas disponibles</p>
                </div>
        </div>
    </MainLayoutDashboard>

</template>

<script setup>
    import {Head} from '@inertiajs/vue3';
    import {ref, onMounted, computed} from 'vue';
    import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
    import {authService} from "@/Services/authService.js";
    import Loader from '@/Components/AdministrationComponent/Loader.vue';
    import browserImageCompression from 'browser-image-compression';
    import axios from "axios";

    //Colores para buttons
    const colorButtons = {
        ver: '#D93F3F',        // Ver detalles
        editar: '#FE6244',     // Naranja - Editar
        reservar: '#FF6C0C',   // Verde - Reservar/Aprobar
        eliminar: '#DC2626',   // Rojo - Eliminar
        cancelar: '#6B7280',   // Gris - Cancelar
    };

    // Configuración de axios
    const API_URL = '/api';
    const getAuthHeaders = () => ({
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    });

    // Estado de autenticación
    const isAuthenticated = ref(false);

    // Estado del formulario
    const groupSize = ref(null);
    const selectedResources = ref([]);
    const showResults = ref(false);

    // Estado para recursos desde la API
    const allTiposRecurso = ref([]);
    const loading = ref(false);
    const error = ref(null);

    // Estado para aulas desde la API
    const cargandoAulas = ref(false);
    const errorAulas = ref(null);
    const mensaje = ref({ tipo: '', texto: '' });

    // Control de cantidad de aulas visibles por categoría
    const itemsPerPage = 10; // máximo inicial
    const visiblePerfect = ref(itemsPerPage);
    const visiblePartial = ref(itemsPerPage);
    const visibleNotRecommended = ref(itemsPerPage);

    // Base de datos de aulas (desde la API)
    const aulas = ref([]);

    // Recursos disponibles (computed desde la API)
    const recursosDisponibles = computed(() => {
        return allTiposRecurso.value.map(tipo => tipo.nombre);
    });

    // Función para mostrar más elementos
    const showMorePerfect = () => (visiblePerfect.value += itemsPerPage);
    const showMorePartial = () => (visiblePartial.value += itemsPerPage);
    const showMoreNotRecommended = () => (visibleNotRecommended.value += itemsPerPage);

    // Computed properties
    const suggestions = computed(() => {
        if (!showResults.value || !groupSize.value || groupSize.value <= 0) {
            return [];
        }

        return aulas.value
            .map(aula => {

                // Soporte para ambas propiedades
                const capacidadReal = aula.capacidad ?? aula.capacidad_pupitres ?? 0;

                // No mostramos las aulas demasiado grandes
                const exceso = capacidadReal - groupSize.value;
                const capacidadPermitida = exceso <= 15; // máximo 10-15 recomendado

                const hasAllResources = selectedResources.value.every(
                    r => aula.recursos.includes(r)
                );

                const capacityMatch = capacidadReal >= groupSize.value;
                const capacityEfficiency = capacityMatch
                    ? groupSize.value / capacidadReal
                    : 0;

                let score = 0;
                if (capacityMatch) score += 50;
                if (hasAllResources) score += 30;
                score += capacityEfficiency * 20;

                return {
                    ...aula,
                    capacidadReal,
                    exceso,
                    capacidadPermitida,
                    hasAllResources,
                    capacityMatch,
                    missingResources: selectedResources.value.filter(
                        r => !aula.recursos.includes(r)
                    ),
                    score
                };
            })
            .filter(a => a.capacidadPermitida) //elimina aulas demasiado grandes
            .sort((a, b) => b.score - a.score);
    });

    // Coincidencias perfectas (capacidad + recursos)
    const perfectMatches = computed(() =>
        suggestions.value.filter(s => s.capacityMatch && s.hasAllResources)
    );

    // Coincidencias parciales (capacidad OK pero faltan recursos)
    const partialMatches = computed(() =>
        suggestions.value.filter(s => s.capacityMatch && !s.hasAllResources)
    );

    // No recomendadas (capacidad insuficiente)
    const notRecommended = computed(() =>
        suggestions.value.filter(s => !s.capacityMatch)
    );

    // ======== Paginación aplicada ======== //
    const visiblePerfectMatches = computed(() =>
        perfectMatches.value.slice(0, visiblePerfect.value)
    );

    const visiblePartialMatches = computed(() =>
        partialMatches.value.slice(0, visiblePartial.value)
    );

    const visibleNotRecommendedMatches = computed(() =>
        notRecommended.value.slice(0, visibleNotRecommended.value)
    );

    // Métodos
    const handleAuthenticated = (status) => {
        isAuthenticated.value = status;
        if (status) {
            fetchTiposRecurso();
            cargarAulas();
        }
    };


    // Función para mostrar mensajes
    const mostrarMensaje = (tipo, texto) => {
        mensaje.value = { tipo, texto };
        setTimeout(() => {
            mensaje.value = { tipo: '', texto: '' };
        }, 5000);
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

    // Cargando aulas desde la API
    const cargarAulas = async () => {
        cargandoAulas.value = true;
        errorAulas.value = null;

        try {
            const response = await axios.get('/api/classrooms/get/all', {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            if (response.data.success) {
                aulas.value = response.data.data;
                console.log(aulas.value[0]);

                if (aulas.value.length === 0) {
                    mostrarMensaje('success', 'No hay aulas registradas. Ejecuta el seeder para agregar aulas de prueba.');
                } else {
                    mostrarMensaje('success', `${aulas.value.length} aulas cargadas exitosamente`);
                }
            } else {
                throw new Error(response.data.message || 'Error al cargar las aulas');
            }
        } catch (err) {

            if (err.response?.status === 404) {
                errorAulas.value = 'Ruta no encontrada. Verifica que las rutas estén en api.php';
            } else if (err.response?.status === 401) {
                errorAulas.value = 'No autorizado. Inicia sesión para ver las aulas.';
            } else if (err.response?.status === 500) {
                errorAulas.value = err.response?.data?.error || 'Error interno del servidor';
            } else {
                errorAulas.value = err.response?.data?.message || err.message || 'Error al conectar con el servidor';
            }

            mostrarMensaje('error', errorAulas.value);
        } finally {
            cargandoAulas.value = false;
        }
    };

    const handleSearch = () => {
        if (groupSize.value && groupSize.value > 0) {
            showResults.value = true;
        }
    };

    const removeResource = (recurso) => {
        selectedResources.value = selectedResources.value.filter(r => r !== recurso);
    };
</script>
