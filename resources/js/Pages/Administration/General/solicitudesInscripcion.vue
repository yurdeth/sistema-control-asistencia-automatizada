<template>
	<Head title="Solicitudes de Inscripción" />

	<!-- Spinner de verificación (igual que en horarios.vue) -->
	<div v-if="!isAuthenticated">
		<div v-if="isLoading" class="flex items-center justify-center min-h-screen bg-gray-100">
			<div class="text-center">
				<div class="animate-spin rounded-full h-16 w-16 border-b-4 border-gray-900 mx-auto"></div>
				<p class="mt-4 text-gray-600 text-lg">Verificando sesión...</p>
			</div>
		</div>
	</div>

	<MainLayoutDashboard>
		<div class="p-4 md:p-6">
			<div class="mb-6">
				<h1 class="text-2xl font-bold text-gray-900 mb-1">Solicitudes de Inscripción</h1>
				<p class="text-gray-600 text-sm">Listado de solicitudes por estudiante y grupo</p>
			</div>

			<div class="bg-white rounded-lg shadow p-4 sm:p-6 mb-6">
				<div class="flex flex-col gap-3 sm:gap-4">
					<div class="flex flex-col sm:flex-row gap-3">
						<input
							v-model="searchTerm"
							type="text"
							placeholder="Buscar por estudiante, grupo, tipo o estado"
							class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-base min-w-0"
						/>
						<div class="flex gap-3">
							<button
								@click="performCleanSearch"
								v-if="searchTerm"
								class="text-white px-4 py-3 sm:px-6 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 min-h-[44px]"
								:style="{background: '#ff6678'}"
							>
								<i class="fa-solid fa-trash text-sm sm:text-xl"></i>
								<span class="hidden sm:inline">Limpiar</span>
								<span class="sm:hidden">×</span>
							</button>
						</div>
					</div>
				</div>

				<br>

				<!-- Loader -->
				<div v-if="loading" class="bg-white rounded-lg shadow-lg p-8 text-center">
					<div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
					<p class="mt-4 text-gray-600">Cargando solicitudes...</p>
				</div>

				<!-- Tabla de datos -->
				<div v-else-if="!loading && filtered.length" class="bg-white rounded-lg overflow-hidden">
					<div class="overflow-x-auto">
						<table class="w-full min-w-[600px]" :style="{ border: '1px solid #d93f3f' }">
							<thead class="bg-gray-50 border-b-2 border-gray-200 text-center" :style="{background: '#d93f3f', height: '40px' }">
								<tr>
									<th class="text-white px-2 sm:px-4 py-2 text-xs sm:text-sm">Estudiante</th>
									<th class="text-white px-2 sm:px-4 py-2 text-xs sm:text-sm">Grupo</th>
									<th class="text-white px-2 sm:px-4 py-2 text-xs sm:text-sm">Materia</th>
									<th class="text-white px-2 sm:px-4 py-2 text-xs sm:text-sm">Tipo</th>
									<th class="text-white px-2 sm:px-4 py-2 text-xs sm:text-sm">Estado</th>
									<th class="text-white px-2 sm:px-4 py-2 text-xs sm:text-sm">Opciones</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-gray-200 text-center align-middle">
								<tr v-for="s in paginated" :key="s.id" class="hover:bg-gray-50 transition-colors">
									<td class="px-2 sm:px-4 py-3 text-xs sm:text-sm text-gray-900">{{ s.estudiante_nombre ?? ('#' + s.estudiante_id) }}</td>
									<td class="px-2 sm:px-4 py-3 text-xs sm:text-sm text-gray-900">{{ s.grupo_nombre ?? ('#' + s.grupo_id) }}</td>
									<td class="px-2 sm:px-4 py-3 text-xs sm:text-sm text-gray-900">{{ s.materia_nombre ?? 'N/A' }}</td>
									<td class="px-2 sm:px-4 py-3 text-xs sm:text-sm text-gray-600">{{ s.tipo_solicitud }}</td>
									<td class="px-2 sm:px-4 py-3 text-xs sm:text-sm font-medium text-gray-900">{{ s.estado }}</td>
									<td class="px-2 sm:px-4 py-3 text-xs sm:text-sm">
										<!-- Mostrar botones solo si la solicitud NO está procesada -->
										<div v-if="!isProcessed(s)" class="flex justify-center gap-1 flex-wrap">
											<button
												@click="acceptRequest(s.id)"
												class="hover:bg-blue-600 text-white px-2 py-1 sm:px-3 sm:py-2 rounded text-xs sm:text-sm transition-colors min-w-[60px] flex items-center justify-center"
												:style="{ background: '#16a34a' }"
												:disabled="loading"
											>
												<i class="fa-solid fa-check sm:mr-1"></i>
												<span class="hidden sm:inline">Aceptar</span>
											</button>
											<button
												@click="rejectRequest(s.id)"
												class="hover:bg-red-600 text-white px-2 py-1 sm:px-3 sm:py-2 rounded text-xs sm:text-sm transition-colors min-w-[60px] flex items-center justify-center"
												:style="{ background: '#9b3b3e' }"
												:disabled="loading"
											>
												<i class="fa-solid fa-times sm:mr-1"></i>
												<span class="hidden sm:inline">Rechazar</span>
											</button>
										</div>

										<!-- Indicador cuando ya fue aceptada/rechazada -->
										<div v-else class="text-sm text-gray-500 font-medium">
											Procesada
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</div>

					<!-- Paginación fija (fuera del scroll) -->
					<div class="bg-gray-50 p-2 sm:p-4 border-t border-gray-200">
						<div class="flex justify-center items-center space-x-1 sm:space-x-2 overflow-x-auto pb-1">
							<button
								@click="prevPage"
								:disabled="currentPage === 1"
								class="p-1 sm:p-2 border rounded transition-colors min-w-[32px] sm:min-w-[44px] text-xs sm:text-sm"
								:class="{ 'bg-gray-200 cursor-not-allowed': currentPage === 1, 'hover:bg-gray-100': currentPage > 1 }">
								<i class="fas fa-chevron-left text-xs sm:text-sm"></i>
							</button>

							<template v-for="(page, index) in visiblePages" :key="index">
								<span v-if="page === '...'" class="px-1 sm:px-2 text-gray-500 text-xs sm:text-sm flex items-center">...</span>

								<button
									v-else
									@click="goToPage(page)"
									class="px-2 py-1 sm:px-3 sm:py-2 border rounded font-bold transition-colors min-w-[32px] sm:min-w-[44px] text-xs sm:text-sm"
									:class="[
										page===currentPage
											? 'text-white'
											: 'text-gray-700 hover:bg-gray-100'
									]"
									:style="{ background: page===currentPage ? '#d93f3f' : 'transparent' }">
									{{ page }}
								</button>
							</template>

							<button
								@click="nextPage"
								:disabled="currentPage === totalPages"
								class="p-1 sm:p-2 border rounded transition-colors min-w-[32px] sm:min-w-[44px] text-xs sm:text-sm"
								:class="{ 'bg-gray-200 cursor-not-allowed': currentPage === totalPages, 'hover:bg-gray-100': currentPage < totalPages }">
								<i class="fas fa-chevron-right text-xs sm:text-sm"></i>
							</button>
						</div>
					</div>
				</div>

				<!-- No hay datos -->
				<div v-else-if="!loading && !filtered.length"
					 class="bg-gray-100 border border-gray-400 text-gray-700 px-6 py-4 rounded-lg mb-6 text-center">
					<p v-if="searchTerm === ''">No hay solicitudes registradas en el sistema.</p>
					<p v-else>No se encontraron solicitudes que coincidan con la búsqueda: <span class="text-red-500">"{{ searchTerm }}"</span></p>
				</div>

			</div>
		</div>
	</MainLayoutDashboard>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
