<template>
	<Head title="Horarios" />

	<!-- Spinner de verificación (igual que en grupos.vue) -->
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
				<h1 class="text-2xl font-bold text-gray-900 mb-1" :style="{color: colorText}">Horarios</h1>
				<p class="text-gray-600 text-sm">Listado de horarios por grupo y aula</p>
			</div>

			<div class="bg-white rounded-lg shadow p-4 sm:p-6 mb-6">
			<!-- Controles: búsqueda, limpiar, nuevo, subir excel -->
				<div class="flex flex-col gap-3 sm:gap-4">
					<!-- Fila de búsqueda y botones -->
					<div class="flex flex-col sm:flex-row gap-3">
						<!-- Búsqueda -->
						<input
							v-model="searchTerm"
							type="text"
							placeholder="Buscar por grupo, aula, día o hora"
							class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-base min-w-0"
						/>

						<!-- Botones de acción -->
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

						<button
							@click="openCreateModal"
							class="text-white px-4 py-3 sm:px-6 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 min-h-[44px]"
							:style="{background: '#ff9966'}"
						>
							<i class="fa-solid fa-plus hidden sm:inline text-sm sm:text-xl"></i>
							<span class="hidden sm:inline">Nuevo</span>
							<span class="sm:hidden">+</span>
						</button>
						</div>
					</div>
				</div>

				<br>

				<!-- Loader -->
				<div v-if="loading" class="bg-white rounded-lg shadow-lg p-8 text-center">
					<div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
					<p class="mt-4 text-gray-600">Cargando horarios...</p>
				</div>

				<!-- Tabla de datos -->
				<div v-else-if="!loading && filtered.length" class="bg-white rounded-lg overflow-hidden">
					<div class="overflow-x-auto">
						<table class="w-full min-w-[600px]" :style="{ border: '1px solid #d93f3f' }">
							<thead class="bg-gray-50 border-b-2 border-gray-200 text-center" :style="{background: '#d93f3f', height: '40px' }">
								<tr>
									<th class="text-white px-2 sm:px-4 py-2 text-xs sm:text-sm">Grupo</th>
									<th class="text-white px-2 sm:px-4 py-2 text-xs sm:text-sm">Materia</th>
									<th class="text-white px-2 sm:px-4 py-2 text-xs sm:text-sm">Docente</th>
									<th class="text-white px-2 sm:px-4 py-2 text-xs sm:text-sm">Aula</th>
									<th class="text-white px-2 sm:px-4 py-2 text-xs sm:text-sm">Día</th>
									<th class="text-white px-2 sm:px-4 py-2 text-xs sm:text-sm">Inicio</th>
									<th class="text-white px-2 sm:px-4 py-2 text-xs sm:text-sm">Fin</th>
									<th class="text-white px-2 sm:px-4 py-2 text-xs sm:text-sm">Opciones</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-gray-200 text-center align-middle">
								<tr v-for="h in paginated" :key="h.id" class="hover:bg-gray-50 transition-colors">
									<td class="px-2 sm:px-4 py-3 text-xs sm:text-sm text-gray-900">{{ h.numero_grupo ?? ('#' + h.grupo_id) }}</td>
									<td class="px-2 sm:px-4 py-3 text-xs sm:text-sm text-gray-900">{{ h.materia_nombre ?? ('#' + (h.materia_id || '')) }}</td>
									<td class="px-2 sm:px-4 py-3 text-xs sm:text-sm text-gray-600">{{ h.docente_nombre ?? ('#' + (h.docente_id || '')) }}</td>
									<td class="px-2 sm:px-4 py-3 text-xs sm:text-sm text-gray-600">{{ h.aula_nombre || h.nombre_aula || ('#' + h.aula_id) }}</td>
									<td class="px-2 sm:px-4 py-3 text-xs sm:text-sm font-medium text-gray-900">{{ h.dia_semana }}</td>
									<td class="px-2 sm:px-4 py-3 text-xs sm:text-sm font-medium text-gray-900">{{ h.hora_inicio }}</td>
									<td class="px-2 sm:px-4 py-3 text-xs sm:text-sm font-medium text-gray-900">{{ h.hora_fin }}</td>
									<td class="px-2 sm:px-4 py-3 text-xs sm:text-sm">
										<div class="flex justify-center gap-1 flex-wrap">
											<button
												@click="openEditModal(h)"
                                                :style="{background: '#FE6244'}"
												class=" text-white px-2 py-1 sm:px-3 sm:py-2 rounded text-xs sm:text-sm transition-colors min-w-[60px] flex items-center justify-center"
												:disabled="loading"
											>
												<i class="fa-solid fa-edit sm:mr-1"></i>
												<span class="hidden sm:inline">Editar</span>
											</button>
											<button
												@click="deleteItem(h.id)"
												class="hover:bg-red-600 text-white px-2 py-1 sm:px-3 sm:py-2 rounded text-xs sm:text-sm transition-colors min-w-[60px] flex items-center justify-center"
												:style="{ background: '#9b3b3e' }"
												:disabled="loading"
											>
												<i class="fa-solid fa-trash sm:mr-1"></i>
												<span class="hidden sm:inline">Eliminar</span>
											</button>
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

							<!-- Botones de paginación inteligente -->
							<template v-for="(page, index) in visiblePages" :key="index">
								<!-- Puntos suspensivos -->
								<span v-if="page === '...'" class="px-1 sm:px-2 text-gray-500 text-xs sm:text-sm flex items-center">...</span>

								<!-- Botón de página -->
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
					<p v-if="searchTerm === ''">No hay horarios registrados en el sistema.</p>
					<p v-else>No se encontraron horarios que coincidan con la búsqueda: <span class="text-red-500">"{{ searchTerm }}"</span></p>
				</div>

				<!-- Modal sticky header (igual que en grupos.vue) -->
				<div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
					<div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
						<div class="sticky top-0 bg-white border-b px-4 sm:px-6 py-3 sm:py-4 flex justify-between items-center z-10">
							<div>
								<h2 class="text-base sm:text-lg font-semibold">
									{{ isEditMode ? 'Editar horario' : 'Nuevo horario' }}
								</h2>
								<p class="text-xs text-gray-500 mt-1">
									Complete la información para asignar un horario
								</p>
							</div>
							<button @click="closeModal" class="text-gray-500 hover:text-gray-700 text-sm sm:text-base p-1 sm:p-0">
								<i class="fas fa-times text-xl"></i>
							</button>
						</div>

						<form @submit.prevent="submitForm" class="space-y-6 p-4 sm:p-6">
							<!-- Paso 1: Selección de Materia -->
							<div class="bg-blue-50 border-l-4 border-blue-500 rounded-lg p-4">
								<div class="flex items-start">
									<div class="flex-shrink-0">
										<div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold">
											1
										</div>
									</div>
									<div class="ml-3 flex-1">
										<h3 class="text-sm font-medium text-blue-900 mb-3">Seleccione la materia</h3>

										<SearchableSelect
											v-model="selectedMateriaId"
											:options="materiasOptions"
											label=""
											placeholder="Buscar materia por nombre o código..."
											value-key="id"
											label-key="nombre"
											sublabel-key="info"
											:error="errors.materia_id ? errors.materia_id[0] : ''"
											required
											@change="onMateriaChange(); validateField('materia_id')"
										/>

										<!-- Info de materia seleccionada -->
										<div v-if="selectedMateriaInfo" class="mt-2 p-2 bg-white rounded border border-blue-200">
											<p class="text-xs text-gray-600">
												<i class="fas fa-info-circle text-blue-500 mr-1"></i>
												<strong>{{ selectedMateriaInfo.nombre }}</strong>
												<span v-if="selectedMateriaInfo.codigo" class="ml-2">
													({{ selectedMateriaInfo.codigo }})
												</span>
											</p>
										</div>
									</div>
								</div>
							</div>

							<!-- Paso 2: Selección de Grupo -->
							<div class="bg-green-50 border-l-4 border-green-500 rounded-lg p-4">
								<div class="flex items-start">
									<div class="flex-shrink-0">
										<div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white font-bold">
											2
										</div>
									</div>
									<div class="ml-3 flex-1">
										<h3 class="text-sm font-medium text-green-900 mb-3">Seleccione el grupo</h3>

										<SearchableSelect
											v-model="form.grupo_id"
											:options="subjectGroupsOptions"
											label=""
											placeholder="Buscar grupo..."
											value-key="id"
											label-key="nombre"
											sublabel-key="info"
											:error="errors.grupo_id ? errors.grupo_id[0] : ''"
											:disabled="!selectedMateriaId || (selectedMateriaId && subjectGroups.length === 0)"
											required
											@change="validateField('grupo_id')"
										/>

										<!-- Mensaje cuando no hay grupos -->
										<div v-if="selectedMateriaId && subjectGroups.length === 0" class="mt-2 p-2 bg-yellow-100 border border-yellow-300 rounded">
											<p class="text-xs text-yellow-800 font-medium">
												<i class="fas fa-exclamation-triangle mr-1"></i>
												Esta materia no tiene grupos disponibles.
											</p>
										</div>

										<!-- Info de grupo seleccionado -->
										<div v-if="selectedGrupoInfo" class="mt-2 p-2 bg-white rounded border border-green-200">
											<div class="grid grid-cols-2 gap-2 text-xs">
												<div>
													<i class="fas fa-user text-green-500 mr-1"></i>
													<strong>Docente:</strong> {{ selectedGrupoInfo.docente_nombre || 'N/A' }}
												</div>
												<div>
													<i class="fas fa-users text-green-500 mr-1"></i>
													<strong>Capacidad:</strong> {{ selectedGrupoInfo.capacidad_maxima || 0 }} estudiantes
												</div>
											</div>
										</div>

										<p v-if="!selectedMateriaId" class="text-xs text-gray-500 mt-2">
											<i class="fas fa-arrow-up mr-1"></i>
											Primero seleccione una materia
										</p>
									</div>
								</div>
							</div>

							<!-- Paso 3: Selección de Aula y Horario -->
							<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
								<!-- Aula -->
								<div class="bg-purple-50 border-l-4 border-purple-500 rounded-lg p-4">
									<div class="flex items-start">
										<div class="flex-shrink-0">
											<div class="w-8 h-8 bg-purple-500 rounded-full flex items-center justify-center text-white font-bold">
												3
											</div>
										</div>
										<div class="ml-3 flex-1">
											<h3 class="text-sm font-medium text-purple-900 mb-3">Seleccione el aula</h3>

											<SearchableSelect
												v-model="form.aula_id"
												:options="classroomsOptions"
												label=""
												placeholder="Buscar aula..."
												value-key="id"
												label-key="nombre"
												sublabel-key="info"
												:error="errors.aula_id ? errors.aula_id[0] : ''"
												required
												@change="validateField('aula_id')"
											/>

											<!-- Info de aula seleccionada -->
											<div v-if="selectedAulaInfo" class="mt-2 p-2 bg-white rounded border border-purple-200">
												<p class="text-xs text-gray-600 mb-1">
													<i class="fas fa-map-marker-alt text-purple-500 mr-1"></i>
													{{ selectedAulaInfo.ubicacion || 'Sin ubicación' }}
												</p>
												<p class="text-xs text-gray-600">
													<i class="fas fa-chair text-purple-500 mr-1"></i>
													Capacidad: {{ selectedAulaInfo.capacidad_pupitres || 0 }} pupitres
												</p>
											</div>
										</div>
									</div>
								</div>

								<!-- Día de la semana -->
								<div class="bg-yellow-50 border-l-4 border-yellow-500 rounded-lg p-4">
									<div class="flex items-start">
										<div class="flex-shrink-0">
											<div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center text-white font-bold">
												4
											</div>
										</div>
										<div class="ml-3 flex-1">
											<h3 class="text-sm font-medium text-yellow-900 mb-3">Seleccione el día</h3>

											<select
												v-model="form.dia_semana"
												@change="validateField('dia_semana')"
												:class="[
													'w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2',
													errors.dia_semana ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-blue-500'
												]"
											>
												<option value="">Seleccione un día</option>
												<option value="Lunes">Lunes</option>
												<option value="Martes">Martes</option>
												<option value="Miercoles">Miércoles</option>
												<option value="Jueves">Jueves</option>
												<option value="Viernes">Viernes</option>
												<option value="Sabado">Sábado</option>
											</select>

											<p v-if="errors.dia_semana" class="text-red-600 text-xs mt-1">
												{{ errors.dia_semana[0] }}
											</p>
										</div>
									</div>
								</div>
							</div>

							<!-- Paso 5: Horario -->
							<div class="bg-indigo-50 border-l-4 border-indigo-500 rounded-lg p-4">
								<div class="flex items-start">
									<div class="flex-shrink-0">
										<div class="w-8 h-8 bg-indigo-500 rounded-full flex items-center justify-center text-white font-bold">
											5
										</div>
									</div>
									<div class="ml-3 flex-1">
										<h3 class="text-sm font-medium text-indigo-900 mb-3">Configure el horario</h3>

										<TimeBlockSelector
											v-model:model-value-inicio="form.hora_inicio"
											v-model:model-value-fin="form.hora_fin"
											label=""
											:error-inicio="errors.hora_inicio ? errors.hora_inicio[0] : ''"
											:error-fin="errors.hora_fin ? errors.hora_fin[0] : ''"
											required
											@update:model-value-inicio="validateField('horario')"
											@update:model-value-fin="validateField('horario')"
										/>

										<!-- Advertencia de conflicto -->
										<div v-if="aulaDisponibilidad && aulaDisponibilidad.length > 0" class="mt-3 p-2 bg-yellow-100 border border-yellow-300 rounded">
											<p class="text-xs text-yellow-800 font-medium">
												<i class="fas fa-exclamation-triangle mr-1"></i>
												Horarios ocupados en esta aula:
											</p>
											<ul class="mt-1 text-xs text-yellow-700 ml-4 list-disc">
												<li v-for="(ocupado, idx) in aulaDisponibilidad.slice(0, 3)" :key="idx">
													{{ ocupado.hora_inicio }} - {{ ocupado.hora_fin }} ({{ ocupado.grupo }})
												</li>
											</ul>
										</div>
									</div>
								</div>
							</div>
						<!-- Errores generales -->
						<div v-if="serverErrorMessage" class="mb-3 mx-4 sm:mx-6 mt-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded text-sm">
							<i class="fas fa-exclamation-circle mr-2"></i>
							{{ serverErrorMessage }}
						</div>
							<!-- Botones de acción -->
							<div class="flex flex-col sm:flex-row justify-end gap-2 sm:gap-3 pt-4 border-t">
								<button
									type="button"
									@click="closeModal"
									class="w-full sm:w-auto px-6 py-3 text-sm sm:text-base text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors order-2 sm:order-1"
								>
									<i class="fas fa-times mr-2"></i>
									Cancelar
								</button>
								<button
									type="submit"
									:disabled="submitting"
									class="w-full sm:w-auto px-6 py-3 text-sm sm:text-base text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed order-1 sm:order-2"
								>
									<i :class="submitting ? 'fas fa-spinner fa-spin' : 'fas fa-check'" class="mr-2"></i>
									{{ submitting ? 'Guardando...' : (isEditMode ? 'Actualizar' : 'Crear Horario') }}
								</button>
							</div>
						</form>

					</div>
				</div>
			</div>
		</div>
	</MainLayoutDashboard>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import TimeBlockSelector from '@/Components/TimeBlockSelector.vue';
