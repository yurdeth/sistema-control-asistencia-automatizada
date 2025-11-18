<template>
	<Head title="Grupos" />

	<!-- Añadido: mostrar spinner mientras se verifica autenticación como en docentes.vue -->
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
			<!-- CABECERA: título y subtítulo estilo docentes.vue -->
			<div class="mb-6">
				<h1 class="text-2xl font-bold text-gray-900 mb-1" :style="{color: colorText}">Grupos</h1>
				<p class="text-gray-600 text-sm">Listado de grupos y su materia</p>
			</div>

			<div class="bg-white rounded-lg shadow p-6 mb-6">
				<!-- BARRA DE CONTROLES: búsqueda, búsqueda avanzada, limpiar, nuevo, subir excel -->
				<div class="flex flex-col sm:flex-row gap-4">
					<input
						v-model="searchTerm"
						type="text"
						placeholder="Buscar por materia, docente, ciclo o Nº de grupo"
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

				<br>

				<!-- VISTA DE DATOS: ahora con estado de carga visible -->
				<!-- show spinner cuando loading === true -->
				<div v-if="loading" class="bg-white rounded-lg shadow-lg p-8 text-center">
					<div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
					<p class="mt-4 text-gray-600">Cargando grupos...</p>
				</div>

				<div v-else-if="!loading && filtered.length" class="bg-white rounded-lg overflow-hidden">
					<div class="overflow-x-auto">
						<table class="w-full" :style="{ border: '1px solid #d93f3f' }">
							<thead class="bg-gray-50 border-b-2 border-gray-200 text-center"
								   :style="{background: '#d93f3f', height: '40px'}">
								<tr>
									<th class="text-white px-4 py-2">Materia</th>
									<th class="text-white px-4 py-2">Nº Grupo</th>
									<th class="text-white px-4 py-2">Docente</th>
									<th class="text-white px-4 py-2">Capacidad</th>
									<th class="text-white px-4 py-2">Inscritos</th>
									<th class="text-white px-4 py-2">Ciclo</th>
									<th class="text-white px-4 py-2">Estado</th>
									<th class="text-white px-4 py-2">Opciones</th>
								</tr>
							</thead>
							<tbody class="divide-y divide-gray-200 text-center align-middle">
								<tr v-for="g in paginated" :key="g.id" class="hover:bg-gray-50 transition-colors">
									<td class="px-6 py-4 text-sm text-gray-900">{{ g.materia_nombre || ('#' + g.materia_id) }}</td>
									<td class="px-6 py-4 text-sm font-medium text-gray-900">{{ g.numero_grupo || '-' }}</td>
									<td class="px-6 py-4 text-sm text-gray-600">{{ g.docente_nombre || ('#' + g.docente_id) }}</td>
									<td class="px-6 py-4 text-sm font-medium text-gray-900">{{ g.capacidad_maxima ?? '-' }}</td>
									<td class="px-6 py-4 text-sm font-medium text-gray-900">{{ g.estudiantes_inscrito ?? '-' }}</td>
									<td class="px-6 py-4 text-sm font-medium text-gray-900">{{ g.ciclo_nombre || ('#' + g.ciclo_id) }}</td>
									<td class="px-6 py-4 text-sm font-medium text-gray-900">{{ g.estado }}</td>
									<td class="px-6 py-4 text-sm">
										<div class="flex justify-center gap-2">
											<button
												@click="openEditModal(g)"
												class="bg-green-500 hover:bg-green-800 text-white px-4 py-2 rounded-lg transition-colors"
												:disabled="loading"
											>
												Editar
											</button>
											<button
												@click="deleteItem(g.id)"
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

						<!-- PAGINACIÓN estilo docentes.vue -->
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

				<div v-else-if="!loading && !filtered.length"
					 class="bg-gray-100 border border-gray-400 text-gray-700 px-6 py-4 rounded-lg mb-6 text-center">
					<p v-if="searchTerm === ''">No hay grupos registrados en el sistema.</p>
					<p v-else>No se encontraron grupos que coincidan con la búsqueda: <span
						class="text-red-500">"{{ searchTerm }}"</span></p>
				</div>

				<!-- ...existing code after data list ... -->
			</div>
		</div>
	</MainLayoutDashboard>

	<!-- Modal adaptado con header sticky como en docentes.vue -->
	<div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
		<div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
			<div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center z-10">
				<h2 class="text-lg font-semibold">{{ isEditMode ? 'Editar grupo' : 'Nuevo grupo' }}</h2>
				<button @click="closeModal" class="text-gray-500 hover:text-gray-700">Cerrar</button>
			</div>
				<form @submit.prevent="submitForm" class="space-y-3 p-4">
					<div class="grid grid-cols-2 gap-3">
						<div>
							<label class="block text-sm">Materia</label>
							<select v-model="form.materia_id" :class="['w-full rounded px-2 py-1 border', errors.materia_id ? 'border-red-600' : '']">
								<option value="">Seleccionar materia...</option>
								<option v-for="m in materias" :key="m.id" :value="m.id">{{ m.nombre }}</option>
							</select>
							<ul v-if="errors.materia_id && errors.materia_id.length" class="text-sm text-red-600 mt-1 list-disc ml-4">
								<li v-for="(m, idx) in errors.materia_id" :key="idx">{{ m }}</li>
							</ul>
						</div>
						<div>
							<label class="block text-sm">Docente</label>
							<select v-model="form.docente_id" :class="['w-full rounded px-2 py-1 border', errors.docente_id ? 'border-red-600' : '']">
								<option value="">Seleccionar docente...</option>
								<option v-for="d in docentes" :key="d.id" :value="d.id">{{ d.nombre_completo }}</option>
							</select>
							<ul v-if="errors.docente_id && errors.docente_id.length" class="text-sm text-red-600 mt-1 list-disc ml-4">
								<li v-for="(m, idx) in errors.docente_id" :key="idx">{{ m }}</li>
							</ul>
						</div>
						<div>
							<label class="block text-sm">Ciclo</label>
							<select v-model="form.ciclo_id" :class="['w-full rounded px-2 py-1 border', errors.ciclo_id ? 'border-red-600' : '']">
								<option value="">Seleccionar ciclo...</option>
								<option v-for="c in ciclos" :key="c.id" :value="c.id">{{ c.nombre }}</option>
							</select>
							<ul v-if="errors.ciclo_id && errors.ciclo_id.length" class="text-sm text-red-600 mt-1 list-disc ml-4">
								<li v-for="(m, idx) in errors.ciclo_id" :key="idx">{{ m }}</li>
							</ul>
						</div>
						<div>
							<label class="block text-sm">Nº Grupo</label>
							<input
								v-model="form.numero_grupo"
								type="text"
								inputmode="numeric"
								pattern="\d*"
								autocomplete="off"
								:class="['w-full rounded px-2 py-1 border', errors.numero_grupo ? 'border-red-600' : '']"
								@input="handleNumeroInput"
								@keydown="handleNumeroKeydown"
								@paste.prevent
								@copy.prevent
								@cut.prevent
							/>
							<ul v-if="errors.numero_grupo && errors.numero_grupo.length" class="text-sm text-red-600 mt-1 list-disc ml-4">
								<li v-for="(m, idx) in errors.numero_grupo" :key="idx">{{ m }}</li>
							</ul>
						</div>
						<div>
							<label class="block text-sm">Capacidad máxima</label>
							<input v-model="form.capacidad_maxima" type="number" :class="['w-full rounded px-2 py-1 border', errors.capacidad_maxima ? 'border-red-600' : '']" />
							<ul v-if="errors.capacidad_maxima && errors.capacidad_maxima.length" class="text-sm text-red-600 mt-1 list-disc ml-4">
								<li v-for="(m, idx) in errors.capacidad_maxima" :key="idx">{{ m }}</li>
							</ul>
						</div>
						<div>
							<label class="block text-sm">Estado</label>
							<select v-model="form.estado" class="w-full border rounded px-2 py-1">
								<option value="activo">Activo</option>
								<option value="inactivo">Inactivo</option>
							</select>
						</div>
					</div>

				<!-- Backend/server errors -->
				<div v-if="serverErrorMessage" class="mb-3 p-2 rounded bg-red-50 text-red-800">
					{{ serverErrorMessage }}
				</div>
				<div v-if="serverErrors && serverErrors.length" class="mb-3 p-2 rounded bg-red-50 text-red-800">
					<ul class="list-disc pl-5">
						<li v-for="(msg, idx) in serverErrors" :key="idx">{{ msg }}</li>
					</ul>
				</div>
				<div class="flex justify-end">
					<button type="submit" :disabled="submitting" class="bg-green-600 text-white px-4 py-2 rounded">Guardar</button>
				</div>
			</form>
		</div>
	</div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
