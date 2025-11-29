<template>
    <Head title="mantenimientos"></Head>

    <Loader
        v-if="!isAuthenticated"
        @authenticated="handleAuthenticated"
        message="Verificando sesión..."
        :redirectDelay="2000"
    />

    <MainLayoutDashboard>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-1" :style="{color:colorTexto}">Gestión de Mantenimientos</h1>
                <p class="text-gray-600 text-sm">Registro y seguimiento de los mantenimientos programados y realizados</p>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
        <div class="flex flex-col sm:flex-row gap-4 flex-wrap">
            <!-- Campo de búsqueda -->
            <input
                v-model="terminoBusqueda"
                type="text"
                placeholder="Buscar por motivo o nombre de aula"
                class="w-full sm:flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />

            <!-- Filtro por estado -->
            <select
                v-model="filtroEstado"
                @change="cargarMantenimientos"
                class="w-full sm:w-auto px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="">Ver todos los estados</option>
                <option value="programado">Programado</option>
                <option value="en_proceso">En Proceso</option>
                <option value="finalizado">Finalizado</option>
                <option value="cancelado">Cancelado</option>
            </select>

            <!-- Filtro por aula -->
            <select
                v-model="filtroAulaId"
                @change="cargarMantenimientos"
                class="w-full sm:w-auto px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="">Ver todas las aulas</option>
                <option v-for="aula in listaAulas" :key="aula.id" :value="aula.id">
                    {{ aula.nombre }}
                </option>
            </select>

            <!-- Botón limpiar -->
            <button
                @click="limpiarFiltros"
                :disabled="!terminoBusqueda && !filtroEstado && !filtroAulaId"
                class="w-full sm:w-auto py-3 px-6 rounded-lg font-semibold transition-colors text-white"
                :style="(!terminoBusqueda && !filtroEstado && !filtroAulaId) ? { background: '#9CA3AF', cursor: 'not-allowed' } : { background: '#6B7280' }"
            >
                Limpiar filtros
            </button>

            <!-- Botón Programar Mantenimiento -->
            <button
                @click="abrirModalCreacion"
                class="w-full sm:w-auto text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 whitespace-nowrap"
                :style="{ background: colors.btn_agregar }"
            >
                <span class="text-xl">+</span>
                Programar Mantenimiento
            </button>
        </div>
                <br>

                <div v-if="cargando" class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600">Cargando mantenimientos...</p>
                </div>

                <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-6">
                    <p class="font-bold">Error al cargar</p>
                    <p>{{ error }}</p>
                </div>
                <br>

                <div v-if="!cargando && mantenimientosFiltrados.length" class="bg-white rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b-2 border-gray-200 text-center" :style="{background: '#d93f3f', height: '40px'}">
                                <tr>
                                    <th class="text-white px-4 py-2">ID</th>
                                    <th class="text-white px-4 py-2">Aula</th>
                                    <th class="text-white px-4 py-2">Motivo</th>
                                    <th class="text-white px-4 py-2">Fecha Inicio</th>
                                    <th class="text-white px-4 py-2">Fecha Prog.</th>
                                    <th class="text-white px-4 py-2">Fecha Real</th>
                                    <th class="text-white px-4 py-2">Estado</th>
                                    <th class="text-white px-4 py-2">Opciones</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 text-center align-middle">
                                <tr v-for="mantenimiento in mantenimientosPaginados" :key="mantenimiento.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ mantenimiento.id }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ obtenerNombreAula(mantenimiento.aula_id) }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">{{ mantenimiento.motivo }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ formatearFecha(mantenimiento.fecha_inicio) }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ formatearFecha(mantenimiento.fecha_fin_programada) }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <span v-if="mantenimiento.fecha_fin_real">{{ formatearFecha(mantenimiento.fecha_fin_real) }}</span>
                                        <span v-else class="text-gray-400">Pendiente</span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <span :class="obtenerClasesEstado(mantenimiento.estado)">
                                            {{ obtenerTextoEstado(mantenimiento.estado) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex justify-center gap-2">
                                            <button
                                                @click="abrirModalEdicion(mantenimiento)"
                                                class=" text-white px-3 py-2 rounded-lg text-xs transition-colors"
                                                :style="{background: colors.btn_editar}"
                                                :disabled="cargando"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                @click="eliminarMantenimiento(mantenimiento.id)"
                                                class="text-white px-3 py-2 rounded-lg text-xs transition-colors"
                                                :disabled="cargando"
                                                :style="{background: colors.btn_eliminar}"
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
                                @click="paginaAnterior"
                                :disabled="paginaActual === 1"
                                class="p-2 border rounded-lg transition-colors"
                                :class="{ 'bg-gray-200 cursor-not-allowed': paginaActual === 1, 'hover:bg-gray-100': paginaActual > 1 }">
                                <i class="fas fa-chevron-left"></i>
                            </button>

                            <button
                                v-for="pagina in paginasVisibles"
                                :key="pagina"
                                @click="irAPagina(pagina)"
                                class="px-4 py-2 border rounded-lg font-bold transition-colors"
                                :style="{background: '#d93f3f'}"
                                :class="{ 'text-white': pagina === paginaActual, 'hover:bg-gray-100 bg-white text-gray-700': pagina !== paginaActual }">
                                {{ pagina }}
                            </button>

                            <button
                                @click="paginaSiguiente"
                                :disabled="paginaActual === totalPaginas"
                                class="p-2 border rounded-lg transition-colors"
                                :class="{ 'bg-gray-200 cursor-not-allowed': paginaActual === totalPaginas, 'hover:bg-gray-100': paginaActual < totalPaginas }">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else-if="!cargando && !mantenimientosFiltrados.length" class="bg-gray-100 border border-gray-400 text-gray-700 px-6 py-4 rounded-lg mb-6 text-center">
                    <p v-if="terminoBusqueda === ''">No hay mantenimientos registrados en el sistema.</p>
                    <p v-else>No se encontraron mantenimientos que coincidan con la búsqueda: <span class="text-red-500">"{{ terminoBusqueda }}"</span></p>
                </div>
            </div>
        </div>
    </MainLayoutDashboard>

    <div v-if="mostrarModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">
                    {{ modoEdicion ? 'Editar Mantenimiento' : 'Programar Nuevo Mantenimiento' }}
                </h2>
                <button @click="cerrarModal" class="text-gray-500 hover:text-gray-700">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>

            <form @submit.prevent="enviarFormulario" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Aula <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="datosFormulario.aula_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': erroresFormulario.aula_id}"
                        required
                    >
                        <option value="" disabled>Seleccione un Aula</option>
                        <option v-for="aula in listaAulas" :key="aula.id" :value="aula.id">
                            {{ aula.nombre }} (ID: {{ aula.id }})
                        </option>
                    </select>
                    <p v-if="erroresFormulario.aula_id" class="text-red-500 text-sm mt-1">
                        {{ erroresFormulario.aula_id[0] }}
                    </p>
                    <p v-if="!listaAulas.length && !cargando" class="text-red-500 text-sm mt-1">
                        No hay aulas disponibles para seleccionar.
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Motivo <span class="text-red-500">*</span>
                    </label>
                    <textarea
                        v-model="datosFormulario.motivo"
                        rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': erroresFormulario.motivo}"
                        required
                    ></textarea>
                    <p v-if="erroresFormulario.motivo" class="text-red-500 text-sm mt-1">
                        {{ erroresFormulario.motivo[0] }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Fecha Inicio <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="datosFormulario.fecha_inicio"
                            type="datetime-local"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': erroresFormulario.fecha_inicio}"
                            required
                        />
                        <p v-if="erroresFormulario.fecha_inicio" class="text-red-500 text-sm mt-1">
                            {{ erroresFormulario.fecha_inicio[0] }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Fecha Fin Programada <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="datosFormulario.fecha_fin_programada"
                            type="datetime-local"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': erroresFormulario.fecha_fin_programada}"
                            required
                        />
                        <p v-if="erroresFormulario.fecha_fin_programada" class="text-red-500 text-sm mt-1">
                            {{ erroresFormulario.fecha_fin_programada[0] }}
                        </p>
                    </div>
                </div>

                <div v-if="modoEdicion" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Fecha Fin Real <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="datosFormulario.fecha_fin_real"
                            type="datetime-local"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': erroresFormulario.fecha_fin_real}"
                        />
                        <p v-if="erroresFormulario.fecha_fin_real" class="text-red-500 text-sm mt-1">
                            {{ erroresFormulario.fecha_fin_real[0] }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Estado <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="datosFormulario.estado"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': erroresFormulario.estado}"
                            required
                        >
                            <option value="programado">Programado</option>
                            <option value="en_proceso">En Proceso</option>
                            <option value="finalizado">Finalizado</option>
                            <option value="cancelado">Cancelado</option>
                        </select>
                        <p v-if="erroresFormulario.estado" class="text-red-500 text-sm mt-1">
                            {{ erroresFormulario.estado[0] }}
                        </p>
                    </div>
                </div>

                <div v-if="erroresFormulario.general" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <p class="font-bold">Error</p>
                    <p>{{ erroresFormulario.general }}</p>
                </div>

                <div v-if="erroresFormulario.usuario_registro_id" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <p class="font-bold">Error de Usuario</p>
                    <p>{{ erroresFormulario.usuario_registro_id[0] }}</p>
                </div>


                <div class="flex justify-end gap-3 pt-4">
                    <button
                        type="button"
                        @click="cerrarModal"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                        :disabled="enviando"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50"
                        :disabled="enviando"
                    >
                        {{ enviando ? 'Guardando...' : (modoEdicion ? 'Actualizar' : 'Programar') }}
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
    import Loader from '@/Components/AdministrationComponent/Loader.vue';
    import { authService } from "@/Services/authService.js";
import { colors } from '@/UI/color';

    const isAuthenticated = ref(localStorage.getItem('isAuthenticated') === 'true');
    const colorTexto = ref('#1F2937');
    const terminoBusqueda = ref('');
    const cargando = ref(false);
    const error = ref(null);
    const todosMantenimientos = ref([]);
    const listaAulas = ref([]);
    const usuarioId = ref(null);

    // Paginación
    const paginaActual = ref(1);
    const porPagina = ref(5);

    const filtroEstado = ref(''); // Para el nuevo filtro por estado (programado, en_proceso, etc.)
    const filtroAulaId = ref(''); // Para el nuevo filtro por ID de aula

    // Configuración de axios
    const URL_API = '/api';
    const ENDPOINT_BASE = '/maintenance';
    const ENDPOINT_AULAS = '/classrooms';
    const obtenerCabecerasAuth = () => ({
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    });

    // Estados del Modal
    const mostrarModal = ref(false);
    const modoEdicion = ref(false);
    const enviando = ref(false);
    const erroresFormulario = ref({});
    const idMantenimientoActual = ref(null);

    // Datos del formulario
    const datosFormulario = ref({
        aula_id: '',
        motivo: '',
        fecha_inicio: '',
        fecha_fin_programada: '',
        fecha_fin_real: null,
        estado: 'programado',
    });

    const obtenerNombreAula = (aulaId) => {
        const aula = listaAulas.value.find(a => a.id == aulaId);
        return aula ? aula.nombre : `ID ${aulaId} (Desconocida)`;
    };

    const mantenimientosFiltrados = computed(() => {
        const datos = Array.isArray(todosMantenimientos.value) ? todosMantenimientos.value : [];
        if (!terminoBusqueda.value) return datos;
        const termino = terminoBusqueda.value.toLowerCase();
        return datos.filter(mantenimiento =>
            mantenimiento.motivo.toLowerCase().includes(termino) ||
            mantenimiento.aula_id.toString().includes(termino) ||
            obtenerNombreAula(mantenimiento.aula_id).toLowerCase().includes(termino)
        );
    });

    const totalPaginas = computed(() => {
        return Math.ceil(mantenimientosFiltrados.value.length / porPagina.value);
    });

    const mantenimientosPaginados = computed(() => {
        const inicio = (paginaActual.value - 1) * porPagina.value;
        const fin = inicio + porPagina.value;
        return mantenimientosFiltrados.value.slice(inicio, fin);
    });

    const maxPaginasVisibles = 5;
    const paginasVisibles = computed(() => {
        const paginas = [];
        let paginaInicial = Math.max(1, paginaActual.value - Math.floor(maxPaginasVisibles / 2));
        let paginaFinal = Math.min(totalPaginas.value, paginaInicial + maxPaginasVisibles - 1);

        if (paginaFinal - paginaInicial + 1 < maxPaginasVisibles) {
            paginaInicial = Math.max(1, paginaFinal - maxPaginasVisibles + 1);
        }

        for (let i = paginaInicial; i <= paginaFinal; i++) {
            paginas.push(i);
        }
        return paginas;
    });

    const formatearFecha = (fechaIso) => {
        if (!fechaIso) return 'N/A';
        try {
            const fecha = new Date(fechaIso);
            if (isNaN(fecha)) return 'Formato Inválido';
            return new Intl.DateTimeFormat('es-SV', {
                year: 'numeric', month: 'short', day: '2-digit',
                hour: '2-digit', minute: '2-digit', hour12: false
            }).format(fecha);
        } catch (e) {
            return fechaIso;
        }
    };

    const obtenerClasesEstado = (estado) => {
        switch (estado) {
            case 'finalizado': return 'bg-green-100 text-green-800 px-3 py-1 rounded-full text-xs font-semibold';
            case 'en_proceso': return 'bg-yellow-100 text-yellow-800 px-3 py-1 rounded-full text-xs font-semibold';
            case 'programado': return 'bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-semibold';
            case 'cancelado': return 'bg-red-100 text-red-800 px-3 py-1 rounded-full text-xs font-semibold';
            default: return 'bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-xs font-semibold';
        }
    };

    const obtenerTextoEstado = (estado) => {
        switch (estado) {
            case 'finalizado': return 'Finalizado';
            case 'en_proceso': return 'En Proceso';
            case 'programado': return 'Programado';
            case 'cancelado': return 'Cancelado';
            default: return 'Desconocido';
        }
    };

    const aDatetimeLocal = (fechaIso) => {
        if (!fechaIso) return '';
        try {
            const fecha = new Date(fechaIso);
            if (isNaN(fecha)) return '';

            const year = fecha.getFullYear();
            const month = String(fecha.getMonth() + 1).padStart(2, '0');
            const day = String(fecha.getDate()).padStart(2, '0');
            const hours = String(fecha.getHours()).padStart(2, '0');
            const minutes = String(fecha.getMinutes()).padStart(2, '0');

            return `${year}-${month}-${day}T${hours}:${minutes}`;
        } catch (e) {
            console.error('Error al convertir fecha:', e);
            return '';
        }
    };

    const datetimeLocalAISO = (datetimeLocal) => {
        if (!datetimeLocal) return null;

        try {
            const [fecha, hora] = datetimeLocal.split('T');
            const [year, month, day] = fecha.split('-');
            const [hours, minutes] = hora.split(':');

            return `${year}-${month}-${day}T${hours}:${minutes}:00`;
        } catch (e) {
            console.error('Error al convertir datetime-local a ISO:', e);
            return null;
        }
    };

    const handleAuthenticated = (status) => {
        isAuthenticated.value = status;
        if (status) {
            cargarAulas();
            cargarMantenimientos();
        }
    };

    watch(mantenimientosFiltrados, () => {
        paginaActual.value = 1;
    });

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

    const irAPagina = (pagina) => {
        if (pagina >= 1 && pagina <= totalPaginas.value) {
            paginaActual.value = pagina;
        }
    };

    const reiniciarFormulario = () => {
        datosFormulario.value = {
            aula_id: '',
            motivo: '',
            fecha_inicio: '',
            fecha_fin_programada: '',
            fecha_fin_real: null,
            estado: 'programado',
        };
        erroresFormulario.value = {};
        idMantenimientoActual.value = null;
    };

    const abrirModalCreacion = () => {
        reiniciarFormulario();
        modoEdicion.value = false;
        mostrarModal.value = true;
    };

    const abrirModalEdicion = (item) => {
        reiniciarFormulario();
        modoEdicion.value = true;
        idMantenimientoActual.value = item.id;

        datosFormulario.value = {
            aula_id: item.aula_id.toString() || '',
            motivo: item.motivo || '',
            fecha_inicio: aDatetimeLocal(item.fecha_inicio),
            fecha_fin_programada: aDatetimeLocal(item.fecha_fin_programada),
            fecha_fin_real: item.fecha_fin_real ? aDatetimeLocal(item.fecha_fin_real) : '',
            estado: item.estado || 'programado',
        };

        mostrarModal.value = true;
    };

    const cerrarModal = () => {
        mostrarModal.value = false;
        reiniciarFormulario();
    };

    async function cargarAulas() {
        try {
            const res = await axios.get(`${URL_API}${ENDPOINT_AULAS}/get/all`, obtenerCabecerasAuth());
            const datos = res.data?.data;
            listaAulas.value = Array.isArray(datos) ? datos.map(a => ({
                id: a.id.toString(),
                nombre: a.nombre || `Aula ${a.id}`
            })) : [];
        } catch (err) {
            console.error("Error al cargar la lista de aulas:", err);
        }
    }

    async function cargarMantenimientos() {
        cargando.value = true;
        error.value = null;

            axios.get('/api/maintenance/get/classroom/1', {
        headers: {
        'Authorization': `Bearer ${localStorage.getItem('token')}`
        }
        })
        .then(res => console.log("RESPUESTA BACKEND:", res.data))
        .catch(err => console.log("ERROR:", err.response?.status, err.response?.data));

        try {
            let url = `${URL_API}${ENDPOINT_BASE}/get/all`;

            // Si solo hay filtro por estado
            if (filtroEstado.value && !filtroAulaId.value) {
                url = `${URL_API}${ENDPOINT_BASE}/get/status/${filtroEstado.value}`;
            }

            // Si solo hay filtro por aula
            if (!filtroEstado.value && filtroAulaId.value) {
                url = `${URL_API}${ENDPOINT_BASE}/get/classroom/${filtroAulaId.value}`;
            }

            // Si ambos filtros están seleccionados → usar solo el de AULA
            if (filtroEstado.value && filtroAulaId.value) {
                url = `${URL_API}${ENDPOINT_BASE}/get/classroom/${filtroAulaId.value}`;
            }

            // Consumimos el endpoint seleccionado
            const res = await axios.get(url, obtenerCabecerasAuth());
            let datos = Array.isArray(res.data?.data)
                ? res.data.data
                : Object.values(res.data?.data || {});

            if (filtroEstado.value && filtroAulaId.value) {
                datos = datos.filter(item => item.estado === filtroEstado.value);
            }

            // if (filtroEstado.value && filtroAulaId.value) {
            //     datos = datos.filter(item =>
            //         item.estado === filtroEstado.value &&
            //         item.aula_id == filtroAulaId.value
            //     );
            // }

            todosMantenimientos.value = datos;
        }
        catch (err) {
            if (err.response?.status === 404) {
                todosMantenimientos.value = [];
                error.value = null;
            } else {
                error.value = "Error al cargar mantenimientos";
            }
        }
        finally {
            cargando.value = false;
        }
    }

    // async function cargarMantenimientos() {
    //     cargando.value = true;
    //     error.value = null;

    //     try {
    //         let url = `${URL_API}${ENDPOINT_BASE}/get/all`;

    //         // Si hay filtro por estado
    //         if (filtroEstado.value) {
    //             url = `${URL_API}${ENDPOINT_BASE}/get/status/${filtroEstado.value}`;
    //         }

    //         // Si hay filtro por aula
    //         if (filtroAulaId.value) {
    //             url = `${URL_API}${ENDPOINT_BASE}/get/classroom/${filtroAulaId.value}`;
    //         }

    //         // Si hay ambos filtros → usar endpoint mixto (si existe) o hacer doble fetch
    //         if (filtroEstado.value && filtroAulaId.value) {
    //             url = `${URL_API}${ENDPOINT_BASE}/get/status/${filtroEstado.value}/classroom/${filtroAulaId.value}`;
    //         }

    //         const res = await axios.get(url, obtenerCabecerasAuth());

    //         const datos = Array.isArray(res.data?.data)
    //             ? res.data.data
    //             : Object.values(res.data?.data || {});

    //         todosMantenimientos.value = datos;

    //     } catch (err) {
    //         // 👉 Si el backend devuelve 404 = no hay datos
    //         if (err.response?.status === 404) {
    //             todosMantenimientos.value = [];   // Mostrar vacío
    //             error.value = null;               // No mostrar error rojo
    //         } else {
    //             error.value = "Error al cargar mantenimientos";
    //         }
    //     } finally {
    //         cargando.value = false;
    //     }
    // }

    // async function cargarMantenimientos() {
    //     cargando.value = true;
    //     error.value = null;

    //     try {
    //         console.log('🔍 Intentando cargar mantenimientos desde:', `${URL_API}${ENDPOINT_BASE}/get/all`);

    //         const res = await axios.get(`${URL_API}${ENDPOINT_BASE}/get/all`, obtenerCabecerasAuth());

    //         const payload = res.data?.data;
    //         const datosCrudos = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);

    //         todosMantenimientos.value = datosCrudos.map(item => ({
    //             id: item.id ?? 'N/A',
    //             aula_id: item.aula_id.toString() ?? 'N/A',
    //             motivo: item.motivo ?? 'Sin motivo',
    //             fecha_inicio: item.fecha_inicio ?? null,
    //             fecha_fin_programada: item.fecha_fin_programada ?? null,
    //             fecha_fin_real: item.fecha_fin_real ?? null,
    //             estado: item.estado ?? 'programado',
    //         }));

    //         console.log('✅ Mantenimientos cargados:', todosMantenimientos.value.length);
    //         error.value = null;

    //     } catch (err) {
    //         console.error("❌ Error al cargar mantenimientos:", err);
    //         const status = err.response?.status;

    //         if (status === 404) {
    //             console.warn('⚠️ Endpoint no encontrado o no hay mantenimientos registrados');
    //             todosMantenimientos.value = [];
    //             error.value = null;
    //         } else if (status === 401 || status === 403) {
    //             error.value = 'Error de autenticación. Por favor, verifica tu sesión.';
    //         } else {
    //             error.value = err.response?.data?.message || 'Error desconocido al cargar los mantenimientos';
    //         }

    //         todosMantenimientos.value = [];
    //     } finally {
    //         cargando.value = false;
    //     }
    // }

    const enviarFormulario = async () => {
        erroresFormulario.value = {};
        enviando.value = true;

        try {
            if (!modoEdicion.value && !usuarioId.value) {
                console.error('❌ ID de usuario no disponible:', {
                    usuarioId: usuarioId.value,
                    localStorage: {
                        user_id: localStorage.getItem('user_id'),
                        userId: localStorage.getItem('userId'),
                        id: localStorage.getItem('id')
                    }
                });

                erroresFormulario.value.usuario_registro_id = [
                    'No se pudo identificar al usuario. Por favor, cierre sesión e inicie sesión nuevamente.'
                ];
                return;
            }

            const cargaUtil = {
                aula_id: parseInt(datosFormulario.value.aula_id),
                motivo: datosFormulario.value.motivo,
                fecha_inicio: datetimeLocalAISO(datosFormulario.value.fecha_inicio),
                fecha_fin_programada: datetimeLocalAISO(datosFormulario.value.fecha_fin_programada),
            };

            if (!modoEdicion.value) {
                cargaUtil.usuario_registro_id = parseInt(usuarioId.value);
            }

            if (modoEdicion.value) {
                cargaUtil.fecha_fin_real = datosFormulario.value.fecha_fin_real
                    ? datetimeLocalAISO(datosFormulario.value.fecha_fin_real)
                    : null;
                cargaUtil.estado = datosFormulario.value.estado;
            }

            console.log('📤 Enviando datos:', cargaUtil);

            const url = modoEdicion.value
                ? `${URL_API}${ENDPOINT_BASE}/edit/${idMantenimientoActual.value}`
                : `${URL_API}${ENDPOINT_BASE}/new`;

            const respuesta = modoEdicion.value
                ? await axios.patch(url, cargaUtil, obtenerCabecerasAuth())
                : await axios.post(url, cargaUtil, obtenerCabecerasAuth());

            if (respuesta.data.success || respuesta.status === 200 || respuesta.status === 201) {
                cerrarModal();
                await cargarMantenimientos();

                alert(modoEdicion.value
                    ? "Mantenimiento actualizado exitosamente"
                    : "Mantenimiento programado exitosamente");
            }
        } catch (err) {
            console.error("❌ Error al guardar el Mantenimiento:", err);
            console.error("📋 Detalles del error:", {
                response: err.response?.data,
                status: err.response?.status,
                message: err.message
            });

            const data = err.response?.data || {};

            if (data.errors) {
                erroresFormulario.value = data.errors;
            } else if (data.message) {
                erroresFormulario.value.general = data.message;
            } else {
                erroresFormulario.value.general = "Error al guardar el Mantenimiento";
            }
        } finally {
            enviando.value = false;
        }
    };

    const eliminarMantenimiento = async (id) => {
        if (!confirm('¿Está seguro de eliminar este Mantenimiento? Esta acción no se puede deshacer.')) return;

        try {
            cargando.value = true;
            const respuesta = await axios.delete(`${URL_API}${ENDPOINT_BASE}/delete/${id}`, obtenerCabecerasAuth());

            if (respuesta.data.success || respuesta.status === 200) {
                alert('Mantenimiento eliminado exitosamente');
                await cargarMantenimientos();
            }
        } catch (err) {
            console.error('Error al eliminar Mantenimiento:', err);
            alert(err.response?.data?.message || 'Error al eliminar el Mantenimiento');
        } finally {
            cargando.value = false;
        }
    };

    const obtenerUsuarioId = () => {
        const posiblesClaves = ['user_id', 'userId', 'id', 'usuario_id'];

        for (const clave of posiblesClaves) {
            const valor = localStorage.getItem(clave);
            if (valor) {
                console.log(`✅ ID de usuario encontrado: ${valor} (clave: ${clave})`);
                return valor;
            }
        }

        const token = localStorage.getItem('token');
        if (token) {
            try {
                const payload = JSON.parse(atob(token.split('.')[1]));
                const id = payload.sub || payload.id || payload.user_id || payload.userId;
                if (id) {
                    console.log(`✅ ID de usuario obtenido del token: ${id}`);
                    return id;
                }
            } catch (e) {
                console.error('❌ Error al decodificar el token:', e);
            }
        }

        console.error('❌ No se pudo obtener el ID del usuario');
        return null;
    };

    // const verificarConfiguracion = () => {
    //     console.log('=== CONFIGURACIÓN ACTUAL ===');
    //     console.log('Usuario ID:', usuarioId.value);
    //     console.log('Token:', localStorage.getItem('token') ? '✅ Presente' : '❌ Ausente');
    //     console.log('Authenticated:', isAuthenticated.value);
    //     console.log('API Base:', URL_API);
    //     console.log('Endpoint:', ENDPOINT_BASE);
    //     console.log('URL Completa:', `${URL_API}${ENDPOINT_BASE}/get/all`);
    //     console.log('LocalStorage keys:', Object.keys(localStorage));
    //     console.log('==========================');
    // };

    const limpiarFiltros = () => {
        filtroEstado.value = "";
        filtroAulaId.value = "";
        cargarMantenimientos();
    };

    onMounted(async () => {
        const token = localStorage.getItem("token");
        await authService.verifyToken(token);
        isAuthenticated.value = localStorage.getItem('isAuthenticated') === 'true';

        usuarioId.value = obtenerUsuarioId();

        // verificarConfiguracion();

        if (!usuarioId.value) {
            console.error('❌ No se pudo obtener el ID del usuario');
            alert('Error: No se pudo identificar al usuario. Por favor, inicie sesión nuevamente.');
            return;
        }

        if (isAuthenticated.value) {
            terminoBusqueda.value = '';
            await cargarAulas();
            await cargarMantenimientos();
        }
    });

</script>
