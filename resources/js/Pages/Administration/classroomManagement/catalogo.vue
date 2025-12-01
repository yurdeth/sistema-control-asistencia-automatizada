<template>
    <Head title="Catalogo"/>

    <!-- Loader mientras verifica -->
    <Loader
        v-if="!isAuthenticated"
        @authenticated="handleAuthenticated"
        message="Verificando sesión..."
        :redirectDelay="2000"
    />

    <MainLayoutDashboard>
        <div class="p-6" v-if="isAuthenticated">
            <!-- Banner para usuarios invitados -->
            <div v-if="isInvitado" class="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-lg shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            Estás navegando como <strong>invitado</strong>. Solo puedes ver la información del catálogo de aulas.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Header -->
            <div class="mb-4 sm:mb-6">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 sm:gap-4 mb-4">
                    <div class="flex-1">
                        <h1 class="text-xl sm:text-2xl font-bold" :style="{color:colors.text_color_dark}">Catálogo de Aulas</h1>
                        <p class="text-gray-600 text-sm mt-1">
                            <span v-if="isInvitado">Explora las aulas disponibles dentro de la facultad</span>
                            <span v-else>Gestiona y visualiza todas las aulas disponibles dentro de la facultad</span>
                        </p>
                    </div>

                    <!-- ====== BOTÓN AGREGAR  -->
                    <button
                        v-if="canEdit"
                        class="hover:bg-blue-700 text-white px-3 sm:px-4 py-2 rounded-lg flex items-center justify-center gap-2 transition-colors text-sm sm:text-base whitespace-nowrap w-full sm:w-auto"
                        :style="{background: colors.btn_agregar}"
                        @click="openCreateModal"
                    >
                        <i class="fa-solid fa-plus"></i>
                        <span>Agregar Aula</span>
                    </button>
                </div>
            </div>

            <!-- Mensajes -->
            <div v-if="mensaje.mostrar"
                 :class="mensaje.tipo === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700'"
                 class="border-l-4 p-3 sm:p-4 mb-4 rounded">
                <div class="flex justify-between items-start gap-2">
                    <p class="font-medium text-sm sm:text-base flex-1">{{ mensaje.texto }}</p>
                    <button @click="cerrarMensaje"
                            class="text-xl font-bold flex-shrink-0 hover:opacity-70 transition-opacity">
                        &times;
                    </button>
                </div>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow-sm p-3 sm:p-4 border border-gray-200">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    <!-- Búsqueda -->
                    <div class="relative w-full sm:col-span-2 lg:col-span-1">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
                        <input
                            v-model="filtros.busqueda"
                            type="text"
                            placeholder="Buscar aula, código o ubicación..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                        />
                    </div>

                    <!-- Filtro por capacidad -->
                    <select
                        v-model="filtros.capacidad_pupitres"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm appearance-none bg-white cursor-pointer"
                    >
                        <option value="all">Todas las capacidades</option>
                        <option value="small">Pequeña (≤30 personas)</option>
                        <option value="medium">Mediana (31-100 personas)</option>
                        <option value="large">Grande (>100 personas)</option>
                    </select>

                    <!-- Filtro por estado -->
                    <select
                        v-model="filtros.estado"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm appearance-none bg-white cursor-pointer"
                    >
                        <option value="all">Todos los estados</option>
                        <option value="disponible">Disponible</option>
                        <option value="ocupada">Ocupada</option>
                        <option value="mantenimiento">Mantenimiento</option>
                        <option value="inactiva">Inactiva</option>
                    </select>
                </div>
            </div>
            <br>

            <!-- Resultados -->
            <div class="mb-4 text-gray-600 text-sm">
                <span v-if="usarPaginacionBackend">
                    Mostrando {{ aulasBackend.length }} de {{ paginacionBackend.total }} aulas (página {{ paginacionBackend.pagina_actual }} de {{ paginacionBackend.ultima_pagina }})
                </span>
                <span v-else>
                    Mostrando {{ aulasFiltradas.length }} de {{ aulas.length }} aulas
                </span>
            </div>

            <!-- Loading -->
            <div v-if="cargando || (usarPaginacionBackend && cargandoPaginado)" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="text-gray-600 mt-4">
                    <span v-if="usarPaginacionBackend">Cargando aulas paginadas...</span>
                    <span v-else>Cargando aulas...</span>
                </p>
            </div>

            <!-- Error al cargar -->
            <div v-else-if="error || (usarPaginacionBackend && errorPaginado)" class="text-center py-12 bg-red-50 rounded-lg border border-red-200">
                <i class="fa-solid fa-exclamation-triangle text-6xl text-red-400 mb-4"></i>
                <p class="text-red-600 text-lg font-semibold">Error al cargar las aulas</p>
                <p class="text-gray-600 text-sm mt-2">{{ usarPaginacionBackend ? errorPaginado : error }}</p>
                <button
                    @click="usarPaginacionBackend ? cargarAulasPaginadas() : cargarAulas()"
                    class="mt-4 bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700"
                >
                    <i class="fa-solid fa-rotate-right"></i> Reintentar
                </button>
            </div>

            <!-- Lista de aulas -->
            <div v-else-if="(usarPaginacionBackend ? aulasBackend.length > 0 : aulas.length > 0)" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <!--  agrego el canEdit y el userRole al componente card  -->
                <Card
                    v-for="aula in usarPaginacionBackend ? aulasBackend : aulasPaginadas"
                    :key="aula.id"
                    :aula="aula"
                    :can-edit="canEdit"
                    :user-role="authService.getUserRole()"
                />
            </div>

            <!-- Sin resultados -->
            <div v-if="!usarPaginacionBackend && aulasFiltradas.length === 0 && (filtros.busqueda || filtros.capacidad_pupitres !== 'all' || filtros.estado !== 'all')"
                 class="text-center py-12 bg-gray-50 rounded-lg">
                <div class="flex flex-col items-center gap-3">
                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-gray-600 text-lg font-medium">No se encontraron resultados</p>
                    <p class="text-gray-500 text-sm max-w-md mx-auto px-4">
                        <span v-if="filtros.busqueda">No hay aulas que coincidan con <strong>"{{ filtros.busqueda }}"</strong></span>
                        <span v-if="filtros.capacidad_pupitres !== 'all'">
                            <br v-if="filtros.busqueda" />
                            Con capacidad: {{ filtros.capacidad_pupitres === 'small' ? 'Pequeña (≤30 personas)' :
                                            filtros.capacidad_pupitres === 'medium' ? 'Mediana (31-100 personas)' : 'Grande (>100 personas)' }}
                        </span>
                        <span v-if="filtros.estado !== 'all'">
                            <br v-if="filtros.busqueda || filtros.capacidad_pupitres !== 'all'" />
                            Estado: <strong>{{ filtros.estado }}</strong>
                        </span>
                    </p>
                    <button
                        @click="limpiarFiltros"
                        class="mt-4 px-6 py-2.5 text-white rounded-lg transition-colors flex items-center gap-2 font-medium"
                        :style="{backgroundColor: colorButton}"
                    >
                        <i class="fa-solid fa-xmark"></i>
                        Limpiar filtros
                    </button>
                </div>
            </div>

            <!-- Sin resultados con paginación backend -->
            <div v-else-if="usarPaginacionBackend && aulasBackend.length === 0" class="text-center py-12 bg-gray-50 rounded-lg">
                <div class="flex flex-col items-center gap-3">
                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-gray-600 text-lg font-medium">
                        <span v-if="paginacionBackend.pagina_actual === 1">No hay aulas registradas</span>
                        <span v-else>No hay aulas en esta página</span>
                    </p>
                    <p class="text-gray-500 text-sm">
                        <span v-if="paginacionBackend.pagina_actual === 1">Comienza agregando tu primera aula</span>
                        <span v-else>Intenta con otra página o ajusta los filtros</span>
                    </p>
                </div>
            </div>

            <!-- Sin aulas -->
            <div v-else-if="!usarPaginacionBackend && aulas.length === 0" class="text-center py-12 bg-gray-50 rounded-lg">
                <div class="flex flex-col items-center gap-3">
                    <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-gray-600 text-lg font-medium">No hay aulas registradas</p>
                    <p class="text-gray-500 text-sm">Comienza agregando tu primera aula</p>
                </div>
            </div>

            <!-- Controles de paginación -->
            <!-- Paginación Frontend -->
            <div v-if="!usarPaginacionBackend && aulasFiltradas.length > 0" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6">
                <div class="text-sm text-gray-700">
                    Mostrando {{ ((paginaActual - 1) * porPagina) + 1 }} a {{ Math.min(paginaActual * porPagina, aulasFiltradas.length) }}
                    de {{ aulasFiltradas.length }} resultados
                </div>

                <div class="flex items-center gap-2">
                    <!-- Selector de elementos por página -->
                    <select
                        v-model="porPagina"
                        @change="paginaActual = 1"
                        class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option :value="6">6 por página</option>
                        <option :value="9">9 por página</option>
                        <option :value="12">12 por página</option>
                        <option :value="24">24 por página</option>
                    </select>

                    <!-- Controles de navegación -->
                    <div class="flex items-center gap-1">
                        <!-- Primera página -->
                        <button
                            @click="paginaActual = 1"
                            :disabled="paginaActual === 1"
                            class="px-2 py-1 text-sm rounded bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Primera página"
                        >
                            <i class="fa-solid fa-angle-double-left"></i>
                        </button>

                        <!-- Página anterior -->
                        <button
                            @click="paginaActual--"
                            :disabled="paginaActual === 1"
                            class="px-2 py-1 text-sm rounded bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Página anterior"
                        >
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>

                        <!-- Números de página -->
                        <div class="flex items-center gap-1">
                            <template v-for="page in displayedPages" :key="page">
                                <button
                                    v-if="page !== '...'"
                                    @click="paginaActual = page"
                                    :class="page === paginaActual ? 'bg-blue-500 text-white' : 'bg-gray-100 hover:bg-gray-200'"
                                    class="px-3 py-1 text-sm rounded font-medium transition-colors"
                                >
                                    {{ page }}
                                </button>
                                <span
                                    v-else
                                    class="px-2 py-1 text-sm text-gray-500"
                                >
                                    ...
                                </span>
                            </template>
                        </div>

                        <!-- Página siguiente -->
                        <button
                            @click="paginaActual++"
                            :disabled="paginaActual === totalPaginas"
                            class="px-2 py-1 text-sm rounded bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Página siguiente"
                        >
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>

                        <!-- Última página -->
                        <button
                            @click="paginaActual = totalPaginas"
                            :disabled="paginaActual === totalPaginas"
                            class="px-2 py-1 text-sm rounded bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Última página"
                        >
                            <i class="fa-solid fa-angle-double-right"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Paginación Backend -->
            <div v-else-if="usarPaginacionBackend && paginacionBackend.total > 0" class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-6">
                <div class="text-sm text-gray-700">
                    Mostrando {{ aulasBackend.length }} de {{ paginacionBackend.total }} aulas
                    (página {{ paginacionBackend.pagina_actual }} de {{ paginacionBackend.ultima_pagina }})
                </div>

                <div class="flex items-center gap-2">
                    <!-- Selector de elementos por página -->
                    <select
                        v-model="paginacionBackend.por_pagina"
                        @change="paginacionBackend.pagina_actual = 1; cargarAulasPaginadas()"
                        class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    >
                        <option :value="10" selected>10 por página</option>
                        <option :value="15">15 por página</option>
                        <option :value="25">25 por página</option>
                        <option :value="50">50 por página</option>
                        <option :value="100">100 por página</option>
                    </select>

                    <!-- Controles de navegación -->
                    <div class="flex items-center gap-1">
                        <!-- Primera página -->
                        <button
                            @click="paginacionBackend.pagina_actual = 1; cargarAulasPaginadas()"
                            :disabled="paginacionBackend.pagina_actual === 1"
                            class="px-2 py-1 text-sm rounded bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Primera página"
                        >
                            <i class="fa-solid fa-angle-double-left"></i>
                        </button>

                        <!-- Página anterior -->
                        <button
                            @click="paginacionBackend.pagina_actual--; cargarAulasPaginadas()"
                            :disabled="paginacionBackend.pagina_actual === 1"
                            class="px-2 py-1 text-sm rounded bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Página anterior"
                        >
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>

                        <!-- Números de página -->
                        <div class="flex items-center gap-1">
                            <template v-for="page in displayedPagesBackend" :key="page">
                                <button
                                    v-if="page !== '...'"
                                    @click="paginacionBackend.pagina_actual = page; cargarAulasPaginadas()"
                                    :class="page === paginacionBackend.pagina_actual ? 'bg-blue-500 text-white' : 'bg-gray-100 hover:bg-gray-200'"
                                    class="px-3 py-1 text-sm rounded font-medium transition-colors"
                                >
                                    {{ page }}
                                </button>
                                <span
                                    v-else
                                    class="px-2 py-1 text-sm text-gray-500"
                                >
                                    ...
                                </span>
                            </template>
                        </div>

                        <!-- Página siguiente -->
                        <button
                            @click="paginacionBackend.pagina_actual++; cargarAulasPaginadas()"
                            :disabled="paginacionBackend.pagina_actual === paginacionBackend.ultima_pagina"
                            class="px-2 py-1 text-sm rounded bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Página siguiente"
                        >
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>

                        <!-- Última página -->
                        <button
                            @click="paginacionBackend.pagina_actual = paginacionBackend.ultima_pagina; cargarAulasPaginadas()"
                            :disabled="paginacionBackend.pagina_actual === paginacionBackend.ultima_pagina"
                            class="px-2 py-1 text-sm rounded bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed"
                            title="Última página"
                        >
                            <i class="fa-solid fa-angle-double-right"></i>
                        </button>
                    </div>
                </div>
            </div>
            <br>
        </div>


        <Modal :show="showModal" @close="closeModal">
            <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold">
                    {{ isEditMode ? 'Editar aula' : 'Agregar aula' }}
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium">Código</label>
                            <input
                                type="text"
                                v-model="form.codigo"
                                class="w-full mt-1 border-gray-300 rounded-md"
                                :class="{ 'border-red-500': formErrors.codigo }"
                            />
                            <p v-if="formErrors.codigo" class="text-red-500 text-xs mt-1">
                                {{ formErrors.codigo[0] }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Nombre</label>
                            <input
                                type="text"
                                v-model="form.nombre"
                                @input="form.nombre = form.nombre.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/g, '')"
                                class="w-full mt-1 border-gray-300 rounded-md"
                                :class="{ 'border-red-500': formErrors.nombre }"
                            />
                            <p v-if="formErrors.nombre" class="text-red-500 text-xs mt-1">
                                {{ formErrors.nombre[0] }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Capacidad</label>
                            <input
                                type="number"
                                v-model="form.capacidad_pupitres"
                                class="w-full mt-1 border-gray-300 rounded-md"
                                max="150"
                                min="1"
                                :class="{ 'border-red-500': formErrors.capacidad_pupitres }"
                                @input="validateCapacity"
                            />
                            <p v-if="formErrors.capacidad_pupitres" class="text-red-500 text-xs mt-1">
                                {{ formErrors.capacidad_pupitres[0] }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Ubicación</label>
                            <textarea
                                v-model="form.ubicacion"
                                class="w-full mt-1 border-gray-300 rounded-md"
                                :class="{ 'border-red-500': formErrors.ubicacion }"
                            ></textarea>
                            <p v-if="formErrors.ubicacion" class="text-red-500 text-xs mt-1">
                                {{ formErrors.ubicacion[0] }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium">Estado</label>
                            <select
                                v-model="form.estado"
                                class="w-full mt-1 border-gray-300 rounded-md"
                                :class="{ 'border-red-500': formErrors.estado }"
                            >
                                <option value="disponible">Disponible</option>
                                <option value="ocupada">Ocupada</option>
                                <option value="mantenimiento">Mantenimiento</option>
                                <option value="inactiva">Inactiva</option>
                            </select>
                            <p v-if="formErrors.estado" class="text-red-500 text-xs mt-1">
                                {{ formErrors.estado[0] }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- Sección de Imágenes -->
                        <div>
                            <label class="block text-sm font-medium mb-2">Imágenes del Aula</label>

                            <!-- Input oculto para seleccionar archivos -->
                            <input
                                ref="fileInput"
                                type="file"
                                @change="handleImageUpload"
                                accept="image/*"
                                multiple
                                class="hidden"
                            />

                            <!-- Contenedor de imágenes -->
                            <div
                                class="space-y-4"
                                @dragover.prevent="handleDragOver"
                                @dragleave.prevent="handleDragLeave"
                                @drop.prevent="handleDrop"
                            >
                                <!-- Botón para agregar imágenes -->
                                <div
                                    @click="handleImageAreaClick"
                                    class="w-full h-32 border-2 border-dashed rounded-lg flex items-center justify-center cursor-pointer transition-all relative"
                                    :class="[
                                        imagePreviews.length > 0 ? 'border-gray-300 bg-gray-50' : 'border-gray-300 bg-gray-100',
                                        isDragging ? 'border-blue-500 bg-blue-50 border-4' : 'hover:border-blue-500 hover:bg-blue-50',
                                        isProcessing ? 'cursor-not-allowed opacity-75' : ''
                                    ]"
                                >
                                    <!-- Overlay de procesamiento -->
                                    <div v-if="isProcessing" class="absolute inset-0 bg-white bg-opacity-90 rounded-lg flex items-center justify-center z-10">
                                        <div class="text-center">
                                            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent mb-3"></div>
                                            <p class="text-blue-600 font-medium text-sm">{{ processingText }}</p>
                                        </div>
                                    </div>

                                    <!-- Contenido normal -->
                                    <div class="text-center" :class="{ 'opacity-0': isProcessing }">
                                        <i
                                            class="text-4xl mb-2 transition-colors"
                                            :class="[
                                                isProcessing ? 'text-blue-300' :
                                                isDragging ? 'fa-solid fa-cloud-arrow-down text-blue-500' : 'fa-solid fa-images text-gray-400'
                                            ]"
                                        ></i>
                                        <p class="text-gray-500 font-medium">
                                            {{ isProcessing ? 'Procesando imágenes...' :
                                               (isDragging ? 'Suelta las imágenes aquí' :
                                                (imagePreviews.length > 0 ? 'Agregar más imágenes' : 'Seleccionar imágenes del aula')) }}
                                        </p>
                                        <p class="text-gray-400 text-sm mt-1">
                                            {{ isProcessing ? processingText :
                                               (isDragging ? 'Arrastra y suelta para agregar' :
                                                (imagePreviews.length > 0 ? `${imagePreviews.length} imagen(es) seleccionada(s)` : 'Haz clic o arrastra imágenes aquí')) }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Grid de previsualizaciones -->
                                <div v-if="imagePreviews.length > 0" class="grid grid-cols-2 gap-4">
                                    <div
                                        v-for="(preview, index) in imagePreviews"
                                        :key="index"
                                        class="relative group"
                                    >
                                        <!-- Contenedor clickeable para vista previa -->
                                        <div
                                            @click="openImagePreview(index)"
                                            class="cursor-pointer relative overflow-hidden rounded-lg"
                                        >
                                            <img
                                                :src="preview.url"
                                                :alt="`Vista previa ${index + 1}`"
                                                class="w-full h-32 object-cover rounded-lg border-2 border-gray-200 transition-transform duration-200 group-hover:scale-105"
                                            />
                                            <!-- Overlay de hover -->
                                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 flex items-center justify-center">
                                                <i class="fa-solid fa-search-plus text-white text-xl opacity-0 group-hover:opacity-100 transition-opacity duration-200"></i>
                                            </div>
                                        </div>
                                        <!-- Botón eliminar -->
                                        <button
                                            type="button"
                                            @click.stop="removeImage(index)"
                                            class="absolute top-2 right-2 bg-red-500 text-white p-2 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-200 hover:bg-red-600 z-10"
                                            title="Eliminar imagen"
                                        >
                                            <i class="fa-solid fa-times text-xs"></i>
                                        </button>
                                        <!-- Información del archivo -->
                                        <div class="mt-2 text-xs text-gray-600">
                                            <p class="font-medium truncate">{{ preview.name }}</p>
                                            <p>{{ formatFileSize(preview.size) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información de límite -->
                                <div class="text-xs text-gray-500 bg-gray-50 p-2 rounded">
                                    <p><i class="fa-solid fa-info-circle mr-1"></i> Puedes seleccionar múltiples imágenes (máximo 5MB por imagen)</p>
                                    <p v-if="imagePreviews.length > 0" class="mt-1">
                                        <i class="fa-solid fa-check-circle mr-1 text-green-500"></i>
                                        {{ imagePreviews.length }} imagen(es) lista(s) para subir
                                    </p>
                                </div>
                            </div>

                            <p v-if="formErrors.fotos" class="text-red-500 text-xs mt-2">
                                {{ formErrors.fotos[0] }}
                            </p>
                        </div>

                        <div>
                            <input
                                type="text"
                                v-model="form.videos"
                                id="youtubeVideoUrl"
                                class="w-full mt-1 border-gray-300 rounded-md"
                            />
                            <label for="youtubeVideoUrl" class="text-sm font-medium">
                                URL del video de YouTube (opcional)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="closeModal"
                            class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors"
                            :disabled="loading">
                        {{ isEditMode ? 'Actualizar' : 'Crear' }}
                    </button>
                </div>
            </form>
        </Modal>

    </MainLayoutDashboard>

    <!-- Modal de vista previa de imágenes (fuera del layout) -->
    <ImagePreviewModal
        :visible="imagePreviewVisible"
        :images="imagePreviews"
        :initial-index="currentImageIndex"
        @close="closeImagePreview"
        @remove-image="handleRemoveImage"
    />
</template>

<script setup>
import {ref, computed, onMounted, watch} from 'vue';
import {Head, Link} from '@inertiajs/vue3';
import Loader from '@/Components/AdministrationComponent/Loader.vue';
import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
import axios from 'axios';
import browserImageCompression from 'browser-image-compression';

// componentes
import Card from '@/Components/AdministrationComponent/Card.vue';
import {authService} from "@/Services/authService.js";
import Modal from "@/Components/Modal.vue";
import ImagePreviewModal from "@/Components/AdministrationComponent/ImagePreviewModal.vue";
import QRCode from 'qrcode';
import { colors } from '@/UI/color';

const colorButton = ref('#d93f3f');
const showModal = ref(false);
const isEditMode = ref(false);
const fileInput = ref(null);
const imagePreviews = ref([]);
const imagePreviewVisible = ref(false);
const currentImageIndex = ref(0);
const isDragging = ref(false);
const isProcessing = ref(false);
const processingText = ref('');
// Estado de autenticación
const isAuthenticated = ref(false);

// permisos del usuario con rol 6
const canEdit = computed(() => authService.canEdit());

// verificación si es invitado
const isInvitado = computed(() => authService.getUserRole() === 7);

// Maneja cuando la autenticación es exitosa
const handleAuthenticated = (status) => {
    isAuthenticated.value = status;
};

const form = ref({
    id: null,
    codigo: '',
    nombre: '',
    capacidad_pupitres: '',
    ubicacion: '',
    estado: 'activo',
    fotos: [],
    videos: '',
    qrCodeDataUrl: ''
});

const formErrors = ref({});

// Función para limpiar filtros
const limpiarFiltros = () => {
    filtros.value = {
        busqueda: '',
        capacidad_pupitres: 'all',
        estado: 'all'
    };
    paginaActual.value = 1;
};

// ======| Estados |======
const aulas = ref([]);
const cargando = ref(false);
const loading = ref(false);
const error = ref(null);

// Estados para paginación backend
const aulasBackend = ref([]);
const paginacionBackend = ref({
    pagina_actual: 1,
    por_pagina: 10,
    total: 0,
    ultima_pagina: 1
});
const cargandoPaginado = ref(false);
const errorPaginado = ref(null);
const usarPaginacionBackend = ref(true); // Switch para activar/desactivar paginación backend

const mensaje = ref({
    mostrar: false,
    tipo: '',
    texto: ''
});

const filtros = ref({
    busqueda: '',
    capacidad_pupitres: 'all',
    estado: 'all'
});

// Watchers para los filtros - recargar cuando cambian los filtros
watch(filtros, () => {
    if (usarPaginacionBackend.value) {
        paginacionBackend.value.pagina_actual = 1; // Resetear a primera página
        cargarAulasPaginadas();
    } else {
        paginaActual.value = 1; // Resetear a primera página
    }
}, { deep: true });

onMounted(async () => {
    await authService.verifyToken(localStorage.getItem("token"));

    if (usarPaginacionBackend.value) {
        await cargarAulasPaginadas();
    } else {
        await cargarAulas();
    }
    // isLoading.value = false;
});

// ======| Para la paginación |======
const paginaActual = ref(1)
const porPagina = ref(9)

const totalPaginas = computed(() => Math.ceil(aulasFiltradas.value.length / porPagina.value))

const aulasPaginadas = computed(() => {
    const inicio = (paginaActual.value - 1) * porPagina.value
    const fin = inicio + porPagina.value
    return aulasFiltradas.value.slice(inicio, fin)
})

// ======| Filtrado dinámico de las aulas |======
const aulasFiltradas = computed(() => {
    return aulas.value.filter(aula => {
        // Filtrado por nombre, código o ubicación
        const busquedaAula =
            aula.nombre.toLowerCase().includes(filtros.value.busqueda.toLowerCase()) ||
            aula.codigo.toLowerCase().includes(filtros.value.busqueda.toLowerCase()) ||
            aula.ubicacion.toLowerCase().includes(filtros.value.busqueda.toLowerCase());

        // Filtra según la capacidad
        const capacidadAula = filtros.value.capacidad_pupitres === 'all' ||
            (filtros.value.capacidad_pupitres === 'small' && aula.capacidad_pupitres <= 30) ||
            (filtros.value.capacidad_pupitres === 'medium' && aula.capacidad_pupitres > 30 && aula.capacidad_pupitres <= 100) ||
            (filtros.value.capacidad_pupitres === 'large' && aula.capacidad_pupitres > 100);

        // Filtrado por estado
        const estadoAula = filtros.value.estado === 'all' || aula.estado === filtros.value.estado;

        return busquedaAula && capacidadAula && estadoAula;
    });
});

// ======| Computed properties para paginación |======
// Para paginación frontend
const displayedPages = computed(() => {
    const total = totalPaginas.value;
    const current = paginaActual.value;
    const delta = 2; // número de páginas a mostrar antes y después de la actual

    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }

    let range = [];
    let rangeWithDots = [];

    for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
        range.push(i);
    }

    if (current - delta > 2) {
        rangeWithDots.push(1, '...');
    } else {
        rangeWithDots.push(1);
    }

    rangeWithDots.push(...range);

    if (current + delta < total - 1) {
        rangeWithDots.push('...', total);
    } else {
        rangeWithDots.push(total);
    }

    return rangeWithDots;
});

// Para paginación backend
const displayedPagesBackend = computed(() => {
    const total = paginacionBackend.value.ultima_pagina;
    const current = paginacionBackend.value.pagina_actual;
    const delta = 2; // número de páginas a mostrar antes y después de la actual

    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }

    let range = [];
    let rangeWithDots = [];

    for (let i = Math.max(2, current - delta); i <= Math.min(total - 1, current + delta); i++) {
        range.push(i);
    }

    if (current - delta > 2) {
        rangeWithDots.push(1, '...');
    } else {
        rangeWithDots.push(1);
    }

    rangeWithDots.push(...range);

    if (current + delta < total - 1) {
        rangeWithDots.push('...', total);
    } else {
        rangeWithDots.push(total);
    }

    return rangeWithDots;
});

// ======| Métodos API |======

const cargarAulas = async () => {
    cargando.value = true;
    error.value = null;

    try {
        const response = await axios.get('/api/classrooms/get/all', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });

        if (response.data.success) {
            aulas.value = response.data.data;

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
            error.value = 'Ruta no encontrada. Verifica que las rutas estén en api.php';
        } else if (err.response?.status === 401) {
            error.value = 'No autorizado. Inicia sesión para ver las aulas.';
        } else if (err.response?.status === 500) {
            error.value = err.response?.data?.error || 'Error interno del servidor';
        } else {
            error.value = err.response?.data?.message || err.message || 'Error al conectar con el servidor';
        }

        mostrarMensaje('error', error.value);
    } finally {
        cargando.value = false;
    }
};

// ======| Método para consumir paginación backend |======
const cargarAulasPaginadas = async () => {
    cargandoPaginado.value = true;
    errorPaginado.value = null;

    try {
        // Construir query params
        const queryParams = new URLSearchParams({
            page: paginacionBackend.value.pagina_actual,
            per_page: paginacionBackend.value.por_pagina,
        });

        // Agregar filtros si están activos
        if (filtros.value.busqueda) {
            queryParams.append('search', filtros.value.busqueda);
        }

        if (filtros.value.estado !== 'all') {
            queryParams.append('estado', filtros.value.estado);
        }

        if (filtros.value.capacidad_pupitres !== 'all') {
            queryParams.append('capacidad', filtros.value.capacidad_pupitres);
        }

        // Ordenamiento por defecto
        queryParams.append('sort_by', 'nombre');
        queryParams.append('sort_dir', 'asc');

        const response = await axios.get(`/api/classrooms/get/paginated?${queryParams}`, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });

        if (response.data.success) {
            aulasBackend.value = response.data.data;
            // generateQrCode(aulasBackend.value);
            paginacionBackend.value = {
                pagina_actual: response.data.pagination.pagina_actual,
                por_pagina: response.data.pagination.por_pagina,
                total: response.data.pagination.total,
                ultima_pagina: response.data.pagination.ultima_pagina
            };

            //console.log('Aulas paginadas:', aulasBackend.value);

            if (aulasBackend.value.length === 0 && paginacionBackend.value.pagina_actual === 1) {
                mostrarMensaje('info', 'No hay aulas que coincidan con los filtros seleccionados');
            } else {
                const desde = response.data.pagination.desde || 0;
                const hasta = response.data.pagination.hasta || 0;
                mostrarMensaje('success', `Mostrando ${aulasBackend.value.length} aulas (registro ${desde}-${hasta} de ${paginacionBackend.value.total} totales)`);
            }
        } else {
            throw new Error(response.data.message || 'Error al cargar las aulas paginadas');
        }
    } catch (err) {
        console.error('Error en cargarAulasPaginadas:', err);

        if (err.response?.status === 404) {
            errorPaginado.value = 'Ruta no encontrada. Verifica que el endpoint paginado esté disponible.';
        } else if (err.response?.status === 401) {
            errorPaginado.value = 'No autorizado. Inicia sesión para ver las aulas.';
        } else if (err.response?.status === 422) {
            errorPaginado.value = 'Parámetros inválidos: ' + JSON.stringify(err.response.data.errors);
        } else if (err.response?.status === 500) {
            errorPaginado.value = err.response?.data?.error || 'Error interno del servidor';
        } else {
            errorPaginado.value = err.response?.data?.message || err.message || 'Error al conectar con el servidor';
        }

        mostrarMensaje('error', errorPaginado.value);
    } finally {
        cargandoPaginado.value = false;
    }
};

//para ver el detalle de una aula especifica
const verDetalle = async (aula) => {
    try {
        const response = await axios.get(`/api/classrooms/get/${aula.id}`);

        if (response.data.success) {
            const aulaDetalle = response.data.data;
            let recursos = '';
            if (aulaDetalle.recursos && aulaDetalle.recursos.length > 0) {
                recursos = '\n\n Recursos:\n' + aulaDetalle.recursos.map(r =>
                    `  • ${r.nombre} (x${r.cantidad}) - ${r.estado}`
                ).join('\n');
            }

            alert(`
                    ${aulaDetalle.nombre}
                    Código: ${aulaDetalle.codigo}
                    Capacidad: ${aulaDetalle.capacidad_pupitres} personas
                    Ubicación: ${aulaDetalle.ubicacion}
                    Estado: ${aulaDetalle.estado}${recursos}
            `);
        }
    } catch (err) {
        mostrarMensaje('error', 'Error al cargar los detalles del aula');
    }
};

/**
 * Editar aula
 */
const editarAula = (aula) => {
    mostrarMensaje('success', `Editar "${aula.nombre}" - Función por implementar`);
};

/**
 * Gestionar disponibilidad (cambiar estado)
 */
const gestionarDisponibilidad = async (aula) => {

    const nuevoEstado = prompt(
        `Estado actual: ${aula.estado}\n\n` +
        'Ingrese el nuevo estado:\n' +
        '1. disponible\n' +
        '2. ocupada\n' +
        '3. mantenimiento\n' +
        '4. inactiva',
        aula.estado
    );

    if (nuevoEstado && ['disponible', 'ocupada', 'mantenimiento', 'inactiva'].includes(nuevoEstado)) {
        try {
            const response = await axios.patch(`/api/classrooms/change-status/${aula.id}`, {
                estado: nuevoEstado
            });

            if (response.data.success) {
                mostrarMensaje('success', 'Estado actualizado exitosamente');
                cargarAulas();
            }
        } catch (err) {
            mostrarMensaje('error', 'Error al cambiar el estado del aula');
        }
    }
};

/**
 * Ir a crear aula
 */
const irACrearAula = () => {
    mostrarMensaje('success', 'Redirigiendo a formulario de creación - Por implementar');
};

/**
 * Mostrar mensaje temporal
 */
const mostrarMensaje = (tipo, texto) => {
    mensaje.value = {
        mostrar: true,
        tipo,
        texto
    };

    setTimeout(() => {
        cerrarMensaje();
    }, 5000);
};

// Función para abrir modal de creación
function openCreateModal() {
    isEditMode.value = false
    form.value = {
        id: null,
        codigo: '',
        nombre: '',
        capacidad: '',
        ubicacion: '',
        estado: 'activo',
        fotos: [],
        videos: '',
        qrCodeDataUrl: ''
    }
    // Liberar todas las URLs de objeto para evitar memory leaks
    imagePreviews.value.forEach(preview => {
        URL.revokeObjectURL(preview.url);
    });
    imagePreviews.value = []
    formErrors.value = {}
    showModal.value = true
}

/**
 * Cerrar mensaje
 */
const cerrarMensaje = () => {
    mensaje.value.mostrar = false;
};

function closeModal() {
    showModal.value = false
    // Liberar todas las URLs de objeto para evitar memory leaks
    imagePreviews.value.forEach(preview => {
        URL.revokeObjectURL(preview.url);
    });
    imagePreviews.value = []
    formErrors.value = {}
}

async function handleSubmit() {
    try {
        formErrors.value = {};

        // Validación del nombre
        const nombreRegex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/;
        if (!nombreRegex.test(form.value.nombre)) {
            formErrors.value.nombre = ['El nombre no debe contener números ni caracteres especiales.'];
            return;
        }

        // Crear FormData para enviar archivos
        const formData = new FormData();
        formData.append('codigo', form.value.codigo);
        formData.append('nombre', form.value.nombre);
        formData.append('capacidad_pupitres', form.value.capacidad_pupitres);
        formData.append('ubicacion', form.value.ubicacion);
        formData.append('estado', form.value.estado);
        formData.append('videos', form.value.videos || '');

        // Agregar las imágenes si existen
        if (form.value.fotos && Array.isArray(form.value.fotos) && form.value.fotos.length > 0) {
            form.value.fotos.forEach((file, index) => {
                if (file instanceof File || file instanceof Blob) {
                    formData.append('fotos[]', file);
                }
            });
        }


        const response = await axios.post(`/api/classrooms/new`, formData, {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'Content-Type': 'multipart/form-data'
            }
        });

        const data = response.data;
        if (!data.success) {
            throw new Error(data.message || 'Error al crear el aula');
        }

        alert('Aula creada exitosamente');

        if (usarPaginacionBackend.value) {
            await cargarAulasPaginadas();
        } else {
            await cargarAulas();
        }
        closeModal();

        // Limpiar formulario
        form.value = {
            codigo: '',
            nombre: '',
            capacidad_pupitres: '',
            ubicacion: '',
            estado: 'activo',
            fotos: [],
            videos: '',
            qrCodeDataUrl: ''
        };
        imagePreviews.value = [];

    } catch (error) {
        console.error('Error completo:', error);
        if (error.response?.status === 422) {
            formErrors.value = error.response.data.errors;
            alert('Por favor, verifica los campos del formulario');
        } else {
            alert('Ocurrió un error al guardar: ' + (error.response?.data?.message || error.message));
        }
    }
}