import axios from 'axios';
import { authService } from '@/Services/authService';

const API_URL = '/api';
const getAuthHeaders = () => ({ headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } });

/* --- Añadido: variables de estado y color para coincidir con docentes.vue --- */
const isLoading = ref(true);
const colorText = ref('#1F2937');
const isAuthenticated = localStorage.getItem('isAuthenticated');

/* ...existing reactive data... */
const list = ref([]);
const loading = ref(false);
const searchTerm = ref('');
const currentPage = ref(1);
const perPage = ref(5); // ajustado a 5 para coincidir con docentes.vue

const showModal = ref(false);
const isEditMode = ref(false);
const submitting = ref(false);
const form = ref({ nombre: '', numero_grupo: '', capacidad_maxima: '', estudiantes_inscrito: '', materia_id: '', docente_id: '', ciclo_id: '', estado: 'activo' });
const currentId = ref(null);

// Select options
const materias = ref([]);
const docentes = ref([]);
const ciclos = ref([]);
// Validation errors
const errors = ref({});
// Backend / server messages
const serverErrorMessage = ref('');
const serverErrors = ref([]);

/* --- Añadido: variables y formularios para filtros --- */
const selectedOption = ref('view-all');
const filterForm = ref({ materia_id: '', docente_id: '', ciclo_id: '' });

