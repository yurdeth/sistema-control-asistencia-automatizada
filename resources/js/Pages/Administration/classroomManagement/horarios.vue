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

			<div class="bg-white rounded-lg shadow p-6 mb-6">
				<!-- Controles: búsqueda, limpiar, nuevo, subir excel -->
				<div class="flex flex-col sm:flex-row gap-4">
					<input
						v-model="searchTerm"
						type="text"
						placeholder="Buscar por grupo, aula, día o hora"
						class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
					/>

					<button
						@click="performCleanSearch"
						v-if="searchTerm"
						class="text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 whitespace-nowrap"
						:style="{background: '#ff6678'}"
					>
						<span class="text-xl"><i class="fa-solid fa-trash"></i></span>
						Limpiar Búsqueda
					</button>

					<button
						@click="openCreateModal"
						class="text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 whitespace-nowrap"
						:style="{background: '#ff9966'}"
					>
						<span class="text-xl">+</span>
						Nuevo
					</button>
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
						<table class="w-full" :style="{ border: '1px solid #d93f3f' }">
							<thead class="bg-gray-50 border-b-2 border-gray-200 text-center" :style="{background: '#d93f3f', height: '40px'}">
								<tr>
									<th class="text-white px-4 py-2">Grupo</th>
									<th class="text-white px-4 py-2">Materia</th>
									<th class="text-white px-4 py-2">Docente</th>
									<th class="text-white px-4 py-2">Aula</th>
									<th class="text-white px-4 py-2">Día</th>
									<th class="text-white px-4 py-2">Inicio</th>
									<th class="text-white px-4 py-2">Fin</th>
									<th class="text-white px-4 py-2">Opciones</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-gray-200 text-center align-middle">
								<tr v-for="h in paginated" :key="h.id" class="hover:bg-gray-50 transition-colors">
									<td class="px-6 py-4 text-sm text-gray-900">{{ h.numero_grupo ?? ('#' + h.grupo_id) }}</td>
									<td class="px-6 py-4 text-sm text-gray-900">{{ h.materia_nombre ?? ('#' + (h.materia_id || '')) }}</td>
									<td class="px-6 py-4 text-sm text-gray-600">{{ h.docente_nombre ?? ('#' + (h.docente_id || '')) }}</td>
									<td class="px-6 py-4 text-sm text-gray-600">{{ h.aula_nombre || h.nombre_aula || ('#' + h.aula_id) }}</td>
									<td class="px-6 py-4 text-sm font-medium text-gray-900">{{ h.dia_semana }}</td>
									<td class="px-6 py-4 text-sm font-medium text-gray-900">{{ h.hora_inicio }}</td>
									<td class="px-6 py-4 text-sm font-medium text-gray-900">{{ h.hora_fin }}</td>
									<td class="px-6 py-4 text-sm">
										<div class="flex justify-center gap-2">
											<button
												@click="openEditModal(h)"
												class="bg-green-500 hover:bg-green-800 text-white px-4 py-2 rounded-lg transition-colors"
												:disabled="loading"
											>
												Editar
											</button>
											<button
												@click="deleteItem(h.id)"
												class="hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors"
												:style="{ background: '#9b3b3e' }"
												:disabled="loading"
											>
												Eliminar
											</button>
										</div>
									</td>
								</tr>
							</tbody>
						</table>

						<!-- Paginación (estilo grupos.vue) -->
						<div class="flex justify-center items-center space-x-2 p-4 border-t border-gray-200">
							<button
								@click="prevPage"
								:disabled="currentPage === 1"
								class="p-2 border rounded-lg transition-colors"
								:class="{ 'bg-gray-200 cursor-not-allowed': currentPage === 1, 'hover:bg-gray-100': currentPage > 1 }">
								<i class="fas fa-chevron-left"></i></button>

							<button
								v-for="p in totalPages"
								:key="p"
								@click="goToPage(p)"
								class="px-4 py-2 border rounded-lg font-bold text-white transition-colors"
								:style="{ background: p===currentPage ? '#d93f3f' : 'transparent' }">
								{{ p }}
							</button>

							<button
								@click="nextPage"
								:disabled="currentPage === totalPages"
								class="p-2 border rounded-lg transition-colors"
								:class="{ 'bg-gray-200 cursor-not-allowed': currentPage === totalPages, 'hover:bg-gray-100': currentPage < totalPages }">
								<i class="fas fa-chevron-right"></i></button>
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
					<div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
						<div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center z-10">
							<h2 class="text-lg font-semibold">{{ isEditMode ? 'Editar horario' : 'Nuevo horario' }}</h2>
							<button @click="closeModal" class="text-gray-500 hover:text-gray-700">Cerrar</button>
						</div>

						<!-- server/global error -->
						<div v-if="serverErrorMessage" class="mb-3 p-3 bg-red-50 border border-red-200 text-red-700 rounded">
							{{ serverErrorMessage }}
						</div>

						<!-- Agregado: mostrar errores generales del servidor como lista -->
						<div v-if="serverErrors && serverErrors.length" class="mb-3 p-2 rounded bg-red-50 text-red-800">
							<ul class="list-disc pl-5">
								<li v-for="(msg, idx) in serverErrors" :key="idx">{{ msg }}</li>
							</ul>
						</div>

						<form @submit.prevent="submitForm" class="space-y-3 p-4">
							<div class="grid grid-cols-2 gap-3">
								<!-- Materia select (nuevo) -->
								<div>
									<label class="block text-sm">Materia</label>
									<select v-model="selectedMateriaId" @change="onMateriaChange" class="w-full border rounded px-2 py-1">
										<option value="">Seleccione materia...</option>
										<option v-for="m in materias" :key="m.id" :value="m.id">{{ m.nombre }}</option>
									</select>
								</div>

								<!-- Grupo select dependiente -->
								<div>
									<label class="block text-sm">Grupo</label>
									<select ref="grupoRef" v-model="form.grupo_id" class="w-full border rounded px-2 py-1">
										<option value="">Seleccione</option>
										<option v-for="g in subjectGroups" :key="g.id" :value="g.id">
											{{ g.numero_grupo ?? g.nombre ?? ('#' + g.id) }}
										</option>
									</select>
									<ul v-if="errors.grupo_id && errors.grupo_id.length" class="text-red-600 text-sm mt-1 list-disc ml-4">
										<li v-for="(m, idx) in errors.grupo_id" :key="idx">{{ m }}</li>
									</ul>
								</div>

								<div>
									<label class="block text-sm">Aula</label>
									<select ref="aulaRef" v-model="form.aula_id" class="w-full border rounded px-2 py-1">
										<option value="">Seleccione</option>
										<option v-for="a in classrooms" :key="a.id" :value="a.id">
											{{ a.nombre ?? a.nombre_aula ?? a.codigo ?? ('#' + a.id) }}
										</option>
									</select>
									<ul v-if="errors.aula_id && errors.aula_id.length" class="text-red-600 text-sm mt-1 list-disc ml-4">
										<li v-for="(m, idx) in errors.aula_id" :key="idx">{{ m }}</li>
									</ul>
								</div>

								<div>
									<label class="block text-sm">Día</label>
									<select ref="diaRef" v-model="form.dia_semana" class="w-full border rounded px-2 py-1">
										<option value="">Seleccione</option>
										<option>Lunes</option>
										<option>Martes</option>
										<option>Miercoles</option>
										<option>Jueves</option>
										<option>Viernes</option>
										<option>Sabado</option>
										<option>Domingo</option>
									</select>
									<ul v-if="errors.dia_semana && errors.dia_semana.length" class="text-red-600 text-sm mt-1 list-disc ml-4">
										<li v-for="(m, idx) in errors.dia_semana" :key="idx">{{ m }}</li>
									</ul>
								</div>

								<div>
									<label class="block text-sm">Hora Inicio</label>
									<input ref="horaInicioRef" type="time" v-model="form.hora_inicio" step="60" class="w-full border rounded px-2 py-1" />
									<ul v-if="errors.hora_inicio && errors.hora_inicio.length" class="text-red-600 text-sm mt-1 list-disc ml-4">
										<li v-for="(m, idx) in errors.hora_inicio" :key="idx">{{ m }}</li>
									</ul>
								</div>

								<div>
									<label class="block text-sm">Hora Fin</label>
									<input ref="horaFinRef" type="time" v-model="form.hora_fin" step="60" class="w-full border rounded px-2 py-1" />
									<ul v-if="errors.hora_fin && errors.hora_fin.length" class="text-red-600 text-sm mt-1 list-disc ml-4">
										<li v-for="(m, idx) in errors.hora_fin" :key="idx">{{ m }}</li>
									</ul>
								</div>
							</div>

							<div class="flex justify-end">
								<button type="submit" :disabled="submitting" class="bg-green-600 text-white px-4 py-2 rounded">Guardar</button>
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
const selectedMateriaId = ref(''); // materia currently selected in modal

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