import axios from 'axios';
import { authService } from '@/Services/authService';

const API_URL = '/api';
const getAuthHeaders = () => ({ headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } });

/* Estado de verificación (spinner) */
const isLoading = ref(true);
const isAuthenticated = localStorage.getItem('isAuthenticated');

/* Datos y UI */
const list = ref([]);
const loading = ref(false);
const searchTerm = ref('');
const debouncedSearch = ref('');
let searchTimeout = null;
watch(searchTerm, (v) => {
	if (searchTimeout) clearTimeout(searchTimeout);
	searchTimeout = setTimeout(() => { debouncedSearch.value = v; }, 300);
});

const currentPage = ref(1);
const perPage = ref(5);

/* Fetch de solicitudes */
const fetchAll = async () => {
	loading.value = true;
	try {
		const res = await axios.get(`${API_URL}/enrollment-requests/get/all`, getAuthHeaders());
		list.value = res.data.data || [];
	} catch (e) { console.error(e); list.value = []; } finally { loading.value = false; }
};

/* Filtrado con debounce */
const filtered = computed(() => {
	const q = (debouncedSearch.value || '').toString().trim().toLowerCase();
	if (!q) return list.value;
	const normalized = q.replace(/^#/, '');
	return list.value.filter(i => {
		const candidates = [
			i.estudiante_nombre,
			i.estudiante_id,
			i.grupo_nombre,
			i.grupo_id,
			i.materia_nombre,
			i.tipo_solicitud,
			i.estado,
			i.mensaje
		];
		return candidates.some(c => {
			if (c === null || c === undefined) return false;
			return c.toString().toLowerCase().includes(normalized);
		});
	});
});

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage.value)));
const paginated = computed(() => {
	const start = (currentPage.value - 1) * perPage.value;
	return filtered.value.slice(start, start + perPage.value);
});
watch(filtered, () => { currentPage.value = 1; });