import axios from 'axios';
import { authService } from '@/Services/authService';

const API_URL = '/api';
const getAuthHeaders = () => ({ headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } });

/* Estado de verificación y colores (como en grupos.vue) */
const isLoading = ref(true);
const colorText = ref('#1F2937');
const isAuthenticated = localStorage.getItem('isAuthenticated');

/* Listados y estados */
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

const groups = ref([]); // all groups cache
const classrooms = ref([]);
const materias = ref([]); // subjects list for modal
const profesores = ref([]); // docentes list for lookup/display
const subjectGroups = ref([]); // groups filtered by selected materia in modal
const selectedMateriaId = ref(''); // materia actualmente seleccionada en modal

const showModal = ref(false);
const isEditMode = ref(false);
const submitting = ref(false);
const form = ref({ grupo_id: '', aula_id: '', dia_semana: '', hora_inicio: '', hora_fin: '' });
const currentId = ref(null);
const errors = ref({});
const serverErrorMessage = ref('');
// añadir variable para errores generales del servidor (lista)
const serverErrors = ref([]);

/* refs para foco */
const grupoRef = ref(null);
const aulaRef = ref(null);
const diaRef = ref(null);
const horaInicioRef = ref(null);
const horaFinRef = ref(null);
const materiaRef = ref(null);

