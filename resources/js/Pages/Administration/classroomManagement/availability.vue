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
                <h1 :style="{color: colors.text_color_dark}" class="text-xl sm:text-2xl font-bold text-gray-900 mb-1"> Disponibilidad
                    de Aulas </h1>
                <p class="text-gray-600 text-xs sm:text-sm">
                    Visualice y gestione las aulas, solicitudes de reserva y disponibilidad
                </p>
            </div>

            <!-- Sistema de Pestañas -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4 sm:mb-6">
                <div class="flex border-b border-gray-200 overflow-x-auto">
                    <button
                        @click="tabActiva = 'disponibilidad'"
                        :class="[
                            'px-4 sm:px-6 py-3 sm:py-4 text-sm sm:text-base font-medium whitespace-nowrap transition-colors',
                            tabActiva === 'disponibilidad'
                                ? 'border-b-2 text-blue-600'
                                : 'text-gray-500 hover:text-gray-700'
                        ]"
                        :style="tabActiva === 'disponibilidad' ? {borderBottomColor: colors.btn_editar} : {}"
                    >
                        <i class="fa-solid fa-door-open mr-2"></i>
                        Disponibilidad
                    </button>
                    <button
                        @click="tabActiva = 'solicitudes'"
                        :class="[
                            'px-4 sm:px-6 py-3 sm:py-4 text-sm sm:text-base font-medium whitespace-nowrap transition-colors relative',
                            tabActiva === 'solicitudes'
                                ? 'border-b-2 text-blue-600'
                                : 'text-gray-500 hover:text-gray-700'
                        ]"
                        :style="tabActiva === 'solicitudes' ? {borderBottomColor: colors.btn_editar} : {}"
                    >
                        <i class="fa-solid fa-clipboard-list mr-2"></i>
                        Solicitudes
                        <span v-if="solicitudesPendientesCount > 0"
                            :style="{background: colors.btn_eliminar}"
                            class="ml-2 px-2 py-0.5 text-xs rounded-full text-white">
                            {{ solicitudesPendientesCount }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- CONTENIDO DE DISPONIBILIDAD -->
            <div v-show="tabActiva === 'disponibilidad'">
                <!--Parte del buscador, filtros y opción de agregar-->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-4 md:p-6 mb-4 sm:mb-6">
                    <div class="flex flex-col gap-3 sm:gap-4">
                        <!-- Buscador -->
                        <div class="w-full">
                            <div class="relative">
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input
                                    v-model="busquedaAula"
                                    class="w-full pl-10 pr-4 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm"
                                    placeholder="Buscar aula por nombre, código o sector..."
                                    type="text"
                                >
                            </div>
                        </div>

                        <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                                <!--Para filtrar por cantidad de asientos-->
                                <select
                                    v-model="capacidadSeleccionada"
                                    class="w-full sm:w-64 px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-xs sm:text-sm"
                                >
                                    <option value="all">Todas las capacidades</option>
                                    <option value="pequena">Pequeña (1-30 asientos)</option>
                                    <option value="mediana">Mediana (31-70 asientos)</option>
                                    <option value="grande">Grande (71-100 asientos)</option>
                                    <option value="muy-grande">Muy grande (100+ asientos)</option>
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
                                    :style="{background:colors.btn_agregar}"
                                    class="px-6 py-2 text-white rounded-lg transition-colors text-sm font-medium whitespace-nowrap hover:opacity-90"
                                    @click="abrirModalReserva"
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
                        <div class="text-2xl font-bold text-gray-900">{{ todasLasAulas.length }}</div>
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
                                <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap"
                                    scope="col">
                                    Aula
                                </th>
                                <!--                                <th scope="col" class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">
                                                                    Ubicación
                                                                </th>-->
                                <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap"
                                    scope="col">
                                    Capacidad
                                </th>
                                <!--                                <th scope="col" class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden md:table-cell">
                                                                    Tipo
                                                                </th>-->
                                <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap"
                                    scope="col">
                                    Estado
                                </th>
                                <!--                                <th scope="col" class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden lg:table-cell">
                                                                    Recursos
                                                                </th>-->
                                <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap"
                                    scope="col">
                                    Acciones
                                </th>
                            </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="aula in aulasPaginadas" :key="aula.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2 sm:gap-3">
                                        <div
                                            :class="aula.disponible ? 'bg-blue-100' : 'bg-red-100'"
                                            class="flex-shrink-0 h-8 w-8 sm:h-10 sm:w-10 rounded-lg flex items-center justify-center text-xs sm:text-base">
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

                                <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-1">
                                        <i class="fa-solid fa-people-group text-xs sm:text-sm"></i>
                                        <span class="text-xs sm:text-sm text-gray-900">{{ aula.capacidad_pupitres }}</span>
                                    </div>
                                </td>

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

                                <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 whitespace-nowrap text-right text-xs sm:text-sm font-medium">
                                    <button
                                        :style="{background:colors.btn_ver_detalle}"
                                        class="mr-2 text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg hover:opacity-90 transition-opacity text-xs sm:text-sm"
                                        @click="abrirModal({ aula, modo: 'ver' })"
                                    >
                                        Ver detalles
                                    </button>
                                    <button
                                        v-if="aula.disponible"
                                        :style="{background:colors.btn_reservar}"
                                        class="text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg hover:opacity-90 transition-opacity text-xs sm:text-sm"
                                        @click="openAssignClassroomModal(aula)"
                                    >
                                        Asignar
                                    </button>
                                    <button
                                        v-else
                                        class="bg-green-600 text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg hover:bg-green-700 transition-colors text-xs sm:text-sm"
                                        @click="liberarAula(aula)"
                                    >
                                        Liberar
                                    </button>
                                </td>
                            </tr>

                            <!-- Estado vacío -->
                            <tr v-if="aulasFiltradas.length === 0">
                                <td class="px-4 sm:px-6 py-8 sm:py-12 text-center" colspan="7">
                                    <i :style="{color: colors.btn_editar}" class="fa-solid fa-face-meh text-3xl sm:text-4xl"></i>
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
                        <nav aria-label="Pagination" class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <button
                                :disabled="paginaActual === 1"
                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-xs sm:text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                @click="paginaAnterior"
                            >
                                <span class="sr-only">Anterior</span>
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>

                            <button
                                v-for="pagina in paginasVisibles"
                                :key="pagina"
                                :class="[
                                    pagina === paginaActual
                                        ? 'z-10 border-blue-500 text-blue-600 bg-blue-50'
                                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                    'relative inline-flex items-center px-3 sm:px-4 py-2 border text-xs sm:text-sm font-medium'
                                ]"
                                @click="irAPagina(pagina)"
                            >
                                {{ pagina }}
                            </button>

                            <button
                                :disabled="paginaActual === totalPaginas"
                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-xs sm:text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                @click="paginaSiguiente"
                            >
                                <span class="sr-only">Siguiente</span>
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </nav>
                    </div>
                </div>

            </div>

            <!-- CONTENIDO DE SOLICITUDES -->
            <div v-show="tabActiva === 'solicitudes'">
                <!-- Buscador y Filtros -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-4 md:p-6 mb-4 sm:mb-6">
                    <div class="flex flex-col gap-3 sm:gap-4">
                        <!-- Buscador -->
                        <div class="w-full">
                            <div class="relative">
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input
                                    v-model="busquedaSolicitud"
                                    class="w-full pl-10 pr-4 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm"
                                    placeholder="Buscar por solicitante, aula o actividad..."
                                    type="text"
                                >
                            </div>
                        </div>

                        <!-- Filtros -->
                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                            <select
                                v-model="estadoSolicitudFiltro"
                                class="w-full sm:w-48 px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-xs sm:text-sm"
                            >
                                <option value="todas">Todas las Solicitudes</option>
                                <option value="pendiente">Pendientes</option>
                                <option value="aprobada">Aprobadas</option>
                                <option value="rechazada">Rechazadas</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Loading -->
                <div v-if="cargandoSolicitudes" class="text-center py-12 bg-white rounded-lg border border-gray-200">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                </div>

                <!-- Tabla de Solicitudes -->
                <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-base sm:text-lg font-semibold text-gray-900">
                            Solicitudes de Reserva
                        </h2>
                        <p class="text-xs sm:text-sm text-gray-600 mt-1">{{ solicitudesFiltradas.length }} solicitudes encontradas</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" scope="col">
                                    Solicitante
                                </th>
                                <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" scope="col">
                                    Aula
                                </th>
                                <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" scope="col">
                                    Fecha y Hora
                                </th>
                                <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" scope="col">
                                    Actividad
                                </th>
                                <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" scope="col">
                                    Estado
                                </th>
                                <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" scope="col">
                                    Acciones
                                </th>
                            </tr>
                            </thead>

                            <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="solicitud in solicitudesPaginadas" :key="solicitud.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2 sm:gap-3">
                                        <div class="flex-shrink-0 h-8 w-8 sm:h-10 sm:w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                            <i class="fa-solid fa-user text-gray-600 text-xs sm:text-sm"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs sm:text-sm font-medium text-gray-900 truncate">
                                                {{ solicitud.solicitante_nombre }}
                                            </div>
                                            <div class="text-xs text-gray-500">{{ solicitud.departamento }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 whitespace-nowrap">
                                    <div class="text-xs sm:text-sm font-medium text-gray-900">{{ solicitud.aula_nombre }}</div>
                                    <div class="text-xs text-gray-500">{{ solicitud.aula_codigo }}</div>
                                </td>

                                <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 whitespace-nowrap">
                                    <div class="text-xs sm:text-sm text-gray-900">{{ formatearFecha(solicitud.fecha) }}</div>
                                    <div class="text-xs text-gray-500">{{ solicitud.hora_inicio }} - {{ solicitud.hora_fin }}</div>
                                </td>

                                <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4">
                                    <div class="text-xs sm:text-sm text-gray-900 max-w-xs truncate">{{ solicitud.actividad }}</div>
                                </td>

                                <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 whitespace-nowrap">
                                    <span v-if="solicitud.estado === 'pendiente'"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                        Pendiente
                                    </span>
                                    <span v-else-if="solicitud.estado === 'aprobada'"
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Aprobada
                                    </span>
                                    <span v-else
                                        class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        Rechazada
                                    </span>
                                </td>

                                <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 whitespace-nowrap text-right text-xs sm:text-sm font-medium">
                                    <button
                                        class="mr-2 text-gray-600 px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg hover:bg-gray-100 transition-colors text-xs sm:text-sm border border-gray-300"
                                        @click="verDetallesSolicitud(solicitud)"
                                    >
                                        Ver detalles
                                    </button>
                                    <template v-if="solicitud.estado === 'pendiente'">
                                        <button
                                            class="mr-2 bg-green-600 text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg hover:bg-green-700 transition-colors text-xs sm:text-sm"
                                            @click="aprobarSolicitud(solicitud)"
                                        >
                                            Aprobar
                                        </button>
                                        <button
                                            class="bg-red-600 text-white px-3 sm:px-4 py-1.5 sm:py-2 rounded-lg hover:bg-red-700 transition-colors text-xs sm:text-sm"
                                            @click="rechazarSolicitud(solicitud)"
                                        >
                                            Rechazar
                                        </button>
                                    </template>
                                </td>
                            </tr>

                            <!-- Estado vacío -->
                            <tr v-if="solicitudesFiltradas.length === 0">
                                <td class="px-4 sm:px-6 py-8 sm:py-12 text-center" colspan="6">
                                    <i :style="{color: colors.btn_editar}" class="fa-solid fa-inbox text-3xl sm:text-4xl"></i>
                                    <p class="mt-3 sm:mt-4 text-xs sm:text-sm text-gray-500">No se encontraron solicitudes</p>
                                    <p class="text-xs text-gray-400 mt-1">Intente ajustar los filtros de búsqueda</p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div v-if="solicitudesFiltradas.length > 0"
                        class="bg-white px-3 sm:px-4 md:px-6 py-3 flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 gap-3">
                        <div class="hidden sm:block">
                            <p class="text-xs sm:text-sm text-gray-700">
                                Mostrando
                                <span class="font-medium">{{ indiceInicioSolicitudes + 1 }}</span>
                                a
                                <span class="font-medium">{{ Math.min(indiceFinSolicitudes, solicitudesFiltradas.length) }}</span>
                                de
                                <span class="font-medium">{{ solicitudesFiltradas.length }}</span>
                                resultados
                            </p>
                        </div>

                        <nav aria-label="Pagination" class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <button
                                :disabled="paginaActualSolicitudes === 1"
                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-xs sm:text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                @click="paginaAnteriorSolicitudes"
                            >
                                <span class="sr-only">Anterior</span>
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>

                            <button
                                v-for="pagina in paginasVisiblesSolicitudes"
                                :key="pagina"
                                :class="[
                                    pagina === paginaActualSolicitudes
                                        ? 'z-10 border-blue-500 text-blue-600 bg-blue-50'
                                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                    'relative inline-flex items-center px-3 sm:px-4 py-2 border text-xs sm:text-sm font-medium'
                                ]"
                                @click="irAPaginaSolicitudes(pagina)"
                            >
                                {{ pagina }}
                            </button>

                            <button
                                :disabled="paginaActualSolicitudes === totalPaginasSolicitudes"
                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-xs sm:text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                @click="paginaSiguienteSolicitudes"
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

    <Modal :show="showModal" @close="cerrarModal">
        <div class="p-6 space-y-4">
            <!-- Header -->
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-4xl font-extralight text-gray-900 tracking-tight mb-3">{{ aula.nombre }}</h1>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-2 h-2 rounded-full transition-all"
                            :class="aula.estado === 'Activo' ? 'bg-gray-900 shadow-[0_0_8px_rgba(17,23,39,0.4)]' : 'bg-gray-400'"
                        />
                        <span class="text-sm text-gray-600 font-light">{{ aula.estado }}</span>
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-4xl font-extralight text-gray-900">{{ aula.capacidad_pupitres }}</div>
                    <div class="text-xs text-gray-400 uppercase tracking-wider mt-2 font-light">Estudiantes</div>
                </div>
            </div>

            <!-- Línea divisoria -->
            <div class="border-t border-gray-100" />

            <!-- DATOS DEL AULA EN TARJETAS -->
            <div class="grid gap-4">

                <div class="p-3 bg-gray-50 rounded-lg shadow-sm">
                    <p class="text-gray-600 text-sm">Ubicación</p>
                    <p class="font-semibold text-gray-800">{{ aula.ubicacion }}</p>
                </div>

                <div class="p-3 bg-gray-50 rounded-lg shadow-sm">
                    <p class="text-gray-600 text-sm">Recursos</p>
                    <p class="font-semibold text-gray-800 text-gray-500 italic">
                        {{ aula.recurso || 'No hay recursos asignados' }}
                    </p>
                </div>
            </div>

            <!-- GALERÍA DE IMÁGENES -->
            <div class="pt-2 border-t">
                <h3 class="text-lg font-semibold mb-2">Imágenes del aula</h3>
                <!-- Miniaturas -->
                <div class="overflow-x-auto scrollbar-hide">
                    <div class="flex gap-2">
                        <img
                            v-for="(foto, index) in aula.fotos"
                            :key="foto.id"
                            :src="foto.url"
                            alt="Aula"
                            class="w-20 h-20 object-cover cursor-pointer transition-all duration-300 border-2 border-transparent hover:border-gray-900 flex-shrink-0"
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

    <Modal :show="assignClassrooms" class="p-50 max-w-lg m-5" @close="cerrarModal">
        <div class="p-6 space-y-4">
            <!--Header-->
        <div class="flex items-start justify-between">
            <div>
                <h2 class="text-xl font-extralight text-gray-900 tracking-tight mb-3">
                    Asignar Aula: {{ aula.nombre }}
                </h2>
                <div class="flex items-center gap-3">
                    <div
                        class="w-2 h-2 rounded-full transition-all"
                        :class="aula.estado === 'disponible' ? 'bg-green-500 shadow-[0_0_8px_rgba(34,197,94,0.4)]' : 'bg-gray-400'"
                    />
                    <span class="text-sm text-gray-600">ID: {{ aula.id }}</span>
                </div>
            </div>
        </div>

            <div class="space-y-4">
                <div class="mb-4">
                    <SearchableSelectMejorado
                        v-model="selectedSubject"
                        :api-url="`${API_URL}/subjects/for-select`"
                        label="Materia"
                        placeholder="Buscar materia por nombre o código..."
                        @change="fetchGroups(selectedSubject)"
                    />
                </div>

                <div class="mb-4">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1" for="grupo">Grupo</label>
                        <select
                            id="grupo"
                            v-model="selectedGroup"
                            class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                            name="grupo"
                            @change="fetchSubjectProfessors(selectedSubject)"
                        >
                            <option disabled selected value="">Seleccione el grupo</option>
                            <option v-for="group in groups" :key="group.id" :value="group.id">
                                {{ group.numero_grupo }}
                            </option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="responsible">Responsable</label>
                    <select
                        id="responsible"
                        v-model="selectedResponsible"
                        class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        name="responsible"
                        @change="fetchSchedules(selectedGroup)"
                    >
                        <option disabled selected value="">Seleccione el responsable del aula</option>
                        <option v-if="responsible" :value="responsible.id"> {{ responsible.nombre_completo }}</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="schedule">Horario</label>
                    <select
                        id="schedule"
                        v-model="selectedSchedule"
                        class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        name="schedule"
                    >
                        <option disabled selected value="">Seleccione el horario</option>
                        <option v-for="schedule in schedules" :key="schedule.id" :value="schedule.id">
                            {{ schedule.dia_semana }} - {{ schedule.hora_inicio }} a {{ schedule.hora_fin }}
                        </option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="date">Fecha de la Clase</label>
                    <input
                        type="date"
                        id="date"
                        v-model="selectedDate"
                        :min="new Date().toISOString().split('T')[0]"
                        class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        name="date"
                    />
                </div>
                <div class="flex justify-end">
                    <button
                        class="mr-3 px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors"
                        type="button"
                        @click="cerrarModal"
                    >
                        Cancelar
                    </button>
                    <button
                        class="px-4 py-2 text-white rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        type="button"
                        :style="{background: colors.btn_agregar}"
                        :disabled="!selectedSchedule || !selectedDate || !aula.id"
                        @click.prevent="sendReservation"
                    >
                        Asignar Aula
                    </button>
                </div>
            </div>
        </div>
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
import SearchableSelectMejorado from "@/Components/SearchableSelectMejorado.vue";
import { colors } from '@/UI/color';

