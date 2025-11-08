<template>
	<Head title="Solicitudes de Inscripción" />

	<MainLayoutDashboard>
		<div class="p-4 md:p-6">
			<div class="mb-6 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
				<h1 class="text-2xl font-semibold">Solicitudes de Inscripción</h1>
				<div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2">
					<input v-model="searchTerm" type="text" placeholder="Buscar..." class="border rounded px-3 py-1" />
					<button @click="openCreateModal" class="text-white px-4 py-2 rounded" :style="{background: '#BD3838'}">Nueva</button>
				</div>
			</div>

			<div class="bg-white rounded-lg shadow p-4">
                <div class="overflow-x-auto">
                    <div class="p-4 min-w-[640px]">
                        <table class="w-full table-auto">
                            <thead>
                                <tr class="text-left border-b">
                                    <th class="py-2 px-2">Estudiante</th>
                                    <th class="py-2 px-2">Grupo</th>
                                    <th class="py-2 px-2">Tipo</th>
                                    <th class="py-2 px-2">Estado</th>
                                    <th class="py-2 px-2">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="s in paginated" :key="s.id" class="border-b">
                                    <td class="py-2 px-2">{{ s.estudiante_nombre || s.estudiante_id }}</td>
                                    <td class="py-2 px-2">{{ s.grupo_nombre || s.grupo_id }}</td>
                                    <td class="py-2 px-2">{{ s.tipo_solicitud }}</td>
                                    <td class="py-2 px-2">{{ s.estado }}</td>
                                    <td class="py-2 px-2">
                                        <button @click="openEditModal(s)" class="text-yellow-600 mr-2">Editar</button>
                                        <button @click="acceptRequest(s.id)" class="text-green-600 mr-2">Aceptar</button>
                                        <button @click="rejectRequest(s.id)" class="text-red-600">Rechazar</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

				<div class="flex flex-col sm:flex-row justify-between items-center gap-4 p-4 border-t">
					<div>Mostrando {{ paginated.length }} de {{ filtered.length }}</div>
					<div class="space-x-2">
						<button @click="prevPage" :disabled="currentPage===1" class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed">Anterior</button>
						<button v-for="p in totalPages" :key="p" @click="goToPage(p)"
                                :class="{
                                    'font-bold text-white': p===currentPage,
                                    'border': p!==currentPage
                                }"
							class="px-3 py-1 rounded min-w-[40px]" :style="{background: '#BD3838'}">{{ p }}</button>
						<button @click="nextPage" :disabled="currentPage===totalPages" class="px-3 py-1 border rounded disabled:opacity-50 disabled:cursor-not-allowed">Siguiente</button>
					</div>
				</div>
			</div>
		</div>
	</MainLayoutDashboard>

	<div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
		<div class="bg-white rounded-lg shadow-xl w-full max-w-2xl p-6">
			<div class="flex justify-between items-center mb-4">
				<h2 class="text-lg font-semibold">{{ isEditMode ? 'Editar solicitud' : 'Nueva solicitud' }}</h2>
				<button @click="closeModal">Cerrar</button>
			</div>

			<form @submit.prevent="submitForm" class="space-y-3">
				<div class="grid grid-cols-2 gap-3">
					<div>
						<label class="block text-sm">Estudiante ID</label>
						<input v-model="form.estudiante_id" class="w-full border rounded px-2 py-1" />
					</div>
					<div>
						<label class="block text-sm">Grupo ID</label>
						<input v-model="form.grupo_id" class="w-full border rounded px-2 py-1" />
					</div>
					<div>
						<label class="block text-sm">Tipo</label>
						<input v-model="form.tipo_solicitud" class="w-full border rounded px-2 py-1" />
					</div>
					<div>
						<label class="block text-sm">Motivo</label>
						<input v-model="form.motivo" class="w-full border rounded px-2 py-1" />
					</div>
					<div>
						<label class="block text-sm">Estado</label>
						<select v-model="form.estado" class="w-full border rounded px-2 py-1">
							<option value="pendiente">Pendiente</option>
							<option value="aceptada">Aceptada</option>
							<option value="rechazada">Rechazada</option>
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
const form = ref({ estudiante_id: '', grupo_id: '', tipo_solicitud: '', motivo: '', estado: 'pendiente' });
const currentId = ref(null);

const fetchAll = async () => {
	loading.value = true;
	try {
		const res = await axios.get(`${API_URL}/enrollment-requests/get/all`, getAuthHeaders());
		list.value = res.data.data || [];
	} catch (e) { console.error(e); list.value = []; } finally { loading.value = false; }
};

const filtered = computed(() => {
	if (!searchTerm.value) return list.value;
	return list.value.filter(i => (i.estudiante_nombre || '').toLowerCase().includes(searchTerm.value.toLowerCase()) || (i.tipo_solicitud || '').toLowerCase().includes(searchTerm.value.toLowerCase()));
});

const totalPages = computed(() => Math.max(1, Math.ceil(filtered.value.length / perPage.value)));
const paginated = computed(() => {
	const start = (currentPage.value - 1) * perPage.value;
	return filtered.value.slice(start, start + perPage.value);
});

watch(filtered, () => { currentPage.value = 1; });

const openCreateModal = () => { isEditMode.value = false; form.value = { estudiante_id: '', grupo_id: '', tipo_solicitud: '', motivo: '', estado: 'pendiente' }; currentId.value = null; showModal.value = true; };
const openEditModal = (s) => { isEditMode.value = true; currentId.value = s.id; form.value = { estudiante_id: s.estudiante_id, grupo_id: s.grupo_id, tipo_solicitud: s.tipo_solicitud, motivo: s.motivo || '', estado: s.estado || 'pendiente' }; showModal.value = true; };
const closeModal = () => { showModal.value = false; };

const submitForm = async () => {
	submitting.value = true;
	try {
		if (isEditMode.value) {
			await axios.patch(`${API_URL}/enrollment-requests/edit/${currentId.value}`, form.value, getAuthHeaders());
		} else {
			await axios.post(`${API_URL}/enrollment-requests/new`, form.value, getAuthHeaders());
		}
		await fetchAll();
		closeModal();
	} catch (e) { console.error(e); }
	finally { submitting.value = false; }
};

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

const prevPage = () => { if (currentPage.value > 1) currentPage.value--; };
const nextPage = () => { if (currentPage.value < totalPages.value) currentPage.value++; };
const goToPage = (p) => { if (p>=1 && p<=totalPages.value) currentPage.value = p; };

onMounted(async () => {
	await authService.verifyToken(localStorage.getItem('token'));
	await fetchAll();
});
</script>

<style scoped>
/* Minimal, Tailwind present */
</style>