const fetchAll = async () => {
	loading.value = true;
	try {
		const res = await axios.get(`${API_URL}/groups/get/all`, getAuthHeaders());
		list.value = res.data.data || [];
		// populate related names when missing

		await populateRelatedNames(list.value);
	} catch (e) { console.error(e); list.value = []; } finally { loading.value = false; }
};

// Fetch materials, teachers, and cycles for dropdowns
const fetchSelectOptions = async () => {
	try {
		const [materiasRes, docentesRes, ciclosRes] = await Promise.all([
			axios.get(`${API_URL}/subjects/get/all`, getAuthHeaders()),
			axios.get(`${API_URL}/users/get/professors/all`, getAuthHeaders()),
			axios.get(`${API_URL}/academic-terms/get/all`, getAuthHeaders())
		]);

		materias.value = materiasRes.data.data || [];
		docentes.value = docentesRes.data.data || [];
		ciclos.value = ciclosRes.data.data || [];
	} catch (e) {
		console.error('Error fetching select options:', e);
	}
};

/* --- Nuevas funciones para llamadas a endpoints de filtros --- */
const fetchGroupsBySubject = async (id) => {
	loading.value = true;
	try {
		const res = await axios.get(`${API_URL}/groups/get/subject/${id}`, getAuthHeaders());
		const payload = res.data?.data ?? res.data ?? [];
		list.value = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);
		await populateRelatedNames(list.value);
	} catch (e) {
		console.error('fetchGroupsBySubject error', e);
		list.value = [];
	} finally {
		loading.value = false;
	}
};

const fetchGroupsByCycle = async (id) => {
	loading.value = true;
	try {
		const res = await axios.get(`${API_URL}/groups/get/cycle/${id}`, getAuthHeaders());
		const payload = res.data?.data ?? res.data ?? [];
		list.value = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);
		await populateRelatedNames(list.value);
	} catch (e) {
		console.error('fetchGroupsByCycle error', e);
		list.value = [];
	} finally {
		loading.value = false;
	}
};

const fetchGroupsByProfessor = async (id) => {
	loading.value = true;
	try {
		const res = await axios.get(`${API_URL}/groups/get/professor/${id}`, getAuthHeaders());
		const payload = res.data?.data ?? res.data ?? [];
		list.value = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);
		await populateRelatedNames(list.value);
	} catch (e) {
		console.error('fetchGroupsByProfessor error', e);
		list.value = [];
	} finally {
		loading.value = false;
	}
};