const isLoading = ref(true);

// API URL
const API_URL = '/api';

// Constantes reactivas para el control de la paginación
const paginaActual = ref(1);
const itemsPorPagina = ref(10);

// Definimos los estados a emplear
const fechaSeleccionada = ref(new Date());
const capacidadSeleccionada = ref('all');
const estadoFiltro = ref('disponibles');
const busquedaAula = ref('');
const todasLasAulas = ref([]);
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
let assignClassrooms = ref(false);
let selectedSubject = ref('');
let selectedGroup = ref('');
const subjects = ref([]);
const groups = ref([]);
const responsible = ref({});
const selectedResponsible = ref('');
const schedules = ref([]);
const selectedSchedule = ref('');
const selectedDate = ref('');
const aula_id = ref('');

// Variables de pestaña
const tabActiva = ref('disponibilidad');

// Variables de solicitudes (NUEVAS)
const solicitudes = ref([]);
const cargandoSolicitudes = ref(false);
const busquedaSolicitud = ref('');
const estadoSolicitudFiltro = ref('todas');
const paginaActualSolicitudes = ref(1);
const registrosPorPaginaSolicitudes = ref(10);

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

    // Filtrar por capacidad de asientos
    if (capacidadSeleccionada.value !== 'all') {
        resultado = resultado.filter(a => {
            const capacidad = a.capacidad_pupitres|| 0;

            switch (capacidadSeleccionada.value) {
                case 'pequena':
                    return capacidad >= 1 && capacidad <= 30;
                case 'mediana':
                    return capacidad >= 31 && capacidad <= 70;
                case 'grande':
                    return capacidad >= 71 && capacidad <= 100;
                case 'muy-grande':
                    return capacidad > 100;
                default:
                    return true;
            }
        });
    }
    return resultado;
});