/* refs para foco */
const fieldRefs = { grupo_id: grupoRef, aula_id: aulaRef, dia_semana: diaRef, hora_inicio: horaInicioRef, hora_fin: horaFinRef, materia_id: materiaRef };
const focusFirstError = async () => {
	const keys = Object.keys(errors.value || {});
	if (!keys.length) return;
	const key = keys.find(k => fieldRefs[k]);
	if (!key) return;
	await nextTick();
	const r = fieldRefs[key];
	if (r && r.value && typeof r.value.focus === 'function') {
		try { r.value.focus(); } catch (e) { /* ignore */ }
	}
};

/* Fetch optimizado - UNA SOLA petición con eager loading */
const fetchAll = async () => {
	loading.value = true;
	try {
		// 🔥 OPTIMIZACIÓN: Pedir datos con relaciones incluidas desde el backend
		const res = await axios.get(`${API_URL}/schedules/get/all?with_relations=true`, getAuthHeaders());
		const rawData = res.data.data || [];

		// Transformar datos a formato esperado (sin hacer más peticiones)
		list.value = rawData.map(h => ({
			id: h.id,
			grupo_id: h.grupo_id,
			aula_id: h.aula_id,
			dia_semana: h.dia_semana,
			hora_inicio: h.hora_inicio?.slice(0, 5) || h.hora_inicio,
			hora_fin: h.hora_fin?.slice(0, 5) || h.hora_fin,
			// Datos del grupo (ya vienen en la respuesta)
			numero_grupo: h.grupo?.numero_grupo || h.numero_grupo || null,
			// Datos de la materia
			materia_id: h.grupo?.materia_id || h.materia_id || null,
			materia_nombre: h.grupo?.materia?.nombre || h.materia_nombre || null,
			// Datos del docente
			docente_id: h.grupo?.docente_id || h.grupo?.user_id || h.docente_id || null,
			docente_nombre: h.grupo?.docente?.nombre_completo || h.grupo?.user?.nombre_completo || h.docente_nombre || null,
			// Datos del aula
			aula_nombre: h.aula?.nombre || h.aula?.codigo || h.aula_nombre || null,
			nombre_aula: h.aula?.nombre || h.nombre_aula || null,
		}));

	} catch (e) {
		console.error('Error fetching horarios:', e);

		if (e.response?.status === 500) {
			const errorMsg = e.response?.data?.error || e.response?.data?.message;

			if (errorMsg?.includes('MySQL') || errorMsg?.includes('2006')) {
				alert('⚠️ Error de conexión con la base de datos.\n\nPor favor:\n1. Verifica que MySQL esté corriendo\n2. Recarga la página\n3. Contacta al administrador si persiste');
			} else {
				alert('Error del servidor al cargar horarios. Intenta recargar la página.');
			}
		}

		list.value = [];
	} finally {
		loading.value = false;
	}
};

