<template>
    <Head title="Disponibilidad"/>

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
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900 mb-1" :style="{color:colorText}"> Disponibilidad
                    de Aulas </h1>
                <p class="text-gray-600 text-xs sm:text-sm">
                    Visualice y gestione las aulas ocupadas o disponibles dentro de la facultad
                </p>
            </div>

            <!--Parte del buscador, filtros y opción de agregar-->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-4 md:p-6 mb-4 sm:mb-6">
                <div class="flex flex-col gap-3 sm:gap-4">
                    <!-- Buscador -->
                    <div class="w-full">
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input
                                v-model="busquedaAula"
                                type="text"
                                placeholder="Buscar aula por nombre, código o sector..."
                                class="w-full pl-10 pr-4 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm"
                            >
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                            <!--Para filtrar por lo sectores o zonas-->
                            <select
                                v-model="sectorSeleccionado"
                                class="w-full sm:w-48 px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-xs sm:text-sm"
                            >
                                <option value="all">Todos los Sectores</option>
                                <option
                                    v-for="sector in sectores"
                                    :key="sector.id"
                                    :value="sector.id"
                                >
                                    {{ sector.nombre }}
                                </option>
                            </select>

                            <!--Para filtrar según el estado-->
                            <select
                                v-model="estadoFiltro"
                                class="w-full sm:w-48 px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-xs sm:text-sm"
                            >
                                <option value="disponibles">Aulas Disponibles</option>
                                <option value="ocupadas">Aulas Ocupadas</option>
                                <option value="todas">Todas las Aulas</option>
                            </select>

                            <button
                                @click="abrirModalReserva"
                                class="px-6 py-2 text-white rounded-lg transition-colors text-sm font-medium whitespace-nowrap hover:opacity-90"
                                :style="{background:colorButton}"
                            >
                                Nueva Reserva de Aula +
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-sm text-gray-600 mb-1">Total Aulas</div>
                    <div class="text-2xl font-bold text-gray-900">{{ aulas.length }}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-sm text-gray-600 mb-1">Total Reservas</div>
                    <div class="text-2xl font-bold text-blue-600">{{ totalReservas }}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-sm text-gray-600 mb-1">Aulas Disponibles</div>
                    <div class="text-2xl font-bold text-green-600">{{ aulasDisponiblesCount }}</div>
                </div>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    <div class="text-sm text-gray-600 mb-1">Aulas Ocupadas</div>
                    <div class="text-2xl font-bold text-red-600">{{ aulasOcupadasCount }}</div>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="cargando" class="text-center py-12 bg-white rounded-lg border border-gray-200">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <!--Parte donde se trabaja el encabezado y la tabla que mostrara las aulas-->
            <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">
                        {{
                            estadoFiltro === 'disponibles' ? 'Aulas Disponibles para Asignar' :
                                estadoFiltro === 'ocupadas' ? 'Aulas Ocupadas' : 'Todas las Aulas'
                        }}
                    </h2>
                    <p class="text-xs sm:text-sm text-gray-600 mt-1">{{ aulasFiltradas.length }} aulas encontradas</p>
                </div>

                <!--Tabla con scroll horizontal en móviles-->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                        <tr>
                            <th scope="col"
                                class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Aula
                            </th>
                            <!--                                <th scope="col" class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                                                Ubicación
                                                            </th>-->
                            <th scope="col"
                                class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Capacidad
                            </th>
                            <!--                                <th scope="col" class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden md:table-cell">
                                                                Tipo
                                                            </th>-->
                            <th scope="col"
                                class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Estado
                            </th>
                            <!--                                <th scope="col" class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden lg:table-cell">
                                                                Recursos
                                                            </th>-->
                            <th scope="col"
                                class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                Acciones
                            </th>
                        </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="aula in aulasPaginadas" :key="aula.id" class="hover:bg-gray-50 transition-colors">
                            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2 sm:gap-3">
                                    <div
                                        class="flex-shrink-0 h-8 w-8 sm:h-10 sm:w-10 rounded-lg flex items-center justify-center text-xs sm:text-base"
                                        :class="aula.disponible ? 'bg-blue-100' : 'bg-red-100'">
                                        <i class="fa-solid fa-hotel"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs sm:text-sm font-medium text-gray-900 truncate">
                                            {{ aula.nombre }}
                                        </div>
                                        <div class="text-xs text-gray-500">{{ aula.codigo }}</div>
                                    </div>
                                </div>
                            </td>

                            <!--                                <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 whitespace-nowrap">
                                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                                                    {{ aula.sector }}
                                                                </span>
                                                            </td>-->

                            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 whitespace-nowrap">
                                <div class="flex items-center gap-1">
                                    <i class="fa-solid fa-people-group text-xs sm:text-sm"></i>
                                    <span class="text-xs sm:text-sm text-gray-900">{{ aula.capacidad_pupitres }}</span>
                                </div>
                            </td>

                            <!--                                <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-500 hidden md:table-cell">
                                                                {{ aula.tipo }}
                                                            </td>-->

                            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 whitespace-nowrap">
                                    <span v-if="aula.disponible"
                                          class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Disponible
                                    </span>
                                <span v-else
                                      class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Ocupada
                                    </span>
                            </td>

                            <!--                                <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 hidden lg:table-cell">
                                                                <div class="flex flex-wrap gap-1">
                                                                    <span v-for="equipo in aula.equipamiento" :key="equipo" class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">
                                                                        {{ equipo }}
                                                                    </span>
                                                                </div>
                                                            </td>-->

                            <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 whitespace-nowrap text-right text-xs sm:text-sm font-medium">
                                <button
                                    @click="abrirModal({ aula, modo: 'ver' })"
                                    class="text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg hover:opacity-90 transition-opacity text-xs sm:text-sm"
                                    :style="{background:colorViewButton}"
                                >
                                    Ver detalles
                                </button>
                                <button
                                    v-if="aula.disponible"
                                    @click="asignarAula(aula)"
                                    class="text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg hover:opacity-90 transition-opacity text-xs sm:text-sm"
                                    :style="{background:colorButton}"
                                >
                                    Asignar
                                </button>
                                <button
                                    v-else
                                    @click="liberarAula(aula)"
                                    class="bg-green-600 text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg hover:bg-green-700 transition-colors text-xs sm:text-sm"
                                >
                                    Liberar
                                </button>
                            </td>
                        </tr>

                        <!-- Estado vacío -->
                        <tr v-if="aulasFiltradas.length === 0">
                            <td colspan="7" class="px-4 sm:px-6 py-8 sm:py-12 text-center">
                                <i class="fa-solid fa-face-meh text-3xl sm:text-4xl" :style="{color: colorButton}"></i>
                                <p class="mt-3 sm:mt-4 text-xs sm:text-sm text-gray-500">No se encontraron aulas</p>
                                <p class="text-xs text-gray-400 mt-1">Intente ajustar los filtros de búsqueda</p>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="aulasFiltradas.length > 0"
                     class="bg-white px-3 sm:px-4 md:px-6 py-3 flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 gap-3">
                    <!-- Info de registros (oculta en móvil) -->
                    <div class="hidden sm:block">
                        <p class="text-xs sm:text-sm text-gray-700">
                            Mostrando
                            <span class="font-medium">{{ indiceInicio + 1 }}</span>
                            a
                            <span class="font-medium">{{ Math.min(indiceFin, aulasFiltradas.length) }}</span>
                            de
                            <span class="font-medium">{{ aulasFiltradas.length }}</span>
                            resultados
                        </p>
                    </div>

                    <!-- Controles de paginación -->
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <button
                            @click="paginaAnterior"
                            :disabled="paginaActual === 1"
                            class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-xs sm:text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span class="sr-only">Anterior</span>
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>

                        <button
                            v-for="pagina in paginasVisibles"
                            :key="pagina"
                            @click="irAPagina(pagina)"
                            :class="[
                                pagina === paginaActual
                                    ? 'z-10 border-blue-500 text-blue-600 bg-blue-50'
                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                'relative inline-flex items-center px-3 sm:px-4 py-2 border text-xs sm:text-sm font-medium'
                            ]"
                        >
                            {{ pagina }}
                        </button>

                        <button
                            @click="paginaSiguiente"
                            :disabled="paginaActual === totalPaginas"
                            class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-xs sm:text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span class="sr-only">Siguiente</span>
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    </MainLayoutDashboard>

    <Modal :show="showModal" @close="cerrarModal">
        <div>
            <p><strong>Nombre:</strong> {{ aula.nombre }}</p>
            <p><strong>Capacidad:</strong> {{ aula.capacidad_pupitres }}</p>
            <p><strong>Ubicación:</strong> {{ aula.ubicacion }}</p>
            <p><strong>Recursos:</strong> {{ aula.recurso }}</p>
            <p><strong>Estado:</strong> {{ aula.estado }}</p>

            <p class="mt-4"><strong>Imágenes del aula:</strong></p>

            <!-- Grid de imágenes con scroll horizontal -->
            <div class="overflow-x-auto overflow-y-hidden">
                <div class="flex gap-2 pb-2">
                    <div
                        v-for="(foto, index) in aula.fotos"
                        :key="foto.id"
                        class="flex-shrink-0"
                    >
                        <img
                            :src="foto.url"
                            alt="Imagen del aula"
                            class="w-32 h-32 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity border-2 border-gray-200 hover:border-blue-500"
                            @click="abrirLightbox(index)"
                        />
                    </div>
                </div>
            </div>
        </div>
    </Modal>

    <!-- Lightbox/Carrusel -->
    <Modal :show="mostrarLightbox" @close="cerrarLightbox">
        <LightboxModal
            :imagenes="aula.fotos"
            :indiceInicial="imagenActual"
            @close="cerrarLightbox"
        />
    </Modal>
