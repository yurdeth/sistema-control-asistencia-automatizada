<template>
	<Head title="Grupos" />

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
				<h1 class="text-2xl font-bold text-gray-900 mb-1" :style="{color: colorText}">Grupos</h1>
				<p class="text-gray-600 text-sm">Listado de grupos y su materia</p>
			</div>

			<div class="bg-white rounded-lg shadow p-6 mb-6">
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

				<br><br>

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
											<button
												@click="openAssignModal(g)"
												class="bg-blue-500 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
												:disabled="loading"
											>
												Asignar Aula
											</button>
											<button
												@click="openViewAssignmentsModal(g)"
												class="bg-purple-500 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors"
												:disabled="loading"
											>
												Ver Asignaciones
											</button>
										</div>
									</td>
								</tr>
							</tbody>
						</table>

						<div class="flex justify-center items-center space-x-2 p-4 border-t border-gray-200">
							<button
								@click="prevPage"
								:disabled="currentPage === 1"
								class="p-2 border rounded-lg transition-colors"
								:class="{ 'bg-gray-200 cursor-not-allowed': currentPage === 1, 'hover:bg-gray-100': currentPage > 1 }">
								<i class="fas fa-chevron-left"></i>
							</button>

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
								<i class="fas fa-chevron-right"></i>
							</button>
						</div>
					</div>
				</div>

				<div v-else-if="!loading && !filtered.length"
					 class="bg-gray-100 border border-gray-400 text-gray-700 px-6 py-4 rounded-lg mb-6 text-center">
					<p v-if="searchTerm === ''">No hay grupos registrados en el sistema.</p>
					<p v-else>No se encontraron grupos que coincidan con la búsqueda: <span class="text-red-500">"{{ searchTerm }}"</span></p>
				</div>
			</div>
		</div>
	</MainLayoutDashboard>

	<!-- Notificaciones Toast -->
		<div class="fixed top-4 right-4 z-50 space-y-2">
			<div 
				v-for="notif in notifications" 
				:key="notif.id"
				:class="[
					'transform transition-all duration-300 ease-in-out animate-slide-in',
					'min-w-[300px] max-w-md p-4 rounded-lg shadow-lg border-l-4 flex items-start gap-3',
					{
						'bg-green-50 border-green-500 text-green-800': notif.type === 'success',
						'bg-red-50 border-red-500 text-red-800': notif.type === 'error',
						'bg-blue-50 border-blue-500 text-blue-800': notif.type === 'info',
						'bg-yellow-50 border-yellow-500 text-yellow-800': notif.type === 'warning'
					}
				]"
			>
				<div class="flex-shrink-0">
					<i v-if="notif.type === 'success'" class="fas fa-check-circle text-xl text-green-600"></i>
					<i v-if="notif.type === 'error'" class="fas fa-times-circle text-xl text-red-600"></i>
					<i v-if="notif.type === 'info'" class="fas fa-info-circle text-xl text-blue-600"></i>
					<i v-if="notif.type === 'warning'" class="fas fa-exclamation-triangle text-xl text-yellow-600"></i>
				</div>
				<div class="flex-1">
					<p class="font-medium text-sm">{{ notif.message }}</p>
				</div>
				<button 
					@click="notifications = notifications.filter(n => n.id !== notif.id)"
					class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors"
				>
					<i class="fas fa-times"></i>
				</button>
			</div>
		</div>

	<!-- Modal CRUD Grupos -->
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
						<div v-if="errors.materia_id" class="text-sm text-red-600 mt-1 list-disc ml-4">
							<template v-if="Array.isArray(errors.materia_id)">
								<p v-for="(m, idx) in errors.materia_id" :key="idx">{{ m }}</p>
							</template>
							<p v-else>{{ errors.materia_id }}</p>
						</div>
					</div>
					<div>
						<label class="block text-sm">Docente</label>
						<select v-model="form.docente_id" :class="['w-full rounded px-2 py-1 border', errors.docente_id ? 'border-red-600' : '']">
							<option value="">Seleccionar docente...</option>
							<option v-for="d in docentes" :key="d.id" :value="d.id">{{ d.nombre_completo }}</option>
						</select>
						<div v-if="errors.docente_id" class="text-sm text-red-600 mt-1 list-disc ml-4">
							<template v-if="Array.isArray(errors.docente_id)">
								<p v-for="(m, idx) in errors.docente_id" :key="idx">{{ m }}</p>
							</template>
							<p v-else>{{ errors.docente_id }}</p>
						</div>
					</div>
					<div>
						<label class="block text-sm">Ciclo</label>
						<select v-model="form.ciclo_id" :class="['w-full rounded px-2 py-1 border', errors.ciclo_id ? 'border-red-600' : '']">
							<option value="">Seleccionar ciclo...</option>
							<option v-for="c in ciclos" :key="c.id" :value="c.id">{{ c.nombre }}</option>
						</select>
						<div v-if="errors.ciclo_id" class="text-sm text-red-600 mt-1 list-disc ml-4">
							<template v-if="Array.isArray(errors.ciclo_id)">
								<p v-for="(m, idx) in errors.ciclo_id" :key="idx">{{ m }}</p>
							</template>
							<p v-else>{{ errors.ciclo_id }}</p>
						</div>
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
						<div v-if="errors.numero_grupo" class="text-sm text-red-600 mt-1 list-disc ml-4">
							<template v-if="Array.isArray(errors.numero_grupo)">
								<p v-for="(m, idx) in errors.numero_grupo" :key="idx">{{ m }}</p>
							</template>
							<p v-else>{{ errors.numero_grupo }}</p>
						</div>
					</div>
					<div>
						<label class="block text-sm">Capacidad máxima</label>
						<input v-model="form.capacidad_maxima" type="number" :class="['w-full rounded px-2 py-1 border', errors.capacidad_maxima ? 'border-red-600' : '']" />
						<div v-if="errors.capacidad_maxima" class="text-sm text-red-600 mt-1 list-disc ml-4">
							<template v-if="Array.isArray(errors.capacidad_maxima)">
								<p v-for="(m, idx) in errors.capacidad_maxima" :key="idx">{{ m }}</p>
							</template>
							<p v-else>{{ errors.capacidad_maxima }}</p>
						</div>
					</div>
					<div>
						<label class="block text-sm">Estado</label>
						<select v-model="form.estado" class="w-full border rounded px-2 py-1">
							<option value="activo">Activo</option>
							<option value="inactivo">Inactivo</option>
						</select>
					</div>
				</div>

				<div v-if="serverErrorMessage" class="mb-3 p-2 rounded bg-red-50 text-red-800">
					{{ serverErrorMessage }}
				</div>
				<div v-if="serverErrors && serverErrors.length" class="mb-3 p-2 rounded bg-red-50 text-red-800">
					<ul class="list-disc pl-5">
						<p v-for="(msg, idx) in serverErrors" :key="idx">{{ msg }}</p>
					</ul>
				</div>
				<div class="flex justify-end">
					<button type="submit" :disabled="submitting" class="bg-green-600 text-white px-4 py-2 rounded">Guardar</button>
				</div>
			</form>
		</div>
	</div>

	<!-- Modal Asignar Aula - Solo Mapa Visual -->
	<div v-if="showAssignModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4 overflow-y-auto">
		<div class="bg-white rounded-lg shadow-xl w-full max-w-6xl my-8 max-h-[90vh] overflow-y-auto">
			<div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center z-10">
				<div>
					<h2 class="text-xl font-bold text-gray-900">Asignar Aula - Mapa de Disponibilidad</h2>
					<p class="text-sm text-gray-600 mt-1">Grupo {{ assignForm.numero_grupo }} - {{ assignForm.materia_nombre }}</p>
					<p class="text-sm text-gray-500">Capacidad necesaria: {{ assignForm.capacidad_maxima }} estudiantes</p>
				</div>
				<button @click="closeAssignModal" class="text-gray-500 hover:text-gray-700">
					<i class="fas fa-times text-xl"></i>
				</button>
			</div>

			<div class="p-6">
				<!-- Loading -->
				<div v-if="loadingDisponibilidad" class="text-center py-12">
					<div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
					<p class="mt-4 text-gray-600">Cargando mapa de disponibilidad...</p>
				</div>

				<div v-else-if="disponibilidadAulasOrdenadas.length">
					<!-- Panel de selección STICKY arriba -->
					<div v-if="bloqueSeleccionado" class="sticky top-0 z-20 mb-4 p-4 bg-yellow-50 border-2 border-yellow-400 rounded-lg shadow-lg">
						<div class="flex justify-between items-start mb-3">
							<h3 class="font-bold text-gray-900 text-lg">✓ Bloque Seleccionado</h3>
							<button @click="bloqueSeleccionado = null" class="text-gray-500 hover:text-gray-700">
								<i class="fas fa-times"></i>
							</button>
						</div>
						<div class="grid grid-cols-3 gap-4 text-sm mb-3">
							<div>
								<p class="text-gray-600">Aula:</p>
								<p class="font-semibold text-lg">{{ bloqueSeleccionado.aula.nombre }}</p>
								<p class="text-xs text-gray-500">Cap: {{ bloqueSeleccionado.aula.capacidad }} pupitres</p>
							</div>
							<div>
								<p class="text-gray-600">Día:</p>
								<p class="font-semibold text-lg">{{ bloqueSeleccionado.dia }}</p>
							</div>
							<div>
								<p class="text-gray-600">Horario:</p>
								<p class="font-semibold text-lg">{{ bloqueSeleccionado.horario }}</p>
							</div>
						</div>
						<button 
							@click="usarBloqueSeleccionado"
							:disabled="assignSubmitting"
							class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg font-medium text-lg shadow-md transition-all hover:shadow-lg disabled:bg-gray-400 disabled:cursor-not-allowed">
							<i class="fas fa-check-circle mr-2"></i>
							{{ assignSubmitting ? 'Asignando...' : 'Asignar este horario' }}
						</button>
					</div>

					<!-- Mensaje de error -->
					<div v-if="assignServerError" class="mb-4 bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-800 whitespace-pre-line">
						{{ assignServerError }}
					</div>

					<!-- Mensaje de éxito -->
					<div v-if="assignedAulaInfo" class="mb-4 bg-green-50 border border-green-200 rounded-lg p-4">
						<p class="font-semibold text-green-800 mb-2">✅ Aula asignada exitosamente</p>
						<div class="text-sm text-gray-700 space-y-1">
							<p><strong>Aula:</strong> {{ assignedAulaInfo.aula.nombre || assignedAulaInfo.aula.codigo }}</p>
							<p><strong>Ubicación:</strong> {{ assignedAulaInfo.aula.ubicacion }}</p>
							<p><strong>Capacidad:</strong> {{ assignedAulaInfo.aula.capacidad_pupitres || assignedAulaInfo.aula.capacidad }} pupitres</p>
							<p><strong>Horario:</strong> {{ assignedAulaInfo.horario.dia_semana }} de {{ assignedAulaInfo.horario.hora_inicio }} a {{ assignedAulaInfo.horario.hora_fin }}</p>
						</div>
						<button 
							@click="closeAssignModal"
							class="mt-3 w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors">
							Cerrar
						</button>
					</div>

					<!-- Header con leyenda y botón actualizar -->
					<div class="flex justify-between items-center mb-4">
						<div class="flex gap-4 p-3 bg-gray-50 rounded-lg text-sm">
							<div class="flex items-center gap-2">
								<div class="w-4 h-4 bg-green-100 border-2 border-green-500 rounded"></div>
								<span class="text-gray-700">Disponible</span>
							</div>
							<div class="flex items-center gap-2">
								<div class="w-4 h-4 bg-red-100 border-2 border-red-500 rounded"></div>
								<span class="text-gray-700">Ocupada</span>
							</div>
							<div class="flex items-center gap-2">
								<div class="w-4 h-4 bg-yellow-100 border-2 border-yellow-500 rounded"></div>
								<span class="text-gray-700">Seleccionada</span>
							</div>
						</div>
						
						<button 
							@click="loadDisponibilidad()"
							class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors flex items-center gap-2"
							:disabled="loadingDisponibilidad"
						>
							<i class="fas fa-sync-alt" :class="{'fa-spin': loadingDisponibilidad}"></i>
							Actualizar Mapa
						</button>
					</div>

					<div class="text-sm text-gray-600 mb-3 flex items-center gap-2">
						<i class="fas fa-lightbulb text-yellow-500"></i>
						Aulas ordenadas por capacidad similar ({{ assignForm.capacidad_maxima }} estudiantes)
					</div>

					<!-- Tabla de disponibilidad -->
					<div class="overflow-x-auto border border-gray-300 rounded-lg">
						<table class="w-full text-sm">
							<thead>
								<tr class="bg-gray-100">
									<th class="border-r border-gray-300 px-3 py-2 text-left sticky left-0 bg-gray-100 z-10 min-w-[150px]">
										<div class="font-bold text-gray-700">Aula</div>
									</th>
									<th v-for="dia in diasSemana" :key="dia" class="border-r border-gray-300 px-2 py-2 text-center min-w-[100px]">
										<div class="font-bold text-gray-700">{{ dia }}</div>
									</th>
								</tr>
							</thead>
							<tbody>
								<tr v-for="item in disponibilidadAulasPaginadas" :key="item.aula.id" class="hover:bg-gray-50 border-t border-gray-300">
									<td class="border-r border-gray-300 px-3 py-2 sticky left-0 bg-white font-medium">
										<div class="flex items-center gap-2">
											<div class="text-gray-900 font-bold">{{ item.aula.nombre }}</div>
											<span v-if="item.capacidadSimilar" class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full font-semibold">
												Óptima
											</span>
										</div>
										<div class="text-xs text-gray-600">Cap: {{ item.aula.capacidad }}</div>
										<div class="text-xs text-gray-500 line-clamp-1">{{ item.aula.ubicacion }}</div>
									</td>
									<td v-for="dia in diasSemana" :key="dia" class="border-r border-gray-300 p-1">
										<div class="space-y-1">
											<div 
												v-for="bloque in bloquesHorarios" 
												:key="bloque.inicio"
												@click="seleccionarBloqueVisual(item, dia, bloque)"
												:class="getClaseBloque(item, dia, bloque)"
												class="text-[10px] rounded cursor-pointer transition-all p-1 text-center relative group border-2">
												<div class="font-medium">{{ bloque.label }}</div>
												<div v-if="isOcupado(item, dia, bloque)" class="text-[9px] truncate">
													Grupo {{ getGrupoOcupante(item, dia, bloque) }}
												</div>
												<div v-if="isOcupado(item, dia, bloque)" 
													 class="hidden group-hover:block absolute z-20 bg-gray-900 text-white text-xs rounded p-2 bottom-full left-1/2 transform -translate-x-1/2 mb-1 w-40 shadow-lg">
													<p class="font-bold">{{ getDetallesOcupacion(item, dia, bloque).materia }}</p>
													<p class="text-gray-300">Grupo: {{ getDetallesOcupacion(item, dia, bloque).grupo }}</p>
													<p class="text-gray-300">{{ getDetallesOcupacion(item, dia, bloque).horario }}</p>
												</div>
											</div>
										</div>
									</td>
								</tr>
							</tbody>
						</table>
					</div>

					<!-- Paginación -->
					<div class="flex justify-center items-center gap-4 mt-4 p-4 border-t border-gray-200">
						<button
							@click="currentAulaPage--"
							:disabled="currentAulaPage === 1"
							class="px-4 py-2 border rounded-lg transition-colors flex items-center gap-2"
							:class="{ 'bg-gray-200 cursor-not-allowed text-gray-400': currentAulaPage === 1, 'hover:bg-gray-100 text-gray-700': currentAulaPage > 1 }">
							<i class="fas fa-chevron-left"></i>
							Anterior
						</button>

						<div class="flex items-center gap-2">
							<span class="text-sm text-gray-600">
								Página {{ currentAulaPage }} de {{ totalAulaPages }}
							</span>
							<span class="text-xs text-gray-500">
								({{ disponibilidadAulasOrdenadas.length }} aulas)
							</span>
						</div>

						<button
							@click="currentAulaPage++"
							:disabled="currentAulaPage === totalAulaPages"
							class="px-4 py-2 border rounded-lg transition-colors flex items-center gap-2"
							:class="{ 'bg-gray-200 cursor-not-allowed text-gray-400': currentAulaPage === totalAulaPages, 'hover:bg-gray-100 text-gray-700': currentAulaPage < totalAulaPages }">
							Siguiente
							<i class="fas fa-chevron-right"></i>
						</button>
					</div>
				</div>

				<!-- Sin resultados -->
				<div v-else class="text-center py-12 text-gray-500">
					<i class="fas fa-calendar-times text-4xl mb-4"></i>
					<p>No hay aulas disponibles con la capacidad requerida</p>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal Ver Asignaciones -->
	<div v-if="showViewAssignmentsModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50 p-4">
		<div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
			<div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center z-10">
				<div>
					<h2 class="text-xl font-bold text-gray-900">Asignaciones de Aula</h2>
					<p class="text-sm text-gray-600 mt-1">
						Grupo {{ currentGroupAssignments?.numero_grupo }} - {{ currentGroupAssignments?.materia_nombre }}
					</p>
					<p class="text-xs text-gray-500">
						Docente: {{ currentGroupAssignments?.docente_nombre }} | Ciclo: {{ currentGroupAssignments?.ciclo_nombre }}
					</p>
				</div>
				<button @click="closeViewAssignmentsModal" class="text-gray-500 hover:text-gray-700">
					<i class="fas fa-times text-xl"></i>
				</button>
			</div>

			<div class="p-6">
				<!-- Loading -->
				<div v-if="loadingAssignments" class="text-center py-12">
					<div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-600 mx-auto"></div>
					<p class="mt-4 text-gray-600">Cargando asignaciones...</p>
				</div>

				<!-- Lista de asignaciones -->
				<div v-else-if="groupAssignments.length > 0" class="space-y-4">
					<div 
						v-for="assignment in groupAssignments" 
						:key="assignment.id"
						class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow"
					>
						<div class="flex justify-between items-start">
							<div class="flex-1">
								<div class="flex items-center gap-3 mb-3">
									<div class="bg-purple-100 text-purple-700 px-3 py-1 rounded-full text-sm font-medium">
										{{ assignment.dia_semana || assignment.dia || 'N/A' }}
									</div>
									<div class="text-lg font-semibold text-gray-900">
										{{ assignment.hora_inicio || 'N/A' }} - {{ assignment.hora_fin || 'N/A' }}
									</div>
								</div>
								
								<div class="grid grid-cols-2 gap-4 text-sm">
									<div>
										<p class="text-gray-500 mb-1">Aula</p>
										<p class="font-semibold text-gray-900">
											<i class="fas fa-door-open text-purple-600 mr-2"></i>
											{{ getAulaName(assignment) }}
										</p>
									</div>
									<div>
										<p class="text-gray-500 mb-1">Ubicación</p>
										<p class="font-medium text-gray-700">
											<i class="fas fa-map-marker-alt text-purple-600 mr-2"></i>
											{{ getAulaUbicacion(assignment) }}
										</p>
									</div>
									<div>
										<p class="text-gray-500 mb-1">Capacidad del Aula</p>
										<p class="font-medium text-gray-700">
											<i class="fas fa-users text-purple-600 mr-2"></i>
											{{ getAulaCapacidad(assignment) }}
										</p>
									</div>
									<div>
										<p class="text-gray-500 mb-1">Tipo</p>
										<p class="font-medium text-gray-700">
											<i class="fas fa-tag text-purple-600 mr-2"></i>
											{{ getAulaTipo(assignment) }}
										</p>
									</div>
								</div>
							</div>
							
							<button
								@click="deleteAssignment(assignment.id)"
								class="ml-4 bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg transition-colors text-sm"
								title="Eliminar asignación"
							>
								<i class="fas fa-trash"></i>
							</button>
						</div>
					</div>
				</div>

				<!-- Sin asignaciones -->
				<div v-else class="text-center py-12 text-gray-500">
					<i class="fas fa-calendar-times text-5xl mb-4 text-gray-400"></i>
					<p class="text-lg font-medium">No hay asignaciones registradas</p>
					<p class="text-sm mt-2">Este grupo aún no tiene aulas asignadas</p>
					<button
						@click="closeViewAssignmentsModal(); openAssignModal(currentGroupAssignments)"
						class="mt-4 bg-purple-500 hover:bg-purple-600 text-white px-6 py-2 rounded-lg transition-colors"
					>
						Asignar Aula Ahora
					</button>
				</div>
			</div>

			<div class="border-t px-6 py-4 bg-gray-50">
				<div class="flex justify-between items-center">
					<p class="text-sm text-gray-600">
						Total de asignaciones: <span class="font-semibold">{{ groupAssignments.length }}</span>
					</p>
					<button
						@click="closeViewAssignmentsModal"
						class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition-colors"
					>
						Cerrar
					</button>
				</div>
			</div>
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