// 🔥 ELIMINAR función populateRelatedNames() - ya no es necesaria

/* Select options fetch - OPTIMIZADO con Promise.all */
const fetchSelectOptions = async () => {
	try {
		// 🔥 Hacer todas las peticiones en paralelo
		const [groupsRes, classroomsRes, materiasRes] = await Promise.all([
			axios.get(`${API_URL}/groups/get/all?with_relations=true`, getAuthHeaders()),
			axios.get(`${API_URL}/classrooms/get/all`, getAuthHeaders()),
			axios.get(`${API_URL}/subjects/get/all`, getAuthHeaders())
		]);

		// Procesar grupos con sus relaciones
		groups.value = (groupsRes.data.data || []).map(g => ({
			...g,
			materia_id: g.materia_id || g.subject_id,
			docente_id: g.docente_id || g.user_id,
			docente_nombre: g.docente?.nombre_completo || g.user?.nombre_completo || g.docente_nombre,
			materia_nombre: g.materia?.nombre || g.subject?.nombre || g.materia_nombre
		}));

		classrooms.value = classroomsRes.data.data || [];
		materias.value = materiasRes.data.data || [];

	} catch (e) {
		console.error('fetchSelectOptions error', e);
		groups.value = [];
		classrooms.value = [];
		materias.value = [];
	}
};