// Disparar el input de archivo
const triggerFileInput = () => {
    fileInput.value.click();
};

const handleImageAreaClick = () => {
    if (!isProcessing.value) {
        triggerFileInput();
    }
};

// Generar hash SHA-256 de un archivo para detectar duplicados
const getFileHash = (file) => {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = async (e) => {
            try {
                const buffer = e.target.result;
                const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
                const hashArray = Array.from(new Uint8Array(hashBuffer));
                const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
                resolve(hashHex);
            } catch (error) {
                reject(error);
            }
        };
        reader.onerror = () => reject(new Error('Error al leer el archivo'));
        reader.readAsArrayBuffer(file.slice(0, 1024)); // Solo primeros 1KB para eficiencia
    });
};

// Manejar la carga de imágenes (múltiples)
const handleImageUpload = async (event) => {
    const files = Array.from(event.target.files);

    if (files.length === 0) return;

    //console.log("Procesando", files.length, "archivos seleccionados");

    // Usar la misma función processImages para mostrar el indicador de procesamiento
    await processImages(files);

    // Limpiar el input para permitir seleccionar los mismos archivos si es necesario
    event.target.value = '';
};

// Funciones de Drag & Drop
const handleDragOver = (event) => {
    event.preventDefault();
    event.stopPropagation();
    isDragging.value = true;
};

