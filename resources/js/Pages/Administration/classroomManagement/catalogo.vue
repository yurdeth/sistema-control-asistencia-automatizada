<template>
    <Head title="Catalogo" />
    <MainLayoutDashboard>
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h1 class="text-2xl font-bold" :style="{color:colorText}">Catálogo de Aulas</h1>
                        <p class="text-gray-600 text-sm mt-1">Gestiona y visualiza todas las aulas disponibles dentro de la facultad</p>
                    </div>

                    <button 
                        class="hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                        :style="{background: colorButton}"
                        @click="irACrearAula"
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
    </MainLayoutDashboard>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
import axios from 'axios';
// componentes
import Card from '@/Components/AdministrationComponent/Card.vue';

const colorText = ref('#2C2D2F');
const colorButton = ref('#d93f3f');

// ======| Estados |======
const aulas = ref([]);
const cargando = ref(false);
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
        const response = await axios.get('/api/classrooms/get/all');
        
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

/**
 * Cerrar mensaje
 */
const cerrarMensaje = () => {
    mensaje.value.mostrar = false;
};


onMounted(() => {
    cargarAulas();
});
</script>