const fieldRefs = { grupo_id: grupoRef, aula_id: aulaRef, dia_semana: diaRef, hora_inicio: horaInicioRef, hora_fin: horaFinRef };
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

/* Fetch y manejo de nombres relacionados (grupo.numero_grupo, aula.nombre, materia y docente) */
const fetchAll = async () => {
	loading.value = true;
	try {
		const res = await axios.get(`${API_URL}/schedules/get/all`, getAuthHeaders());
		list.value = res.data.data || [];
		await populateRelatedNames(list.value);
	} catch (e) { console.error(e); list.value = []; } finally { loading.value = false; }
};

const populateRelatedNames = async (items) => {
	try {
		const groupIds = new Set();
		const aulaIds = new Set();

		items.forEach(i => {
			if (!i.numero_grupo && i.grupo_id) groupIds.add(i.grupo_id);
			if (!i.aula_nombre && i.aula_id) aulaIds.add(i.aula_id);
		});

		// fetch groups full objects (so we can extract materia_id/docente_id)
		const groupsMap = {};
		if (groupIds.size > 0) {
			await Promise.all(Array.from(groupIds).map(async id => {
				try {
					const r = await axios.get(`${API_URL}/groups/get/${id}`, getAuthHeaders());
					let d = r.data?.data ?? r.data ?? null;
					// si la API devuelve un array u objeto con primer nivel, tomar el primer elemento útil
					if (Array.isArray(d) && d.length) d = d[0];
					if (d && typeof d === 'object' && !('id' in d) && Object.values(d).length) {
						const firstObj = Object.values(d).find(v => v && typeof v === 'object');
						if (firstObj) d = firstObj;
					}
					if (d) groupsMap[id] = d;
				} catch (e) { console.warn('failed fetch group', id, e?.message); }
			}));
		}

		// fetch aulas names
		const aulasMap = {};
		if (aulaIds.size > 0) {
			await Promise.all(Array.from(aulaIds).map(async id => {
				try {
					const r = await axios.get(`${API_URL}/classrooms/get/${id}`, getAuthHeaders());
					let d = r.data?.data ?? r.data ?? null;
					if (Array.isArray(d) && d.length) d = d[0];
					if (d && typeof d === 'object' && !('nombre' in d) && Object.values(d).length) {
						const firstObj = Object.values(d).find(v => v && typeof v === 'object');
						if (firstObj) d = firstObj;
					}
					if (d) aulasMap[id] = d.nombre ?? d.nombre_aula ?? d.codigo ?? `${d.id}`;
				} catch (e) { console.warn('failed fetch aula', id, e?.message); }
			}));
		}

		// collect subjectIds and profesorIds from groupsMap and items
		const subjectIds = new Set();
		const profesorIds = new Set();

		items.forEach(i => {
			const g = groupsMap[i.grupo_id];
			if (g) {
				if (!i.numero_grupo) i.numero_grupo = g.numero_grupo ?? g.numero ?? g.id;
				// materias en distintas propiedades
				const mid = g.materia_id ?? g.subject_id ?? g.subject?.id ?? g.materia?.id;
				if (mid) subjectIds.add(mid);
				// docentes en distintas propiedades
				const pid = g.docente_id ?? g.user_id ?? g.docente?.id ?? g.user?.id;
				if (pid) profesorIds.add(pid);
				// store ids on item if missing
				if (!i.materia_id && mid) i.materia_id = mid;
				if (!i.docente_id && pid) i.docente_id = pid;
			}
			// include already-present ids
			if (i.materia_id) subjectIds.add(i.materia_id);
			if (i.docente_id) profesorIds.add(i.docente_id);
			// aula name from aulasMap
			if (!i.aula_nombre && i.aula_id && aulasMap[i.aula_id]) i.aula_nombre = aulasMap[i.aula_id];
		});

		// fetch subject names
		const subjectsMap = {};
		if (subjectIds.size > 0) {
			await Promise.all(Array.from(subjectIds).map(async id => {
				try {
					const r = await axios.get(`${API_URL}/subjects/get/${id}`, getAuthHeaders());
					let d = r.data?.data ?? r.data ?? null;
					if (Array.isArray(d) && d.length) d = d[0];
					if (d && typeof d === 'object' && !('nombre' in d) && Object.values(d).length) {
						const firstObj = Object.values(d).find(v => v && typeof v === 'object');
						if (firstObj) d = firstObj;
					}
					if (d) subjectsMap[id] = d.nombre ?? d.name ?? `${d.id}`;
				} catch (e) { console.warn('failed fetch subject', id, e?.message); }
			}));
		}

		// fetch profesores names
		const profesoresMap = {};
		if (profesorIds.size > 0) {
			await Promise.all(Array.from(profesorIds).map(async id => {
				try {
					const r = await axios.get(`${API_URL}/users/get/${id}`, getAuthHeaders());
					let d = r.data?.data ?? r.data ?? null;
					if (Array.isArray(d) && d.length) d = d[0];
					if (d && typeof d === 'object' && !('id' in d) && Object.values(d).length) {
						const firstObj = Object.values(d).find(v => v && typeof v === 'object');
						if (firstObj) d = firstObj;
					}
					if (d) profesoresMap[id] = d.nombre_completo ?? d.name ?? d.nombre ?? `${d.id}`;
				} catch (e) { console.warn('failed fetch profesor', id, e?.message); }
			}));
		}

		// attach resolved names back to items
		items.forEach(i => {
			if ((!i.numero_grupo || i.numero_grupo === '') && groupsMap[i.grupo_id]) {
				i.numero_grupo = groupsMap[i.grupo_id].numero_grupo ?? groupsMap[i.grupo_id].numero ?? (`#${i.grupo_id}`);
			}
			if (!i.materia_nombre && i.materia_id && subjectsMap[i.materia_id]) i.materia_nombre = subjectsMap[i.materia_id];
			if (!i.docente_nombre && i.docente_id && profesoresMap[i.docente_id]) i.docente_nombre = profesoresMap[i.docente_id];
		});
	} catch (e) {
		console.error('populateRelatedNames error', e);
	}
};