const handleDragLeave = (event) => {
    event.preventDefault();
    event.stopPropagation();

    // Verificar si el drag está dejando el área específica
    const rect = event.currentTarget.getBoundingClientRect();
    const x = event.clientX;
    const y = event.clientY;

    if (x < rect.left || x > rect.right || y < rect.top || y > rect.bottom) {
        isDragging.value = false;
    }
};

const handleDrop = async (event) => {
    event.preventDefault();
    event.stopPropagation();
    isDragging.value = false;

    const files = Array.from(event.dataTransfer.files);

    // Filtrar solo imágenes
    const imageFiles = files.filter(file => file.type.startsWith('image/'));

    if (imageFiles.length === 0) {
        formErrors.value.fotos = ['Por favor, arrastra solo archivos de imagen'];
        return;
    }

    // Usar la misma lógica que handleImageUpload
    await processImages(imageFiles);
};

// Función para procesar imágenes (usada por handleImageUpload y handleDrop)
const processImages = async (files) => {
    // Activar indicador de procesamiento
    isProcessing.value = true;
    processingText.value = `Procesando ${files.length} imagen(es)...`;

    try {
        // Crear un Set con todos los hashes existentes para verificación rápida
        const existingHashes = new Set();
        for (let i = 0; i < imagePreviews.value.length; i++) {
            if (imagePreviews.value[i].hash) {
                existingHashes.add(imagePreviews.value[i].hash);
            }
        }

        // Validar límite total de imágenes (10 máximo)
        const MAX_IMAGES = 10;
        const currentImageCount = imagePreviews.value.length;
        const availableSlots = MAX_IMAGES - currentImageCount;

        if (currentImageCount >= MAX_IMAGES) {
            formErrors.value.fotos = [`Ya has alcanzado el límite máximo de ${MAX_IMAGES} imágenes`];
            isProcessing.value = false;
            processingText.value = '';
            return;
        }

        if (files.length > availableSlots) {
            formErrors.value.fotos = [`Solo puedes agregar ${availableSlots} imagen(es) más. Límite total: ${MAX_IMAGES}`];
            isProcessing.value = false;
            processingText.value = '';
            return;
        }

        // Validar y procesar cada archivo
        const validFiles = [];
        const newPreviews = [];
        const skippedFiles = [];

        for (let i = 0; i < files.length; i++) {
            // Actualizar texto de progreso
            processingText.value = `Procesando imagen ${i + 1} de ${files.length}...`;

            const file = files[i];

            // Validar tipo de archivo
            if (!file.type.startsWith('image/')) {
            continue;
        }

        // Validar tamaño (5MB = 5 * 1024 * 1024 bytes)
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            formErrors.value.fotos = [`El archivo ${file.name} excede el límite de 5MB`];
            return;
        }

        try {
            // Generar hash del archivo actual (antes de compresión)
            const currentHash = await getFileHash(file);

            // Verificar si ya existe (duplicado) - comparar con hash original
            if (existingHashes.has(currentHash)) {
                skippedFiles.push(file.name);
                continue;
            }

            // Comprimir la imagen
            const options = {
                maxSizeMB: 1,
                maxWidthOrHeight: 800,
                useWebWorker: true
            };

            const compressedFile = await browserImageCompression(file, options);

            // Agregar el hash del archivo original a los existentes
            existingHashes.add(currentHash);

            validFiles.push(compressedFile);
            newPreviews.push({
                url: URL.createObjectURL(compressedFile),
                name: file.name,
                size: compressedFile.size,
                file: compressedFile,
                hash: currentHash
            });

        } catch (error) {
            console.error(`Error procesando archivo ${file.name}:`, error);
            formErrors.value.fotos = [`Error procesando ${file.name}: ${error.message}`];
        }
    }

    // Agregar todas las vistas previas nuevas
    imagePreviews.value = [...imagePreviews.value, ...newPreviews];

    // Actualizar el formulario para incluir todos los archivos (original y nuevos)
    form.value.fotos = [...form.value.fotos, ...validFiles];

    // Mostrar mensaje de archivos omitidos si existen
    if (skippedFiles.length > 0) {
        console.log(`${skippedFiles.length} imágenes duplicadas omitidas:`, skippedFiles);
    }

    // Limpiar errores si existían
    if (formErrors.value.fotos) {
        delete formErrors.value.fotos;
    }

    } finally {
        // Desactivar indicador de procesamiento
        isProcessing.value = false;
        processingText.value = '';
    }
};