const isLoading = ref(true);
const colorText = ref('#1F2937');
const isAuthenticated = localStorage.getItem('isAuthenticated');

const list = ref([]);
const loading = ref(false);
const searchTerm = ref('');
const currentPage = ref(1);
const perPage = ref(5);

const showModal = ref(false);
const isEditMode = ref(false);
const submitting = ref(false);
const form = ref({ nombre: '', numero_grupo: '', capacidad_maxima: '', estudiantes_inscrito: '', materia_id: '', docente_id: '', ciclo_id: '', estado: 'activo' });
const currentId = ref(null);

const materias = ref([]);
const docentes = ref([]);
const ciclos = ref([]);
const errors = ref({});
const serverErrorMessage = ref('');
const serverErrors = ref([]);

const selectedOption = ref('view-all');
const filterForm = ref({ materia_id: '', docente_id: '', ciclo_id: '' });

//notificaciones
const notifications = ref([]);
let notificationId = 0;

const showNotification = (message, type = 'success') => {
	const id = notificationId++;
	notifications.value.push({ id, message, type });
	
	setTimeout(() => {
		notifications.value = notifications.value.filter(n => n.id !== id);
	}, 4000);
};


// Variables para modal de asignar aula
const showAssignModal = ref(false);
const assignSubmitting = ref(false);
const assignForm = ref({
	grupo_id: null,
	numero_grupo: '',
	materia_nombre: '',
	capacidad_maxima: 0,
	dia_semana: '',
	hora_inicio: '',
	hora_fin: '',
	aula_id: null
});
const assignServerError = ref('');
const assignedAulaInfo = ref(null);