/* Select options fetch (materias y profesores para modal) */
const fetchSelectOptions = async () => {
	try {
		const [groupsRes, classroomsRes, materiasRes, profesoresRes] = await Promise.all([
			axios.get(`${API_URL}/groups/get/all`, getAuthHeaders()),
			axios.get(`${API_URL}/classrooms/get/all`, getAuthHeaders()),
			axios.get(`${API_URL}/subjects/get/all`, getAuthHeaders()),
			axios.get(`${API_URL}/users/get/professors/all`, getAuthHeaders())
		]);
		groups.value = groupsRes.data.data || [];
		classrooms.value = classroomsRes.data.data || [];
		materias.value = materiasRes.data.data || [];
		profesores.value = profesoresRes.data.data || [];
	} catch (e) { console.error('fetchSelectOptions error', e); groups.value = []; classrooms.value = []; materias.value = []; profesores.value = []; }
};

/* Cargar grupos por materia para el select dependiente */
const fetchGroupsForSubject = async (subjectId) => {
	subjectGroups.value = [];
	if (!subjectId) return;
	loading.value = true;
	try {
		const res = await axios.get(`${API_URL}/groups/get/subject/${subjectId}`, getAuthHeaders());
		const payload = res.data?.data ?? res.data ?? [];
		subjectGroups.value = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);
	} catch (e) {
		console.error('fetchGroupsForSubject error', e);
		subjectGroups.value = [];
	} finally { loading.value = false; }
};

