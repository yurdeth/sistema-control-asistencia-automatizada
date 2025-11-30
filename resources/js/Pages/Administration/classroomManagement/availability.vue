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
                <h1 :style="{color: colors.text_color_dark}" class="text-xl sm:text-2xl font-bold text-gray-900 mb-1">
                    Disponibilidad de Aulas
                </h1>
                <p class="text-gray-600 text-xs sm:text-sm">
                    Visualice y gestione todo lo relacionado con las aulas, solicitudes, reservas y su disponibilidad.
                </p>
            </div>

            <!-- Sistema de Pestañas -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-4 sm:mb-6">
                <div class="flex border-b border-gray-200 overflow-x-auto">
                    <!--Opción de Disponibilidad-->
                    <button
                        @click="tabActiva = 'disponibilidad'"
                        :class="[
                            'px-4 sm:px-6 py-3 sm:py-4 text-sm sm:text-base font-medium whitespace-nowrap transition-colors',
                            tabActiva === 'disponibilidad' ? 'border-b-2 text-blue-600' : 'text-gray-500 hover:text-gray-700'
                        ]"
                        :style="tabActiva === 'disponibilidad' ? {borderBottomColor: colors.btn_editar} : {}"
                    >
                            <i class="fa-solid fa-door-open mr-2"></i>
                            Disponibilidad
                    </button>
                    <!--Opción de Solicitudes-->
                    <button
                        @click="tabActiva = 'solicitudes'"
                        :class="[
                            'px-4 sm:px-6 py-3 sm:py-4 text-sm sm:text-base font-medium whitespace-nowrap transition-colors relative',
                            tabActiva === 'solicitudes' ? 'border-b-2 text-blue-600' : 'text-gray-500 hover:text-gray-700'
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
                    <!--Opción de Reservadas-->
                    <button
                        @click="tabActiva = 'reservas'"
                        :class="[
                            'px-4 sm:px-6 py-3 sm:py-4 text-sm sm:text-base font-medium whitespace-nowrap transition-colors',
                            tabActiva === 'reservas' ? 'border-b-2 text-blue-600' : 'text-gray-500 hover:text-gray-700'
                        ]"
                        :style="tabActiva === 'reservas' ? {borderBottomColor: colors.btn_editar} : {}"
                    >
                        <i class="fa-solid fa-calendar-check mr-2"></i>
                        Reservadas
                        <span v-if="totalReservas > 0"
                            :style="{background: colors.btn_ver_detalle}"
                            class="ml-2 px-2 py-0.5 text-xs rounded-full text-white">
                            {{ totalReservas }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- =================| Contenido de las opciones |================= -->

            <!-- CONTENIDO DE DISPONIBILIDAD -->
            <div v-show="tabActiva === 'disponibilidad'">
                <!-- Buscador y Filtros -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-4 md:p-6 mb-4 sm:mb-6">
                    <div class="flex flex-col gap-3 sm:gap-4">
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

                        <!--Opciones de filtro en los selects-->
                        <div class="flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
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

                                <select
                                    v-model="estadoFiltro"
                                    class="w-full sm:w-48 px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-xs sm:text-sm"
                                >
                                    <option value="disponibles">Aulas Disponibles</option>
                                    <option value="ocupadas">Aulas Ocupadas</option>
                                    <option value="todas">Todas las Aulas</option>
                                </select>
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

                <!-- Tabla de Aulas -->
                <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <div class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 border-b border-gray-200 bg-gray-50">
                        <h2 class="text-base sm:text-lg font-semibold text-gray-900">
                            {{ estadoFiltro === 'disponibles' ? 'Aulas Disponibles para Asignar' :
                            estadoFiltro === 'ocupadas' ? 'Aulas Ocupadas' : 'Todas las Aulas' }}
                        </h2>
                        <p class="text-xs sm:text-sm text-gray-600 mt-1">{{ aulasFiltradas.length }} aulas encontradas</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" scope="col">
                                        Aula
                                    </th>
                                    <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" scope="col">
                                        Capacidad
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
                                <tr v-for="aula in paginacionAulas.itemsPaginados.value" :key="aula.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2 sm:gap-3">
                                            <div :class="aula.disponible ? 'bg-blue-100' : 'bg-red-100'"
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
                                            @click="handleVerDetalles(aula)"
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

                                <tr v-if="aulasFiltradas.length === 0">
                                    <td class="px-4 sm:px-6 py-8 sm:py-12 text-center" colspan="4">
                                        <i :style="{color: colors.btn_editar}" class="fa-solid fa-face-meh text-3xl sm:text-4xl"></i>
                                        <p class="mt-3 sm:mt-4 text-xs sm:text-sm text-gray-500">No se encontraron aulas</p>
                                        <p class="text-xs text-gray-400 mt-1">Intente ajustar los filtros de búsqueda</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación Aulas -->
<div v-if="aulasFiltradas.length > 0"
    class="bg-white px-3 sm:px-4 md:px-6 py-3 flex flex-col items-end border-t border-gray-200 gap-2"
>
    <!-- Texto alineado a la derecha -->
    <div>
        <p class="text-xs sm:text-sm text-gray-700 text-right">
            Mostrando
            <span class="font-medium">{{ paginacionAulas.indiceInicio.value + 1 }}</span>
            a
            <span class="font-medium">{{ Math.min(paginacionAulas.indiceFin.value, aulasFiltradas.length) }}</span>
            de
            <span class="font-medium">{{ aulasFiltradas.length }}</span>
            resultados
        </p>
    </div>

                        <!-- Paginación alineada a la derecha -->
                        <nav aria-label="Pagination" class="inline-flex rounded-md shadow-sm -space-x-px">
                            <button
                                :disabled="paginacionAulas.paginaActual.value === 1"
                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-xs sm:text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                @click="paginacionAulas.paginaAnterior()"
                            >
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>

                            <button
                                v-for="pagina in paginacionAulas.paginasVisibles.value"
                                :key="pagina"
                                :class="[
                                    pagina === paginacionAulas.paginaActual.value
                                        ? 'z-10 border-blue-500 text-blue-600 bg-blue-50'
                                        : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                        'relative inline-flex items-center px-3 sm:px-4 py-2 border text-xs sm:text-sm font-medium'
                                    ]"
                                @click="paginacionAulas.irAPagina(pagina)"
                            >
                                {{ pagina }}
                            </button>

                            <button
                                :disabled="paginacionAulas.paginaActual.value === paginacionAulas.totalPaginas.value"
                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-xs sm:text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                @click="paginacionAulas.paginaSiguiente()"
                            >
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- CONTENIDO DE SOLICITUDES -->
        <div v-show="tabActiva === 'solicitudes'">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-4 md:p-6 mb-4 sm:mb-6">
                <div class="flex flex-col gap-3 sm:gap-4">
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

            <div v-if="cargandoSolicitudes" class="text-center py-12 bg-white rounded-lg border border-gray-200">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 border-b border-gray-200 bg-gray-50">
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Solicitudes de Reserva</h2>
                    <p class="text-xs sm:text-sm text-gray-600 mt-1">{{ solicitudesFiltradas.length }} solicitudes encontradas</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" scope="col">Solicitante</th>
                                <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" scope="col">Aula</th>
                                <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" scope="col">Fecha y Hora</th>
                                <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" scope="col">Actividad</th>
                                <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" scope="col">Estado</th>
                                <th class="px-3 sm:px-4 md:px-6 py-2 sm:py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap" scope="col">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="solicitud in paginacionSolicitudes.itemsPaginados.value" :key="solicitud.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-3 sm:px-4 md:px-6 py-3 sm:py-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2 sm:gap-3">
                                        <div class="flex-shrink-0 h-8 w-8 sm:h-10 sm:w-10 rounded-full bg-gray-200 flex items-center justify-center">
                                            <i class="fa-solid fa-user text-gray-600 text-xs sm:text-sm"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xs sm:text-sm font-medium text-gray-900 truncate">{{ solicitud.solicitante_nombre }}</div>
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
                                    <span v-if="solicitud.estado === 'pendiente'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pendiente</span>
                                    <span v-else-if="solicitud.estado === 'aprobada'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aprobada</span>
                                    <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Rechazada</span>
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
                                            @click="handleAprobarSolicitud(solicitud)"
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

                            <tr v-if="solicitudesFiltradas.length === 0">
                                <td class="px-4 sm:px-6 py-8 sm:py-12 text-center" colspan="6">
                                    <i :style="{color: colors.btn_editar}" class="fa-solid fa-inbox text-3xl sm:text-4xl"></i>
                                    <p class="mt-3 sm:mt-4 text-xs sm:text-sm text-gray-500">No se encontraron solicitudes</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Paginación Solicitudes -->
                <div v-if="solicitudesFiltradas.length > 0"
                    class="bg-white px-3 sm:px-4 md:px-6 py-3 flex flex-col sm:flex-row items-center justify-between border-t border-gray-200 gap-3"
                >
                    <div class="hidden sm:block">
                        <p class="text-xs sm:text-sm text-gray-700">
                            Mostrando
                            <span class="font-medium">{{ paginacionSolicitudes.indiceInicio.value + 1 }}</span>
                            a
                            <span class="font-medium">{{ Math.min(paginacionSolicitudes.indiceFin.value, solicitudesFiltradas.length) }}</span>
                            de
                            <span class="font-medium">{{ solicitudesFiltradas.length }}</span>
                            resultados
                        </p>
                    </div>

                    <nav aria-label="Pagination" class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                        <button
                            v-for="pagina in paginacionSolicitudes.paginasVisibles.value"
                            :key="pagina"
                            :class="[
                                pagina === paginacionSolicitudes.paginaActual.value
                                    ? 'z-10 border-blue-500 text-blue-600 bg-blue-50'
                                    : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50',
                                'relative inline-flex items-center px-3 sm:px-4 py-2 border text-xs sm:text-sm font-medium'
                            ]"
                            @click="paginacionSolicitudes.irAPagina(pagina)"
                        >
                            {{ pagina }}
                        </button>

                    </nav>
                </div>
            </div>
        </div>

        <!-- CONTENIDO DE RESERVAS -->
        <div v-show="tabActiva === 'reservas'">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3 sm:p-4 md:p-6 mb-4 sm:mb-6">
                <div class="flex flex-col gap-3 sm:gap-4">
                    <div class="w-full">
                        <div class="relative">
                            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input
                                v-model="busquedaReserva"
                                class="w-full pl-10 pr-4 py-2 text-sm sm:text-base border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm"
                                placeholder="Buscar por aula, materia o profesor..."
                                type="text"
                            >
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                        <input
                            v-model="filtroFechaReserva"
                            type="date"
                            class="w-full sm:w-48 px-3 sm:px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-xs sm:text-sm"
                        >
                        <button
                            v-if="filtroFechaReserva"
                            @click="filtroFechaReserva = ''"
                            class="text-sm text-blue-600 hover:text-blue-800 px-3"
                        >
                            Limpiar filtro
                        </button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <div
                    v-for="reserva in reservasFiltradas"
                    :key="reserva.id"
                    class="bg-gradient-to-br from-white to-indigo-50 rounded-xl p-4 sm:p-6 border-2 border-indigo-200 shadow-md hover:shadow-lg transition-shadow"
                >
                    <div class="flex items-center gap-3 mb-4">
                        <div :style="{background: colors.btn_ver_detalle}" class="text-white p-3 rounded-lg">
                            <i class="fa-solid fa-door-open text-xl sm:text-2xl"></i>
                        </div>
                        <h3 class="text-lg sm:text-xl font-bold text-gray-800">{{ reserva.aula_nombre }}</h3>
                    </div>

                    <div class="space-y-3">
                        <div class="flex items-center gap-2 text-gray-700">
                            <i class="fa-solid fa-calendar text-indigo-600"></i>
                            <span class="font-medium text-sm">{{ formatearFecha(reserva.fecha) }}</span>
                        </div>

                        <div class="flex items-center gap-2 text-gray-700">
                            <i class="fa-solid fa-clock text-indigo-600"></i>
                            <span class="text-sm">{{ reserva.hora_inicio }} - {{ reserva.hora_fin }}</span>
                        </div>

                        <div v-if="reserva.materia" class="pt-3 border-t border-indigo-200">
                            <p class="text-xs text-gray-600">Materia</p>
                            <p class="font-semibold text-gray-800 text-sm">{{ reserva.materia }}</p>
                        </div>

                        <div v-if="reserva.profesor">
                            <p class="text-xs text-gray-600">Profesor</p>
                            <p class="font-semibold text-gray-800 text-sm">{{ reserva.profesor }}</p>
                        </div>

                        <div class="pt-3 flex gap-2">
                            <button
                                @click="verDetallesReserva(reserva)"
                                class="flex-1 bg-gray-600 text-white px-3 py-2 rounded-lg hover:bg-gray-700 transition-colors text-xs sm:text-sm"
                            >
                                Ver detalles
                            </button>
                            <button
                                @click="handleCancelarReserva(reserva)"
                                :style="{background: colors.btn_eliminar}"
                                class="text-white px-3 py-2 rounded-lg hover:opacity-90 transition-opacity text-xs sm:text-sm"
                            >
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="reservasFiltradas.length === 0" class="col-span-full text-center py-12">
                    <i :style="{color: colors.btn_editar}" class="fa-solid fa-calendar-xmark text-4xl sm:text-5xl"></i>
                    <p class="mt-4 text-sm sm:text-base text-gray-500">No hay reservas registradas</p>
                    <p class="text-xs text-gray-400 mt-1">Las reservas aparecerán aquí una vez creadas</p>
                </div>
            </div>
        </div>
    </MainLayoutDashboard>

    <!--Parte donde se trabajan los Modales-->

    <!-- Modal Ver Detalles Aula -->
    <Modal :show="showModal" @close="handleCerrarModal">
        <div class="p-6 space-y-4">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-4xl font-extralight text-gray-900 tracking-tight mb-3">{{ aulaSeleccionada.nombre }}</h1>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-2 h-2 rounded-full transition-all"
                            :class="aulaSeleccionada.estado === 'disponible' ? 'bg-gray-900 shadow-[0_0_8px_rgba(17,23,39,0.4)]' : 'bg-gray-400'"
                        />
                        <span class="text-sm text-gray-600 font-light">{{ aulaSeleccionada.estado }}</span>
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-4xl font-extralight text-gray-900">{{ aulaSeleccionada.capacidad_pupitres }}</div>
                    <div class="text-xs text-gray-400 uppercase tracking-wider mt-2 font-light">Estudiantes</div>
                </div>
            </div>

            <div class="border-t border-gray-100" />

            <div class="grid gap-4">
                <div class="p-3 bg-gray-50 rounded-lg shadow-sm">
                    <p class="text-gray-600 text-sm">Ubicación</p>
                    <p class="font-semibold text-gray-800">{{ aulaSeleccionada.ubicacion }}</p>
                </div>

                <div class="p-3 bg-gray-50 rounded-lg shadow-sm">
                    <p class="text-gray-600 text-sm">Recursos</p>
                    <p class="font-semibold text-gray-800 text-gray-500 italic">
                        {{ aulaSeleccionada.recurso || 'No hay recursos asignados' }}
                    </p>
                </div>
            </div>

            <div v-if="aulaSeleccionada.fotos && aulaSeleccionada.fotos.length > 0" class="pt-2 border-t">
                <h3 class="text-lg font-semibold mb-2">Imágenes del aula</h3>
                <div class="overflow-x-auto scrollbar-hide">
                    <div class="flex gap-2">
                        <img
                            v-for="(foto, index) in aulaSeleccionada.fotos"
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

    <!-- Lightbox Modal -->
    <Modal :show="mostrarLightbox" @close="cerrarLightbox">
        <LightboxModal
            :imagenes="aulaSeleccionada.fotos || []"
            :indiceInicial="imagenActual"
            @close="cerrarLightbox"
        />
    </Modal>

    <!-- Modal Asignar Aula -->
    <Modal :show="assignClassrooms" @close="handleCerrarModalAsignar">
        <div class="p-6 space-y-4">
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
                        :api-url="`/api/subjects/for-select`"
                        label="Materia"
                        placeholder="Buscar materia por nombre o código..."
                        @change="fetchGroups(selectedSubject)"
                    />
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="grupo">Grupo</label>
                    <select
                        id="grupo"
                        v-model="selectedGroup"
                        class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        @change="fetchSubjectProfessors(selectedSubject)"
                    >
                        <option disabled value="">Seleccione el grupo</option>
                        <option v-for="group in groups" :key="group.id" :value="group.id">
                            {{ group.numero_grupo }}
                        </option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="responsible">Responsable</label>
                    <select
                        id="responsible"
                        v-model="selectedResponsible"
                        class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        @change="fetchSchedules(selectedGroup)"
                    >
                        <option disabled value="">Seleccione el responsable del aula</option>
                        <option v-if="responsible" :value="responsible.id">{{ responsible.nombre_completo }}</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="schedule">Horario</label>
                    <select
                        id="schedule"
                        v-model="selectedSchedule"
                        class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    >
                        <option disabled value="">Seleccione el horario</option>
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
                    />
                </div>

                <div class="flex justify-end">
                    <button
                        class="mr-3 px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 transition-colors"
                        type="button"
                        @click="handleCerrarModalAsignar"
                    >
                        Cancelar
                    </button>
                    <button
                        class="px-4 py-2 text-white rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        type="button"
                        :style="{background: colors.btn_agregar}"
                        :disabled="!selectedSchedule || !selectedDate || !aula.id"
                        @click="handleSendReservation"
                    >
                        Asignar Aula
                    </button>
                </div>
            </div>
        </div>
    </Modal>