const fetchGroupsByStatus = async (estado) => {
	loading.value = true;
	try {
		const res = await axios.get(`${API_URL}/groups/get/status/${estado}`, getAuthHeaders());
		const payload = res.data?.data ?? res.data ?? [];
		list.value = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);
		await populateRelatedNames(list.value);
	} catch (e) {
		console.error('fetchGroupsByStatus error', e);
		list.value = [];
	} finally {
		loading.value = false;
	}
};

// Validate form fields and fill `errors`. Returns true when valid.
const validateForm = () => {
	errors.value = {};
	let valid = true;

	// Materia
	if (!form.value.materia_id) {
		errors.value.materia_id = 'Seleccione una materia.';
		valid = false;
	}

	// Docente
	if (!form.value.docente_id) {
		errors.value.docente_id = 'Seleccione un docente.';
		valid = false;
	}

	// Ciclo
	if (!form.value.ciclo_id) {
		errors.value.ciclo_id = 'Seleccione un ciclo.';
		valid = false;
	}

	// Nº Grupo
	if (!form.value.numero_grupo || String(form.value.numero_grupo).trim() === '') {
		errors.value.numero_grupo = 'Ingrese el número de grupo.';
		valid = false;
	}

	// Capacidad máxima (debe ser entero positivo)
	const cap = form.value.capacidad_maxima;
	if (cap === '' || cap === null || typeof cap === 'undefined') {
		errors.value.capacidad_maxima = 'Ingrese la capacidad máxima.';
		valid = false;
	} else {
		const capNum = Number(cap);
		if (!Number.isInteger(capNum) || capNum <= 0) {
			errors.value.capacidad_maxima = 'La capacidad debe ser un número entero mayor a 0.';
			valid = false;
		} else {
			// si hay estudiantes_inscrito validar que no supere la capacidad
			const inscritos = form.value.estudiantes_inscrito;
			if (inscritos !== '' && typeof inscritos !== 'undefined' && inscritos !== null) {
				const insNum = Number(inscritos);
				if (!Number.isInteger(insNum) || insNum < 0) {
					errors.value.estudiantes_inscrito = 'Estudiantes inscritos debe ser un número entero >= 0.';
					valid = false;
				} else if (insNum > capNum) {
					errors.value.estudiantes_inscrito = 'Estudiantes inscritos no puede ser mayor a la capacidad.';
					valid = false;
				}
			}
		}
	}

	return valid;
};

// Handle input for numero_grupo: keep only digits
const handleNumeroInput = (e) => {
	const cleaned = String(e.target.value || '').replace(/\D+/g, '');
	// update both the DOM value and the reactive model
	e.target.value = cleaned;
	form.value.numero_grupo = cleaned;
};

