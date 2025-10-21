<template>
    <Head title="Disponibilidad" />

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
        <div class="p-6">
            <!-- Header de la vista-->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-1" :style="{color:colorText}">Disponibilidad de Aulas</h1>
                <p class="text-gray-600 text-sm">Visualice y gestione las aulas ocupadas o disponibles dentro de la facultad</p>
            </div>

            <!--Parte del buscador, filtros y opción de agregar-->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex flex-col gap-4">
                    <div class="w-full">
                        <input
                            v-model="busquedaAula"
                            type="text"
                            placeholder="Buscar aula por nombre, código o sector..."
                            class="w-full px-4 py-2 text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm"
                            >
                    </div>

                    <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                        <div class="flex gap-3">
                            <!--Para filtrar por lo sectores o zonas-->
                            <select
                                v-model="sectorSeleccionado"
                                class="w-48 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
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
                                class="w-48 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                                >
                                <option value="disponibles">Aulas Disponibles </option>
                                <option value="ocupadas">Aulas Ocupadas </option>
                                <option value="todas">Todas las Aulas </option>
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
                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-lg font-semibold text-gray-900">
                        {{  estadoFiltro === 'disponibles' ? 'Aulas Disponibles para Asignar' :
                            estadoFiltro === 'ocupadas' ? 'Aulas Ocupadas' : 'Todas las Aulas'
                        }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">{{ aulasFiltradas.length }} aulas encontradas</p>
                </div>

                <!--Tabla-->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aula
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ubicación
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Capacidad
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tipo
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Estado
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Equipamiento
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="aula in aulasPaginadas" :key="aula.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 rounded-lg flex items-center justify-center"
                                            :class="aula.disponible ? 'bg-blue-100' : 'bg-red-100'">
                                            <i class="fa-solid fa-hotel"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">{{ aula.nombre }}</div>
                                            <div class="text-sm text-gray-500">{{ aula.codigo }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">
                                        {{ aula.sector }}
                                    </span>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <i class="fa-solid fa-people-group"></i>
                                        <span class="text-sm text-gray-900"> {{ aula.capacidad }}  personas</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ aula.tipo }}
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span v-if="aula.disponible" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Disponible
                                    </span>
                                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Ocupada
                                    </span>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex flex-wrap gap-1">
                                        <span v-for="equipo in aula.equipamiento" :key="equipo" class="px-2 py-1 text-xs bg-gray-100 text-gray-700 rounded">
                                            {{ equipo }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button
                                        v-if="aula.disponible"
                                        @click="asignarAula(aula)"
                                        class="text-white px-4 py-2 rounded-lg hover:opacity-90 transition-opacity"
                                        :style="{background:colorButton}"
                                    >
                                        Asignar
                                    </button>
                                    <button
                                        v-else
                                        @click="liberarAula(aula)"
                                        class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors"
                                    >
                                        Liberar
                                    </button>
                                </td>
                            </tr>

                            <!-- Estado vacío -->
                            <tr v-if="aulasFiltradas.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center">
                                    <i class="fa-solid fa-face-meh" :style="{color: colorButton, fontSize: '40px'}"></i>
                                    <p class="mt-4 text-sm text-gray-500">No se encontraron aulas</p>
                                    <p class="text-xs text-gray-400 mt-1">Intente ajustar los filtros de búsqueda</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!--Parte donde se trabaja la Paginación-->
                <div v-if="aulasFiltradas.length > 0" class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button
                            @click="paginaAnterior"
                            :disabled="paginaActual === 1"
                            class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Anterior
                        </button>

                        <button
                            @click="paginaSiguiente"
                            :disabled="paginaActual === totalPaginas"
                            class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Siguiente
                        </button>
                    </div>

                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Mostrando
                                <span class="font-medium">{{ indiceInicio + 1 }}</span>
                                a
                                <span class="font-medium">{{ Math.min(indiceFin, aulasFiltradas.length) }}</span>
                                de
                                <span class="font-medium">{{ aulasFiltradas.length }}</span>
                                resultados
                            </p>
                        </div>
                    </div>

                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <button
                                @click="paginaAnterior"
                                :disabled="paginaActual === 1"
                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
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
                                        'relative inline-flex items-center px-4 py-2 border text-sm font-medium'
                                    ]"
                                >
                                    {{ pagina }}
                                </button>

                                <button
                                    @click="paginaSiguiente"
                                    :disabled="paginaActual === totalPaginas"
                                    class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <span class="sr-only">Siguiente</span>
                                    <i class="fa-solid fa-chevron-right"></i>
                                </button>
                        </nav>
                    </div>
                </div>
            </div>

        </div>
    </MainLayoutDashboard>
