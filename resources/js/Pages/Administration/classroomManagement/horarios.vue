<template>
	<Head title="Horarios" />

	<MainLayoutDashboard>
		<div class="p-4 md:p-6">
			<div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
				<h1 class="text-2xl font-semibold">Horarios</h1>
				<div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
					<input v-model="searchTerm" type="text" placeholder="Buscar..." class="border rounded px-3 py-1" />
					<button @click="openCreateModal" class="text-white px-4 py-2 rounded" :style="{background: '#BD3838'}">Nuevo</button>
				</div>
			</div>

			<div class="bg-white rounded-lg shadow">
				<!-- Contenedor con scroll horizontal para móviles -->
				<div class="overflow-x-auto">
					<div class="p-4 min-w-[640px]">
						<table class="w-full table-auto">
							<thead>
								<tr class="text-left border-b">
									<th class="py-2 px-2">Grupo</th>
									<th class="py-2 px-2">Aula</th>
									<th class="py-2 px-2">Día</th>
									<th class="py-2 px-2">Inicio</th>
									<th class="py-2 px-2">Fin</th>
									<th class="py-2 px-2">Acciones</th>
								</tr>
							</thead>
							<tbody>
								<tr v-for="h in paginated" :key="h.id" class="border-b">
									<td class="py-2 px-2">{{ h.numero_grupo ?? h.grupo_nombre ?? ('#' + h.grupo_id) }}</td>
									<td class="py-2 px-2">{{ h.aula_nombre || h.nombre_aula || ('#' + h.aula_id) }}</td>
									<td class="py-2 px-2">{{ h.dia_semana }}</td>
									<td class="py-2 px-2">{{ h.hora_inicio }}</td>
									<td class="py-2 px-2">{{ h.hora_fin }}</td>
									<td class="py-2 px-2">
										<div class="flex gap-2 whitespace-nowrap">
											<button @click="openEditModal(h)" class="text-yellow-600 hover:underline">
												Editar
											</button>
											<button @click="deleteItem(h.id)" class="text-red-600 hover:underline">
												Eliminar
											</button>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

				<!-- Paginación responsive -->
				<div class="flex flex-col sm:flex-row justify-between items-center gap-4 p-4 border-t">
					<div class="text-sm text-gray-600">
						Mostrando {{ paginated.length }} de {{ filtered.length }}
					</div>
					<div class="flex flex-wrap justify-center gap-2">
						<button
							@click="prevPage"
							:disabled="currentPage===1"
							class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed"
						>
							Anterior
						</button>
						<button
							v-for="p in totalPages"
							:key="p"
							@click="goToPage(p)"
                            :style="{background: '#BD3838'}"
							:class="{
								'font-bold text-white': p===currentPage,
								'border': p!==currentPage
							}"
							class="px-3 py-1 rounded min-w-[40px]"
						>
							{{ p }}
						</button>
						<button
							@click="nextPage"
							:disabled="currentPage===totalPages"
							class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed"
						>
							Siguiente
						</button>
					</div>
				</div>
            </div>
		</div>
	</MainLayoutDashboard>

	<!-- Modal -->
	<div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
		<div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6">
			<div class="flex justify-between items-center mb-4">
				<h2 class="text-lg font-semibold">{{ isEditMode ? 'Editar horario' : 'Nuevo horario' }}</h2>
				<button @click="closeModal">Cerrar</button>
			</div>

			<form @submit.prevent="submitForm" class="space-y-3">
				<div class="grid grid-cols-2 gap-3">
					<div>
						<label class="block text-sm">Grupo</label>
						<input v-model="form.grupo_id" class="w-full border rounded px-2 py-1" />
					</div>
					<div>
						<label class="block text-sm">Aula</label>
						<input v-model="form.aula_id" class="w-full border rounded px-2 py-1" />
					</div>
					<div>
						<label class="block text-sm">Día</label>
						<select v-model="form.dia_semana" class="w-full border rounded px-2 py-1">
							<option value="">Seleccione</option>
							<option>Lunes</option>
							<option>Martes</option>
							<option>Miercoles</option>
							<option>Jueves</option>
							<option>Viernes</option>
							<option>Sabado</option>
							<option>Domingo</option>
						</select>
					</div>
					<div>
						<label class="block text-sm">Hora Inicio</label>
						<input type="time" v-model="form.hora_inicio" class="w-full border rounded px-2 py-1" />
					</div>
					<div>
						<label class="block text-sm">Hora Fin</label>
						<input type="time" v-model="form.hora_fin" class="w-full border rounded px-2 py-1" />
					</div>
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

const API_URL = 'http://127.0.0.1:8000/api';
const getAuthHeaders = () => ({ headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } });

