<template>
    <Head title="Informes"/>

    <!-- Componente de autenticación reutilizable -->
    <Loader
        v-if="!isAuthenticated"
        @authenticated="handleAuthenticated"
        message="Verificando sesión..."
        :redirectDelay="2000"
    />

    <!-- Dashboard solo se muestra cuando está autenticado -->
    <MainLayoutDashboard>
        <div class="p-3 sm:p-4 md:p-6" v-if="isAuthenticated">
            <div>
                <!-- Header -->
                <div class="mb-4 sm:mb-6 md:mb-8">
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-slate-800 mb-2">
                        Informes y Reportes
                    </h1>
                    <p class="text-sm sm:text-base text-slate-600">
                        Selecciona los informes que deseas visualizar y descargar
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 bg-white p-3 sm:p-4 md:p-6 rounded-lg">
                    <!--Panel de selección del reporte o informe-->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                                <h2 class="text-lg sm:text-xl font-semibold text-slate-800">
                                    Informes Disponibles
                                </h2>
                                <button
                                    @click="handleSelectAll"
                                    class="text-sm text-blue-600 hover:text-blue-700 font-medium text-left sm:text-right"
                                >
                                    {{ selectedReports.length === availableReports.length ? 'Deseleccionar' : 'Seleccionar' }} todos
                                </button>
                            </div>

                            <!-- Búsqueda -->
                            <div class="relative mb-4">
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input
                                    type="text"
                                    v-model="searchTerm"
                                    placeholder="Buscar informes..."
                                    class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm"
                                />
                            </div>

                            <!-- Lista de Informes -->
                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                <div
                                    v-for="report in filteredReports"
                                    :key="report.id"
                                    :class="[
                                        'p-3 rounded-lg border-2 transition-all cursor-pointer',
                                        selectedReports.includes(report.id)
                                            ? 'border-blue-500 bg-blue-50'
                                            : 'border-slate-200 hover:border-slate-300 bg-white'
                                    ]"
                                >
                                    <div class="flex items-start gap-2 sm:gap-3">
                                        <div @click="handleToggleReport(report.id)" class="mt-1 cursor-pointer">
                                            <i
                                                :class="selectedReports.includes(report.id) ? 'fa-solid fa-square-check text-blue-600' : 'fa-regular fa-square text-slate-400'"
                                                class="text-lg cursor-pointer"
                                            ></i>
                                        </div>

                                        <div class="flex-1 min-w-0" @click="handleToggleReport(report.id)">
                                            <h3 class="font-medium text-slate-800 text-sm sm:text-base truncate">
                                                {{ report.name }}
                                            </h3>
                                            <p class="text-xs sm:text-sm text-slate-500">{{ report.category }}</p>
                                            <p class="text-xs text-slate-400 mt-1">{{ report.records }} registros</p>
                                        </div>

                                        <button
                                            @click.stop="handleViewReport(report.id)"
                                            class="text-blue-600 hover:text-blue-700 text-xs sm:text-sm font-medium whitespace-nowrap flex-shrink-0"
                                        >
                                            Ver
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Botones de Descarga -->
                            <div v-if="selectedReports.length > 0" class="mt-4 sm:mt-6 pt-4 sm:pt-6 border-t border-slate-200">
                                <p class="text-xs sm:text-sm text-slate-600 mb-3">
                                    {{ selectedReports.length }} informe(s) seleccionado(s)
                                </p>

                                <div class="space-y-2">
                                    <button
                                        @click="handleDownloadReport('PDF')"
                                        class="w-full flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg transition-colors text-sm"
                                    >
                                        <i class="fa-solid fa-download"></i>
                                        <span>Descargar PDF</span>
                                    </button>

                                    <button
                                        @click="handleDownloadReport('Excel')"
                                        class="w-full flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg transition-colors text-sm"
                                    >
                                        <i class="fa-solid fa-download"></i>
                                        <span>Descargar Excel</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de Visualización -->
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
                            <template v-if="activeReport !== null">
                                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4 sm:mb-6">
                                    <div class="flex-1 min-w-0">
                                        <h2 class="text-xl sm:text-2xl font-semibold text-slate-800 truncate">
                                            {{ currentReport?.name }}
                                        </h2>
                                        <p class="text-slate-600 text-xs sm:text-sm mt-1">
                                            {{ currentReport?.category }}
                                        </p>
                                    </div>
                                    <i class="fa-regular fa-file text-2xl sm:text-3xl text-slate-400"></i>
                                </div>

                                <!-- Vista Desktop/Laptop: Tabla -->
                                <div class="hidden md:block overflow-x-auto">
                                    <table class="w-full" :style="{ border: '1px solid #d93f3f' }">
                                        <thead class="bg-gray-50 border-b-2 border-gray-200"
                                            :style="{background: '#d93f3f', height: '40px'}">
                                            <tr class="border-b-2 border-slate-200">
                                                <th
                                                    v-for="header in tableHeaders"
                                                    :key="header"
                                                    class="text-left py-3 px-4 text-sm font-semibold text-white uppercase"
                                                >
                                                    {{ header }}
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr
                                                v-for="(row, idx) in currentReportData"
                                                :key="row.id"
                                                :class="[
                                                    'border-b border-slate-100',
                                                    idx % 2 === 0 ? 'bg-slate-50' : 'bg-white'
                                                ]"
                                            >
                                                <td
                                                    v-for="header in tableHeaders"
                                                    :key="header"
                                                    class="py-3 px-4 text-slate-700"
                                                >
                                                    {{ row[header] }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Vista Móvil: Cards -->
                                <div class="md:hidden space-y-3">
                                    <div
                                        v-for="(row, idx) in currentReportData"
                                        :key="row.id"
                                        class="border border-slate-200 rounded-lg p-3 bg-white hover:shadow-md transition-shadow"
                                    >
                                        <div class="space-y-2">
                                            <div
                                                v-for="header in tableHeaders"
                                                :key="header"
                                                class="flex justify-between items-start gap-2"
                                            >
                                                <span class="text-xs font-semibold text-slate-500 uppercase flex-shrink-0">
                                                    {{ header }}:
                                                </span>
                                                <span class="text-sm text-slate-700 text-right flex-1">
                                                    {{ row[header] }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 text-xs sm:text-sm text-slate-500">
                                    Mostrando {{ currentReportData.length }} registro(s)
                                </div>
                            </template>

                            <template v-else>
                                <div class="flex flex-col items-center justify-center py-12 sm:py-16 text-center px-4">
                                    <i class="fa-regular fa-file text-5xl sm:text-6xl md:text-7xl text-slate-300 mb-4"></i>
                                    <h3 class="text-lg sm:text-xl font-semibold text-slate-700 mb-2">
                                        Selecciona un informe para visualizar
                                    </h3>
                                    <p class="text-sm sm:text-base text-slate-500">
                                        Haz clic en "Ver" en cualquier informe de la lista para visualizar sus datos
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayoutDashboard>
</template>

<script setup>
    import { Head } from '@inertiajs/vue3';
    import { ref, computed } from 'vue';
    import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
    import Loader from '@/Components/AdministrationComponent/Loader.vue';

    // Estado de autenticación
    const isAuthenticated = ref(false);

    // Maneja cuando la autenticación es exitosa
    const handleAuthenticated = (status) => {
        isAuthenticated.value = status;
        console.log('Vista de Informes: Usuario autenticado');
    };

    // Datos de informes disponibles
    const availableReports = ref([
        { id: 1, name: 'Informe de Aulas', category: 'Aulas', records: 50 },
        { id: 2, name: 'Informe de Docentes', category: 'Usuarios', records: 30 },
        { id: 3, name: 'Informe de Estudiantes', category: 'Usuarios', records: 200 },
    ]);

    // Datos de ejemplo para los reportes
    const reportData = {
        1: [
            { id: 1, Código: 'A101', Nombre: 'Aula Principal', Capacidad: 30, Edificio: 'A' },
            { id: 2, Código: 'A102', Nombre: 'Laboratorio', Capacidad: 25, Edificio: 'A' },
        ],
        2: [
            { id: 1, Nombre: 'Juan Pérez', Especialidad: 'Matemáticas', Antigüedad: '5 años' },
            { id: 2, Nombre: 'María García', Especialidad: 'Física', Antigüedad: '3 años' },
        ],
        3: [
            { id: 1, Nombre: 'Carlos López', Carrera: 'Ingeniería', Semestre: '5to' },
            { id: 2, Nombre: 'Ana Martínez', Carrera: 'Medicina', Semestre: '3ro' },
        ],
    };

    const selectedReports = ref([]);
    const searchTerm = ref('');
    const activeReport = ref(null);

    // Filtra los informes según el término de búsqueda
    const filteredReports = computed(() => {
        return availableReports.value.filter(report =>
            report.name.toLowerCase().includes(searchTerm.value.toLowerCase()) ||
            report.category.toLowerCase().includes(searchTerm.value.toLowerCase())
        );
    });

    // Obtiene el informe activo a partir de su id
    const currentReport = computed(() => {
        return availableReports.value.find(r => r.id === activeReport.value);
    });

    // Datos del informe activo
    const currentReportData = computed(() => {
        return reportData[activeReport.value] || [];
    });

    // Genera los encabezados de la tabla según las claves del primer registro
    const tableHeaders = computed(() => {
        if (!activeReport.value || !reportData[activeReport.value]) return [];
        const data = reportData[activeReport.value][0];
        if (!data) return [];
        return Object.keys(data).filter(key => key !== 'id');
    });

    // Alterna la selección de un informe
    const handleToggleReport = (reportId) => {
        const index = selectedReports.value.indexOf(reportId);
        if (index > -1) {
            selectedReports.value.splice(index, 1);
        } else {
            selectedReports.value.push(reportId);
        }
    };

    // Selecciona o deselecciona todos los informes
    const handleSelectAll = () => {
        if (selectedReports.value.length === availableReports.value.length) {
            selectedReports.value = [];
        } else {
            selectedReports.value = availableReports.value.map(r => r.id);
        }
    };

    // Activa la vista de un informe específico
    const handleViewReport = (reportId) => {
        activeReport.value = reportId;
    };

    // Simula la descarga de informes
    const handleDownloadReport = (format) => {
        const reportNames = availableReports.value
            .filter(r => selectedReports.value.includes(r.id))
            .map(r => r.name)
            .join(', ');

        alert(`Descargando informes en formato ${format}:\n${reportNames}`);
    };
</script>