// Variables para mapa de disponibilidad
const loadingDisponibilidad = ref(false);
const disponibilidadAulas = ref([]);
const bloqueSeleccionado = ref(null);

// Variables para paginación de aulas
const currentAulaPage = ref(1);
const aulasPerPage = ref(5);

// Variables para ver asignaciones
const showViewAssignmentsModal = ref(false);
const loadingAssignments = ref(false);
const currentGroupAssignments = ref(null);
const groupAssignments = ref([]);

const diasSemana = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];
const bloquesHorarios = [
	{ inicio: '07:00', fin: '09:00', label: '7-9 AM' },
	{ inicio: '09:00', fin: '11:00', label: '9-11 AM' },
	{ inicio: '11:00', fin: '13:00', label: '11-1 PM' },
	{ inicio: '13:00', fin: '15:00', label: '1-3 PM' },
	{ inicio: '15:00', fin: '17:00', label: '3-5 PM' },
	{ inicio: '17:00', fin: '19:00', label: '5-7 PM' },
];

// ordenar las aulas por capacidad similar
const disponibilidadAulasOrdenadas = computed(() => {
	if (!disponibilidadAulas.value.length) return [];
	
	const capacidadNecesaria = assignForm.value.capacidad_maxima || 0;
	
	return [...disponibilidadAulas.value].sort((a, b) => {
		const capA = a.aula.capacidad_pupitres || a.aula.capacidad || 0;
		const capB = b.aula.capacidad_pupitres || b.aula.capacidad || 0;
		
		const diffA = Math.abs(capA - capacidadNecesaria);
		const diffB = Math.abs(capB - capacidadNecesaria);
		
		const margen = capacidadNecesaria * 0.2;
		a.capacidadSimilar = diffA <= margen && capA >= capacidadNecesaria;
		b.capacidadSimilar = diffB <= margen && capB >= capacidadNecesaria;
		
		if (a.capacidadSimilar && !b.capacidadSimilar) return -1;
		if (!a.capacidadSimilar && b.capacidadSimilar) return 1;
		
		return diffA - diffB;
	});
});

