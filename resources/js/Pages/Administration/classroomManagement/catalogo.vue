<template>
    <Head title="Catalogo" />
    <MainLayoutDashboard>
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <div class="flex justify-between items-center mb-4">
                    <div>
                        <h1 class="text-2xl font-bold " :style="{color:colorText}">Catálogo de aulaes</h1>
                        <p class="text-gray-600 text-sm mt-1">Gestiona y visualiza todos las aulas disponibles dentro de la faculta</p>
                    </div>

                    <button className="hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors"
                            :style="{background: colorButton}">
                        <Plus size={18} />
                        Agregar Salón
                    </button>
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
                            placeholder="Buscar salón o ubicación..."
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
                        <option value="ocupado">Ocupado</option>
                        <option value="mantenimiento">Mantenimiento</option>
                    </select>
                </div>
            </div>
            <br>

            <!-- Resultados -->
            <div class="mb-4 text-gray-600 text-sm">
                Mostrando {{ salonesFiltrados.length }} de {{ salones.length }} salones
            </div>

            <!-- Loading -->
            <div v-if="cargando" class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <!-- parte donde se mostraran las aulas -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">

            </div>




        </div>

    </MainLayoutDashboard>

</template>

<script setup>
    import { ref , computed, onMounted } from 'vue';
    import { Head, Link } from '@inertiajs/vue3';
    import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';
    import axios from 'axios';

    const colorText = ref('#2C2D2F');
    const colorButton = ref('#d93f3f');

    // ======| Parte donde se trabajan lo estados |======
    const salones = ref([]);
    const cargando = ref(false);
    const filtros = ref({
        busqueda: '',
        capacidad: 'all',
        estado: 'all'
    });

    // ======| Parte donde se trabaja el Filtro dinámico de las aulas |======

    // devuelve una lista de salones filtrados según los criterios de búsqueda, capacidad y estado
    const salonesFiltrados = computed(() => {
        return salones.value.filter(salon => {

            // Filtrado por nombre
            const busquedaAula =aulas.nombre.toLowerCase().includes(filtros.value.busqueda.toLowerCase()) ||
                aulas.ubicacion.toLowerCase().includes(filtros.value.busqueda.toLowerCase());

            //Filtra según la capacidad
            const capacidadAula = filtros.value.capacidad === 'all' ||
                (filtros.value.capacidad === 'small' && aula.capacidad <= 30) ||
                (filtros.value.capacidad === 'medium' && aula.capacidad > 30 && aula.capacidad <= 100) ||
                (filtros.value.capacidad === 'large' && aula.capacidad > 100);

            // Filtrado por estado (disponible, ocupado, mantenimiento o todos)
            const estadoAula = filtros.value.estado === 'all' || aula.estado === filtros.value.estado;

            // El salón debe cumplir con todos los filtros
            return busquedaAula && capacidadAula && estadoAula;
        });
    });

    //Metodos
    const cargarAulas = async () => {
        cargando.value = true;
        try {
            const response = await axios.get('#');
            aulas.value = response.data;
        } catch (error) {
            console.error('Error al cargar las aulas:', error);
        } finally {
            cargando.value = false;
        }
    };

</script>