// Prevent non-digit key presses and block paste/copy via keyboard shortcuts
const handleNumeroKeydown = (e) => {
	// Block Ctrl/Cmd + V/C/X (paste/copy/cut)
	if ((e.ctrlKey || e.metaKey) && ['v', 'V', 'c', 'C', 'x', 'X'].includes(e.key)) {
		e.preventDefault();
		return;
	}

	// Allow navigation/editing keys
	const allowed = ['Backspace', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Delete', 'Home', 'End'];
	if (allowed.includes(e.key)) return;

	// Allow digits (top row and numpad)
	if (/^[0-9]$/.test(e.key)) return;

	// Otherwise prevent
	e.preventDefault();
};

// Fetch and attach related names (materia, docente, ciclo) efficiently
const populateRelatedNames = async (items) => {
	try {
		// collect unique ids
		const materias = new Set();
		const docentes = new Set();
		const ciclos = new Set();

		items.forEach(i => {
			// --- Materia: intentar obtener nombre desde objetos anidados antes de usar materia_id ---
			if (!i.materia_nombre) {
				if (i.materia && (i.materia.nombre || i.materia.name)) {
					i.materia_nombre = i.materia.nombre ?? i.materia.name;
					if (!i.materia_id && i.materia.id) materias.add(i.materia.id);
				} else if (i.subject && (i.subject.nombre || i.subject.name)) {
					i.materia_nombre = i.subject.nombre ?? i.subject.name;
					if (!i.materia_id && i.subject.id) materias.add(i.subject.id);
				} else if (i.materia_id) {
					materias.add(i.materia_id);
				} else if (i.subject_id) {
					materias.add(i.subject_id);
				}
			}

			// --- Docente: intentar desde objetos anidados (docente / user) ---
			if (!i.docente_nombre) {
				if (i.docente && (i.docente.nombre_completo || i.docente.name || i.docente.nombre)) {
					i.docente_nombre = i.docente.nombre_completo ?? i.docente.name ?? i.docente.nombre;
					if (!i.docente_id && i.docente.id) docentes.add(i.docente.id);
				} else if (i.user && (i.user.nombre_completo || i.user.name || i.user.nombre)) {
					i.docente_nombre = i.user.nombre_completo ?? i.user.name ?? i.user.nombre;
					if (!i.docente_id && i.user.id) docentes.add(i.user.id);
				} else if (i.docente_id) {
					docentes.add(i.docente_id);
				} else if (i.user_id) {
					docentes.add(i.user_id);
				}
			}

			// --- Ciclo: intentar desde objeto anidado ciclo ---
			if (!i.ciclo_nombre) {
				if (i.ciclo && (i.ciclo.nombre || i.ciclo.name)) {
					i.ciclo_nombre = i.ciclo.nombre ?? i.ciclo.name;
					if (!i.ciclo_id && i.ciclo.id) ciclos.add(i.ciclo.id);
				} else if (i.ciclo_id) {
					ciclos.add(i.ciclo_id);
				}
			}
		});

		// helper to fetch multiple and return map id->name
        const fetchMap = async (ids, urlTemplate, nameField = 'nombre') => {
	        const map = {};
	        if (ids.size === 0) return map;

	        const promises = Array.from(ids).map(async id => {
	            try {
	                const r = await axios.get(`${API_URL}/${urlTemplate.replace('{id}', id)}`, getAuthHeaders());
	                let d = r.data?.data || r.data || null;

	                // Si d es un objeto con claves numéricas dinámicas, tomar primer valor
	                if (d && typeof d === 'object' && !d[nameField] && !d.nombre && !d.nombre_completo) {
	                    const values = Object.values(d);
	                    if (values.length > 0) d = values[0];
	                }

	                if (d) {
	                    map[id] = d[nameField] ?? d.nombre ?? d.nombre_completo ?? d.name ?? `${d.id}`;
	                }
	            } catch (e) {
	                console.warn('failed fetch', urlTemplate, id, e?.message);
	            }
	        });

	        await Promise.all(promises);
	        return map;
        };

		const materiasMap = await fetchMap(materias, 'subjects/get/{id}', 'nombre');
		const docentesMap = await fetchMap(docentes, 'users/get/{id}', 'nombre_completo');
		const ciclosMap = await fetchMap(ciclos, 'academic-terms/get/{id}', 'nombre');

		// attach names back to items only si no estaban ya resueltas
		items.forEach(i => {
			if ((!i.materia_nombre || i.materia_nombre === '') && i.materia_id && materiasMap[i.materia_id]) {
				i.materia_nombre = materiasMap[i.materia_id];
			}
			if ((!i.docente_nombre || i.docente_nombre === '') && i.docente_id && docentesMap[i.docente_id]) {
				i.docente_nombre = docentesMap[i.docente_id];
			}
			if ((!i.ciclo_nombre || i.ciclo_nombre === '') && i.ciclo_id && ciclosMap[i.ciclo_id]) {
				i.ciclo_nombre = ciclosMap[i.ciclo_id];
			}
		});
	} catch (e) {
		console.error('populateRelatedNames error', e);
	}
};

const filtered = computed(() => {
	if (!searchTerm.value) return list.value;
	const term = searchTerm.value.toLowerCase();
	return list.value.filter(i => {
		const materia = (i.materia_nombre || '').toString().toLowerCase();
		const docente = (i.docente_nombre || '').toString().toLowerCase();
		const ciclo = (i.ciclo_nombre || '').toString().toLowerCase();
		const numero = (i.numero_grupo || '').toString().toLowerCase();
		const nombre = (i.nombre || '').toString().toLowerCase();
		return materia.includes(term) || docente.includes(term) || ciclo.includes(term) || numero.includes(term) || nombre.includes(term);
	});
});

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage.value)));
const paginated = computed(() => {
	const start = (currentPage.value - 1) * perPage.value;
	return filtered.value.slice(start, start + perPage.value);
});

