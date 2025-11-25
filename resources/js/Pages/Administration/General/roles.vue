<template>
    <Head title="Roles" />

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
                <h1 class="text-2xl font-bold text-gray-900 mb-1" :style="{color:colorText}">Roles</h1>
                <p class="text-gray-600 text-sm">Listado de los roles dentro del sistema</p>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <input
                        v-model="searchTerm"
                        type="text"
                        placeholder="Buscar por nombre"
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <button
                        @click="openCreateModal"
                        class="text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 whitespace-nowrap"
                        :style="{background: '#d93f68'}"
                    >
                        <span class="text-xl">+</span>
                        Agregar Usuario
                    </button>
                </div>
                <br>

                <div v-if="loading" class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600">Cargando roles...</p>
                </div>

                <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-6">
                    <p class="font-bold">Error al cargar</p>
                    <p>{{ error }}</p>
                </div>
                <br>

                <div v-if="!loading && rolesFiltrados.length" class="bg-white rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full" :style="{ border: '1px solid #BD3838' }">
                            <thead class="bg-gray-50 border-b-2 border-gray-200 text-center" :style="{background: '#BD3838', height: '40px'}">
                            <tr>
                                <th class="text-white px-4 py-2">Id</th>
                                <th class="text-white px-4 py-2">Nombre</th>
                                <th class="text-white px-4 py-2">Descripción</th>
                                <th class="text-white px-4 py-2">Opciones</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 text-center align-middle">
                            <tr v-for="rol in paginatedRoles" :key="rol.id" class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ rol.id }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ rol.nombre }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">{{ rol.descripcion }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex justify-center gap-2">
                                        <button
                                            @click="getUsersByRol(rol.id)"
                                            class="hover:bg-red-600 text-white px-4 py-2 rounded-lg transition-colors"
                                            :style="{ background: '#A0153E' }"
                                            :disabled="loading"
                                        >
                                            Ver usuarios
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
                                <i class="fas fa-chevron-left"></i> </button>

                            <button
                                @click="goToPage(currentPage)"
                                class="px-4 py-2 border rounded-lg font-bold text-white transition-colors"
                                :style="{ background: '#d93f3f' }">
                                {{ currentPage }}
                            </button>

                            <button
                                @click="nextPage"
                                :disabled="currentPage === totalPages"
                                class="p-2 border rounded-lg transition-colors"
                                :class="{ 'bg-gray-200 cursor-not-allowed': currentPage === totalPages, 'hover:bg-gray-100': currentPage < totalPages }">
                                <i class="fas fa-chevron-right"></i> </button>
                        </div>
                    </div>
                </div>

                <div v-else-if="!loading && !rolesFiltrados.length" class="bg-gray-100 border border-gray-400 text-gray-700 px-6 py-4 rounded-lg mb-6 text-center">
                    <p v-if="searchTerm === ''">No hay roles registrados en el sistema.</p>
                    <p v-else>No se encontraron roles que coincidan con la búsqueda: <span class="text-red-500">"{{ searchTerm }}"</span></p>
                </div>
            </div>
        </div>
    </MainLayoutDashboard>

    <div v-if="showModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl sm:max-w-2xl w-full overflow-y-auto">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">
                    {{ isEditMode ? 'Editar Rol' : 'Agregar Nuevo Rol' }}
                </h2>
                <button @click="closeModal" class="text-gray-500 hover:text-gray-700">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>

            <form @submit.prevent="submitForm" class="p-6 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" v-if="isEditMode">
                    <!-- ID (solo en modo edición) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            ID
                        </label>
                        <input
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.id}"
                            :value="currentRolId"
                            :disabled="formData.nombre === 'root' || currentRolId === 1"
                            required
                        />
                    </div>

                    <!-- Nombre -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="formData.nombre"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.nombre}"
                            :disabled="formData.nombre === 'root' || currentRolId === 1"
                            required
                        />
                        <p v-if="formErrors.nombre" class="text-red-500 text-sm mt-1">
                            {{ formErrors.nombre[0] }}
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4" v-else>
                    <!-- Columna 1 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre completo <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="userFormData.nombre_completo"
                            type="text"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.nombre_completo}"
                            required
                        />
                        <p v-if="formErrors.nombre_completo" class="text-red-500 text-sm mt-1">
                            {{ formErrors.nombre_completo[0] }}
                        </p>
                    </div>

                    <!-- Columna 2 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Correo electrónico <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="userFormData.email"
                            type="email"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.email}"
                            placeholder="nombre.apellido@ues.edu.sv"
                            required
                        />
                        <p v-if="formErrors.email" class="text-red-500 text-sm mt-1">
                            {{ formErrors.email[0] }}
                        </p>
                    </div>

                    <!-- Columna 1 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Teléfono <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="userFormData.telefono"
                            type="tel"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.telefono}"
                            placeholder="1234-5678"
                            @input="formatPhone"
                            required
                        />
                        <p v-if="formErrors.telefono" class="text-red-500 text-sm mt-1">
                            {{ formErrors.telefono[0] }}
                        </p>
                    </div>

                    <!-- Columna 2 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Rol <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model.number="userFormData.rol_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.rol_id}"
                            required
                        >
                            <option value="" disabled>Seleccione un rol</option>
                            <option
                                v-for="rol in allRoles"
                                :key="rol.id"
                                :value="rol.id"
                            >
                                {{ rol.nombre }}
                            </option>
                        </select>
                        <p v-if="formErrors.rol_id" class="text-red-500 text-sm mt-1">
                            {{ formErrors.rol_id[0] }}
                        </p>
                    </div>

                    <!-- Columna 1 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Contraseña <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                v-model="userFormData.password"
                                :type="showPassword ? 'text' : 'password'"
                                class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                :class="{'border-red-500': formErrors.password}"
                                required
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                            >
                                <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                        <p v-if="formErrors.password" class="text-red-500 text-sm mt-1">
                            {{ formErrors.password[0] }}
                        </p>
                    </div>

                    <!-- Columna 2 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Repita la contraseña <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                v-model="userFormData.password_confirmation"
                                :type="showPasswordConfirmation ? 'text' : 'password'"
                                class="w-full px-4 py-2 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                :class="{'border-red-500': formErrors.password_confirmation}"
                                required
                            />
                            <button
                                type="button"
                                @click="showPasswordConfirmation = !showPasswordConfirmation"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                            >
                                <i :class="showPasswordConfirmation ? 'fas fa-eye-slash' : 'fas fa-eye'"></i>
                            </button>
                        </div>
                        <p v-if="formErrors.password_confirmation" class="text-red-500 text-sm mt-1">
                            {{ formErrors.password_confirmation[0] }}
                        </p>
                    </div>

                    <!-- Columna 1 -->
                    <div v-if="[3, 4, 5, 6].includes(userFormData.rol_id)">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Departamento <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model.number="userFormData.departamento_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.departamento_id}"
                            required
                            @change="fetchCarreras(userFormData.departamento_id)"
                        >
                            <option value="" disabled>Seleccione un departamento</option>
                            <option
                                v-for="departamento in allDepartments"
                                :key="departamento.id"
                                :value="departamento.id"
                            >
                                {{ departamento.nombre }}
                            </option>
                        </select>
                        <p v-if="formErrors.departamento_id" class="text-red-500 text-sm mt-1">
                            {{ formErrors.departamento_id[0] }}
                        </p>
                    </div>

                    <!-- Columna 2 -->
                    <div v-if="[4, 6].includes(userFormData.rol_id)" :class="{'display': display}">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Carrera <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model.number="userFormData.carrera_id"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            :class="{'border-red-500': formErrors.carrera_id}"
                            required
                        >
                            <option value="" disabled>Seleccione una carrera</option>
                            <option
                                v-for="carrera in allCarreras"
                                :key="carrera.id"
                                :value="carrera.id"
                            >
                                {{ carrera.nombre }}
                            </option>
                        </select>
                        <p v-if="formErrors.carrera_id" class="text-red-500 text-sm mt-1">
                            {{ formErrors.carrera_id[0] }}
                        </p>
                    </div>
                </div>

                <div v-if="formErrors.general" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <p class="font-bold">Error</p>
                    <p>{{ formErrors.general }}</p>
                </div>

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

    <!-- Modal de Usuarios por Rol -->
    <div v-if="showUsersModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-full w-full overflow-hidden">
            <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center">
                <h2 class="text-xl font-bold text-gray-900">
                    Usuarios con rol: {{ currentRoleName }}
                </h2>
                <button @click="closeUsersModal" class="text-gray-500 hover:text-gray-700">
                    <span class="text-2xl">&times;</span>
                </button>
            </div>

            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <div v-if="usersInRole.length === 0" class="text-center py-8 text-gray-500">
                    No hay usuarios con este rol
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full border border-gray-300">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left border-b">Nombre</th>
                                <th class="px-4 py-2 text-left border-b">Email</th>
                                <th class="px-4 py-2 text-left border-b">Teléfono</th>
                                <th class="px-4 py-2 text-left border-b">Departamento</th>
                                <th class="px-4 py-2 text-left border-b">Carrera</th>
                                <th class="px-4 py-2 text-left border-b">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in usersInRole" :key="user.email" class="hover:bg-gray-50">
                                <td class="px-4 py-2 border-b">{{ user.nombre_completo }}</td>
                                <td class="px-4 py-2 border-b">{{ user.email }}</td>
                                <td class="px-4 py-2 border-b">{{ user.telefono }}</td>
                                <td class="px-4 py-2 border-b">{{ user.nombre_departamento || 'N/A' }}</td>
                                <td class="px-4 py-2 border-b">{{ user.nombre_carrera || 'N/A' }}</td>
                                <td class="px-4 py-2 border-b">
                                    <span :class="user.estado === 'activo' ? 'text-green-600' : 'text-red-600'">
                                        {{ user.estado }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="sticky bottom-0 bg-white border-t px-6 py-4 flex justify-end">
                <button
                    @click="closeUsersModal"
                    class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors"
                >
                    Cerrar
                </button>
            </div>
        </div>
    </div>

</template>

<script setup>

    import { Head } from '@inertiajs/vue3';
    import { ref, computed, onMounted, watch } from 'vue';
    import axios from 'axios';
    import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
    import { authService } from "@/Services/authService.js";
    import {getDeparmentsAll} from '@/Services/deparmentsService';

    const isLoading = ref(true);
    const colorText = ref('#1F2937');
    const searchTerm = ref('');
    const loading = ref(false);
    const error = ref(null);
    const allRoles = ref([]);
    const isAuthenticated = localStorage.getItem('isAuthenticated');

    const userFormData = ref({
        nombre_completo: '',
        email: '',
        telefono: '',
        password: '',
        password_confirmation: '',
        departamento_id: null,
        carrera_id: null,
        rol_id: null,
        estado: 'activo',
    });

    const allDepartments = ref([]);
    const display = ref(false);

    const allCarreras = ref([]);

    const showUsersModal = ref(false);
    const usersInRole = ref([]);
    const currentRoleName = ref('');

    const showPassword = ref(false);
    const showPasswordConfirmation = ref(false);

    // Paginación
    const currentPage = ref(1);
    const perPage = ref(5); // Número de registros por página

    // Configuración de axios
    const API_URL = '/api';
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
    const currentRolId = ref(null);

    // Datos del formulario (Simplificado para Rol)
    const formData = ref({
        nombre: '',
        descripcion: '',
    });

    // Filtrado (ajustado para roles)
    const rolesFiltrados = computed(() => {
        const data = Array.isArray(allRoles.value) ? allRoles.value : [];
        if (!searchTerm.value) return data;
        const term = searchTerm.value.toLowerCase();
        return data.filter(rol =>
            rol.nombre.toLowerCase().includes(term)
        );
    });

    // Paginación (ajustado para roles)
    const totalPages = computed(() => {
        return Math.ceil(rolesFiltrados.value.length / perPage.value);
    });

    const paginatedRoles = computed(() => {
        const start = (currentPage.value - 1) * perPage.value;
        const end = start + perPage.value;
        return rolesFiltrados.value.slice(start, end);
    });

    // Observa los roles filtrados
    watch(rolesFiltrados, () => {
        currentPage.value = 1;
    });

    // Funciones del Modal
    const resetForm = () => {
        formData.value = {
            nombre: '',
            descripcion: '',
        };
        formErrors.value = {};
        currentRolId.value = null;
    };

    const openCreateModal = () => {
        resetForm();
        isEditMode.value = false;
        showModal.value = true;
    };

    const openEditModal = (rol) => {
        console.log('Abriendo modal para editar:', rol);

        resetForm();
        isEditMode.value = true;
        currentRolId.value = rol.id;

        // Cargar datos
        formData.value = {
            nombre: rol.nombre || '',
            descripcion: rol.descripcion || '',
        };

        console.log('Form data cargado:', formData.value);

        showModal.value = true;
    };

    const closeModal = () => {
        showModal.value = false;
        userFormData.value = {
            nombre_completo: '',
            email: '',
            telefono: '',
            password: '',
            password_confirmation: '',
            departamento_id: null,
            carrera_id: null,
            rol_id: null,
            estado: 'activo',
        };
        resetForm();
    };

    // Funciones CRUD
    const submitForm = async () => {
        formErrors.value = {};
        submitting.value = true;

        try {
            /*const payloadEdit = {
                nombre: formData.value.nombre,
                descripcion: formData.value.descripcion,
            };*/

            const payloadCreate = {
                nombre_completo: userFormData.value.nombre_completo,
                email: userFormData.value.email,
                telefono: userFormData.value.telefono,
                password: userFormData.value.password,
                password_confirmation: userFormData.value.password_confirmation,
                departamento_id: userFormData.value.departamento_id,
                carrera_id: userFormData.value.carrera_id,
                rol_id: userFormData.value.rol_id,
                estado: userFormData.value.estado,
            };

            /*if (isEditMode.value){
                const response = await axios.patch(`${API_URL}/roles/edit/${currentRolId.value}`, payloadEdit, getAuthHeaders());

                const data = response.data;

                if(!data.success) {
                    throw new Error(data.message || 'Error al actualizar el rol');
                }

                alert('Rol actualizado exitosamente');

            } else{

            }*/

            const response = await axios.post(`${API_URL}/users/new`, payloadCreate, getAuthHeaders());

            const data = response.data;

            if(!data.success) {
                formErrors.value.general = data.message || 'Error al crear el usuario';
                alert(data.message || 'Error al crear el usuario');
                return;
            }

            alert('Usuario creado exitosamente');
            closeModal();
        } catch (err) {
            console.error("Error al guardar rol:", err);

            const data = err.response?.data || {};

            if (data.errors) {
                formErrors.value = data.errors; // errores de validación
            } else {
                formErrors.value.general =
                    data.message || data.error || "Error al guardar el rol";
            }
        } finally {
            submitting.value = false;
        }
    };

    const getUsersByRol = async (id) => {
        try {
            if (id === 5) {
                window.location.href = '/dashboard/docentes';
                return;
            }

            if (id === 6) {
                window.location.href = '/dashboard/estudiantes';
                return;
            }

            const response = await axios.get(`${API_URL}/roles/get/users/${id}`, getAuthHeaders());

            const data = response.data;
            if(!data.success) {
                alert(data.message || 'Error al obtener usuarios del rol');
                return;
            }

            usersInRole.value = data.data;
            const rol = allRoles.value.find(r => r.id === id);
            currentRoleName.value = rol?.nombre || 'Rol';
            showUsersModal.value = true;

        } catch (err) {
            console.error('Error al obtener usuarios:', err);
            alert(err.response?.data?.message || 'Error al obtener usuarios del rol');
        }
    };

    const closeUsersModal = () => {
        showUsersModal.value = false;
        usersInRole.value = [];
        currentRoleName.value = '';
    };

    // Paginación
    const prevPage = () => {
        if (currentPage.value > 1) {
            currentPage.value--;
        }
    };

    const nextPage = () => {
        if (currentPage.value < totalPages.value) {
            currentPage.value++;
        }
    };

    const goToPage = (page) => {
        if (page >= 1 && page <= totalPages.value) {
            currentPage.value = page;
        }
    };

    // Cargar datos (Roles)
    async function fetchRoles() {
        loading.value = true;
        error.value = null;

        try {
            const res = await axios.get(`${API_URL}/roles/get/all`, getAuthHeaders());

            const payload = res.data?.data;
            const raw = Array.isArray(payload) ? payload : (payload ? Object.values(payload) : []);

            allRoles.value = raw.map(rol => ({
                id: rol.id ?? 'N/A',
                nombre: rol.nombre ?? 'Unknown',
                descripcion: rol.descripcion ?? 'Sin descripción',
            }));

            error.value = null;

        } catch (err) {
            const status = err.response?.status;

            if (status === 404) {
                allRoles.value = [];
                error.value = null;
            } else if (status === 401 || status === 403) {
                error.value = err.response?.data?.message || 'Acceso no autorizado. Verifica tu sesión/rol.';
                allRoles.value = [];
            } else {
                error.value = err.response?.data?.message || 'Error al cargar los roles';
                allRoles.value = [];
            }
        } finally {
            loading.value = false;
        }
    }

    async function fetchDepartments() {
        try {
            loading.value = true;
            error.value = null;
            const response = await getDeparmentsAll();
            allDepartments.value = Array.isArray(response) ? response : response.data;
        } catch (error) {
            console.error('Error al obtener los departamentos:', error);
            error.value = 'Error al cargar los departamentos';
        } finally {
            loading.value = false;
        }
    }

    async function fetchCarreras(departamento_id) {
        try {
            const response = await axios.get(`/api/careers/get/by-departament/${departamento_id}`, {
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${localStorage.getItem('token')}`
                }
            });

            const data = response.data;
            allCarreras.value = Array.isArray(data) ? data : data.data;
            display.value = true;
        } catch (err) {
            console.error('Error al cargar carreras:', err);
            console.error('Error completo:', err.response?.data);
            formErrors.value.general = 'Error al cargar carreras';
            return [];
        }
    }

    const formatPhone = () => {
        // Eliminar cualquier carácter que no sea dígito
        let value = userFormData.value.telefono.replace(/\D/g, '');

        // Verificar si el primer dígito es 2, 6 o 7
        if (value.length > 0 && !/^[267]/.test(value)) {
            // Si el primer dígito no es 2, 6 ni 7, borrar todo
            userFormData.value.telefono = '';
        } else {
            // Si hay más de 8 dígitos, truncar a 8
            if (value.length > 8) {
                value = value.slice(0, 8);
            }
            // Formatear el número de teléfono con guion después de 4 dígitos
            if (value.length > 4) {
                userFormData.value.telefono = `${value.slice(0, 4)}-${value.slice(4)}`;
            } else {
                userFormData.value.telefono = value;
            }
        }
    };

    onMounted(async () => {
        await authService.verifyToken(localStorage.getItem("token"));
        searchTerm.value = '';
        await fetchRoles();
        await fetchDepartments();
        isLoading.value = false;
    });

</script>