const reservasDia = computed(() => {
    const fechaStr = fechaSeleccionada.value.toISOString().split('T')[0];
    return reservas.value.filter(r => r.fecha === fechaStr);
});

const totalReservas = computed(() => reservasDia.value.length);

const aulasDisponiblesCount = computed(() => {
    return todasLasAulas.value.filter(a => a.estado === 'disponible').length;
});

const aulasOcupadasCount = computed(() => {
    return todasLasAulas.value.filter(a => a.estado === 'ocupada' || a.estado === 'ocupado').length;
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

// nueva función para obtener TODAS las aulas
const fetchAllClassrooms = async () => {
    const response = await axios.get("/api/classrooms/get/all", { // Cambia esta ruta según tu API
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
        }
    });

    const data = response.data;

    if (!data.success) {
        alert("No se pudieron cargar las aulas");
        console.debug(data.message);
    }

    return data.data;
}

// ==================== COMPUTED DE SOLICITUDES ====================
const solicitudesPendientesCount = computed(() => {
    return solicitudes.value.filter(s => s.estado === 'pendiente').length;
});

const solicitudesFiltradas = computed(() => {
    let resultado = solicitudes.value;

    // Filtrar por búsqueda
    if (busquedaSolicitud.value) {
        resultado = resultado.filter(s =>
            s.solicitante_nombre.toLowerCase().includes(busquedaSolicitud.value.toLowerCase()) ||
            s.aula_nombre.toLowerCase().includes(busquedaSolicitud.value.toLowerCase()) ||
            s.actividad.toLowerCase().includes(busquedaSolicitud.value.toLowerCase())
        );
    }

    // Filtrar por estado
    if (estadoSolicitudFiltro.value !== 'todas') {
        resultado = resultado.filter(s => s.estado === estadoSolicitudFiltro.value);
    }

    return resultado;
});

const solicitudesPaginadas = computed(() => {
    return solicitudesFiltradas.value.slice(
        indiceInicioSolicitudes.value,
        indiceFinSolicitudes.value
    );
});

const indiceInicioSolicitudes = computed(() => {
    return (paginaActualSolicitudes.value - 1) * registrosPorPaginaSolicitudes.value;
});

const indiceFinSolicitudes = computed(() => {
    return paginaActualSolicitudes.value * registrosPorPaginaSolicitudes.value;
});

const totalPaginasSolicitudes = computed(() => {
    return Math.ceil(solicitudesFiltradas.value.length / registrosPorPaginaSolicitudes.value);
});

const paginasVisiblesSolicitudes = computed(() => {
    const paginas = [];
    for (let i = 1; i <= totalPaginasSolicitudes.value; i++) {
        paginas.push(i);
    }
    return paginas;
});

// ==================== MÉTODOS DE PAGINACIÓN AULAS ====================
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

// ==================== MÉTODOS DE AULAS ====================
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
    try {
        const response = await axios.get(`/api/classrooms/get/${id}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
            }
        });

        const data = response.data;
        if (!data.success) {
            alert("No se pudo obtener la información del aula");
            console.debug(data.message);
            return;
        }

        aula.value = data.data;
        // Clear selected values when fetching a new classroom
        selectedSubject.value = '';
        selectedGroup.value = '';
        selectedResponsible.value = '';
        selectedSchedule.value = '';
        selectedDate.value = '';
    } catch (error) {
        console.error('Error fetching classroom:', error);
        const errorMessage = error.response?.data?.message || "Error al obtener el aula";
        alert(errorMessage);
        aula.value = {};
    }
};


const fetchGroups = async (id) => {
    try {
        const response = await axios.get(`/api/groups/get/subject/${id}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
            }
        });

        const data = response.data;

        if (!data.success) {
            alert(data.message || "Error al obtener grupos");
            return;
        }

        groups.value = data.data;
    } catch (error) {
        console.error('Error fetching groups:', error);
        const errorMessage = error.response?.data?.message || "Error al obtener grupos";
        alert(errorMessage);
        groups.value = [];
    }
};