</template>

<script setup>
//Imports a utilizar
import {Head, Link} from '@inertiajs/vue3';
import {ref, computed, onMounted} from 'vue';
import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
import {authService} from "@/Services/authService.js";
import AulaModalContent from "@/Components/AdministrationComponent/AulaModalContent.vue";
import Modal from "@/Components/Modal.vue";
import LightboxModal from "@/Components/AdministrationComponent/LightboxModal.vue";

const isLoading = ref(true);
// Constantes reactivas para los colores de la interfaz
const colorText = ref('#2C2D2F');
const colorButton = ref('#d93f3f');
const colorViewButton = ref('#3f7dd9');

// Constantes reactivas para el control de la paginación
const paginaActual = ref(1);
const itemsPorPagina = ref(10);

// Definimos los estados a emplear
const fechaSeleccionada = ref(new Date());
const sectorSeleccionado = ref('all');
const estadoFiltro = ref('disponibles');
const busquedaAula = ref('');
const aulas = ref([]);
let aula = ref({});
const sectores = ref([]);
const reservas = ref([]);
const cargando = ref(false);
const isAuthenticated = localStorage.getItem('isAuthenticated');

let showModal = ref(false);
// =======| Lightbox y Carrusel |=======
let mostrarLightbox = ref(false);
const imagenActual = ref(0);

