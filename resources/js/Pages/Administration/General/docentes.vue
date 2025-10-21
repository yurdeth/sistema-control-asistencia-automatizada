<template>
    <Head title="Docentes" />

    <div v-if="!isAuthenticated">
        <div v-if="isLoading" class="flex items-center justify-center min-h-screen bg-gray-100">
            <div class="text-center">
                <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-gray-900 mx-auto"></div>
                <p class="mt-4 text-gray-600 text-lg">Verificando sesión...</p>
            </div>
        </div>
    </div>

    <MainLayoutDashboard>
        <div class="p-6">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-1" :style="{color:colorText}">Docentes</h1>
                <p class="text-gray-600 text-sm">Listado de los docentes dentro de la facultad</p>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <input
                        v-model="searchTerm"
                        type="text"
                        placeholder="Buscar por nombre o email"
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <button
                        @click="openCreateModal"
                        class="text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 whitespace-nowrap"
                        :style="{background: '#ff9966'}"
                    >
                        <span class="text-xl">+</span>
                        Agregar
                    </button>

                    <label
                        for="fileUpload"
                        class="cursor-pointer bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium flex items-center justify-center gap-2 transition-colors whitespace-nowrap"
                    >
                        <i class="fa-solid fa-file-excel"></i>
                        Subir Excel
                    </label>
                    <input id="fileUpload" type="file" accept=".xlsx, .xls" class="hidden" @change="handleExcelUpload" />
                </div>
                <br>

                <div v-if="loading" class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600">Cargando docentes...</p>
                </div>

                <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-6">
                    <p class="font-bold">Error al cargar</p>
                    <p>{{ error }}</p>
                </div>
                <br>

                <div v-if="!loading && docentesFiltrados.length" class="bg-white rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full" :style="{ border: '1px solid #d93f3f' }">
                            <thead class="bg-gray-50 border-b-2 border-gray-200 text-center" :style="{background: '#d93f3f', height: '40px'}">
                                <tr>
                                    <th class="text-white px-4 py-2">Id</th>
                                    <th class="text-white px-4 py-2">Nombre</th>
                                    <th class="text-white px-4 py-2">Email</th>
                                    <th class="text-white px-4 py-2">Teléfono</th>
                                    <th class="text-white px-4 py-2">Estado</th>
                                    <th class="text-white px-4 py-2">Opciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-center align-middle">
                                <tr v-for="docente in docentesFiltrados" :key="docente.id" class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-sm text-gray-900">{{ docente.id }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ docente.nombre_completo }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ docente.email }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ docente.telefono }}</td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ docente.estado }}</td>
                                    <td class="px-6 py-4 text-sm">
                                        <div class="flex justify-center gap-2">
                                            <button
                                                @click="openEditModal(docente)"
                                                class="bg-green-500 hover:bg-green-800 text-white px-4 py-2 rounded-lg transition-colors"
                                                :disabled="loading"
                                            >
                                                Editar
                                            </button>
                                            <button
                                                @click="deleteItem(docente.id)"
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
                    </div>
                </div>

                <div v-else-if="!loading && !docentesFiltrados.length" class="bg-gray-100 border border-gray-400 text-gray-700 px-6 py-4 rounded-lg mb-6 text-center">
                    <p v-if="searchTerm === ''">No hay docentes registrados en el sistema.</p>
                    <p v-else>No se encontraron docentes que coincidan con la búsqueda: **"{{ searchTerm }}"**.</p>
                </div>
            </div>
        </div>
    </MainLayoutDashboard>

    <!-- Modal para Crear/Editar Docente -->
    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">
                    {{ isEditMode ? 'Editar Docente' : 'Agregar Nuevo Docente' }}
                </h2>
                <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="p-6 space-y-4">
                <!-- Nombre Completo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre Completo <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="formData.nombre_completo"
                        type="text"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.nombre_completo}"
                        required
                    />
                    <p v-if="formErrors.nombre_completo" class="text-red-500 text-sm mt-1">
                        {{ formErrors.nombre_completo[0] }}
                    </p>
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="formData.email"
                        type="email"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.email}"
                        required
                    />
                    <p v-if="formErrors.email" class="text-red-500 text-sm mt-1">
                        {{ formErrors.email[0] }}
                    </p>
                </div>

                <!-- Teléfono -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Teléfono
                    </label>
                    <input
                        v-model="formData.telefono"
                        type="text"
                        placeholder="+503 1234-5678"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.telefono}"
                    />
                    <p v-if="formErrors.telefono" class="text-red-500 text-sm mt-1">
                        {{ formErrors.telefono[0] }}
                    </p>
                </div>

                <!-- Contraseña (solo al crear o opcional al editar) -->
                <div v-if="!isEditMode">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Contraseña <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="formData.password"
                        type="password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.password}"
                        required
                    />
                    <p v-if="formErrors.password" class="text-red-500 text-sm mt-1">
                        {{ formErrors.password[0] }}
                    </p>
                </div>

                <!-- Confirmar Contraseña (solo al crear o opcional al editar) -->
                <div v-if="!isEditMode">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Confirmar Contraseña <span class="text-red-500">*</span>
                    </label>
                    <input
                        v-model="formData.password_confirmation"
                        type="password"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.password_confirmation}"
                        required
                    />
                    <p v-if="formErrors.password_confirmation" class="text-red-500 text-sm mt-1">
                        {{ formErrors.password_confirmation[0] }}
                    </p>
                </div>

                <!-- Cambiar contraseña (opcional al editar) -->
                <div v-if="isEditMode" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <p class="text-sm text-blue-800 mb-3">
                        <strong>Nota:</strong> Deja estos campos vacíos si no deseas cambiar la contraseña
                    </p>

                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nueva Contraseña (opcional)
                        </label>
                        <input
                            v-model="formData.password"
                            type="password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.password}"
                        />
                        <p v-if="formErrors.password" class="text-red-500 text-sm mt-1">
                            {{ formErrors.password[0] }}
                        </p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Confirmar Nueva Contraseña
                        </label>
                        <input
                            v-model="formData.password_confirmation"
                            type="password"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.password_confirmation}"
                        />
                        <p v-if="formErrors.password_confirmation" class="text-red-500 text-sm mt-1">
                            {{ formErrors.password_confirmation[0] }}
                        </p>
                    </div>
                </div>

                <!-- Tipo de Asignación -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Asignar a <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="formData.tipo_asignacion"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        @change="onTipoAsignacionChange"
                        required
                    >
                        <option value="">Seleccione...</option>
                        <option value="departamento">Departamento</option>
                        <option value="carrera">Carrera</option>
                    </select>
                </div>

                <!-- Departamento -->
                <div v-if="formData.tipo_asignacion === 'departamento'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Departamento <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="formData.departamento_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.departamento_id}"
                        required
                    >
                        <option value="">Seleccione un departamento</option>
                        <option v-for="dep in departamentos" :key="dep.id" :value="dep.id">
                            {{ dep.nombre }}
                        </option>
                    </select>
                    <p v-if="formErrors.departamento_id" class="text-red-500 text-sm mt-1">
                        {{ formErrors.departamento_id[0] }}
                    </p>
                </div>

                <!-- Carrera -->
                <div v-if="formData.tipo_asignacion === 'carrera'">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Carrera <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="formData.carrera_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.carrera_id}"
                        required
                    >
                        <option value="">Seleccione una carrera</option>
                        <option v-for="carrera in carreras" :key="carrera.id" :value="carrera.id">
                            {{ carrera.nombre }}
                        </option>
                    </select>
                    <p v-if="formErrors.carrera_id" class="text-red-500 text-sm mt-1">
                        {{ formErrors.carrera_id[0] }}
                    </p>
                </div>

                <!-- Estado -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Estado <span class="text-red-500">*</span>
                    </label>
                    <select
                        v-model="formData.estado"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        :class="{'border-red-500': formErrors.estado}"
                        required
                    >
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                        <option value="suspendido">Suspendido</option>
                    </select>
                    <p v-if="formErrors.estado" class="text-red-500 text-sm mt-1">
                        {{ formErrors.estado[0] }}
                    </p>
                </div>

                <!-- Error General -->
                <div v-if="formErrors.general" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <p class="font-bold">Error</p>
                    <p>{{ formErrors.general }}</p>
                </div>

                <!-- Botones -->
                <div class="flex justify-end gap-3 pt-4">
                    <button
                        type="button"
                        @click="closeModal"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors"
                        :disabled="submitting"
                    >
                        Cancelar
                    </button>
                    <button
                        type="submit"
                        class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors disabled:opacity-50"
                        :disabled="submitting"
                    >
                        {{ submitting ? 'Guardando...' : (isEditMode ? 'Actualizar' : 'Crear') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
    import { Head } from '@inertiajs/vue3';
    import { ref, computed, onMounted, watch } from 'vue';
    import axios from 'axios';
    import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
    import { authService } from "@/Services/authService.js";

    const isLoading = ref(true);
    const colorText = ref('#1F2937');
    const searchTerm = ref('');
    const loading = ref(false);
    const error = ref(null);
    const allDocentes = ref([]);
    const isAuthenticated = localStorage.getItem('isAuthenticated');

    // Configuración de axios
    const API_URL = 'http://127.0.0.1:8000/api';
    const getAuthHeaders = () => ({
        headers: {
            'Authorization': `Bearer ${localStorage.getItem('token')}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
    });

    // Estados del Modal
    const showModal = ref(false);
    const isEditMode = ref(false);
    const submitting = ref(false);
    const formErrors = ref({});
    const currentDocenteId = ref(null);

    // Datos del formulario
    const formData = ref({
        nombre_completo: '',
        email: '',
        telefono: '',
        password: '',
        password_confirmation: '',
        tipo_asignacion: '',
        departamento_id: '',
        carrera_id: '',
        estado: 'activo'
    });

    // Listas para selects
    const departamentos = ref([]);
    const carreras = ref([]);

    // Filtrado
    const docentesFiltrados = computed(() => {
        const data = Array.isArray(allDocentes.value) ? allDocentes.value : [];
        if (!searchTerm.value) return data;
        const term = searchTerm.value.toLowerCase();
        return data.filter(docente =>
            docente.nombre_completo.toLowerCase().includes(term) ||
            docente.email.toLowerCase().includes(term)
        );
    });

    // Funciones del Modal
    const resetForm = () => {
        formData.value = {
            nombre_completo: '',
            email: '',
            telefono: '',
            password: '',
            password_confirmation: '',
            tipo_asignacion: '',
            departamento_id: '',
            carrera_id: '',
            estado: 'activo'
        };
        formErrors.value = {};
        currentDocenteId.value = null;
    };

    const openCreateModal = async () => {
        resetForm();
        isEditMode.value = false;
        await fetchDepartamentosYCarreras();
        showModal.value = true;
    };

    const openEditModal = async (docente) => {
        console.log('Abriendo modal para editar:', docente);

        resetForm();
        isEditMode.value = true;
        currentDocenteId.value = docente.id;

        // Determinar si es departamento o carrera
        let tipoAsignacion = '';
        if (docente.departamento_id) {
            tipoAsignacion = 'departamento';
        } else if (docente.carrera_id) {
            tipoAsignacion = 'carrera';
        }

        formData.value = {
            nombre_completo: docente.nombre_completo || '',
            email: docente.email || '',
            telefono: docente.telefono || '',
            password: '',
            password_confirmation: '',
            tipo_asignacion: tipoAsignacion,
            departamento_id: docente.departamento_id || '',
            carrera_id: docente.carrera_id || '',
            estado: docente.estado || 'activo'
        };

        console.log('Form data cargado:', formData.value);

        await fetchDepartamentosYCarreras();
        showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
        resetForm();
    };

    const onTipoAsignacionChange = () => {
        formData.value.departamento_id = '';
        formData.value.carrera_id = '';
    };

    // Funciones CRUD
    const submitForm = async () => {
  formErrors.value = {};
  submitting.value = true;

  try {
    const toIntOrNull = (v) =>
      v === '' || v === null || v === undefined ? null : Number(v);

    const payload = {
      nombre_completo: formData.value.nombre_completo,
      email: formData.value.email,
      telefono: formData.value.telefono,
      rol_id: 5, // Siempre docente
      estado: formData.value.estado,
      departamento_id:
        formData.value.tipo_asignacion === 'departamento'
          ? toIntOrNull(formData.value.departamento_id)
          : null,
      carrera_id:
        formData.value.tipo_asignacion === 'carrera'
          ? toIntOrNull(formData.value.carrera_id)
          : null,
    };

    if (!isEditMode.value) {
      payload.password = formData.value.password;
      payload.password_confirmation = formData.value.password_confirmation;
    }

    console.log("📦 Payload enviado:", JSON.stringify(payload, null, 2));

    const url = isEditMode.value
      ? `${API_URL}/users/edit/${currentDocenteId.value}`
      : `${API_URL}/users/new`;

    const response = isEditMode.value
      ? await axios.patch(url, payload, getAuthHeaders())
      : await axios.post(url, payload, getAuthHeaders());

    if (response.data.success) {
      closeModal();
      await fetchDocentes();
      alert(
        isEditMode.value
          ? "Docente actualizado exitosamente"
          : "Docente creado exitosamente"
      );
    }
  } catch (err) {
    console.error("❌ Error al guardar docente:", err);

    const data = err.response?.data || {};
    if (data.errors) {
      formErrors.value = data.errors; // errores de validación
    } else {
      formErrors.value.general =
        data.message || data.error || "Error al guardar el docente";
    }
  } finally {
    submitting.value = false;
  }
};


    const deleteItem = async (id) => {
        if (!confirm('¿Está seguro de eliminar este docente? Esta acción no se puede deshacer.')) return;

        try {
            loading.value = true;
            console.log('Eliminando docente ID:', id);

            const response = await axios.delete(`${API_URL}/users/delete/${id}`, getAuthHeaders());

            console.log('Respuesta eliminación:', response.data);

            if (response.data.success) {
                alert('Docente eliminado exitosamente');
                await fetchDocentes();
            }
        } catch (err) {
            console.error('Error al eliminar docente:', err);
            alert(err.response?.data?.message || 'Error al eliminar el docente');
        } finally {
            loading.value = false;
        }
    };

    // Cargar datos
   async function fetchDocentes() {
  loading.value = true;
  error.value = null;

  try {
    const res = await axios.get(`${API_URL}/users/get/all`, getAuthHeaders());

    // res.data.data puede ser un array o un objeto indexado; soporta ambos
    const payload = res.data?.data;
    const raw = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);

    // Filtrar solo Docentes (rol_id = 5) considerando distintas estructuras
    const docentes = raw.filter(user => {
      // 1) rol_id directo
      if (user.rol_id === 5) return true;

      // 2) roles: [{id, nombre}] o [{rol_id, ...}]
      if (Array.isArray(user.roles) && user.roles.some(r => (r.id ?? r.rol_id) === 5)) return true;

      // 3) usuario_roles: [{rol_id, ...}]
      if (Array.isArray(user.usuario_roles) && user.usuario_roles.some(r => r.rol_id === 5)) return true;

      return false;
    });

    allDocentes.value = docentes.map(docente => ({
      id: docente.id ?? 'N/A',
      nombre_completo: docente.nombre_completo ?? docente.name ?? 'Unknown',
      email: docente.email ?? 'N/A',
      telefono: docente.telefono ?? docente.phone ?? 'N/A',
      estado: docente.estado ?? docente.status ?? 'N/A',
      departamento_id: docente.departamento_id ?? null,
      carrera_id: docente.carrera_id ?? null,
    }));

    // Limpia cualquier error previo si todo fue bien
    error.value = null;

  } catch (err) {
    const status = err.response?.status;

    if (status === 404) {
      // Backend usa 404 para "no hay usuarios" → trátalo como lista vacía, sin error
      allDocentes.value = [];
      error.value = null;
    } else if (status === 401 || status === 403) {
      error.value = err.response?.data?.message || 'Acceso no autorizado. Verifica tu sesión/rol.';
      allDocentes.value = [];
    } else {
      error.value = err.response?.data?.message || 'Error al cargar los docentes';
      allDocentes.value = [];
    }
  } finally {
    loading.value = false;
  }
}


    async function fetchDepartamentosYCarreras() {
        try {
            const [depResponse, carResponse] = await Promise.all([
                axios.get(`${API_URL}/departaments/get/all`, getAuthHeaders()),
                axios.get(`${API_URL}/careers/get/all`, getAuthHeaders())
            ]);

            console.log('Respuesta Departamentos:', depResponse.data);
            console.log('Respuesta Carreras:', carResponse.data);

            departamentos.value = depResponse.data.data || depResponse.data || [];
            carreras.value = carResponse.data.data || carResponse.data || [];

            console.log('Departamentos procesados:', departamentos.value);
            console.log('Carreras procesadas:', carreras.value);
        } catch (err) {
            console.error('Error al cargar departamentos y carreras:', err);
            console.error('Error completo:', err.response?.data);
            formErrors.value.general = 'Error al cargar departamentos y carreras';
        }
    }

    const handleExcelUpload = (event) => {
        console.log('Subir Excel', event.target.files[0]);
    };

    onMounted(async () => {
        await authService.verifyToken(localStorage.getItem("token"));
        searchTerm.value = '';
        await fetchDocentes();
        isLoading.value = false;
    });
</script>