watch(filtered, () => { currentPage.value = 1; });

const openCreateModal = () => {
	isEditMode.value = false;
	form.value = { nombre: '', numero_grupo: '', capacidad_maxima: '', estudiantes_inscrito: '', materia_id: '', docente_id: '', ciclo_id: '', estado: 'activo' };
	currentId.value = null;
	errors.value = {};
	serverErrorMessage.value = '';
	serverErrors.value = [];
	showModal.value = true;
};
const openEditModal = (g) => {
	isEditMode.value = true;
	currentId.value = g.id;
	form.value = { nombre: g.nombre || '', numero_grupo: g.numero_grupo || '', capacidad_maxima: g.capacidad_maxima ?? '', estudiantes_inscrito: g.estudiantes_inscrito ?? '', materia_id: g.materia_id || '', docente_id: g.docente_id || '', ciclo_id: g.ciclo_id || '', estado: g.estado || 'activo' };
	errors.value = {};
	serverErrorMessage.value = '';
	serverErrors.value = [];
	showModal.value = true;
};
const closeModal = () => { showModal.value = false; };

const submitForm = async () => {
	submitting.value = true;
	// limpiar errores previos
	errors.value = {};
	serverErrorMessage.value = '';
	serverErrors.value = [];

	try {
		// client-side validation
		if (!validateForm()) {
			submitting.value = false;
			return;
		}

		if (isEditMode.value) {
			await axios.patch(`${API_URL}/groups/edit/${currentId.value}`, form.value, getAuthHeaders());
		} else {
			await axios.post(`${API_URL}/groups/new`, form.value, getAuthHeaders());
		}
		await fetchAll();
		closeModal();
	} catch (e) {
		const data = e?.response?.data ?? null;

		// campos esperados para grupos
		const known = new Set(['materia_id','docente_id','ciclo_id','numero_grupo','capacidad_maxima','estudiantes_inscrito','estado','nombre']);

		// si vienen errores estilo Laravel (object)
		if (data && data.errors && typeof data.errors === 'object') {
			Object.keys(data.errors).forEach(k => {
				const raw = data.errors[k];
				const msgArr = Array.isArray(raw) ? raw : [raw];
				if (known.has(k)) {
					// mantener formato de array porque la plantilla itera arrays
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
	if (!confirm('¿Eliminar grupo?')) return;
	try { await axios.delete(`${API_URL}/groups/delete/${id}`, getAuthHeaders()); await fetchAll(); } catch (e) { console.error(e); }
};

const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };
const goToPage = (p) => { if (p>=1 && p<=totalPages.value) currentPage.value = p; };

const handleSelectFilter = async () => {
	if (!selectedOption.value || selectedOption.value === 'view-all') {
		await fetchAll();
		return;
	}
	if (selectedOption.value === 'view-actives') {
		await fetchGroupsByStatus('activo');
		return;
	}
	if (selectedOption.value === 'view-inactives') {
		await fetchGroupsByStatus('inactivo');
		return;
	}
	// Para los filtros que requieren selección adicional (materia/ciclo/docente),
	// se espera a que el usuario elija en el select condicional y llame al handler correspondiente.
};

const handleFilterBySubject = async () => {
	if (!filterForm.value.materia_id) {
		await fetchAll();
		return;
	}
	await fetchGroupsBySubject(filterForm.value.materia_id);
};

const handleFilterByCycle = async () => {
	if (!filterForm.value.ciclo_id) {
		await fetchAll();
		return;
	}
	await fetchGroupsByCycle(filterForm.value.ciclo_id);
};

const handleFilterByProfessor = async () => {
	if (!filterForm.value.docente_id) {
		await fetchAll();
		return;
	}
	await fetchGroupsByProfessor(filterForm.value.docente_id);
};

onMounted(async () => {
	await authService.verifyToken(localStorage.getItem('token'));
	await fetchSelectOptions();
	await fetchAll();
	// indicar que verificación inicial terminó (ocultar spinner)
	isLoading.value = false;
});
</script>

<style scoped>
/* Rely on Tailwind; se han adap */
</style>