const onMateriaChange = async () => {
	await fetchGroupsForSubject(selectedMateriaId.value);
	// reset grupo selection when materia changes
	form.value.grupo_id = '';
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
const submitForm = async () => {
	submitting.value = true;
	const normalizeTime = (t) => {
		if (t === null || t === undefined) return t;
		const s = t.toString();
		if (s.length >= 5) return s.slice(0,5);
		return s;
	};

	const payload = {
		...form.value,
		hora_inicio: normalizeTime(form.value.hora_inicio),
		hora_fin: normalizeTime(form.value.hora_fin)
	};

	// limpiar errores previos
	errors.value = {};
	serverErrorMessage.value = '';
	serverErrors.value = [];

	try {
		if (isEditMode.value) {
			await axios.patch(`${API_URL}/schedules/edit/${currentId.value}`, payload, getAuthHeaders());
		} else {
			await axios.post(`${API_URL}/schedules/new`, payload, getAuthHeaders());
		}
		await fetchAll();
		closeModal();
	} catch (e) {
		const data = e?.response?.data ?? null;

		// campos esperados para horarios
		const known = new Set(['grupo_id','aula_id','dia_semana','hora_inicio','hora_fin']);

		// si vienen errores estilo Laravel (object)
		if (data && data.errors && typeof data.errors === 'object') {
			Object.keys(data.errors).forEach(k => {
				const raw = data.errors[k];
				const msgArr = Array.isArray(raw) ? raw : [raw];
				if (known.has(k)) {
					// mantener formato de array porque plantilla itera arrays
					errors.value[k] = msgArr;
				} else {
					// errores no mapeables como lista general
					serverErrors.value.push(...msgArr.map(m => (typeof m === 'string' ? m : JSON.stringify(m))));
				}
			});
			// mensaje general opcional
			if (data.message && typeof data.message === 'string') serverErrorMessage.value = data.message;
			await focusFirstError();
		}
		// si viene un mensaje genérico (texto)
		else if (data && typeof data === 'string') {
			serverErrorMessage.value = data;
		}
		// si existe campo error (objeto con error)
		else if (data && (data.error || data.message)) {
			serverErrorMessage.value = data.error || data.message;
		}
		// fallback por status 422 sin estructura conocida
		else if (e?.response?.status === 422 && e?.response?.data) {
			const d = e.response.data;
			if (d.errors && typeof d.errors === 'object') {
				Object.keys(d.errors).forEach(k => {
					const raw = d.errors[k];
					errors.value[k] = Array.isArray(raw) ? raw : [raw];
				});
				serverErrorMessage.value = d.message || 'Error de validación';
				await focusFirstError();
			} else {
				serverErrorMessage.value = d.message || 'Error de validación';
			}
		} else {
			serverErrorMessage.value = e?.message || 'Error en la solicitud. Revisa la consola para más detalles.';
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

/* Montaje inicial */
onMounted(async () => {
	await authService.verifyToken(localStorage.getItem('token'));
	await fetchSelectOptions();
	await fetchAll();
	isLoading.value = false;
});
</script>

<style scoped>
/* Rely on Tailwind; colores inline adaptados para coincidir con grupos.vue */
</style>