// Formatear tamaño de archivo
function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';

    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Eliminar imagen específica
const removeImage = (index) => {
    // Liberar la URL del objeto para evitar memory leaks
    URL.revokeObjectURL(imagePreviews.value[index].url);

    // Eliminar del array de previews
    imagePreviews.value.splice(index, 1);

    // Eliminar del array de archivos del formulario
    form.value.fotos.splice(index, 1);
};

// Abrir modal de vista previa de imagen
const openImagePreview = (index) => {
    currentImageIndex.value = index;

    // Cerrar temporalmente el modal principal para evitar conflictos de z-index
    const originalShowModal = showModal.value;
    showModal.value = false;

    // Guardar estado original para restaurar después
    const originalModalState = originalShowModal;

    // Abrir el modal de vista previa
    imagePreviewVisible.value = true;

    // Guardar referencia para poder restaurar el modal principal
    window._tempModalState = originalModalState;
};

// Cerrar modal de vista previa
const closeImagePreview = () => {
    imagePreviewVisible.value = false;

    // Restaurar el modal principal si estaba abierto
    if (window._tempModalState === true) {
        // Pequeño delay para asegurar que el modal de vista previa se cierre completamente
        setTimeout(() => {
            showModal.value = true;
            window._tempModalState = null;
        }, 100);
    } else {
        window._tempModalState = null;
    }
};