//Parte donde se trabaja el filtrado de las aulas
const aulasFiltradas = computed(() => {
    let resultado = aulas.value;

    //filtramos según el valor del estado
    if (estadoFiltro.value === 'disponibles') {
        resultado = resultado.filter(a => a.disponible);
    } else if (estadoFiltro.value === 'ocupadas') {
        resultado = resultado.filter(a => !a.disponible);
    }

    //Filtra según lo que se busque (nombre, código o sector)
    if (busquedaAula.value) {
        const busqueda = busquedaAula.value.toLowerCase();
        resultado = resultado.filter(a =>
            a.nombre.toLowerCase().includes(busqueda) ||
            a.codigo.toLowerCase().includes(busqueda) ||
            a.sector.toLowerCase().includes(busqueda)
        );
    }

    // Filtrar por ubicación, sector o zona específica
    if (sectorSeleccionado.value !== 'all') {
        resultado = resultado.filter(a => a.sectorId === sectorSeleccionado.value);
    }
    return resultado;
});

const reservasDia = computed(() => {
    const fechaStr = fechaSeleccionada.value.toISOString().split('T')[0];
    return reservas.value.filter(r => r.fecha === fechaStr);
});

const totalReservas = computed(() => reservasDia.value.length);

const aulasDisponiblesCount = computed(() => {
    return aulas.value.filter(a => a.disponible).length;
});