// Computed para paginación de aulas
const totalAulaPages = computed(() => {
	return Math.max(1, Math.ceil(disponibilidadAulasOrdenadas.value.length / aulasPerPage.value));
});

const disponibilidadAulasPaginadas = computed(() => {
	const start = (currentAulaPage.value - 1) * aulasPerPage.value;
	const end = start + aulasPerPage.value;
	return disponibilidadAulasOrdenadas.value.slice(start, end);
});

const fetchAll = async () => {
	loading.value = true;
	try {
		const res = await axios.get(`${API_URL}/groups/get/all`, getAuthHeaders());
		list.value = res.data.data || [];
	} catch (e) { 
		console.error(e); 
		list.value = []; 
	} finally { 
		loading.value = false; 
	}
};

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

const validateForm = () => {
	errors.value = {};
	let valid = true;

	if (!form.value.materia_id) {
		errors.value.materia_id = 'Seleccione una materia.';
		valid = false;
	}

	if (!form.value.docente_id) {
		errors.value.docente_id = 'Seleccione un docente.';
		valid = false;
	}

	if (!form.value.ciclo_id) {
		errors.value.ciclo_id = 'Seleccione un ciclo.';
		valid = false;
	}

	if (!form.value.numero_grupo || String(form.value.numero_grupo).trim() === '') {
		errors.value.numero_grupo = 'Ingrese el número de grupo.';
		valid = false;
	}

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

const openViewAssignmentsModal = async (g) => {
	currentGroupAssignments.value = {
		id: g.id,
		numero_grupo: g.numero_grupo || g.id,
		materia_nombre: g.materia_nombre || 'Sin nombre',
		docente_nombre: g.docente_nombre || 'Sin docente',
		ciclo_nombre: g.ciclo_nombre || 'Sin ciclo'
	};
	showViewAssignmentsModal.value = true;
	await loadGroupAssignments(g.id);
};

const closeViewAssignmentsModal = () => {
	showViewAssignmentsModal.value = false;
	currentGroupAssignments.value = null;
	groupAssignments.value = [];
};

const getAulaName = (assignment) => {
	return assignment.aula?.nombre || 
	       assignment.aula?.codigo || 
	       assignment.classroom?.nombre || 
	       assignment.classroom?.codigo ||
	       (assignment.aula_id ? `Aula #${assignment.aula_id}` : 'N/A');
};

const getAulaUbicacion = (assignment) => {
	return assignment.aula?.ubicacion || 
	       assignment.classroom?.ubicacion || 
	       'Sin ubicación';
};

const getAulaCapacidad = (assignment) => {
	const capacidad = assignment.aula?.capacidad_pupitres || 
	                  assignment.classroom?.capacidad_pupitres ||
	                  assignment.aula?.capacidad || 
	                  assignment.classroom?.capacidad;
	
	return capacidad ? `${capacidad} pupitres` : 'N/A';
};

const getAulaTipo = (assignment) => {
	return assignment.aula?.tipo || 
	       assignment.classroom?.tipo || 
	       assignment.aula?.type ||
	       assignment.classroom?.type ||
	       'Estándar';
};

const loadGroupAssignments = async (grupoId) => {
	loadingAssignments.value = true;
	try {
		const res = await axios.get(
			`${API_URL}/schedules/get/group/${grupoId}`,
			getAuthHeaders()
		);
		
		let assignments = res.data.data || res.data || [];
		
		if (!Array.isArray(assignments)) {
			assignments = Object.values(assignments);
		}
		groupAssignments.value = assignments;
		
	} catch (e) {
		if (e?.response?.status === 404) {
			groupAssignments.value = [];
		} else {
			showNotification('Error al cargar asignaciones: ' + (e.response?.data?.message || e.message), 'error');
			groupAssignments.value = [];
		}
	} finally {
		loadingAssignments.value = false;
	}
};
const deleteAssignment = async (assignmentId) => {
	if (!confirm('¿Está seguro de eliminar esta asignación?')) return;
	
	try {
		await axios.delete(
			`${API_URL}/schedules/delete/${assignmentId}`,
			getAuthHeaders()
		);
		
		showNotification('Asignación eliminada correctamente', 'success');
		
		await loadGroupAssignments(currentGroupAssignments.value.id);
		await fetchAll();
		
	} catch (e) {
		const errorMsg = e.response?.data?.message || 
		                 e.response?.data?.error || 
		                 'Error al eliminar la asignación';
		
		showNotification('❌ ' + errorMsg, 'error');
	}
};

const handleNumeroInput = (e) => {
	const cleaned = String(e.target.value || '').replace(/\D+/g, '');
	e.target.value = cleaned;
	form.value.numero_grupo = cleaned;
};

const handleNumeroKeydown = (e) => {
	if ((e.ctrlKey || e.metaKey) && ['v', 'V', 'c', 'C', 'x', 'X'].includes(e.key)) {
		e.preventDefault();
		return;
	}

	const allowed = ['Backspace', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Delete', 'Home', 'End'];
	if (allowed.includes(e.key)) return;

	if (/^[0-9]$/.test(e.key)) return;

	e.preventDefault();
};

const populateRelatedNames = async (items) => {
	try {
		const materias = new Set();
		const docentes = new Set();
		const ciclos = new Set();

		items.forEach(i => {
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

			if (!i.ciclo_nombre) {
				if (i.ciclo && (i.ciclo.nombre || i.ciclo.name)) {
					i.ciclo_nombre = i.ciclo.nombre ?? i.ciclo.name;
					if (!i.ciclo_id && i.ciclo.id) ciclos.add(i.ciclo.id);
				} else if (i.ciclo_id) {
					ciclos.add(i.ciclo_id);
				}
			}
		});

		const fetchMap = async (ids, urlTemplate, nameField = 'nombre') => {
			const map = {};
			if (ids.size === 0) return map;

			const promises = Array.from(ids).map(async id => {
				try {
					const r = await axios.get(`${API_URL}/${urlTemplate.replace('{id}', id)}`, getAuthHeaders());
					let d = r.data?.data || r.data || null;

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

const openAssignModal = async (g) => {
	assignForm.value = {
		grupo_id: g.id,
		numero_grupo: g.numero_grupo || g.id,
		materia_nombre: g.materia_nombre || 'Sin nombre',
		capacidad_maxima: g.capacidad_maxima || 0,
		dia_semana: '',
		hora_inicio: '',
		hora_fin: '',
		aula_id: null
	};
	assignServerError.value = '';
	assignedAulaInfo.value = null;
	bloqueSeleccionado.value = null;
	showAssignModal.value = true;
	
	// Cargar automáticamente el mapa
	await loadDisponibilidad();
};

const closeAssignModal = () => {
	showAssignModal.value = false;
	assignForm.value = {
		grupo_id: null,
		numero_grupo: '',
		materia_nombre: '',
		capacidad_maxima: 0,
		dia_semana: '',
		hora_inicio: '',
		hora_fin: '',
		aula_id: null
	};
	assignServerError.value = '';
	assignedAulaInfo.value = null;
	bloqueSeleccionado.value = null;
	disponibilidadAulas.value = [];
};

const submitAssignAula = async () => {
	assignSubmitting.value = true;
	assignServerError.value = '';
	assignedAulaInfo.value = null;

	try {
		const payload = {
			grupo_id: assignForm.value.grupo_id,
			aula_id: assignForm.value.aula_id,
			dia_semana: assignForm.value.dia_semana,
			hora_inicio: assignForm.value.hora_inicio,
			hora_fin: assignForm.value.hora_fin
		};

		const res = await axios.post(
			`${API_URL}/schedules/new`,
			payload,
			getAuthHeaders()
		);
		if (res.data.success || res.data.data) {
			const aulaRes = await axios.get(
				`${API_URL}/classrooms/get/${assignForm.value.aula_id}`,
				getAuthHeaders()
			);
			
			assignedAulaInfo.value = {
				aula: aulaRes.data.data || aulaRes.data,
				horario: res.data.data || payload
			};
			
			showNotification(' Aula asignada exitosamente', 'success');
		}

		await fetchAll();
		
	} catch (e) {
		const data = e?.response?.data ?? null;
		
		if (e?.response?.status === 409) {
			const conflictMsg = data?.message || 'Conflicto de horario detectado';
			assignServerError.value = `⚠️ ${conflictMsg}`;
			showNotification(conflictMsg, 'warning');
			await loadDisponibilidad();
		} 
		else if (data && data.message) {
			assignServerError.value = data.message;
			showNotification('❌ ' + data.message, 'error');
		} 
		else {
			assignServerError.value = 'Error al asignar aula. Intente nuevamente.';
			showNotification('❌ Error al asignar aula', 'error');
		}
		
	} finally {
		assignSubmitting.value = false;
	}
};

const loadDisponibilidad = async () => {
	loadingDisponibilidad.value = true;
	currentAulaPage.value = 1;
	try {
		const res = await axios.get(
			`${API_URL}/groups/classrooms/availability?capacidad_minima=${assignForm.value.capacidad_maxima}`,
			getAuthHeaders()
		);
		disponibilidadAulas.value = res.data.data || [];
		
	} catch (e) {
		disponibilidadAulas.value = [];
	} finally {
		loadingDisponibilidad.value = false;
	}
};
const isOcupado = (item, dia, bloque) => {
	return item.horarios_ocupados.some(h => {
		if (h.dia !== dia) return false;
		const ocupadoInicio = h.hora_inicio.padStart(5, '0');
		const ocupadoFin = h.hora_fin.padStart(5, '0');
		const bloqueInicio = bloque.inicio.padStart(5, '0');
		const bloqueFin = bloque.fin.padStart(5, '0');
		
		return ocupadoInicio < bloqueFin && ocupadoFin > bloqueInicio;
	});
};
const getClaseBloque = (item, dia, bloque) => {
	const ocupado = isOcupado(item, dia, bloque);
	const seleccionado = bloqueSeleccionado.value && 
		bloqueSeleccionado.value.aula.id === item.aula.id &&
		bloqueSeleccionado.value.dia === dia &&
		bloqueSeleccionado.value.bloque.inicio === bloque.inicio;
	
	if (seleccionado) {
		return 'bg-yellow-100 border-yellow-500 hover:bg-yellow-200';
	}
	if (ocupado) {
		return 'bg-red-100 border-red-500 cursor-not-allowed opacity-60';
	}
	return 'bg-green-100 border-green-500 hover:bg-green-200 hover:shadow-md';
};

const getGrupoOcupante = (item, dia, bloque) => {
	const horario = item.horarios_ocupados.find(h => {
		if (h.dia !== dia) return false;
		return h.hora_inicio < bloque.fin && h.hora_fin > bloque.inicio;
	});
	return horario ? horario.grupo : '';
};

const getDetallesOcupacion = (item, dia, bloque) => {
	const horario = item.horarios_ocupados.find(h => {
		if (h.dia !== dia) return false;
		return h.hora_inicio < bloque.fin && h.hora_fin > bloque.inicio;
	});
	return horario ? {
		materia: horario.materia,
		grupo: horario.grupo,
		horario: `${horario.hora_inicio} - ${horario.hora_fin}`
	} : {};
};

const verificarConflictoGrupo = async (dia, bloque) => {
	try {
		const res = await axios.get(
			`${API_URL}/schedules/get/group/${assignForm.value.grupo_id}`,
			getAuthHeaders()
		);
		
		const asignaciones = res.data.data || res.data || [];
		
		return asignaciones.some(a => {
			if (a.dia_semana !== dia && a.dia !== dia) return false;
			
			const existeInicio = a.hora_inicio;
			const existeFin = a.hora_fin;
			const nuevoInicio = bloque.inicio;
			const nuevoFin = bloque.fin;
			
			return (existeInicio < nuevoFin && existeFin > nuevoInicio);
		});
	} catch (e) {
		return false;
	}
};

const seleccionarBloqueVisual = async (item, dia, bloque) => {
	if (isOcupado(item, dia, bloque)) {
		alert(' Este bloque está ocupado por otro grupo');
		return;
	}
	
	const grupoTieneClase = await verificarConflictoGrupo(dia, bloque);
	if (grupoTieneClase) {
		const confirmar = confirm(`⚠️ ADVERTENCIA: El grupo ya tiene una clase asignada el ${dia} en el horario ${bloque.inicio} - ${bloque.fin}.\n\n¿Desea continuar de todos modos?`);
		if (!confirmar) return;
	}
	
	bloqueSeleccionado.value = {
		aula: item.aula,
		dia: dia,
		bloque: bloque,
		horario: `${bloque.inicio} - ${bloque.fin}`,
		disponible: true
	};
};

const usarBloqueSeleccionado = async () => {
	if (!bloqueSeleccionado.value) return;
	
	assignForm.value.aula_id = bloqueSeleccionado.value.aula.id;
	assignForm.value.dia_semana = bloqueSeleccionado.value.dia;
	assignForm.value.hora_inicio = bloqueSeleccionado.value.bloque.inicio;
	assignForm.value.hora_fin = bloqueSeleccionado.value.bloque.fin;
	
	await submitAssignAula();
	
	if (assignedAulaInfo.value) {
		bloqueSeleccionado.value = null;
		await loadDisponibilidad();
	}
};


const compararHoras = (hora1, hora2) => {
	const formatear = (h) => {
		const partes = h.split(':');
		return partes[0].padStart(2, '0') + ':' + partes[1];
	};
	
	const h1 = formatear(hora1);
	const h2 = formatear(hora2);
	
	return h1.localeCompare(h2);
};

const submitForm = async () => {
	submitting.value = true;
	errors.value = {};
	serverErrorMessage.value = '';
	serverErrors.value = [];

	try {
		if (!validateForm()) {
			submitting.value = false;
			return;
		}

		// Limpiar payload
		const payload = {
			materia_id: form.value.materia_id,
			docente_id: form.value.docente_id,
			ciclo_id: form.value.ciclo_id,
			numero_grupo: form.value.numero_grupo,
			capacidad_maxima: parseInt(form.value.capacidad_maxima),
			estado: form.value.estado || 'activo'
		};

		if (form.value.estudiantes_inscrito && form.value.estudiantes_inscrito !== '') {
			payload.estudiantes_inscrito = parseInt(form.value.estudiantes_inscrito);
		}

		if (isEditMode.value) {
			await axios.patch(`${API_URL}/groups/edit/${currentId.value}`, payload, getAuthHeaders());
			showNotification(' Grupo actualizado exitosamente', 'success');
		} else {
			await axios.post(`${API_URL}/groups/new`, payload, getAuthHeaders());
			showNotification(' Grupo creado exitosamente', 'success');
		}
		
		closeModal();
		await fetchAll();
		
	} catch (e) {
		const data = e?.response?.data ?? null;
		const known = new Set(['materia_id','docente_id','ciclo_id','numero_grupo','capacidad_maxima','estudiantes_inscrito','estado']);

		if (data && data.errors && typeof data.errors === 'object') {
			Object.keys(data.errors).forEach(k => {
				const raw = data.errors[k];
				const msgArr = Array.isArray(raw) ? raw : [raw];
				if (known.has(k)) {
					errors.value[k] = msgArr;
				} else {
					serverErrors.value.push(...msgArr.map(m => (typeof m === 'string' ? m : JSON.stringify(m))));
				}
			});
			if (data.message && typeof data.message === 'string') {
				serverErrorMessage.value = data.message;
			}
		}
		else if (data && typeof data === 'string') {
			serverErrorMessage.value = data;
		}
		else if (data && (data.error || data.message)) {
			serverErrorMessage.value = data.error || data.message;
		}
		else if (e?.response?.status === 422) {
			const d = e.response.data;
			if (d.errors && typeof d.errors === 'object') {
				Object.keys(d.errors).forEach(k => {
					const raw = d.errors[k];
					errors.value[k] = Array.isArray(raw) ? raw : [raw];
				});
				serverErrorMessage.value = d.message || 'Error de validación';
			} else {
				serverErrorMessage.value = d.message || 'Error de validación';
			}
		} else {
			serverErrorMessage.value = e?.message || 'Error en la solicitud';
		}
		
		showNotification('❌ ' + (serverErrorMessage.value || 'Error al guardar el grupo'), 'error');
		console.error(e);
		
	} finally {
		submitting.value = false;
	}
};

const deleteItem = async (id) => {
	if (!confirm('¿Eliminar grupo?')) return;
	try { await axios.delete(`${API_URL}/groups/delete/${id}`, getAuthHeaders()); await fetchAll(); } catch (e) { console.error(e); }
};

const performCleanSearch = () => {
	searchTerm.value = '';
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
	isLoading.value = false;
});
</script>

<style scoped>
.line-clamp-1 {
	overflow: hidden;
	display: -webkit-box;
	-webkit-box-orient: vertical;
	-webkit-line-clamp: 1;
	line-clamp: 1;
}
</style>