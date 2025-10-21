<template>
    <Head title="Departamentos"/>

    <!-- Loader mientras verifica -->
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
            <!-- Header de la vista-->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-1" :style="{color:colorText}">Departamentos</h1>
                <p class="text-gray-600 text-sm">Listado de los departamentos dentro de la facultad</p>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <!--Barra de busqueda + agregar-->
                <div class="flex flex-col sm:flex-row gap-4">
                    <input
                        v-model="searchTerm"
                        type="text"
                        placeholder="Buscar por nombre o estado"
                        class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    />
                    <button
                        @click="openCreateModal"
                        class="text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center justify-center gap-2 whitespace-nowrap"
                        :style="{background: '#ff9966'}"
                    >
                        <span class="text-xl">+</span>
                        Agregar Departamento
                    </button>
                </div>
                <br>

                <!--mensajes-->
                <div v-if="loading" class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="mt-4 text-gray-600">Cargando los departamentos...</p>
                </div>

                <div v-if="error" class="bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg mb-6">
                    <p class="font-bold">Error al cargar</p>
                    <p>{{ error }}</p>
                </div>
                <br>

                <!--Tabla con los datos-->
                <div v-if="!loading" class="bg-white rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full" :style="{ border: '1px solid #d93f3f' }">
                            <thead class="bg-gray-50 border-b-2 border-gray-200"
                                   :style="{background: '#d93f3f', height: '40px'}">
                            <tr>
                                <th class="text-white">Id</th>
                                <th class="text-white">Nombre</th>
                                <th class="text-white">Descripción</th>
                                <th class="text-white">Estado</th>
                                <th class="text-white">Opciones</th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                            <!-- Mensaje cuando no hay resultados -->
                            <tr v-if="departments.length === 0">
                                <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                    <div class="flex flex-col items-center gap-2">
                                        <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor"
                                             viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <p class="text-lg font-medium">No se encontraron resultados</p>
                                        <p class="text-sm">
                                            {{
                                                searchTerm ? `No hay departamentos que coincidan con "${searchTerm}"` : 'No hay departamentos registrados'
                                            }}
                                        </p>
                                    </div>
                                </td>
                            </tr>

                            <!-- Filas con datos -->
                            <tr v-else v-for="department in departments" :key="department.id"
                                class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 text-sm text-gray-900">{{ department.id }}</td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ department.nombre }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600 max-w-xs">
                                    <div class="relative group">
                                        <!-- Texto truncado -->
                                        <p class="truncate">
                                            {{ department.descripcion || 'Sin descripción' }}
                                        </p>

                                        <!-- Tooltip con descripción completa al hacer hover -->
                                        <div
                                            class="invisible group-hover:visible absolute z-10 w-64 p-3 text-white text-xs rounded-lg shadow-lg -top-2 left-0 transform -translate-y-full"
                                            :style="{ background: '#ff6e61' }">
                                            {{ department.descripcion || 'Sin descripción' }}
                                            <div
                                                class="absolute bottom-0 left-6 transform translate-y-1/2 rotate-45 w-2 h-2"
                                                :style="{ background: '#ff6e61' }"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ department.estado }}</td>
                                <td class="px-6 py-4 text-sm">
                                    <div class="flex gap-2">
                                        <button
                                            @click="openEditModal(department)"
                                            class="bg-green-500 hover:bg-green-800 text-white px-4 py-2 rounded-lg transition-colors"
                                            :disabled="loading"
                                        >
                                            Editar
                                        </button>
                                        <button
                                            @click="deleteItem(department.id)"
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
            </div>
        </div>

        <!--Parte donde se trabaja el modal con los formularios-->
        <Modal :show="showModal" @close="closeModal" max-width="lg">
            <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold">
                    {{ isEditMode ? 'Editar Departamento' : 'Agregar Departamento' }}
                </h2>

                <div>
                    <label class="block text-sm font-medium">Nombre</label>
                    <input
                        type="text"
                        v-model="form.nombre"
                        @input="form.nombre = form.nombre.replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/g, '')"
                        class="w-full mt-1 border-gray-300 rounded-md"
                        :class="{ 'border-red-500': formErrors.nombre }"
                    />
                    <p v-if="formErrors.nombre" class="text-red-500 text-xs mt-1">
                        {{ formErrors.nombre[0] }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium">Descripción</label>
                    <textarea
                        v-model="form.descripcion"
                        class="w-full mt-1 border-gray-300 rounded-md"
                        :class="{ 'border-red-500': formErrors.descripcion }"
                    ></textarea>
                    <p v-if="formErrors.descripcion" class="text-red-500 text-xs mt-1">
                        {{ formErrors.descripcion[0] }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium">Estado</label>
                    <select
                        v-model="form.estado"
                        class="w-full mt-1 border-gray-300 rounded-md"
                        :class="{ 'border-red-500': formErrors.estado }"
                    >
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                    <p v-if="formErrors.estado" class="text-red-500 text-xs mt-1">
                        {{ formErrors.estado[0] }}
                    </p>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" @click="closeModal" class="px-4 py-2 bg-gray-300 rounded">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded" :disabled="loading">
                        {{ isEditMode ? 'Actualizar' : 'Crear' }}
                    </button>
                </div>
            </form>
        </Modal>

    </MainLayoutDashboard>

</template>

<script setup>
import {Head} from '@inertiajs/vue3';
import {ref, onMounted, computed} from 'vue';
import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
import Modal from '@/Components/Modal.vue';
import {
    getDeparmentsAll,
    createDeparments,
    updateDepartment,
    deleteDepartment,
    searchName,
    searchStatus
} from '@/Services/deparmentsService';
import {authService} from "@/Services/authService.js";

const isLoading = ref(true)

// Definimos las propiedades necesarias
const colorText = ref('#1F2937')
const searchTerm = ref('')
const loading = ref(false)
const error = ref(null)
const isAuthenticated = localStorage.getItem('isAuthenticated');

const allDepartments = ref([]);

// Computed property para filtrar departamentos
const departments = computed(() => {
    if (!searchTerm.value.trim()) {
        return allDepartments.value;
    }

    const search = searchTerm.value.toLowerCase().trim();

    return allDepartments.value.filter(dept => {
        const nombre = dept.nombre?.toLowerCase() || '';
        const estado = dept.estado?.toLowerCase() || '';
        const descripcion = dept.descripcion?.toLowerCase() || '';

        return nombre.includes(search) ||
            estado.includes(search) ||
            descripcion.includes(search);
    });
});

// Función para cargar departamentos
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

onMounted(async () => {
    await authService.verifyToken(localStorage.getItem("token"));

    await fetchDepartments();
    isLoading.value = false;
});

// parte donde se trabaja lo del modal
const showModal = ref(false)
const isEditMode = ref(false)

// Formulario reactivo
const form = ref({
    nombre: '',
    descripcion: '',
    estado: 'activo',
});

const formErrors = ref({});

// Función para abrir modal de creación
function openCreateModal() {
    isEditMode.value = false
    form.value = {
        id: null,
        nombre: '',
        descripcion: '',
        estado: 'activo',
    }
    formErrors.value = {}
    showModal.value = true
}

// Función para abrir modal de edición
function openEditModal(department) {
    isEditMode.value = true
    form.value = {...department}
    formErrors.value = {}
    showModal.value = true;
}

// Función para cerrar modal
function closeModal() {
    showModal.value = false
    formErrors.value = {}
}

// Lógica para crear o editar
async function handleSubmit() {
    try {
        // Limpiar errores previos
        formErrors.value = {};

        if (isEditMode.value) {
            // Actualizar departamento existente
            await updateDepartment(form.value.id, form.value);
            alert('Departamento actualizado exitosamente');
        } else {
            // Crear nuevo departamento
            console.log('Creando departamento:', form.value);
            // Validación personalizada: no permitir números en el nombre
            const nombreRegex = /^[A-Za-zÁÉÍÓÚáéíóúÑñ\s]+$/;
            if (!nombreRegex.test(form.value.nombre)) {
                formErrors.value.nombre = ['El nombre no debe contener números ni caracteres especiales.'];
                return;
            }

            await createDeparments(form.value);
            alert('Departamento creado exitosamente');
        }

        // Recargar la tabla
        await fetchDepartments();
        closeModal();

        // Limpiar el formulario
        form.value = {
            nombre: '',
            descripcion: '',
            estado: 'activo',
        };

    } catch (error) {
        console.error('Error completo:', error);
        if (error.response?.status === 422) {
            formErrors.value = error.response.data.errors;
            console.error('Errores de validación:', formErrors.value);
            alert('Por favor, verifica los campos del formulario:');
        } else {
            console.error('Error inesperado:', error);
            alert('Ocurrió un error al guardar: ' + (error.response?.data?.message || error.message));
        }
    }
}

// Función para eliminar departamento
async function deleteItem(id) {
    if (!confirm('¿Estás seguro de que deseas eliminar este departamento?')) {
        return;
    }

    try {
        await deleteDepartment(id);

        allDepartments.value = allDepartments.value.filter(dept => dept.id !== id);
        alert('Departamento eliminado exitosamente');
    } catch (error) {
        console.error('Error al eliminar:', error);
        if (error.response?.status === 404) {
            alert('El departamento no existe o ya fue eliminado.');
        } else if (error.response?.status === 403) {
            alert('No tienes permisos para eliminar este departamento.');
        } else {
            alert('Error al eliminar: ' + (error.response?.data?.message || error.message));
        }
    }
}
</script>