</template>

<script setup>
    //Imports a utilizar
    import { Head, Link } from '@inertiajs/vue3';
    import { ref, computed, onMounted } from 'vue';
    import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
    import {authService} from "@/Services/authService.js";

    const isLoading = ref(true);
    // Constantes reactivas para los colores de la interfaz
    const colorText = ref('#2C2D2F');
    const colorButton = ref('#d93f3f');

    // Constantes reactivas para el control de la paginación
    const paginaActual = ref(1);
    const itemsPorPagina = ref(10);

    // Definimos los estados a emplear
    const fechaSeleccionada = ref(new Date());
    const sectorSeleccionado = ref('all');
    const estadoFiltro = ref('disponibles');
    const busquedaAula = ref('');
    const aulas = ref([]);
    const sectores = ref([]);
    const reservas = ref([]);
    const cargando = ref(false);
    const isAuthenticated = localStorage.getItem('isAuthenticated');

    //Parte donde se trabaja el filtrado de las aulas
    const aulasFiltradas = computed( () =>{
        let resultado = aulas.value;

        //filtramos según el valor del estado
        if (estadoFiltro.value === 'disponibles') {
            resultado = resultado.filter(a => a.disponible);
        } else if (estadoFiltro.value === 'ocupadas') {
            resultado = resultado.filter(a => !a.disponible);
        }

        //Filtra según lo que se busque (nombre, código o sector)
        if (busquedaAula.value) {
            const busqueda = busquedaAula.value.toLowerCase(); // Convertimos la búsqueda a minúsculas para comparar
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
        // Retornamos la lista final de aulas filtradas
        return resultado;
    });

    // Parte donde se trabaja las reservas
    const reservasDia = computed(() => {
        // Se obtiene la fecha seleccionada en formato "YYYY-MM-DD"
        const fechaStr = fechaSeleccionada.value.toISOString().split('T')[0];
        // Se filtran las reservas que coinciden con la fecha seleccionada
        return reservas.value.filter(r => r.fecha === fechaStr);
    });

    // Calcula el número total de reservas para el día seleccionado
    const totalReservas = computed(() => reservasDia.value.length);

    const aulasDisponiblesCount = computed(() => {
        // Cuenta cuántas aulas están disponibles
        return aulas.value.filter(a => a.disponible).length;
    });

    const aulasOcupadasCount = computed(() => {
        // Cuenta cuántas aulas están ocupadas
        return aulas.value.filter(a => !a.disponible).length;
    });

    //parte donde se trabaja la paginación
    const totalPaginas = computed(() => {
        // Calcula el número total de páginas basado en el número de aulas filtradas
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

    // calculando las páginas visibles
    const paginasVisibles = computed(() => {
        const paginas = [];
        const maxPaginas = 5; // Máximo de páginas visibles
        let inicio = Math.max(1, paginaActual.value - 2);
        let fin = Math.min(totalPaginas.value, inicio + maxPaginas - 1);

        // Asegura que siempre se muestren 5 páginas si es posible
        if (fin - inicio < maxPaginas - 1) {
            inicio = Math.max(1, fin - maxPaginas + 1);
        }

        // Llena el arreglo con los números de página visibles
        for (let i = inicio; i <= fin; i++) {
            paginas.push(i);
        }
        return paginas;
    });

    // Funciones de Navegación entre Páginas
    const irAPagina = (pagina) => {
        // Cambia a la página especificada
        paginaActual.value = pagina;
    };

    const paginaAnterior = () => {
        // Va a la página anterior si no está en la primera
        if (paginaActual.value > 1) {
            paginaActual.value--;
        }
    };

    const paginaSiguiente = () => {
        // Va a la siguiente página si no está en la última
        if (paginaActual.value < totalPaginas.value) {
            paginaActual.value++;
        }
    };

    // Métodos
    const abrirModalReserva = () => {
        console.log('Abrir modal de reserva');
    };

    const asignarAula = (aula) => {
        console.log('Asignar aula:', aula);
        // Aquí iría la lógica para asignar el aula
    };

    const liberarAula = (aula) => {
        // Cambiar estado a disponible
        const index = aulas.value.findIndex(a => a.id === aula.id);
        if (index !== -1) {
            aulas.value[index].disponible = true;
        }
        console.log('Aula liberada:', aula);
    };

    // Datos de ejemplos para probar
    onMounted(async () => {
        await authService.verifyToken(localStorage.getItem("token"));

        aulas.value = [
            {
                id: 1,
                nombre: 'Aula A-101',
                codigo: 'A101',
                sector: 'Sector A',
                sectorId: 1,
                capacidad: 40,
                tipo: 'Teoría',
                disponible: true,
                equipamiento: ['Proyector', 'Pizarra', 'AC']
            },
            {
                id: 2,
                nombre: 'Laboratorio B-203',
                codigo: 'B203',
                sector: 'Sector B',
                sectorId: 2,
                capacidad: 30,
                tipo: 'Laboratorio',
                disponible: true,
                equipamiento: ['Computadoras', 'Proyector', 'AC']
            },
            {
                id: 3,
                nombre: 'Aula C-105',
                codigo: 'C105',
                sector: 'Sector C',
                sectorId: 3,
                capacidad: 50,
                tipo: 'Teoría',
                disponible: false,
                equipamiento: ['Proyector', 'Pizarra']
            },
            {
                id: 4,
                nombre: 'Aula Magna',
                codigo: 'AM01',
                sector: 'Sector A',
                sectorId: 1,
                capacidad: 200,
                tipo: 'Auditorio',
                disponible: true,
                equipamiento: ['Sistema de Audio', 'Proyector', 'AC', 'Micrófonos']
            },
            {
                id: 5,
                nombre: 'Aula A-102',
                codigo: 'A102',
                sector: 'Sector A',
                sectorId: 1,
                capacidad: 35,
                tipo: 'Teoría',
                disponible: false,
                equipamiento: ['Proyector', 'Pizarra']
            },
            {
                id: 6,
                nombre: 'Laboratorio C-301',
                codigo: 'C301',
                sector: 'Sector C',
                sectorId: 3,
                capacidad: 25,
                tipo: 'Laboratorio',
                disponible: true,
                equipamiento: ['Computadoras', 'Proyector']
            },
            {
                id: 7,
                nombre: 'Aula B-205',
                codigo: 'B205',
                sector: 'Sector B',
                sectorId: 2,
                capacidad: 45,
                tipo: 'Teoría',
                disponible: false,
                equipamiento: ['Proyector', 'Pizarra', 'AC']
            },
            {
                id: 8,
                nombre: 'Aula A-103',
                codigo: 'A103',
                sector: 'Sector A',
                sectorId: 1,
                capacidad: 40,
                tipo: 'Teoría',
                disponible: true,
                equipamiento: ['Proyector', 'Pizarra']
            },
            {
                id: 9,
                nombre: 'Laboratorio B-204',
                codigo: 'B204',
                sector: 'Sector B',
                sectorId: 2,
                capacidad: 28,
                tipo: 'Laboratorio',
                disponible: false,
                equipamiento: ['Computadoras', 'AC']
            },
            {
                id: 10,
                nombre: 'Aula C-106',
                codigo: 'C106',
                sector: 'Sector C',
                sectorId: 3,
                capacidad: 38,
                tipo: 'Teoría',
                disponible: true,
                equipamiento: ['Proyector', 'Pizarra', 'AC']
            },
            {
                id: 11,
                nombre: 'Aula A-104',
                codigo: 'A104',
                sector: 'Sector A',
                sectorId: 1,
                capacidad: 42,
                tipo: 'Teoría',
                disponible: false,
                equipamiento: ['Proyector', 'AC']
            },
            {
                id: 12,
                nombre: 'Laboratorio C-302',
                codigo: 'C302',
                sector: 'Sector C',
                sectorId: 3,
                capacidad: 30,
                tipo: 'Laboratorio',
                disponible: true,
                equipamiento: ['Computadoras', 'Proyector', 'AC']
            }
        ];

        sectores.value = [
            {id: 1, nombre: 'Sector A'},
            {id: 2, nombre: 'Sector B'},
            {id: 3, nombre: 'Sector C'}
        ];

        isLoading.value = false;
    });
</script>