/* Cargar grupos por materia - OPTIMIZADO */
const fetchGroupsForSubject = async (subjectId) => {
	subjectGroups.value = [];
	if (!subjectId) return;

	// 🔥 Primero buscar en caché
	const cached = groups.value.filter(g =>
		g.materia_id === subjectId || g.subject_id === subjectId
	);

	if (cached.length > 0) {
		subjectGroups.value = cached;
		return;
	}

	// Si no está en caché, hacer petición
	loading.value = true;
	try {
		const res = await axios.get(
			`${API_URL}/groups/get/subject/${subjectId}?with_relations=true`,
			getAuthHeaders()
		);
		const payload = res.data?.data ?? res.data ?? [];
		subjectGroups.value = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);
	} catch (e) {
		console.error('fetchGroupsForSubject error', e);
		subjectGroups.value = [];
	} finally {
		loading.value = false;
	}
};

const onMateriaChange = async () => {
	await fetchGroupsForSubject(selectedMateriaId.value);
	// reset grupo selection when materia changes
	form.value.grupo_id = '';
	// limpiar errores previos de grupo
	delete errors.value.grupo_id;
};

/* Búsqueda filtrada con debounce */
const filtered = computed(() => {
	const q = (debouncedSearch.value || '').toString().trim().toLowerCase();
	if (!q) return list.value;
	const normalized = q.replace(/^#/, '');
	return list.value.filter(i => {
		const candidates = [
			i.numero_grupo,
			i.grupo_nombre,
			i.materia_nombre,
			i.docente_nombre,
			i.grupo_id,
			i.aula_nombre,
			i.nombre_aula,
			i.nombre,
			i.aula_id,
			i.dia_semana,
			i.hora_inicio,
			i.hora_fin
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

// Calcular qué páginas mostrar en la paginación
const visiblePages = computed(() => {
	const total = totalPages.value;
	const current = currentPage.value;
	const pages = [];

	// Si hay pocas páginas (5 o menos), mostrar todas
	if (total <= 5) {
		for (let i = 1; i <= total; i++) {
			pages.push(i);
		}
		return pages;
	}

	// Lógica para muchas páginas - mostrar máximo 5 botones
	pages.push(1); // Siempre mostrar la primera página

	// Páginas alrededor de la actual (solo 1 a cada lado)
	const startPage = Math.max(2, current - 1);
	const endPage = Math.min(total - 1, current + 1);

	// Si no estamos cerca del inicio, agregar puntos suspensivos
	if (startPage > 2) {
		pages.push('...');
	}

	// Agregar páginas alrededor de la actual
	for (let i = startPage; i <= endPage; i++) {
		pages.push(i);
	}

	// Si no estamos cerca del final, agregar puntos suspensivos
	if (endPage < total - 1) {
		pages.push('...');
	}

	// Siempre mostrar la última página (si es diferente de la primera)
	if (total > 1) {
		pages.push(total);
	}

	return pages;
});

/* CRUD UI handlers */
const performCleanSearch = () => { searchTerm.value = ''; debouncedSearch.value = ''; };
const openCreateModal = () => {
	isEditMode.value = false;
	form.value = { grupo_id: '', aula_id: '', dia_semana: '', hora_inicio: '', hora_fin: '' };
	currentId.value = null;
	errors.value = {};
	serverErrorMessage.value = '';
	selectedMateriaId.value = '';
	subjectGroups.value = [];
	showModal.value = true;
};
const openEditModal = async (h) => {
	isEditMode.value = true;
	currentId.value = h.id;
	// prefill form
	form.value = { grupo_id: h.grupo_id, aula_id: h.aula_id, dia_semana: h.dia_semana, hora_inicio: h.hora_inicio, hora_fin: h.hora_fin };
	errors.value = {};
	serverErrorMessage.value = '';
	// determine materia for the group and preload subjectGroups
	let materiaId = null;
	// try to find in cached groups
	const cached = groups.value.find(g => g.id === h.grupo_id);
	if (cached && (cached.materia_id || cached.subject_id)) {
		materiaId = cached.materia_id ?? cached.subject_id;
	} else {
		// try to fetch group detail
		if (h.grupo_id) {
			try {
				const r = await axios.get(`${API_URL}/groups/get/${h.grupo_id}`, getAuthHeaders());
				const gd = r.data?.data || r.data || null;
				if (gd && (gd.materia_id || gd.subject_id)) materiaId = gd.materia_id ?? gd.subject_id;
			} catch (e) {
				console.warn('failed fetch group detail for edit modal', e);
			}
		}
	}
	selectedMateriaId.value = materiaId || '';
	if (selectedMateriaId.value) await fetchGroupsForSubject(selectedMateriaId.value);
	showModal.value = true;
};
const closeModal = () => { showModal.value = false; };

/* Normalizar tiempos y submit */
const friendlyField = {
	'grupo_id': 'Seleccione un grupo.',
	'aula_id': 'Seleccione un aula.',
	'dia_semana': 'Seleccione un día.',
	'hora_inicio': 'Ingrese la hora de inicio.',
	'hora_fin': 'Ingrese la hora de fin.',
	'materia_id': 'Seleccione una materia.'
};

const validateForm = () => {
	const errs = {};
	// campos obligatorios básicos
	if (!selectedMateriaId.value) errs.materia_id = [friendlyField.materia_id];
	
	// Validar que la materia seleccionada tenga grupos
	if (selectedMateriaId.value && subjectGroups.value.length === 0) {
		errs.grupo_id = ['Esta materia no tiene grupos disponibles.'];
	} else if (!form.value.grupo_id) {
		errs.grupo_id = [friendlyField.grupo_id];
	}
	
	if (!form.value.aula_id) errs.aula_id = [friendlyField.aula_id];
	if (!form.value.dia_semana) errs.dia_semana = [friendlyField.dia_semana];
	if (!form.value.hora_inicio) errs.hora_inicio = [friendlyField.hora_inicio];
	if (!form.value.hora_fin) errs.hora_fin = [friendlyField.hora_fin];

	// validación simple de horas (hora_fin posterior a hora_inicio)
	if (form.value.hora_inicio && form.value.hora_fin) {
		const hi = form.value.hora_inicio.toString().slice(0,5);
		const hf = form.value.hora_fin.toString().slice(0,5);
		if (hf <= hi) {
			errs.hora_fin = ['La hora de fin debe ser posterior a la hora de inicio.'];
		}
	}

	errors.value = errs;
	return Object.keys(errs).length === 0;
};

const submitForm = async () => {
	submitting.value = true;
	serverErrorMessage.value = '';
	serverErrors.value = [];
	errors.value = {};

	// validación cliente antes de enviar (incluye materia)
	if (!validateForm()) {
		await focusFirstError();
		submitting.value = false;
		return;
	}

	const normalizeTime = (t) => {
		if (t === null || t === undefined) return t;
		const s = t.toString();
		if (s.length >= 5) return s.slice(0,5);
		return s;
	};

	const payload = {
		...form.value,
		materia_id: selectedMateriaId.value, // incluir materia para coherencia con UI
		hora_inicio: normalizeTime(form.value.hora_inicio),
		hora_fin: normalizeTime(form.value.hora_fin)
	};

	try {
		if (isEditMode.value) {
			await axios.patch(`${API_URL}/schedules/edit/${currentId.value}`, payload, getAuthHeaders());
		} else {
			await axios.post(`${API_URL}/schedules/new`, payload, getAuthHeaders());
		}
		await fetchAll();
		closeModal();
	} catch (e) {
		const resp = e?.response ?? null;
		const data = resp?.data ?? null;

		// Si vienen errores de validación (estructura Laravel)
		if (data && data.errors && typeof data.errors === 'object') {
			Object.keys(data.errors).forEach(k => {
				const raw = data.errors[k];
				const arr = Array.isArray(raw) ? raw : [raw];
				// si tenemos un mensaje amigable para el campo, usarlo (evita mostrar "campo_id")
				if (friendlyField[k]) {
					errors.value[k] = [friendlyField[k]];
				} else {
					errors.value[k] = arr.map(m => (typeof m === 'string' ? m : JSON.stringify(m)));
				}
			});
			// mensaje general opcional
			if (data.message && typeof data.message === 'string') {
				serverErrorMessage.value = data.message;
			}
			await focusFirstError();
		}
		// Si llega un mensaje simple (string)
		else if (data && typeof data === 'string') {
			serverErrorMessage.value = data;
		}
		// Si es un objeto con message/error pero sin estructura errors
		else if (data && (data.error || data.message)) {
			// si es 500 mostrar mensaje más amigable
			if (resp && resp.status === 500) {
				serverErrorMessage.value = 'Error interno del servidor. Intente nuevamente más tarde.';
			} else {
				serverErrorMessage.value = data.error || data.message;
			}
		}
		// Si no hay estructura conocida, manejar por status
		else if (resp && resp.status === 500) {
			serverErrorMessage.value = 'Error interno del servidor. Intente nuevamente más tarde.';
		} else if (resp && resp.status === 409) {
			// conflictos (p. ej. conflicto de horario)
			serverErrorMessage.value = data?.message ?? 'Conflicto detectado al guardar.';
		} else {
			serverErrorMessage.value = e?.message || 'Error en la solicitud. Revisa la consola para más detalles.';
		}

		// Si el backend devolvió mensajes no ligados a campos, intentar agregarlos a serverErrors
		if (data && data.errors && typeof data.errors !== 'object') {
			serverErrors.value.push(typeof data.errors === 'string' ? data.errors : JSON.stringify(data.errors));
		}
		console.error(e);
	} finally {
		submitting.value = false;
	}
};

const deleteItem = async (id) => {
	if (!confirm('¿Eliminar horario?')) return;
	try { await axios.delete(`${API_URL}/schedules/delete/${id}`, getAuthHeaders()); await fetchAll(); } catch (e) { console.error(e); }
};

/* Paginación */
const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };
const goToPage = (p) => { if (p>=1 && p<=totalPages.value) currentPage.value = p; };

/* Handler placeholder para subir excel (puede implementarse servidor) */
const handleExcelUpload = (e) => {
	const file = e.target.files && e.target.files[0];
	if (!file) return;
	// Implementar subida si se requiere. Por ahora solo informar en consola.
	console.info('Excel seleccionado:', file.name);
};

/* Montaje inicial - OPTIMIZADO */
onMounted(async () => {
	await authService.verifyToken(localStorage.getItem('token'));

	// 🔥 Cargar todo en paralelo
	await Promise.all([
		fetchSelectOptions(),
		fetchAll()
	]);

	isLoading.value = false;
});

// Computed para preparar opciones de los selects con información adicional
const materiasOptions = computed(() => {
	return materias.value.map(m => ({
		id: m.id,
		nombre: m.nombre,
		codigo: m.codigo,
		// Sublabel con información adicional
		info: m.codigo ? `Código: ${m.codigo}` : ''
	}));
});

const subjectGroupsOptions = computed(() => {
	return subjectGroups.value.map(g => ({
		id: g.id,
		nombre: `Grupo ${g.numero_grupo || g.nombre || g.id}`,
		// Información del docente y capacidad
		info: `${g.docente_nombre || 'Sin docente'} | Cap: ${g.capacidad_maxima || 0}`
	}));
});

const classroomsOptions = computed(() => {
	return classrooms.value.map(a => ({
		id: a.id,
		nombre: a.nombre || a.codigo || `Aula ${a.id}`,
		// Información de capacidad y ubicación
		info: `Cap: ${a.capacidad_pupitres || 0} | ${a.ubicacion || 'Sin ubicación'}`
	}));
});

// Estado para información contextual
const selectedMateriaInfo = ref(null);
const selectedGrupoInfo = ref(null);
const selectedAulaInfo = ref(null);
const aulaDisponibilidad = ref(null);

// Watchers para cargar información adicional
watch(selectedMateriaId, async (newVal) => {
	if (newVal) {
		selectedMateriaInfo.value = materias.value.find(m => m.id === newVal);
	} else {
		selectedMateriaInfo.value = null;
		selectedGrupoInfo.value = null;
	}
});

watch(() => form.value.grupo_id, async (newVal) => {
	if (newVal) {
		selectedGrupoInfo.value = subjectGroups.value.find(g => g.id === newVal);
	} else {
		selectedGrupoInfo.value = null;
	}
});

watch(() => form.value.aula_id, async (newVal) => {
	if (newVal) {
		selectedAulaInfo.value = classrooms.value.find(a => a.id === newVal);
		// Cargar disponibilidad del aula
		await checkAulaDisponibilidad(newVal);
	} else {
		selectedAulaInfo.value = null;
		aulaDisponibilidad.value = null;
	}
});

// Verificar disponibilidad del aula
const checkAulaDisponibilidad = async (aulaId) => {
	// if (!aulaId || !form.value.dia_semana) return;

	// try {
	// 	const res = await axios.get(
	// 		`${API_URL}/classrooms/get/${aulaId}/availability?dia=${form.value.dia_semana}`,
	// 		getAuthHeaders()
	// 	);
	// 	aulaDisponibilidad.value = res.data.data || [];
	// } catch (e) {
	// 	console.warn('Error checking aula availability:', e);
	// 	aulaDisponibilidad.value = null;
	// }
};

// Watch para revalidar cuando cambia el día
watch(() => form.value.dia_semana, async () => {
	if (form.value.aula_id) {
		await checkAulaDisponibilidad(form.value.aula_id);
	}
});

// Validación mejorada en tiempo real
const validateField = (field) => {
	const errs = { ...errors.value };

	switch(field) {
		case 'materia_id':
			if (!selectedMateriaId.value) {
				errs.materia_id = [friendlyField.materia_id];
			} else {
				delete errs.materia_id;
			}
			break;
		case 'grupo_id':
			if (selectedMateriaId.value && subjectGroups.value.length === 0) {
				errs.grupo_id = ['Esta materia no tiene grupos disponibles.'];
			} else if (!form.value.grupo_id) {
				errs.grupo_id = [friendlyField.grupo_id];
			} else {
				delete errs.grupo_id;
			}
			break;
		case 'aula_id':
			if (!form.value.aula_id) {
				errs.aula_id = [friendlyField.aula_id];
			} else {
				delete errs.aula_id;
			}
			break;
		case 'dia_semana':
			if (!form.value.dia_semana) {
				errs.dia_semana = [friendlyField.dia_semana];
			} else {
				delete errs.dia_semana;
			}
			break;
		case 'horario':
			if (!form.value.hora_inicio) {
				errs.hora_inicio = [friendlyField.hora_inicio];
			} else {
				delete errs.hora_inicio;
			}

			if (!form.value.hora_fin) {
				errs.hora_fin = [friendlyField.hora_fin];
			} else {
				delete errs.hora_fin;
			}

			// Validar que fin sea después de inicio
			if (form.value.hora_inicio && form.value.hora_fin) {
				if (form.value.hora_fin <= form.value.hora_inicio) {
					errs.hora_fin = ['La hora de fin debe ser posterior a la hora de inicio.'];
				}
			}
			break;
	}

	errors.value = errs;
};
</script>

<style scoped>
/* Rely on Tailwind; colores inline adaptados para coincidir con grupos.vue */
</style>