// Manejar eliminación de imagen desde el modal de vista previa
const handleRemoveImage = (index) => {
    // Usar la misma función que se usa para eliminar desde el formulario
    removeImage(index);

    // Si solo queda una imagen, cerrar el modal de vista previa
    if (imagePreviews.value.length <= 1) {
        closeImagePreview();
    }

    // Ajustar el índice actual si es necesario
    if (currentImageIndex.value >= imagePreviews.value.length && currentImageIndex.value > 0) {
        currentImageIndex.value = imagePreviews.value.length - 1;
    }
};

function validateCapacity(event) {
    const value = event.target.value;
    // Only allow numbers up to 150
    const regex = /^(?:1[0-4][0-9]|150|[1-9]?[0-9])$/;
    if (!regex.test(value)) {
        form.value.capacidad_pupitres = Math.min(Number(value), 150);
    }
}

watch(
    () => form.value.codigo,
    async (newCodigo) => {
        if (newCodigo && newCodigo.trim() !== '') {
            try {
                const baseUrl = window.location.origin;
                const qrText = `${baseUrl}/aula/${newCodigo.trim()}`;

                // Usar QRCode.toDataURL (la función del objeto importado)
                form.value.qrCodeDataUrl = await QRCode.toDataURL(qrText, {
                    errorCorrectionLevel: 'H',
                    width: 250,
                    margin: 2,
                    color: {
                        dark: '#000000',
                        light: '#FFFFFF'
                    }
                });

                // Actualizar el QR en el aula correspondiente (si está en edición)
                if (form.value.id) {
                    const aulaArray = usarPaginacionBackend.value ? aulasBackend.value : aulas.value;
                    const aulaEncontrada = aulaArray.find(a => a.id === form.value.id);
                    if (aulaEncontrada) {
                        aulaEncontrada.qrCodeDataUrl = form.value.qrCodeDataUrl;
                    }
                }

                //console.log('QR generado correctamente');
            } catch (err) {
                console.error('Error generando QR:', err);
                form.value.qrCodeDataUrl = '';
            }
        } else {
            form.value.qrCodeDataUrl = '';
        }
    },
    { immediate: false }
);

</script>