const list = ref([]);
const loading = ref(false);
const searchTerm = ref('');
const currentPage = ref(1);
const perPage = ref(8);

const showModal = ref(false);
const isEditMode = ref(false);
const submitting = ref(false);
const form = ref({ grupo_id: '', aula_id: '', dia_semana: '', hora_inicio: '', hora_fin: '' });
const currentId = ref(null);

const fetchAll = async () => {
	loading.value = true;
	try {
		const res = await axios.get(`${API_URL}/schedules/get/all`, getAuthHeaders());
		list.value = res.data.data || [];
		// populate group numbers and classroom names when missing
		await populateRelatedNames(list.value);
	} catch (e) {
		console.error(e);
		list.value = [];
	} finally { loading.value = false; }
};

// Fetch and attach related data: grupo.numero_grupo and aula.nombre
const populateRelatedNames = async (items) => {
	try {
		const groupIds = new Set();
		const aulaIds = new Set();

		items.forEach(i => {
			if (!i.numero_grupo && i.grupo_id) groupIds.add(i.grupo_id);
			if (!i.aula_nombre && i.aula_id) aulaIds.add(i.aula_id);
		});

		const fetchMap = async (ids, urlTemplate, preferField) => {
			const map = {};
			if (ids.size === 0) return map;
			const promises = Array.from(ids).map(async id => {
				try {
					const r = await axios.get(`${API_URL}/${urlTemplate.replace('{id}', id)}`, getAuthHeaders());
					const d = r.data?.data || r.data || null;
					if (d) {
						// prefer a given field, fallback to common ones
						map[id] = d[preferField] ?? d.numero_grupo ?? d.nombre ?? d.nombre_aula ?? d.nombre_completo ?? `${d.id}`;
					}
				} catch (e) {
					console.warn('failed fetch', urlTemplate, id, e?.message);
				}
			});
			await Promise.all(promises);
			return map;
		};

		const groupsMap = await fetchMap(groupIds, 'groups/get/{id}', 'numero_grupo');
		const aulasMap = await fetchMap(aulaIds, 'classrooms/get/{id}', 'nombre');

		items.forEach(i => {
			if (!i.numero_grupo && i.grupo_id && groupsMap[i.grupo_id]) i.numero_grupo = groupsMap[i.grupo_id];
			if (!i.aula_nombre && i.aula_id && aulasMap[i.aula_id]) i.aula_nombre = aulasMap[i.aula_id];
		});
	} catch (e) {
		console.error('populateRelatedNames error', e);
	}
};

const filtered = computed(() => {
	if (!searchTerm.value) return list.value;
	return list.value.filter(i => (i.grupo_nombre || '').toString().toLowerCase().includes(searchTerm.value.toLowerCase()) || (i.dia_semana || '').toLowerCase().includes(searchTerm.value.toLowerCase()));
});

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage.value)));
const paginated = computed(() => {
	const start = (currentPage.value - 1) * perPage.value;
	return filtered.value.slice(start, start + perPage.value);
});

watch(filtered, () => { currentPage.value = 1; });

const openCreateModal = () => { isEditMode.value = false; form.value = { grupo_id: '', aula_id: '', dia_semana: '', hora_inicio: '', hora_fin: '' }; currentId.value = null; showModal.value = true; };
const openEditModal = (h) => { isEditMode.value = true; currentId.value = h.id; form.value = { grupo_id: h.grupo_id, aula_id: h.aula_id, dia_semana: h.dia_semana, hora_inicio: h.hora_inicio, hora_fin: h.hora_fin }; showModal.value = true; };
const closeModal = () => { showModal.value = false; };

const submitForm = async () => {
	submitting.value = true;
	try {
		if (isEditMode.value) {
			await axios.patch(`${API_URL}/schedules/edit/${currentId.value}`, form.value, getAuthHeaders());
		} else {
			await axios.post(`${API_URL}/schedules/new`, form.value, getAuthHeaders());
		}
		await fetchAll();
		closeModal();
	} catch (e) { console.error(e); }
	finally { submitting.value = false; }
};

const deleteItem = async (id) => {
	if (!confirm('¿Eliminar horario?')) return;
	try { await axios.delete(`${API_URL}/schedules/delete/${id}`, getAuthHeaders()); await fetchAll(); } catch (e) { console.error(e); }
};

const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };
const goToPage = (p) => { if (p>=1 && p<=totalPages.value) currentPage.value = p; };

onMounted(async () => {
	await authService.verifyToken(localStorage.getItem('token'));
	await fetchAll();
});
</script>

<style scoped>
/* Minimal styles, rely on Tailwind in project */
</style>