</template>

<script setup>
    //Import con los componentes, composables y utilidades necesarios para la vista
    import { Head } from '@inertiajs/vue3';
    import { ref, onMounted } from 'vue';
    import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
    import Modal from "@/Components/Modal.vue";
    import LightboxModal from "@/Components/AdministrationComponent/LightboxModal.vue";
    import SearchableSelectMejorado from "@/Components/SearchableSelectMejorado.vue";
    import { colors } from '@/UI/color';
    // Imports de composables personalizados relacionados con disponibilidad de aulas
    import { useAulas } from '@/Services/availability_services/useAulas';
    import { useAsignacionAula } from '@/Services/availability_services/useAsignacionAula';
    import { useSolicitudes } from '@/Services/availability_services/useSolicitudes';
    import { useReservas } from '@/Services/availability_services/useReservas';
    import { usePaginacion } from '@/Services/availability_services/usePaginacion';
    import { useModal } from '@/Services/availability_services/useModal';

    // ==================== COMPOSABLES ====================
    // Composable encargado de obtener y gestionar información de aulas
    const {
        todasLasAulas,
        aulas,
        cargando,
        busquedaAula,
        capacidadSeleccionada,
        estadoFiltro,
        aulasFiltradas,
        aulasDisponiblesCount,
        aulasOcupadasCount,
        fetchAula,
        recargarAulas,
        liberarAula,
        cargarAulasIniciales
    } = useAulas();

    // Composable para manejar la asignación de aulas (selección de profesor, grupo, horario, etc.)
    const {
        aula,
        selectedSubject,
        selectedGroup,
        selectedResponsible,
        selectedSchedule,
        selectedDate,
        groups,
        responsible,
        schedules,
        assignClassrooms,
        fetchGroups,
        fetchSubjectProfessors,
        fetchSchedules,
        sendReservation,
        openAssignClassroomModal,
        closeModal
    } = useAsignacionAula();

    // Composable para gestionar las solicitudes de aulas
    const {
        solicitudes,
        cargandoSolicitudes,
        busquedaSolicitud,
        estadoSolicitudFiltro,
        solicitudesPendientesCount,
        solicitudesFiltradas,
        cargarSolicitudes,
        aprobarSolicitud,
        rechazarSolicitud,
        verDetallesSolicitud
    } = useSolicitudes();

    // Composable para gestionar reservas de aulas existentes
    const {
        busquedaReserva,
        filtroFechaReserva,
        reservasFiltradas,
        totalReservas,
        cargarReservas,
        verDetallesReserva,
        cancelarReserva,
        formatearFecha
    } = useReservas();

    // Paginación aplicada a listas filtradas (aulas y solicitudes)
    const paginacionAulas = usePaginacion(aulasFiltradas, 10);
    const paginacionSolicitudes = usePaginacion(solicitudesFiltradas, 10);

    // Composable genérico para manejo de modales e imágenes
    const {
        showModal,
        mostrarLightbox,
        imagenActual,
        abrirModal: abrirModalGenerico,
        cerrarModal: cerrarModalGenerico,
        abrirLightbox,
        cerrarLightbox
    } = useModal();

    // ==================== ESTADOS LOCALES ====================
    const tabActiva = ref('disponibilidad');
    const isLoading = ref(true);
    const isAuthenticated = localStorage.getItem('isAuthenticated');
    const aulaSeleccionada = ref({});

    // ==================== HANDLERS ====================
    // Abrir modal con detalles completos de un aula
    const handleVerDetalles = async (aulaData) => {
        const aulaCompleta = await fetchAula(aulaData.id);
        if (aulaCompleta) {
            aulaSeleccionada.value = aulaCompleta;
            showModal.value = true;
        }
    };

    // Cierra el modal de detalles
    const handleCerrarModal = () => {
        showModal.value = false;
        aulaSeleccionada.value = {};
    };

    // Cierra el modal de asignar aula
    const handleCerrarModalAsignar = () => {
        closeModal();
    };

    // Envía una nueva reserva y luego refresca datos generales
    const handleSendReservation = async () => {
        const success = await sendReservation(async () => {
            await recargarAulas();
            await cargarReservas();
        });
    };

    // Aprueba una solicitud y recarga las aulas
    const handleAprobarSolicitud = async (solicitud) => {
        await aprobarSolicitud(solicitud, recargarAulas);
    };

    // Cancela una reserva y recarga las aulas disponibles
    const handleCancelarReserva = async (reserva) => {
        await cancelarReserva(reserva, recargarAulas);
    };

    // ==================== LIFECYCLE ====================
    // Al montar el componente se cargan: aulas iniciales, solicitudes pendientes y reservas
    onMounted(async () => {
        try {
            await cargarAulasIniciales();
            await cargarSolicitudes();
            await cargarReservas();
        } catch (error) {
            console.error('Error en inicialización:', error);
        } finally {
            isLoading.value = false;
        }
    });
</script>
