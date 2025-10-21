<template>
    <Head title="Catalogo"/>

    <!-- Loader mientras verifica -->
    <div v-if="isLoading" class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="text-center">
            <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-gray-900 mx-auto"></div>
            <p class="mt-4 text-gray-600 text-lg">Verificando sesión...</p>
        </div>
    </div>

    <MainLayoutDashboard>
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h1 class="text-2xl font-bold" :style="{color:colorText}">Catálogo de Aulas</h1>
                        <p class="text-gray-600 text-sm mt-1">Gestiona y visualiza todas las aulas disponibles dentro de
                            la facultad</p>
                    </div>

                    <button
                        class="hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                        :style="{background: colorButton}"
                        @click="openCreateModal"
                    >
                        <i class="fa-solid fa-plus"></i>
                        Agregar Aula
                    </button>
                </div>
            </div>

            <!-- Mensajes -->
            <div v-if="mensaje.mostrar"
                 :class="mensaje.tipo === 'success' ? 'bg-green-100 border-green-500 text-green-700' : 'bg-red-100 border-red-500 text-red-700'"
                 class="border-l-4 p-4 mb-4 rounded">
                <div class="flex justify-between items-center">
                    <p class="font-medium">{{ mensaje.texto }}</p>
                    <button @click="cerrarMensaje" class="text-xl font-bold">&times;</button>
                </div>
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <!-- Búsqueda -->
                    <div class="relative w-full">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500"></i>
                        <input
                            v-model="filtros.busqueda"
                            type="text"
                            placeholder="Buscar aula, código o ubicación..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                        />
                    </div>

                    <!-- Filtro por capacidad -->
                    <select
                        v-model="filtros.capacidad"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                    >
                        <option value="all">Todas las capacidades</option>
                        <option value="small">Pequeña (≤30 personas)</option>
                        <option value="medium">Mediana (31-100 personas)</option>
                        <option value="large">Grande (>100 personas)</option>
                    </select>

                    <!-- Filtro por estado -->
                    <select
                        v-model="filtros.estado"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                    >
                        <option value="all">Todos los estados</option>
                        <option value="disponible">Disponible</option>
                        <option value="ocupada">Ocupada</option>
                        <option value="mantenimiento">Mantenimiento</option>
                        <option value="inactiva">Inactiva</option>
                    </select>
                </div>
            </div>
            <br>

            <!-- Resultados -->
            <div class="mb-4 text-gray-600 text-sm">
                Mostrando {{ aulasFiltradas.length }} de {{ aulas.length }} aulas
            </div>

            <!-- Loading -->
            <div v-if="cargando" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                <p class="text-gray-600 mt-4">Cargando aulas...</p>
            </div>

            <!-- Error al cargar -->
            <div v-else-if="error" class="text-center py-12 bg-red-50 rounded-lg border border-red-200">
                <i class="fa-solid fa-exclamation-triangle text-6xl text-red-400 mb-4"></i>
                <p class="text-red-600 text-lg font-semibold">Error al cargar las aulas</p>
                <p class="text-gray-600 text-sm mt-2">{{ error }}</p>
                <button
                    @click="cargarAulas"
                    class="mt-4 bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700"
                >
                    <i class="fa-solid fa-rotate-right"></i> Reintentar
                </button>
            </div>

            <!-- Lista de aulas -->
            <div v-else-if="aulas.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <Card
                    v-for="aula in aulasFiltradas"
                    :key="aula.id"
                    :aula="aula"
                />
            </div>

            <!-- Sin aulas -->
            <div v-else class="text-center py-12 bg-gray-50 rounded-lg">
                <i class="fa-solid fa-door-open text-6xl text-gray-300 mb-4"></i>
                <p class="text-gray-600 text-lg">No hay aulas registradas</p>
                <p class="text-gray-500 text-sm mt-2">Comienza agregando tu primera aula</p>
            </div>
        </div>

        <Modal :show="showModal" @close="closeModal" max-width="lg">
            <form @submit.prevent="handleSubmit" class="p-6 space-y-4">
                <h2 class="text-lg font-semibold">
                    {{ isEditMode ? 'Editar aula' : 'Agregar aula' }}
                </h2>

                <!-- Sección de Imagen -->
                <div>
                    <label class="block text-sm font-medium mb-2">Imagen del Aula</label>

                    <!-- Input oculto para seleccionar archivo -->
                    <input
                        ref="fileInput"
                        type="file"
                        @change="handleImageUpload"
                        accept="image/*"
                        class="hidden"
                    />

                    <!-- Vista previa o placeholder -->
                    <div class="flex flex-col items-center">
                        <!-- Cuadro de imagen -->
                        <div
                            @click="triggerFileInput"
                            class="w-full h-64 border-2 border-dashed rounded-lg flex items-center justify-center cursor-pointer transition-all hover:border-blue-500 hover:bg-blue-50"
                            :class="imagePreview ? 'border-gray-300 bg-gray-50' : 'border-gray-300 bg-gray-100'"
                        >
                            <!-- Sin imagen -->
                            <div v-if="!imagePreview" class="text-center">
                                <i class="fa-solid fa-image text-5xl text-gray-400 mb-3"></i>
                                <p class="text-gray-500 font-medium">Imagen del aula</p>
                                <p class="text-gray-400 text-sm mt-1">Haz clic para agregar una imagen</p>
                            </div>

                            <!-- Con imagen -->
                            <img
                                v-else
                                :src="imagePreview"
                                alt="Vista previa"
                                class="w-full h-full object-cover rounded-lg"
                            />
                        </div>

                        <!-- Botones de acción (solo se muestran si hay imagen) -->
                        <div v-if="imagePreview" class="flex gap-3 mt-3">
                            <button
                                type="button"
                                @click="triggerFileInput"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
                            >
                                <i class="fa-solid fa-pen"></i>
                                Editar
                            </button>
                            <button
                                type="button"
                                @click="removeImage"
                                class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors flex items-center gap-2"
                            >
                                <i class="fa-solid fa-trash"></i>
                                Eliminar
                            </button>
                        </div>
                    </div>

                    <p v-if="formErrors.imagen" class="text-red-500 text-xs mt-2">
                        {{ formErrors.imagen[0] }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium">Código</label>
                    <input
                        type="text"
                        v-model="form.codigo"
                        class="w-full mt-1 border-gray-300 rounded-md"
                        :class="{ 'border-red-500': formErrors.codigo }"
                    />
                    <p v-if="formErrors.codigo" class="text-red-500 text-xs mt-1">
                        {{ formErrors.codigo[0] }}
                    </p>
                </div>

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
                    <label class="block text-sm font-medium">Capacidad</label>
                    <input
                        type="number"
                        v-model="form.capacidad"
                        class="w-full mt-1 border-gray-300 rounded-md"
                        :class="{ 'border-red-500': formErrors.capacidad }"
                    />
                    <p v-if="formErrors.capacidad" class="text-red-500 text-xs mt-1">
                        {{ formErrors.capacidad[0] }}
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium">Ubicación</label>
                    <textarea
                        v-model="form.ubicacion"
                        class="w-full mt-1 border-gray-300 rounded-md"
                        :class="{ 'border-red-500': formErrors.ubicacion }"
                    ></textarea>
                    <p v-if="formErrors.ubicacion" class="text-red-500 text-xs mt-1">
                        {{ formErrors.ubicacion[0] }}
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
                    <button type="button" @click="closeModal" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors" :disabled="loading">
                        {{ isEditMode ? 'Actualizar' : 'Crear' }}
                    </button>
                </div>
            </form>
        </Modal>

    </MainLayoutDashboard>
</template>

<script setup>
import {ref, computed, onMounted} from 'vue';
import {Head, Link} from '@inertiajs/vue3';
import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
import axios from 'axios';
import browserImageCompression from 'browser-image-compression';

// componentes
import Card from '@/Components/AdministrationComponent/Card.vue';
import {authService} from "@/Services/authService.js";
import Modal from "@/Components/Modal.vue";
import {createDeparments, updateDepartment} from "@/Services/deparmentsService.js";

const isLoading = ref(true);
const colorText = ref('#2C2D2F');
const colorButton = ref('#d93f3f');
const showModal = ref(false);
const isEditMode = ref(false);
const fileInput = ref(null);
const imagePreview = ref(null);

const form = ref({
    id: null,
    codigo: '',
    nombre: '',
    capacidad: '',
    ubicacion: '',
    estado: 'activo',
    imagen: null,
});

const formErrors = ref({});

// ======| Estados |======
const aulas = ref([]);
const cargando = ref(false);
const loading = ref(false);
const error = ref(null);

const mensaje = ref({
    mostrar: false,
    tipo: '',
    texto: ''
});

const filtros = ref({
    busqueda: '',
    capacidad: 'all',
    estado: 'all'
});

onMounted(async () => {
    await authService.verifyToken(localStorage.getItem("token"));

    await cargarAulas();
    isLoading.value = false;
});

// ======| Filtrado dinámico de las aulas |======
const aulasFiltradas = computed(() => {
    return aulas.value.filter(aula => {
        // Filtrado por nombre, código o ubicación
        const busquedaAula =
            aula.nombre.toLowerCase().includes(filtros.value.busqueda.toLowerCase()) ||
            aula.codigo.toLowerCase().includes(filtros.value.busqueda.toLowerCase()) ||
            aula.ubicacion.toLowerCase().includes(filtros.value.busqueda.toLowerCase());

        // Filtra según la capacidad
        const capacidadAula = filtros.value.capacidad === 'all' ||
            (filtros.value.capacidad === 'small' && aula.capacidad_pupitres <= 30) ||
            (filtros.value.capacidad === 'medium' && aula.capacidad_pupitres > 30 && aula.capacidad_pupitres <= 100) ||
            (filtros.value.capacidad === 'large' && aula.capacidad_pupitres > 100);

        // Filtrado por estado
        const estadoAula = filtros.value.estado === 'all' || aula.estado === filtros.value.estado;

        return busquedaAula && capacidadAula && estadoAula;
    });
});

// ======| Métodos API |======

const cargarAulas = async () => {
    cargando.value = true;
    error.value = null;

    try {
        const response = await axios.get('/api/classrooms/get/all', {
            headers: {
                'Authorization': `Bearer ${localStorage.getItem('token')}`
            }
        });

        if (response.data.success) {
            aulas.value = response.data.data;

            if (aulas.value.length === 0) {
                mostrarMensaje('success', 'No hay aulas registradas. Ejecuta el seeder para agregar aulas de prueba.');
            } else {
                mostrarMensaje('success', `${aulas.value.length} aulas cargadas exitosamente`);
            }
        } else {
            throw new Error(response.data.message || 'Error al cargar las aulas');
        }
    } catch (err) {

        if (err.response?.status === 404) {
            error.value = 'Ruta no encontrada. Verifica que las rutas estén en api.php';
        } else if (err.response?.status === 401) {
            error.value = 'No autorizado. Inicia sesión para ver las aulas.';
        } else if (err.response?.status === 500) {
            error.value = err.response?.data?.error || 'Error interno del servidor';
        } else {
            error.value = err.response?.data?.message || err.message || 'Error al conectar con el servidor';
        }

        mostrarMensaje('error', error.value);
    } finally {
        cargando.value = false;
    }
};

//para ver el detalle de una aula especifica
const verDetalle = async (aula) => {
    try {
        const response = await axios.get(`/api/classrooms/get/${aula.id}`);

        if (response.data.success) {
            const aulaDetalle = response.data.data;
            let recursos = '';
            if (aulaDetalle.recursos && aulaDetalle.recursos.length > 0) {
                recursos = '\n\n Recursos:\n' + aulaDetalle.recursos.map(r =>
                    `  • ${r.nombre} (x${r.cantidad}) - ${r.estado}`
                ).join('\n');
            }

            alert(`
                    ${aulaDetalle.nombre}
                    Código: ${aulaDetalle.codigo}
                    Capacidad: ${aulaDetalle.capacidad_pupitres} personas
                    Ubicación: ${aulaDetalle.ubicacion}
                    Estado: ${aulaDetalle.estado}${recursos}
            `);
        }
    } catch (err) {
        mostrarMensaje('error', 'Error al cargar los detalles del aula');
    }
};

/**
 * Editar aula
 */
const editarAula = (aula) => {
    mostrarMensaje('success', `Editar "${aula.nombre}" - Función por implementar`);
};

/**
 * Gestionar disponibilidad (cambiar estado)
 */
const gestionarDisponibilidad = async (aula) => {

    const nuevoEstado = prompt(
        `Estado actual: ${aula.estado}\n\n` +
        'Ingrese el nuevo estado:\n' +
        '1. disponible\n' +
        '2. ocupada\n' +
        '3. mantenimiento\n' +
        '4. inactiva',
        aula.estado
    );

    if (nuevoEstado && ['disponible', 'ocupada', 'mantenimiento', 'inactiva'].includes(nuevoEstado)) {
        try {
            const response = await axios.patch(`/api/classrooms/change-status/${aula.id}`, {
                estado: nuevoEstado
            });

            if (response.data.success) {
                mostrarMensaje('success', 'Estado actualizado exitosamente');
                cargarAulas();
            }
        } catch (err) {
            mostrarMensaje('error', 'Error al cambiar el estado del aula');
        }
    }
};

/**
 * Ir a crear aula
 */
const irACrearAula = () => {
    mostrarMensaje('success', 'Redirigiendo a formulario de creación - Por implementar');
};

/**
 * Mostrar mensaje temporal
 */
const mostrarMensaje = (tipo, texto) => {
    mensaje.value = {
        mostrar: true,
        tipo,
        texto
    };

    setTimeout(() => {
        cerrarMensaje();
    }, 5000);
};

// Función para abrir modal de creación
function openCreateModal() {
    isEditMode.value = false
    form.value = {
        id: null,
        codigo: '',
        nombre: '',
        capacidad: '',
        ubicacion: '',
        estado: 'activo',
        imagen: null,
    }
    imagePreview.value = null
    formErrors.value = {}
    showModal.value = true
}

/**
 * Cerrar mensaje
 */
const cerrarMensaje = () => {
    mensaje.value.mostrar = false;
};

function closeModal() {
    showModal.value = false
    imagePreview.value = null
    formErrors.value = {}
}

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
            codigo: '',
            nombre: '',
            capacidad: '',
            ubicacion: '',
            estado: 'activo',
            imagen: null,
        };
        imagePreview.value = null;

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

// Disparar el input de archivo
const triggerFileInput = () => {
    fileInput.value.click();
};

// Manejar la carga de imagen
const handleImageUpload = async (event) => {
    const file = event.target.files[0];

    if (file) {
        // Validar tipo de archivo
        if (!file.type.startsWith('image/')) {
            formErrors.value.imagen = ['Por favor selecciona un archivo de imagen válido'];
            return;
        }

        try {
            // Comprimir la imagen
            const options = {
                maxSizeMB: 1,
                maxWidthOrHeight: 800,
                useWebWorker: true
            };

            const compressedFile = await browserImageCompression(file, options);
            form.value.imagen = compressedFile;

            // Crear vista previa
            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreview.value = e.target.result;
            };
            reader.readAsDataURL(compressedFile);

            // Limpiar error si existía
            if (formErrors.value.imagen) {
                delete formErrors.value.imagen;
            }
        } catch (error) {
            console.error('Error al comprimir la imagen:', error);
            formErrors.value.imagen = ['Error al procesar la imagen'];
        }
    }
};

// Eliminar imagen
const removeImage = () => {
    imagePreview.value = null;
    form.value.imagen = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

</script>
