<template>
    <Head title="Informes"/>
    <Loader
        v-if="!isAuthenticated"
        @authenticated="handleAuthenticated"
        message="Verificando sesión..."
        :redirectDelay="2000"
    />
    <MainLayoutDashboard>
        <div class="p-3 sm:p-4 md:p-6" v-if="isAuthenticated">
            <div>
                <div class="mb-4 sm:mb-6 md:mb-8">
                    <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-slate-800 mb-2">
                        Informes y Reportes
                    </h1>
                    <p class="text-sm sm:text-base text-slate-600">
                        Selecciona los informes que deseas visualizar y descargar
                    </p>
                </div>

                <div v-if="loading" class="flex justify-center items-center py-12">
                    <div class="text-center">
                        <i class="fa-solid fa-spinner fa-spin text-4xl text-blue-600 mb-4"></i>
                        <p class="text-slate-600">Cargando reportes...</p>
                    </div>
                </div>

                <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-exclamation-circle text-red-600 text-xl"></i>
                        <div class="flex-1">
                            <h3 class="font-semibold text-red-800 mb-1">Error al cargar reportes</h3>
                            <p class="text-sm text-red-600">{{ error }}</p>
                            <button
                                @click="fetchReports"
                                class="mt-2 text-sm text-red-700 hover:text-red-800 font-medium underline"
                            >
                                Reintentar
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 bg-white p-3 sm:p-4 md:p-6 rounded-lg">
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-4">
                                <h2 class="text-lg sm:text-xl font-semibold text-slate-800">
                                    Informes Disponibles
                                </h2>
                                <button
                                    v-if="filteredReports.length > 0"
                                    @click="handleSelectAll"
                                    class="text-sm text-blue-600 hover:text-blue-700 font-medium text-left sm:text-right"
                                >
                                    {{ selectedReports.length === filteredReports.length ? 'Deseleccionar' : 'Seleccionar' }} todos
                                </button>
                            </div>

                            <div class="relative mb-4">
                                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input
                                    type="text"
                                    v-model="searchTerm"
                                    placeholder="Buscar informes..."
                                    class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm"
                                />
                            </div>

                            <div class="mb-4 space-y-2">
                                <select
                                    v-model="filterCategoria"
                                    @change="applyFilters"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm"
                                >
                                    <option value="">Todas las categorías</option>
                                    <option value="recurso_defectuoso">Recurso Defectuoso</option>
                                    <option value="qr_danado">QR Dañado</option>
                                    <option value="limpieza">Limpieza</option>
                                    <option value="infraestructura">Infraestructura</option>
                                    <option value="conectividad">Conectividad</option>
                                    <option value="otro">Otro</option>
                                </select>

                                <select
                                    v-model="filterEstado"
                                    @change="applyFilters"
                                    class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none text-sm"
                                >
                                    <option value="">Todos los estados</option>
                                    <option value="reportado">Reportado</option>
                                    <option value="en_revision">En Revisión</option>
                                    <option value="asignado">Asignado</option>
                                    <option value="en_proceso">En Proceso</option>
                                    <option value="resuelto">Resuelto</option>
                                    <option value="rechazado">Rechazado</option>
                                    <option value="cerrado">Cerrado</option>
                                </select>

                                <button
                                    v-if="filterCategoria || filterEstado"
                                    @click="clearFilters"
                                    class="w-full flex items-center justify-center gap-2 bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded-lg transition-colors text-sm"
                                >
                                    <i class="fa-solid fa-times"></i>
                                    <span>Limpiar filtros</span>
                                </button>
                            </div>

                            <div v-if="totalReports > 0" class="mb-4 p-3 bg-blue-50 rounded-lg">
                                <div class="text-xs text-slate-600 space-y-1">
                                    <div class="flex justify-between">
                                        <span>Total de reportes:</span>
                                        <span class="font-semibold">{{ totalReports }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Filtrados:</span>
                                        <span class="font-semibold">{{ filteredReports.length }}</span>
                                    </div>
                                </div>
                            </div>

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
                                            <h3 class="font-medium text-slate-800 text-sm sm:text-base">
                                                Reporte #{{ report.id }}
                                            </h3>
                                            <p class="text-xs sm:text-sm text-slate-500">
                                                {{ getCategoriaLabel(report.categoria) }}
                                            </p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span
                                                    :class="getEstadoBadgeClass(report.estado)"
                                                    class="text-xs px-2 py-0.5 rounded-full"
                                                >
                                                    {{ getEstadoLabel(report.estado) }}
                                                </span>
                                            </div>
                                            <p class="text-xs text-slate-400 mt-1">
                                                {{ formatDate(report.created_at) }}
                                            </p>
                                        </div>

                                        <button
                                            @click.stop="handleViewReport(report.id)"
                                            class="text-blue-600 hover:text-blue-700 text-xs sm:text-sm font-medium whitespace-nowrap flex-shrink-0"
                                        >
                                            Ver
                                        </button>
                                    </div>
                                </div>

                                <div v-if="filteredReports.length === 0" class="text-center py-8">
                                    <i class="fa-solid fa-inbox text-4xl text-slate-300 mb-3"></i>
                                    <p class="text-sm font-medium text-slate-700 mb-1">No se encontraron reportes</p>
                                    <p v-if="filterCategoria || filterEstado" class="text-xs text-slate-500">
                                        Intenta cambiar los filtros aplicados
                                    </p>
                                    <button
                                        v-if="filterCategoria || filterEstado"
                                        @click="clearFilters"
                                        class="mt-3 text-xs text-blue-600 hover:text-blue-700 font-medium"
                                    >
                                        Limpiar filtros
                                    </button>
                                </div>
                            </div>

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

                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-xl shadow-lg p-4 sm:p-6">
                            <template v-if="activeReport !== null && currentReport">
                                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3 mb-4 sm:mb-6">
                                    <div class="flex-1 min-w-0">
                                        <h2 class="text-xl sm:text-2xl font-semibold text-slate-800">
                                            Reporte #{{ currentReport.id }}
                                        </h2>
                                        <div class="flex flex-wrap items-center gap-2 mt-2">
                                            <span
                                                :class="getEstadoBadgeClass(currentReport.estado)"
                                                class="text-xs px-2 py-1 rounded-full font-medium"
                                            >
                                                {{ getEstadoLabel(currentReport.estado) }}
                                            </span>
                                            <span class="text-xs text-slate-500">
                                                {{ getCategoriaLabel(currentReport.categoria) }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex gap-2">
                                        <button
                                            @click="openChangeStatusModal(currentReport)"
                                            class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg text-sm transition-colors flex items-center gap-2"
                                            title="Cambiar estado"
                                        >
                                            <i class="fa-solid fa-exchange-alt"></i>
                                            <span class="hidden sm:inline">Cambiar Estado</span>
                                        </button>
                                        <button
                                            @click="confirmDeleteReport(currentReport.id)"
                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm transition-colors"
                                            title="Eliminar reporte"
                                        >
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="bg-slate-50 rounded-lg p-4">
                                        <h3 class="font-semibold text-slate-700 mb-2 flex items-center gap-2">
                                            <i class="fa-solid fa-door-open text-blue-600"></i>
                                            Aula
                                        </h3>
                                        <p class="text-slate-600">
                                            {{ currentReport.aula?.nombre || 'N/A' }}
                                        </p>
                                        <p class="text-sm text-slate-500">
                                            Código: {{ currentReport.aula?.codigo || 'N/A' }}
                                        </p>
                                    </div>

                                    <div class="bg-slate-50 rounded-lg p-4">
                                        <h3 class="font-semibold text-slate-700 mb-2 flex items-center gap-2">
                                            <i class="fa-solid fa-align-left text-blue-600"></i>
                                            Descripción
                                        </h3>
                                        <p class="text-slate-600 text-sm">
                                            {{ currentReport.descripcion }}
                                        </p>
                                    </div>

                                    <div class="bg-slate-50 rounded-lg p-4">
                                        <h3 class="font-semibold text-slate-700 mb-2 flex items-center gap-2">
                                            <i class="fa-solid fa-user text-blue-600"></i>
                                            Reportado por
                                        </h3>
                                        <p class="text-slate-600">
                                            {{ currentReport.usuario_reporta?.name || 'N/A' }}
                                        </p>
                                        <p class="text-sm text-slate-500">
                                            {{ formatDate(currentReport.created_at) }}
                                        </p>
                                    </div>

                                    <div v-if="currentReport.usuario_asignado" class="bg-slate-50 rounded-lg p-4">
                                        <h3 class="font-semibold text-slate-700 mb-2 flex items-center gap-2">
                                            <i class="fa-solid fa-user-check text-blue-600"></i>
                                            Asignado a
                                        </h3>
                                        <p class="text-slate-600">
                                            {{ currentReport.usuario_asignado.name || 'N/A' }}
                                        </p>
                                    </div>

                                    <div v-if="currentReport.fecha_resolucion" class="bg-green-50 rounded-lg p-4">
                                        <h3 class="font-semibold text-green-700 mb-2 flex items-center gap-2">
                                            <i class="fa-solid fa-check-circle text-green-600"></i>
                                            Fecha de resolución
                                        </h3>
                                        <p class="text-green-600">
                                            {{ formatDate(currentReport.fecha_resolucion) }}
                                        </p>
                                        <p v-if="currentReport.notas_resolucion" class="text-sm text-green-600 mt-2">
                                            <strong>Notas:</strong> {{ currentReport.notas_resolucion }}
                                        </p>
                                    </div>

                                    <div v-if="currentReport.foto_evidencia" class="bg-slate-50 rounded-lg p-4">
                                        <h3 class="font-semibold text-slate-700 mb-2 flex items-center gap-2">
                                            <i class="fa-solid fa-camera text-blue-600"></i>
                                            Evidencia fotográfica
                                        </h3>
                                        <img
                                            :src="`/storage/${currentReport.foto_evidencia}`"
                                            alt="Evidencia"
                                            class="w-full max-w-md rounded-lg shadow-md mt-2"
                                            @error="handleImageError"
                                        />
                                        <p v-if="imageError" class="text-sm text-red-600 mt-2">
                                            <i class="fa-solid fa-exclamation-circle mr-1"></i>
                                            No se pudo cargar la imagen
                                        </p>
                                    </div>
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

    <div v-if="showChangeStatusModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-900">Cambiar Estado del Reporte</h2>
                <button @click="closeChangeStatusModal" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form @submit.prevent="submitChangeStatus">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nuevo Estado</label>
                    <select
                        v-model="changeStatusForm.estado"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        required
                    >
                        <option value="reportado">Reportado</option>
                        <option value="en_revision">En Revisión</option>
                        <option value="asignado">Asignado</option>
                        <option value="en_proceso">En Proceso</option>
                        <option value="resuelto">Resuelto</option>
                        <option value="rechazado">Rechazado</option>
                        <option value="cerrado">Cerrado</option>
                    </select>
                </div>

                <div v-if="changeStatusError" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                    {{ changeStatusError }}
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        @click="closeChangeStatusModal"
                        class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        :disabled="changingStatus"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:bg-gray-400"
                    >
                        {{ changingStatus ? 'Guardando...' : 'Guardar' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';
import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
import Loader from '@/Components/AdministrationComponent/Loader.vue';
import axios from 'axios';
import { jsPDF } from 'jspdf';
import 'jspdf-autotable';
import * as XLSX from 'xlsx';
import Swal from 'sweetalert2';

const API_URL = '/api';
const getAuthHeaders = () => ({ 
    headers: { 
        Authorization: `Bearer ${localStorage.getItem('token')}` 
    } 
});

const isAuthenticated = ref(false);
const allReports = ref([]);
const loading = ref(false);
const error = ref(null);
const selectedReports = ref([]);
const searchTerm = ref('');
const activeReport = ref(null);
const filterCategoria = ref('');
const filterEstado = ref('');
const imageError = ref(false);

const showChangeStatusModal = ref(false);
const changingStatus = ref(false);
const changeStatusError = ref(null);
const changeStatusForm = ref({
    reportId: null,
    estado: ''
});

const handleAuthenticated = (status) => {
    isAuthenticated.value = status;
    if (status) {
        fetchReports();
    }
};

const fetchReports = async () => {
    loading.value = true;
    error.value = null;

    try {
        const res = await axios.get(`${API_URL}/classroom-reports/get/all`, getAuthHeaders());
        
        if (res.data.success) {
            allReports.value = res.data.data || [];
        } else {
            error.value = res.data.message || 'Error al cargar los reportes';
        }
    } catch (err) {
        console.error('Error:', err);
        
        if (err.response?.status === 401) {
            error.value = 'Sesión expirada. Por favor, inicia sesión nuevamente.';
        } else if (err.response?.status === 403) {
            error.value = 'No tienes permisos para ver los reportes.';
        } else {
            error.value = err.response?.data?.message || 'Error al cargar los reportes.';
        }
    } finally {
        loading.value = false;
    }
};

const applyFilters = async () => {
    loading.value = true;
    error.value = null;

    try {
        let url = `${API_URL}/classroom-reports/get/all`;

        if (filterCategoria.value && filterEstado.value) {
            const response = await axios.get(url, getAuthHeaders());
            if (response.data.success) {
                allReports.value = response.data.data.filter(report =>
                    report.categoria === filterCategoria.value &&
                    report.estado === filterEstado.value
                );
            }
        } else if (filterCategoria.value) {
            url = `${API_URL}/classroom-reports/get/category/${filterCategoria.value}`;
            const response = await axios.get(url, getAuthHeaders());
            if (response.data.success) {
                allReports.value = response.data.data || [];
            } else {
                allReports.value = [];
            }
        } else if (filterEstado.value) {
            url = `${API_URL}/classroom-reports/get/status/${filterEstado.value}`;
            const response = await axios.get(url, getAuthHeaders());
            if (response.data.success) {
                allReports.value = response.data.data || [];
            } else {
                allReports.value = [];
            }
        } else {
            await fetchReports();
        }
    } catch (err) {
        console.error('Error aplicando filtros:', err);
        if (err.response?.status === 404) {
            allReports.value = [];
        } else {
            error.value = err.response?.data?.message || 'Error al aplicar filtros';
        }
    } finally {
        loading.value = false;
    }
};

const clearFilters = () => {
    filterCategoria.value = '';
    filterEstado.value = '';
    fetchReports();
};

const openChangeStatusModal = (report) => {
    changeStatusForm.value = {
        reportId: report.id,
        estado: report.estado
    };
    changeStatusError.value = null;
    showChangeStatusModal.value = true;
};

const closeChangeStatusModal = () => {
    showChangeStatusModal.value = false;
    changeStatusForm.value = {
        reportId: null,
        estado: ''
    };
    changeStatusError.value = null;
};

const submitChangeStatus = async () => {
    changingStatus.value = true;
    changeStatusError.value = null;

    try {
        const res = await axios.patch(
            `${API_URL}/classroom-reports/change-status/${changeStatusForm.value.reportId}`,
            { estado: changeStatusForm.value.estado },
            getAuthHeaders()
        );

        if (res.data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Estado Actualizado',
                text: 'El estado del reporte ha sido actualizado exitosamente',
                confirmButtonColor: '#3B82F6',
                timer: 2000,
                timerProgressBar: true
            });
            closeChangeStatusModal();
            await fetchReports();
        }
    } catch (err) {
        changeStatusError.value = err.response?.data?.message || 'Error al cambiar el estado';
        await Swal.fire({
            icon: 'error',
            title: 'Error',
            text: changeStatusError.value,
            confirmButtonColor: '#EF4444'
        });
    } finally {
        changingStatus.value = false;
    }
};

const confirmDeleteReport = async (reportId) => {
    const result = await Swal.fire({
        title: '¿Estás seguro?',
        text: "Esta acción no se puede deshacer",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#EF4444',
        cancelButtonColor: '#6B7280',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    });

    if (result.isConfirmed) {
        try {
            await axios.delete(
                `${API_URL}/classroom-reports/delete/${reportId}`,
                getAuthHeaders()
            );

            await Swal.fire({
                icon: 'success',
                title: 'Eliminado',
                text: 'El reporte ha sido eliminado exitosamente',
                confirmButtonColor: '#10B981',
                timer: 2000,
                timerProgressBar: true
            });
            
            activeReport.value = null;
            await fetchReports();
        } catch (err) {
            await Swal.fire({
                icon: 'error',
                title: 'Error',
                text: err.response?.data?.message || 'Error al eliminar el reporte',
                confirmButtonColor: '#EF4444'
            });
        }
    }
};

const handleImageError = () => {
    imageError.value = true;
};

const availableReports = computed(() => allReports.value);
const totalReports = computed(() => allReports.value.length);

const filteredReports = computed(() => {
    if (!searchTerm.value) return availableReports.value;
    
    return availableReports.value.filter(report => {
        const searchLower = searchTerm.value.toLowerCase();
        const categoriaLabel = getCategoriaLabel(report.categoria).toLowerCase();
        const estadoLabel = getEstadoLabel(report.estado).toLowerCase();
        const aulaName = report.aula?.nombre?.toLowerCase() || '';
        const aulaCodigo = report.aula?.codigo?.toLowerCase() || '';
        const usuarioReporta = report.usuario_reporta?.name?.toLowerCase() || '';

        return (
            report.id.toString().includes(searchLower) ||
            categoriaLabel.includes(searchLower) ||
            estadoLabel.includes(searchLower) ||
            aulaName.includes(searchLower) ||
            aulaCodigo.includes(searchLower) ||
            usuarioReporta.includes(searchLower) ||
            report.descripcion?.toLowerCase().includes(searchLower)
        );
    });
});

const currentReport = computed(() => {
    return availableReports.value.find(r => r.id === activeReport.value);
});

const handleToggleReport = (reportId) => {
    const index = selectedReports.value.indexOf(reportId);
    if (index > -1) {
        selectedReports.value.splice(index, 1);
    } else {
        selectedReports.value.push(reportId);
    }
};

const handleSelectAll = () => {
    if (selectedReports.value.length === filteredReports.value.length) {
        selectedReports.value = [];
    } else {
        selectedReports.value = filteredReports.value.map(r => r.id);
    }
};

const handleViewReport = (reportId) => {
    activeReport.value = reportId;
    imageError.value = false;
};

const handleDownloadReport = async (format) => {
    if (selectedReports.value.length === 0) {
        await Swal.fire({
            icon: 'warning',
            title: 'No hay reportes seleccionados',
            text: 'Por favor, selecciona al menos un reporte para descargar',
            confirmButtonColor: '#F59E0B'
        });
        return;
    }

    try {
        Swal.fire({
            title: 'Generando archivo...',
            text: 'Por favor espera',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const reportes = allReports.value.filter(r => selectedReports.value.includes(r.id));
        
        if (format === 'PDF') {
            generatePDF(reportes);
        } else if (format === 'Excel') {
            generateExcel(reportes);
        }

        Swal.close();
        
        await Swal.fire({
            icon: 'success',
            title: 'Descarga Completada',
            text: `Se han descargado ${reportes.length} reporte(s) en formato ${format}`,
            confirmButtonColor: '#10B981',
            timer: 2000,
            timerProgressBar: true
        });
    } catch (err) {
        console.error('Error:', err);
        await Swal.fire({
            icon: 'error',
            title: 'Error al generar archivo',
            text: err.message,
            confirmButtonColor: '#EF4444'
        });
    }
};

const generatePDF = (reportes) => {
    const doc = new jsPDF({
        orientation: 'p',
        unit: 'mm',
        format: 'letter',
        putOnlyUsedFonts: true,
        compress: true
    });
    
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 15;
    let yPos = margin;
    
    doc.setFont('helvetica');
    
    doc.setFillColor(217, 63, 63);
    doc.rect(0, 0, pageWidth, 25, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(18);
    doc.setFont('helvetica', 'bold');
    doc.text('REPORTES DE PROBLEMAS EN AULAS', pageWidth / 2, 12, { align: 'center' });
    doc.setFontSize(10);
    doc.setFont('helvetica', 'normal');
    doc.text('Sistema de Control de Asistencia Automatizada', pageWidth / 2, 19, { align: 'center' });
    
    yPos = 35;
    
    doc.setTextColor(100, 100, 100);
    doc.setFontSize(9);
    const now = new Date();
    const dia = String(now.getDate()).padStart(2, '0');
    const mes = String(now.getMonth() + 1).padStart(2, '0');
    const anio = now.getFullYear();
    const hora = String(now.getHours()).padStart(2, '0');
    const minuto = String(now.getMinutes()).padStart(2, '0');
    const fechaGeneracion = `${dia}/${mes}/${anio}, ${hora}:${minuto}`;
    
    doc.text(`Fecha de generacion: ${fechaGeneracion}`, pageWidth - margin, yPos, { align: 'right' });
    yPos += 10;
    
    doc.setFillColor(240, 249, 255);
    doc.rect(margin, yPos, pageWidth - (2 * margin), 10, 'F');
    doc.setTextColor(30, 64, 175);
    doc.setFont('helvetica', 'bold');
    doc.text(`Total de reportes: ${reportes.length}`, pageWidth / 2, yPos + 7, { align: 'center' });
    yPos += 20;
    
    reportes.forEach((reporte, index) => {
        if (yPos > pageHeight - 70) {
            doc.addPage();
            yPos = margin;
        }
        
        doc.setFillColor(248, 249, 250);
        doc.roundedRect(margin, yPos, pageWidth - (2 * margin), 65, 2, 2, 'F');
        
        doc.setDrawColor(217, 63, 63);
        doc.setLineWidth(0.5);
        doc.roundedRect(margin, yPos, pageWidth - (2 * margin), 65, 2, 2);
        
        let cardY = yPos + 6;
        
        doc.setTextColor(217, 63, 63);
        doc.setFontSize(12);
        doc.setFont('helvetica', 'bold');
        doc.text(`Reporte #${reporte.id}`, margin + 5, cardY);
        cardY += 7;
        
        doc.setFontSize(8);
        const estadoColor = getEstadoColorPDF(reporte.estado);
        doc.setFillColor(...estadoColor.bg);
        doc.setTextColor(...estadoColor.text);
        doc.roundedRect(margin + 5, cardY - 3, 25, 5, 1, 1, 'F');
        const estadoLabel = getEstadoLabel(reporte.estado);
        doc.text(estadoLabel, margin + 7, cardY);
        
        doc.setFillColor(224, 231, 255);
        doc.setTextColor(55, 48, 163);
        const categoriaLabel = getCategoriaLabel(reporte.categoria);
        const categoriaWidth = Math.max(doc.getTextWidth(categoriaLabel) + 4, 30);
        doc.roundedRect(margin + 32, cardY - 3, categoriaWidth, 5, 1, 1, 'F');
        doc.text(categoriaLabel, margin + 34, cardY);
        cardY += 10;
        
        doc.setTextColor(0, 0, 0);
        doc.setFontSize(9);
        
        const leftX = margin + 5;
        const rightX = margin + (pageWidth - 2 * margin) / 2 + 5;
        const labelWidth = 20;
        
        doc.setFont('helvetica', 'bold');
        doc.text('Aula:', leftX, cardY);
        doc.setFont('helvetica', 'normal');
        const aulaNombre = reporte.aula?.nombre || 'N/A';
        doc.text(aulaNombre, leftX + labelWidth, cardY);
        
        doc.setFont('helvetica', 'bold');
        doc.text('Reportado por:', rightX, cardY);
        doc.setFont('helvetica', 'normal');
        const nombreUsuario = reporte.usuario_reporta?.name || 'N/A';
        const reportadoMaxWidth = pageWidth - rightX - margin - 30;
        const reportadoLines = doc.splitTextToSize(nombreUsuario, reportadoMaxWidth);
        doc.text(reportadoLines[0], rightX + 28, cardY);
        cardY += 5;
        
        doc.setFont('helvetica', 'bold');
        doc.text('Codigo:', leftX, cardY);
        doc.setFont('helvetica', 'normal');
        const aulaCodigo = reporte.aula?.codigo || 'N/A';
        doc.text(aulaCodigo, leftX + labelWidth, cardY);
        
        doc.setFont('helvetica', 'bold');
        doc.text('Fecha:', rightX, cardY);
        doc.setFont('helvetica', 'normal');
        doc.text(formatDatePDF(reporte.created_at), rightX + 28, cardY);
        cardY += 8;
        
        if (reporte.usuario_asignado) {
            doc.setFont('helvetica', 'bold');
            doc.text('Asignado a:', leftX, cardY);
            doc.setFont('helvetica', 'normal');
            const asignadoNombre = reporte.usuario_asignado.name || 'N/A';
            doc.text(asignadoNombre, leftX + 25, cardY);
            cardY += 5;
        }
        
        doc.setFillColor(243, 244, 246);
        const descBoxHeight = 22;
        doc.roundedRect(margin + 5, cardY - 2, pageWidth - (2 * margin) - 10, descBoxHeight, 1, 1, 'F');
        
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(8);
        doc.text('Descripcion:', margin + 7, cardY + 1);
        
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(8);
        const maxWidth = pageWidth - (2 * margin) - 16;
        const descripcionTexto = reporte.descripcion || '';
        const splitDesc = doc.splitTextToSize(descripcionTexto, maxWidth);
        const linesToShow = splitDesc.slice(0, 4);
        
        linesToShow.forEach((line, idx) => {
            doc.text(line, margin + 7, cardY + 5 + (idx * 3.5));
        });
        
        if (splitDesc.length > 4) {
            doc.text('...', margin + 7, cardY + 5 + (3 * 3.5));
        }
        
        yPos += 70;
    });
    
    const totalPages = doc.internal.getNumberOfPages();
    for (let i = 1; i <= totalPages; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(150, 150, 150);
        doc.setFont('helvetica', 'normal');
        doc.text(
            `Documento generado automaticamente - ${fechaGeneracion}`,
            pageWidth / 2,
            pageHeight - 10,
            { align: 'center' }
        );
        doc.text(
            `Pagina ${i} de ${totalPages}`,
            pageWidth - margin,
            pageHeight - 10,
            { align: 'right' }
        );
    }
    
    const timestamp = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    doc.save(`reportes_problemas_${timestamp}.pdf`);
};

const formatDatePDF = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        const dia = String(date.getDate()).padStart(2, '0');
        const mes = String(date.getMonth() + 1).padStart(2, '0');
        const anio = date.getFullYear();
        const hora = String(date.getHours()).padStart(2, '0');
        const minuto = String(date.getMinutes()).padStart(2, '0');
        return `${dia}/${mes}/${anio}, ${hora}:${minuto}`;
    } catch (e) {
        return 'N/A';
    }
};

const generateExcel = (reportes) => {
    const data = reportes.map(reporte => ({
        'ID': reporte.id,
        'Aula': reporte.aula?.nombre || 'N/A',
        'Codigo Aula': reporte.aula?.codigo || 'N/A',
        'Categoria': getCategoriaLabel(reporte.categoria),
        'Estado': getEstadoLabel(reporte.estado),
        'Descripcion': reporte.descripcion,
        'Reportado Por': reporte.usuario_reporta?.name || 'N/A',
        'Asignado A': reporte.usuario_asignado?.name || 'No asignado',
        'Fecha Reporte': formatDate(reporte.created_at),
        'Fecha Resolucion': reporte.fecha_resolucion ? formatDate(reporte.fecha_resolucion) : 'N/A',
        'Notas Resolucion': reporte.notas_resolucion || 'N/A'
    }));
    
    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.json_to_sheet(data);
    
    const colWidths = [
        { wch: 5 },
        { wch: 20 },
        { wch: 12 },
        { wch: 18 },
        { wch: 15 },
        { wch: 40 },
        { wch: 25 },
        { wch: 25 },
        { wch: 18 },
        { wch: 18 },
        { wch: 30 }
    ];
    ws['!cols'] = colWidths;
    
    XLSX.utils.book_append_sheet(wb, ws, 'Reportes de Problemas');
    
    XLSX.writeFile(wb, `reportes_problemas_aulas_${new Date().toISOString().slice(0, 10)}.xlsx`);
};

const getEstadoColorPDF = (estado) => {
    const colors = {
        'reportado': { bg: [219, 234, 254], text: [30, 64, 175] },
        'en_revision': { bg: [254, 243, 199], text: [146, 64, 14] },
        'asignado': { bg: [233, 213, 255], text: [107, 33, 168] },
        'en_proceso': { bg: [207, 250, 254], text: [21, 94, 117] },
        'resuelto': { bg: [209, 250, 229], text: [6, 95, 70] },
        'rechazado': { bg: [254, 226, 226], text: [153, 27, 27] },
        'cerrado': { bg: [229, 231, 235], text: [55, 65, 81] }
    };
    return colors[estado] || colors.reportado;
};

const getCategoriaLabel = (categoria) => {
    const categorias = {
        'recurso_defectuoso': 'Recurso Defectuoso',
        'qr_danado': 'QR Dañado',
        'limpieza': 'Limpieza',
        'infraestructura': 'Infraestructura',
        'conectividad': 'Conectividad',
        'otro': 'Otro'
    };
    return categorias[categoria] || categoria;
};

const getEstadoLabel = (estado) => {
    const estados = {
        'reportado': 'Reportado',
        'en_revision': 'En Revision',
        'asignado': 'Asignado',
        'en_proceso': 'En Proceso',
        'resuelto': 'Resuelto',
        'rechazado': 'Rechazado',
        'cerrado': 'Cerrado'
    };
    return estados[estado] || estado;
};

const getEstadoBadgeClass = (estado) => {
    const classes = {
        'reportado': 'bg-blue-100 text-blue-800',
        'en_revision': 'bg-yellow-100 text-yellow-800',
        'asignado': 'bg-purple-100 text-purple-800',
        'en_proceso': 'bg-cyan-100 text-cyan-800',
        'resuelto': 'bg-green-100 text-green-800',
        'rechazado': 'bg-red-100 text-red-800',
        'cerrado': 'bg-gray-100 text-gray-800'
    };
    return classes[estado] || 'bg-gray-100 text-gray-800';
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (e) {
        return 'Fecha invalida';
    }
};

onMounted(() => {
    if (isAuthenticated.value) {
        fetchReports();
    }
});
</script>