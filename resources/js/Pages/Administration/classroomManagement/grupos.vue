<template>
	<Head title="Grupos" />

	<MainLayoutDashboard>
		<div class="p-6">
			<div class="mb-6 flex justify-between items-center">
				<h1 class="text-2xl font-semibold">Grupos</h1>
				<div class="flex items-center gap-2">
					<input v-model="searchTerm" type="text" placeholder="Buscar..." class="border rounded px-3 py-1" />
					<button @click="openCreateModal" class="bg-blue-600 text-white px-4 py-2 rounded">Nuevo</button>
				</div>
			</div>

			<div class="bg-white rounded-lg shadow p-4">
				<table class="w-full table-auto">
					<thead>
						<tr class="text-left border-b">
							<th class="py-2">Materia</th>
							<th class="py-2">Nº Grupo</th>
							<th class="py-2">Docente</th>
							<th class="py-2">Capacidad</th>
							<th class="py-2">Inscritos</th>
							<th class="py-2">Ciclo</th>
							<th class="py-2">Estado</th>
							<th class="py-2">Acciones</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="g in paginated" :key="g.id" class="border-b">
							<td class="py-2">{{ g.materia_nombre || ('#' + g.materia_id) }}</td>
							<td class="py-2">{{ g.numero_grupo || '-' }}</td>
							<td class="py-2">{{ g.docente_nombre || ('#' + g.docente_id) }}</td>
							<td class="py-2">{{ g.capacidad_maxima ?? '-' }}</td>
							<td class="py-2">{{ g.estudiantes_inscrito ?? '-' }}</td>
							<td class="py-2">{{ g.ciclo_nombre || ('#' + g.ciclo_id) }}</td>
							<td class="py-2">{{ g.estado }}</td>
							<td class="py-2">
								<button @click="openEditModal(g)" class="text-yellow-600 mr-2">Editar</button>
								<button @click="deleteItem(g.id)" class="text-red-600">Eliminar</button>
							</td>
						</tr>
					</tbody>
				</table>

				<div class="flex justify-between items-center mt-4">
					<div>Mostrando {{ paginated.length }} de {{ filtered.length }}</div>
					<div class="space-x-2">
						<button @click="prevPage" :disabled="currentPage===1">Anterior</button>
						<button v-for="p in totalPages" :key="p" @click="goToPage(p)" :class="{ 'font-bold': p===currentPage }">{{ p }}</button>
						<button @click="nextPage" :disabled="currentPage===totalPages">Siguiente</button>
					</div>
				</div>
			</div>
		</div>
	</MainLayoutDashboard>

	<div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
		<div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6">
			<div class="flex justify-between items-center mb-4">
				<h2 class="text-lg font-semibold">{{ isEditMode ? 'Editar grupo' : 'Nuevo grupo' }}</h2>
				<button @click="closeModal">Cerrar</button>
			</div>

				<form @submit.prevent="submitForm" class="space-y-3">
					<div class="grid grid-cols-2 gap-3">
						<div>
							<label class="block text-sm">Nº Grupo</label>
							<input v-model="form.numero_grupo" class="w-full border rounded px-2 py-1" />
						</div>
						<div>
							<label class="block text-sm">Capacidad máxima</label>
							<input v-model="form.capacidad_maxima" type="number" class="w-full border rounded px-2 py-1" />
						</div>
						<div>
							<label class="block text-sm">Estudiantes inscritos</label>
							<input v-model="form.estudiantes_inscrito" type="number" class="w-full border rounded px-2 py-1" />
						</div>
						<div>
							<label class="block text-sm">Materia ID</label>
							<input v-model="form.materia_id" class="w-full border rounded px-2 py-1" />
						</div>
						<div>
							<label class="block text-sm">Docente ID</label>
							<input v-model="form.docente_id" class="w-full border rounded px-2 py-1" />
						</div>
						<div>
							<label class="block text-sm">Ciclo ID</label>
							<input v-model="form.ciclo_id" class="w-full border rounded px-2 py-1" />
						</div>
						<div>
							<label class="block text-sm">Estado</label>
							<select v-model="form.estado" class="w-full border rounded px-2 py-1">
								<option value="activo">Activo</option>
								<option value="inactivo">Inactivo</option>
							</select>
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
const form = ref({ nombre: '', numero_grupo: '', capacidad_maxima: '', estudiantes_inscrito: '', materia_id: '', docente_id: '', ciclo_id: '', estado: 'activo' });
const currentId = ref(null);

