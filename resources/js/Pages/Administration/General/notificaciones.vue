<template>
	<Head title="Notificaciones" />

	<MainLayoutDashboard>
		<div class="p-4 md:p-6">
			<div class="mb-6">
				<h1 class="text-2xl font-bold text-gray-900 mb-1">Notificaciones</h1>
				<p class="text-gray-600 text-sm">Vista provisional: revisa y marca notificaciones como leídas</p>
			</div>

			<div class="bg-white rounded-lg shadow p-4 sm:p-6">
				<div v-if="loading" class="text-center py-8">
					<div class="animate-spin rounded-full h-10 w-10 border-b-2 border-gray-900 mx-auto"></div>
					<p class="mt-3 text-gray-600">Cargando notificaciones...</p>
				</div>

				<div v-else>
					<div v-if="!notifications.length" class="p-6 text-center text-gray-600">
						No hay notificaciones.
					</div>

					<div v-else class="space-y-3">
						<div v-for="(n, idx) in notifications" :key="n.id ?? idx"
							class="p-4 border rounded hover:shadow transition flex justify-between items-start gap-4">
							<div class="flex-1">
								<div class="flex items-center justify-between">
									<div>
										<div class="font-medium text-gray-800">{{ n.titulo ?? n.title ?? 'Sin título' }}</div>
										<div class="text-xs text-gray-500">{{ formatFecha(n.created_at) }}</div>
									</div>
									<span
										class="text-xs px-2 py-1 rounded-full font-semibold"
										:class="n.estado === 'leida' || n.read ? 'bg-gray-100 text-gray-700' : 'bg-red-100 text-red-800'">
										{{ (n.estado === 'leida' || n.read) ? 'Leída' : 'No leída' }}
									</span>
								</div>

								<p class="text-sm text-gray-700 mt-2">
									{{ n.mensaje ?? n.body ?? '—' }}
								</p>

								<!-- Detalle expandible provisional -->
								<div v-if="expanded === (n.id ?? idx)" class="mt-2 text-sm text-gray-600 bg-gray-50 p-2 rounded">
									<pre class="whitespace-pre-wrap text-xs">{{ n.datos_adicionales ? JSON.stringify(n.datos_adicionales, null, 2) : '' }}</pre>
								</div>
							</div>

							<div class="flex flex-col items-end gap-2">
								<button @click="toggleExpand(n, idx)"
									class="text-sm text-gray-600 hover:text-gray-900 px-3 py-1 border rounded">
									{{ expanded === (n.id ?? idx) ? 'Ocultar' : 'Ver' }}
								</button>

								<button
									v-if="!(n.estado === 'leida' || n.read)"
									@click="markAsRead(n, idx)"
									class="text-sm text-white px-3 py-1 rounded"
									:style="{ background: '#d93f3f' }">
									Marcar leída
								</button>
							</div>
						</div>
					</div>

					<!-- Acción global provisional -->
					<div class="mt-4 text-right">
						<button @click="markAllRead" class="px-4 py-2 rounded text-sm"
							:style="{ background: '#6b7280', color: '#fff' }">
							Marcar todas como leídas
						</button>
					</div>
				</div>

				<div v-if="error" class="mt-4 text-sm text-red-600">
					{{ error }}
				</div>
			</div>
		</div>
	</MainLayoutDashboard>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
import axios from 'axios';
import { authService } from '@/Services/authService';

const API_URL = '/api';

const notifications = ref([]);
const loading = ref(true);
const error = ref(null);
const expanded = ref(null);

const getAuthHeaders = () => ({ headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } });

function formatFecha(f) {
	if (!f) return '';
	try {
		return new Date(f).toLocaleString('es-ES');
	} catch { return String(f); }
}

async function fetchNotifications() {
	loading.value = true;
	error.value = null;
	try {
		const res = await axios.get(`${API_URL}/notifications/get/my/all`, getAuthHeaders());
		if (res.data?.success) {
			notifications.value = res.data.data || [];
		} else {
			notifications.value = [];
			error.value = res.data?.message || 'No se pudieron cargar las notificaciones';
		}
	} catch (e) {
		console.error(e);
		error.value = 'Error al obtener notificaciones';
		notifications.value = [];
	} finally {
		loading.value = false;
		// persistir provisional para sincronizar con layout
		localStorage.setItem('notifications', JSON.stringify(notifications.value));
		localStorage.setItem('notificationCount', String(notifications.value.filter(n => !(n.estado === 'leida' || n.read)).length));
	}
}

async function markAsRead(n, idx) {
	try {
		const id = n.id;
		// Llamada API provisional: PATCH /notifications/mark-read/{id}
		await axios.patch(`${API_URL}/notifications/mark-read/${id}`, {}, getAuthHeaders());
		// Actualizar client-side provisional
		notifications.value[idx].estado = 'leida';
		notifications.value[idx].read = true;
		localStorage.setItem('notifications', JSON.stringify(notifications.value));
		localStorage.setItem('notificationCount', String(notifications.value.filter(x => !(x.estado === 'leida' || x.read)).length));
	} catch (e) {
		console.error(e);
		alert('No se pudo marcar como leída (provisional).');
	}
}

function toggleExpand(n, idx) {
	const key = n.id ?? idx;
	expanded.value = expanded.value === key ? null : key;
}

async function markAllRead() {
	// Marcado provisional: actualizar local y llamar a cada endpoint si se desea
	try {
		await Promise.all(notifications.value.filter(n => !(n.estado === 'leida' || n.read)).map(n => {
			const id = n.id;
			return axios.patch(`${API_URL}/notifications/mark-read/${id}`, {}, getAuthHeaders()).catch(()=>null);
		}));
		notifications.value = notifications.value.map(n => ({ ...n, estado: 'leida', read: true }));
		localStorage.setItem('notifications', JSON.stringify(notifications.value));
		localStorage.setItem('notificationCount', '0');
	} catch (e) {
		console.error(e);
	}
}

onMounted(async () => {
	// Opcional: verificar token
	const token = authService.getToken();
	if (!token) {
		// sin token, intentar cargar desde localStorage provisional
		const stored = localStorage.getItem('notifications');
		if (stored) notifications.value = JSON.parse(stored);
		loading.value = false;
		return;
	}
	await fetchNotifications();
});
</script>

<style scoped>
/* Estilos mínimos; confía en Tailwind del proyecto */
</style>
