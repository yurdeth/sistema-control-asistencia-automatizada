<template>
    <Head title="Incidencias"></Head>

    <Loader
        v-if="!isAuthenticated"
        @authenticated="handleAuthenticated"
        message="Verificando sesión..."
        :redirectDelay="2000"
    />

    <MainLayoutDashboard>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-1">
                    Reporte de Incidencias en Aulas
                </h1>
                <p class="text-gray-600 text-sm">
                    Visualización y seguimiento de todas las incidencias reportadas en aulas
                </p>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">

                <!-- Buscador -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <input
                        v-model="terminoBusqueda"
                        type="text"
                        placeholder="Buscar por descripción o aula"
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg
                               focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                </div>

                <br>

                <div v-if="cargando" class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600">Cargando incidencias...</p>
                </div>

                <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-6">
                    <p class="font-bold">Error al cargar</p>
                    <p>{{ error }}</p>
                </div>

                <div v-if="!cargando && incidenciasFiltradas.length" class="bg-white rounded-lg overflow-hidden mt-4">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50 border-b-2 border-gray-200 text-center"
                                   style="background:#d93f3f; height:40px;">
                                <tr>
                                    <th class="text-white px-4 py-2">ID</th>
                                    <th class="text-white px-4 py-2">Aula</th>
                                    <th class="text-white px-4 py-2">Categoría</th>
                                    <th class="text-white px-4 py-2">Descripción</th>
                                    <!-- <th class="text-white px-4 py-2">Reportado Por</th> -->
                                    <th class="text-white px-4 py-2">Fecha</th>
                                    <th class="text-white px-4 py-2">Estado</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 text-center align-middle">
                                <tr v-for="item in incidenciasPaginadas" :key="item.id"
                                    class="hover:bg-gray-50 transition-colors">
                                    
                                    <td class="px-6 py-3 text-sm font-medium text-gray-900">
                                        {{ item.id }}
                                    </td>

                                    <td class="px-6 py-3 text-sm text-gray-700">
                                        {{ item.aula_nombre || `Aula ${item.aula_id}` }}
                                    </td>

                                    <td class="px-6 py-3 text-sm text-gray-600">
                                        {{ formatCategory(item.categoria) }}
                                    </td>

                                    <td class="px-6 py-3 text-sm text-gray-600 max-w-xs truncate">
                                        {{ item.descripcion }}
                                    </td>

                                    <!-- <td class="px-6 py-3 text-sm text-gray-600">
                                        {{ obtenerNombreUsuario(item) }}
                                    </td> -->

                                    <td class="px-6 py-3 text-sm text-gray-700">
                                        {{ formatFecha(item.created_at) }}
                                    </td>

                                    <td class="px-6 py-3 text-sm">
                                        <span :class="getStatusBadge(item.estado)">
                                            {{ formatEstado(item.estado) }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="flex justify-center items-center space-x-2 p-4 border-t border-gray-200">

                            <button
                                @click="paginaAnterior"
                                :disabled="paginaActual === 1"
                                class="p-2 border rounded-lg transition-colors"
                                :class="paginaActual === 1 ? 'bg-gray-200 cursor-not-allowed' : 'hover:bg-gray-100'">
                                <i class="fas fa-chevron-left"></i>
                            </button>

                            <button
                                v-for="pagina in paginasVisibles"
                                :key="pagina"
                                @click="irAPagina(pagina)"
                                class="px-4 py-2 rounded-lg font-bold transition-colors"
                                :style="pagina === paginaActual ? {background:'#d93f3f', color:'#fff'} : {}">
                                {{ pagina }}
                            </button>

                            <button
                                @click="paginaSiguiente"
                                :disabled="paginaActual === totalPaginas"
                                class="p-2 border rounded-lg transition-colors"
                                :class="paginaActual === totalPaginas ? 'bg-gray-200 cursor-not-allowed' : 'hover:bg-gray-100'">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>

                    </div>
                </div>

                <div v-else-if="!cargando && !incidenciasFiltradas.length"
                     class="bg-gray-100 border border-gray-400 text-gray-700 px-6 py-4 rounded-lg text-center">

                    <p v-if="terminoBusqueda === ''">
                        No hay incidencias registradas.
                    </p>

                    <p v-else>
                        No se encontraron incidencias para: 
                        <span class="text-red-500">"{{ terminoBusqueda }}"</span>
                    </p>

                </div>

            </div>
        </div>
    </MainLayoutDashboard>
</template>

<script setup>
    import { ref, computed, onMounted } from "vue";
    import axios from "axios";
    import Loader from "@/Components/AdministrationComponent/Loader.vue";
    import MainLayoutDashboard from "@/Layouts/MainLayoutDashboard.vue";
    import { authService } from "@/Services/authService.js";
    import { Head } from "@inertiajs/vue3";

    const isAuthenticated = ref(false);
    const cargando = ref(false);
    const error = ref(null);
    const terminoBusqueda = ref("");

    const incidencias = ref([]);
    const usuarios = ref([]);

    const URL_API = "/api";

    const handleAuthenticated = async (status) => {
        isAuthenticated.value = status;

        if (status) {
            const token = authService.getToken();

            authService.setAxiosToken(token);

            await authService.verifyToken(token);

            // await cargarUsuarios();
            await cargarIncidencias();
        }
    };

    async function cargarIncidencias() {
        cargando.value = true;
        error.value = null;

        try {
            // console.log("Token enviado:", axios.defaults.headers.common["Authorization"]);

            const res = await axios.post(`${URL_API}/classroom-reports/report/all`);
            console.log("📌 Datos recibidos:", res.data);

            incidencias.value = res.data.data || res.data;
        } catch (err) {
            console.error("❌ Error en API:", err.response);
            error.value = "Error al cargar las incidencias.";
        } finally {
            cargando.value = false;
        }
    }

    // async function cargarUsuarios() {
    //     try {
    //         const res = await axios.get(`${URL_API}/users/get/all`);
    //         usuarios.value = res.data.data || res.data;

    //         console.log(usuarios.value);
    //     } catch (error) {
    //         console.error("Error cargando usuarios:", error);
    //     }
    // }

    // const obtenerNombreUsuario = (item) => {
    //     // 1. Si la API ya devuelve usuario_nombre
    //     if (item.usuario_nombre) return item.usuario_nombre;

    //     // 2. Buscar usuario en la lista cargada por ID
    //     const user = usuarios.value.find(u => u.id === item.usuario_reporta_id);

    //     if (user) return user.name || user.nombre;

    //     // 3. Fallback
    //     if (item.usuario_reporta_id) return `Usuario #${item.usuario_reporta_id}`;

    //     return "Usuario no disponible";
    // };

    // const obtenerNombreUsuario = (item) => {
    //     if (item.usuario_nombre) return item.usuario_nombre;
    //     if (item.user?.name) return item.user.name;
    //     if (item.usuario?.nombre) return item.usuario.nombre;
    //     if (item.reportado_por) return item.reportado_por;
    //     if (item.usuario_reporta_id) return `Usuario #${item.usuario_reporta_id}`;
    //     return "Usuario no disponible";
    // };

    const incidenciasFiltradas = computed(() => {
        if (!terminoBusqueda.value) return incidencias.value;
        const t = terminoBusqueda.value.toLowerCase();

        return incidencias.value.filter(i =>
            i.descripcion?.toLowerCase().includes(t) ||
            (i.aula_nombre || "").toLowerCase().includes(t) ||
            i.categoria?.toLowerCase().includes(t)
        );
    });

    const paginaActual = ref(1);
    const porPagina = ref(6);

    const totalPaginas = computed(() =>
        Math.ceil(incidenciasFiltradas.value.length / porPagina.value)
    );

    const incidenciasPaginadas = computed(() => {
        const start = (paginaActual.value - 1) * porPagina.value;
        return incidenciasFiltradas.value.slice(start, start + porPagina.value);
    });

    const paginasVisibles = computed(() => {
        const max = 5;
        let inicio = Math.max(1, paginaActual.value - 2);
        let fin = Math.min(totalPaginas.value, inicio + max - 1);

        if (fin - inicio < max - 1) {
            inicio = Math.max(1, fin - max + 1);
        }

        return Array.from({ length: fin - inicio + 1 }, (_, i) => inicio + i);
    });

    const paginaAnterior = () => paginaActual.value--;
    const paginaSiguiente = () => paginaActual.value++;
    const irAPagina = (p) => paginaActual.value = p;

    const formatCategory = (c) => c.replace(/_/g, " ").replace(/\b\w/g, l => l.toUpperCase());

    const formatEstado = (e) => {
        const map = {
            reportado: "Reportado",
            en_revision: "En Revisión",
            asignado: "Asignado",
            en_proceso: "En Proceso",
            resuelto: "Resuelto",
            cerrado: "Cerrado",
        };
        return map[e] || e;
    };

    const getStatusBadge = (estado) => {
        const base = "px-3 py-1 text-xs font-bold rounded-full";

        return {
            reportado: base + " bg-red-100 text-red-800",
            en_revision: base + " bg-yellow-100 text-yellow-800",
            asignado: base + " bg-blue-100 text-blue-800",
            en_proceso: base + " bg-indigo-100 text-indigo-800",
            resuelto: base + " bg-green-100 text-green-800",
            cerrado: base + " bg-green-300 text-green-900",
        }[estado] || base + " bg-gray-100 text-gray-700";
    };

    const formatFecha = (fecha) => {
        if (!fecha) return "N/A";
        return new Date(fecha).toLocaleDateString("es-SV", {
            year: "numeric",
            month: "short",
            day: "numeric",
        });
    };

    onMounted(async () => {
        const token = authService.getToken();
        await authService.verifyToken(token);
    });
</script>