/* Paginación inteligente (visiblePages) */
const visiblePages = computed(() => {
	const total = totalPages.value;
	const current = currentPage.value;
	const pages = [];

	if (total <= 5) {
		for (let i = 1; i <= total; i++) pages.push(i);
		return pages;
	}

	pages.push(1);
	const startPage = Math.max(2, current - 1);
	const endPage = Math.min(total - 1, current + 1);

	if (startPage > 2) pages.push('...');
	for (let i = startPage; i <= endPage; i++) pages.push(i);
	if (endPage < total - 1) pages.push('...');
	if (total > 1) pages.push(total);

	return pages;
});

/* Handlers UI */
const performCleanSearch = () => { searchTerm.value = ''; debouncedSearch.value = ''; };

/* Nuevo helper: determina si una solicitud ya fue procesada (aceptada o rechazada) */
const isProcessed = (s) => {
	const estado = (s?.estado || '').toString().toLowerCase();
	return estado === 'aceptada' || estado === 'rechazada';
};

/* Acciones */
const acceptRequest = async (id) => {
	try { await axios.post(`${API_URL}/enrollment-requests/accept/${id}`, {}, getAuthHeaders()); await fetchAll(); } catch (e) { console.error(e); }
};

const rejectRequest = async (id) => {
	try { await axios.post(`${API_URL}/enrollment-requests/reject/${id}`, {}, getAuthHeaders()); await fetchAll(); } catch (e) { console.error(e); }
};

const deleteItem = async (id) => {
	if (!confirm('¿Eliminar solicitud?')) return;
	try { await axios.delete(`${API_URL}/enrollment-requests/delete/${id}`, getAuthHeaders()); await fetchAll(); } catch (e) { console.error(e); }
};

/* Paginación */
const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };
const goToPage = (p) => { if (p>=1 && p<=totalPages.value) currentPage.value = p; };

onMounted(async () => {
	await authService.verifyToken(localStorage.getItem('token'));
	await fetchAll();
	isLoading.value = false;
});
</script>

<style scoped>
/* Rely on Tailwind; colores inline adaptados */
</style>