const aulasOcupadasCount = computed(() => {
    return aulas.value.filter(a => !a.disponible).length;
});

const totalPaginas = computed(() => {
    return Math.ceil(aulasFiltradas.value.length / itemsPorPagina.value);
});

const indiceInicio = computed(() => {
    return (paginaActual.value - 1) * itemsPorPagina.value;
});

const indiceFin = computed(() => {
    return paginaActual.value * itemsPorPagina.value;
});

const aulasPaginadas = computed(() => {
    return aulasFiltradas.value.slice(indiceInicio.value, indiceFin.value);
});

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

const irAPagina = (pagina) => {
    paginaActual.value = pagina;
};

const paginaAnterior = () => {
    if (paginaActual.value > 1) {
        paginaActual.value--;
    }
};

const paginaSiguiente = () => {
    if (paginaActual.value < totalPaginas.value) {
        paginaActual.value++;
    }
};

const abrirModalReserva = () => {
    console.log('Abrir modal de reserva');
};

const asignarAula = (aula) => {
    console.log('Asignar aula:', aula);
};

const liberarAula = (aula) => {
    const index = aulas.value.findIndex(a => a.id === aula.id);
    if (index !== -1) {
        aulas.value[index].disponible = true;
    }
    console.log('Aula liberada:', aula);
};

const fetchAllAvailableClassrooms = async () => {
    const response = await axios.get("/api/classrooms/get/available/all", {
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
        }
    });

    const data = response.data;

    if (!data.success) {
        alert("No se encontraron aulas disponibles");
        console.debug(data.message);
    }

    return data.data;
}

const fetchAula = async (id) => {
    const response = await axios.get(`/api/classrooms/get/${id}`, {
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
        }
    });

    const data = response.data;
    if (!data.success) {
        alert("No se pudo obtener la información del aula");
        console.debug(data.message);
    }

    aula = data.data;
};

// Update the abrirModal function to handle the aula parameter
const abrirModal = ({aula: aulaData}) => {
    fetchAula(aulaData.id);
    showModal.value = true; // Use .value to modify ref
};

const cerrarModal = () => {
    showModal.value = false; // Use .value to modify ref
};

const abrirLightbox = (index) => {
    imagenActual.value = index;
    mostrarLightbox.value = true;
};
const cerrarLightbox = () => (mostrarLightbox.value = false);

const imagenSiguiente = () => {
    if (imagenActual.value < props.aula.fotos.length - 1) {
        imagenActual.value++;
    } else {
        imagenActual.value = 0;
    }
};

const imagenAnterior = () => {
    if (imagenActual.value > 0) {
        imagenActual.value--;
    } else {
        imagenActual.value = props.aula.fotos.length - 1;
    }
};

onMounted(async () => {
    try {
        const response = await fetchAllAvailableClassrooms();
        // Handle array of classrooms
        aulas.value = response.map(classroom => ({
            id: classroom.id,
            nombre: classroom.nombre,
            codigo: classroom.codigo,
            sector: classroom.sector ?? '',
            capacidad_pupitres: classroom.capacidad_pupitres,
            ubicacion: classroom.ubicacion,
            disponible: true // Add disponible property since it's used in the template
        }));
    } catch (error) {
        console.error('Error fetching classrooms:', error);
        aulas.value = []; // Set empty array on error
    } finally {
        isLoading.value = false;
    }
});
</script>