const fetchSubjectProfessors = async (id) => {
    try {
        const response = await axios.post(`/api/groups/get/professor/`,
            {
                grupo_id: selectedGroup.value,
                materia_id: id
            },
            {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                }
            }
        );

        const data = response.data;

        if (!data.success) {
            alert("No se encontraron grupos");
            return;
        }

        responsible.value = data.data;
    } catch (error) {
        console.error('Error fetching groups:', error);
        subjects.value = [];
    }
};

const fetchSchedules = async (id) => {
    try {
        const response = await axios.get(`/api/schedules/get/group/${id}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
            }
        });

        const data = response.data;

        if (!data.success) {
            alert(data.message || "Error al obtener los horarios");
            return;
        }

        schedules.value = data.data;
    } catch (error) {
        console.error('Error fetching schedules:', error);
        const errorMessage = error.response?.data?.message || "Error al obtener grupos";
        alert(errorMessage);
        schedules.value = [];
    }
};

const sendReservation = async () => {
    // Validaciones
    if (!aula.value?.id) {
        alert("No se ha seleccionado un aula válida");
        return false;
    }

    if (!selectedSchedule.value) {
        alert("Debe seleccionar un horario");
        return false;
    }

    if (!selectedDate.value) {
        alert("Debe seleccionar una fecha");
        return false;
    }

    try {
        const payload = {
            horario_id: selectedSchedule.value,
            fecha_clase: selectedDate.value
        };

        const response = await axios.post('/api/class-sessions/new', payload, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'application/json'
            }
        });

        const data = response.data;

        if (!data.success) {
            alert(data.message || "No se pudo crear la reserva");
            return false;
        }

        alert("Reserva creada exitosamente");

        await recargarAulas();

        cerrarModal();
        return true;
    } catch (error) {
        if (error.response?.data?.errors) {
            console.error('Errores de validación:', error.response.data.errors);
            const errores = Object.values(error.response.data.errors).flat().join('\n');
            alert(`Errores de validación:\n${errores}`);
        } else if (error.response?.data?.message) {
            alert(error.response.data.message);
        } else {
            alert("Ocurrió un error al crear la reserva");
        }

        return false;
    }
};

// Nueva función para recargar aulas
const recargarAulas = async () => {
    try {
        console.log('Recargando aulas...');

        const todasResponse = await fetchAllClassrooms();
        todasLasAulas.value = todasResponse.map(classroom => ({
            id: classroom.id,
            nombre: classroom.nombre,
            codigo: classroom.codigo,
            sector: classroom.sector ?? '',
            capacidad_pupitres: classroom.capacidad_pupitres,
            ubicacion: classroom.ubicacion,
            estado: classroom.estado,
            disponible: classroom.estado === 'disponible'
        }));

        const disponiblesResponse = await fetchAllAvailableClassrooms();
        aulas.value = disponiblesResponse.map(classroom => ({
            id: classroom.id,
            nombre: classroom.nombre,
            codigo: classroom.codigo,
            sector: classroom.sector ?? '',
            capacidad_pupitres: classroom.capacidad_pupitres,
            ubicacion: classroom.ubicacion,
            estado: classroom.estado,
            disponible: true
        }));

        console.log('Aulas recargadas');
    } catch (error) {
        console.error('Error al recargar aulas:', error);
    }
};

const abrirModal = ({aula: aulaData}) => {
    fetchAula(aulaData.id);
    showModal.value = true;
};

const cerrarModal = () => {
    showModal.value = false;
    assignClassrooms.value = false;

    // Reset
    aula.value = {};
    subjects.value = [];
    groups.value = [];
    schedules.value = [];
    responsible.value = {};

    // Reset selected values
    selectedSubject.value = '';
    selectedGroup.value = '';
    selectedResponsible.value = '';
    selectedSchedule.value = '';
    selectedDate.value = '';
};

const abrirLightbox = (index) => {
    imagenActual.value = index;
    mostrarLightbox.value = true;
};
const cerrarLightbox = () => (mostrarLightbox.value = false);

const openAssignClassroomModal = (aulaData) => {
    aula.value = aulaData;
    assignClassrooms.value = true;
};

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

// ==================== MÉTODOS DE SOLICITUDES ====================
const cargarSolicitudes = async () => {
    cargandoSolicitudes.value = true;
    try {
        // para llamar a la API y obtener las solicitudes

        solicitudes.value = [
            {
                id: 1,
                solicitante_nombre: 'Juan Pérez',
                departamento: 'Ingeniería',
                aula_nombre: 'Aula 101',
                aula_codigo: 'A-101',
                fecha: '2024-01-15',
                hora_inicio: '08:00',
                hora_fin: '10:00',
                actividad: 'Clase de Programación',
                estado: 'pendiente'
            },
            {
                id: 2,
                solicitante_nombre: 'María González',
                departamento: 'Ciencias',
                aula_nombre: 'Laboratorio 203',
                aula_codigo: 'L-203',
                fecha: '2024-01-16',
                hora_inicio: '10:00',
                hora_fin: '12:00',
                actividad: 'Práctica de Laboratorio',
                estado: 'pendiente'
            },
            {
                id: 3,
                solicitante_nombre: 'Carlos Ramírez',
                departamento: 'Matemáticas',
                aula_nombre: 'Aula 305',
                aula_codigo: 'A-305',
                fecha: '2024-01-14',
                hora_inicio: '14:00',
                hora_fin: '16:00',
                actividad: 'Seminario de Álgebra',
                estado: 'aprobada'
            }
        ];
    } catch (error) {
        console.error('Error al cargar solicitudes:', error);
        solicitudes.value = [];
    } finally {
        cargandoSolicitudes.value = false;
    }
};

const aprobarSolicitud = async (solicitud) => {
    try {
        // Acá iria lo de la lógica para aprobar en el backend

        const index = solicitudes.value.findIndex(s => s.id === solicitud.id);
        if (index !== -1) {
            solicitudes.value[index].estado = 'aprobada';
        }

        // Recargar aulas para actualizar disponibilidad
        const response = await fetchAllAvailableClassrooms();
        aulas.value = response.map(classroom => ({
            id: classroom.id,
            nombre: classroom.nombre,
            codigo: classroom.codigo,
            sector: classroom.sector ?? '',
            capacidad_pupitres: classroom.capacidad_pupitres,
            ubicacion: classroom.ubicacion,
            disponible: true
        }));

        alert('Solicitud aprobada exitosamente');
    } catch (error) {
        console.error('Error al aprobar solicitud:', error);
        alert('Error al aprobar la solicitud');
    }
};

const rechazarSolicitud = async (solicitud) => {
    const motivo = prompt('Ingrese el motivo del rechazo (opcional):');

    try {
        // Acá iria lo de para rechazar en el backend
        // si es que hay

        // Actualizar localmente
        const index = solicitudes.value.findIndex(s => s.id === solicitud.id);
        if (index !== -1) {
            solicitudes.value[index].estado = 'rechazada';
        }

        alert('Solicitud rechazada');
    } catch (error) {
        console.error('Error al rechazar solicitud:', error);
        alert('Error al rechazar la solicitud');
    }
};

const verDetallesSolicitud = (solicitud) => {
    // Aquí puedes abrir un modal con más detalles
    console.log('Ver detalles de solicitud:', solicitud);
    // Ejemplo: abrirModalDetallesSolicitud(solicitud);
};

const formatearFecha = (fecha) => {
    const date = new Date(fecha);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric'
    });
};

const paginaAnteriorSolicitudes = () => {
    if (paginaActualSolicitudes.value > 1) paginaActualSolicitudes.value--;
};

const paginaSiguienteSolicitudes = () => {
    if (paginaActualSolicitudes.value < totalPaginasSolicitudes.value) {
        paginaActualSolicitudes.value++;
    }
};

const irAPaginaSolicitudes = (pagina) => {
    paginaActualSolicitudes.value = pagina;
};

// ==================== LIFECYCLE ====================
onMounted(async () => {
    try {
        // Cargar TODAS las aulas del sistema
        const todasResponse = await fetchAllClassrooms();
        todasLasAulas.value = todasResponse.map(classroom => ({
            id: classroom.id,
            nombre: classroom.nombre,
            codigo: classroom.codigo,
            sector: classroom.sector ?? '',
            capacidad_pupitres: classroom.capacidad_pupitres,
            ubicacion: classroom.ubicacion,
            estado: classroom.estado,
            disponible: classroom.estado === 'disponible'
        }));

        // Cargar aulas disponibles para la tabla
        const disponiblesResponse = await fetchAllAvailableClassrooms();
        aulas.value = disponiblesResponse.map(classroom => ({
            id: classroom.id,
            nombre: classroom.nombre,
            codigo: classroom.codigo,
            sector: classroom.sector ?? '',
            capacidad_pupitres: classroom.capacidad_pupitres,
            ubicacion: classroom.ubicacion,
            estado: classroom.estado,
            disponible: true
        }));

        // Cargar solicitudes
        await cargarSolicitudes();

    } catch (error) {
        console.error('Error fetching classrooms:', error);
        aulas.value = [];
        todasLasAulas.value = [];
    } finally {
        isLoading.value = false;
    }
});
</script>