const fetchAll = async () => {
	loading.value = true;
	try {
		const res = await axios.get(`${API_URL}/groups/get/all`, getAuthHeaders());
		list.value = res.data.data || [];
		// populate related names when missing
        
		await populateRelatedNames(list.value);
	} catch (e) { console.error(e); list.value = []; } finally { loading.value = false; }
};

// Fetch and attach related names (materia, docente, ciclo) efficiently
const populateRelatedNames = async (items) => {
	try {
		// collect unique ids
		const materias = new Set();
		const docentes = new Set();
		const ciclos = new Set();

		items.forEach(i => {
			if (!i.materia_nombre && i.materia_id) materias.add(i.materia_id);
			if (!i.docente_nombre && i.docente_id) docentes.add(i.docente_id)
            else{
                log(i.docente_nombre);
                console.log(i.docente_nombre);
                
            };
			if (!i.ciclo_nombre && i.ciclo_id) ciclos.add(i.ciclo_id);
		});


		// helper to fetch multiple and return map id->name
        const fetchMap = async (ids, urlTemplate, nameField = 'nombre') => {
        const map = {};
        if (ids.size === 0) return map;

        const promises = Array.from(ids).map(async id => {
            try {
            const r = await axios.get(`${API_URL}/${urlTemplate.replace('{id}', id)}`, getAuthHeaders());
            let d = r.data?.data || r.data || null;

            // 👇 Si d es un objeto con claves numéricas dinámicas (como "2": {...})
            if (d && typeof d === 'object' && !d[nameField] && !d.nombre && !d.nombre_completo) {
                const values = Object.values(d);
                if (values.length > 0) d = values[0]; // tomar el primer objeto
            }

            if (d)
                map[id] = d[nameField] ?? d.nombre ?? d.nombre_completo ?? `${d.id}`;
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

        
		// attach names back to items
		items.forEach(i => {
			if (!i.materia_nombre && i.materia_id && materiasMap[i.materia_id]) i.materia_nombre = materiasMap[i.materia_id];
			if (!i.docente_nombre && i.docente_id && docentesMap[i.docente_id]) i.docente_nombre = docentesMap[i.docente_id];
			if (!i.ciclo_nombre && i.ciclo_id && ciclosMap[i.ciclo_id]) i.ciclo_nombre = ciclosMap[i.ciclo_id];
		});
	} catch (e) {
		console.error('populateRelatedNames error', e);
	}
};

const filtered = computed(() => {
	if (!searchTerm.value) return list.value;
	return list.value.filter(i => (i.nombre || '').toLowerCase().includes(searchTerm.value.toLowerCase()));
});

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage.value)));
const paginated = computed(() => {
	const start = (currentPage.value - 1) * perPage.value;
	return filtered.value.slice(start, start + perPage.value);
});

watch(filtered, () => { currentPage.value = 1; });

const openCreateModal = () => { isEditMode.value = false; form.value = { nombre: '', materia_id: '', docente_id: '', ciclo_id: '', estado: 'activo' }; currentId.value = null; showModal.value = true; };
const openEditModal = (g) => { isEditMode.value = true; currentId.value = g.id; form.value = { nombre: g.nombre, materia_id: g.materia_id, docente_id: g.docente_id, ciclo_id: g.ciclo_id, estado: g.estado || 'activo' }; showModal.value = true; };
const closeModal = () => { showModal.value = false; };

const submitForm = async () => {
	submitting.value = true;
	try {
		if (isEditMode.value) {
			await axios.patch(`${API_URL}/groups/edit/${currentId.value}`, form.value, getAuthHeaders());
		} else {
			await axios.post(`${API_URL}/groups/new`, form.value, getAuthHeaders());
		}
		await fetchAll();
		closeModal();
	} catch (e) { console.error(e); }
	finally { submitting.value = false; }
};

const deleteItem = async (id) => {
	if (!confirm('¿Eliminar grupo?')) return;
	try { await axios.delete(`${API_URL}/groups/delete/${id}`, getAuthHeaders()); await fetchAll(); } catch (e) { console.error(e); }
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
/* Rely on Tailwind */
</style>

