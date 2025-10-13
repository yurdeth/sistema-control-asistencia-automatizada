<template>
    <Head title="Disponibilidad" />
    <MainLayoutDashboard>
        <div class="p-6">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 mb-1" :style="{color:colorText}">Disponibilidad de Aulas</h1>
                <p class="text-gray-600 text-sm">Visualice y gestione las aulas ocupadas o disponibles</p>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
                <div class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between">
                    <!-- Navegación de fecha -->
                    <div class="flex items-center gap-3">
                        <button
                            @click="cambiarDia(-1)"
                            class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>

                        <div class="text-center min-w-[280px]">
                            <div class="font-semibold text-gray-900">{{ fechaFormateada }}</div>
                            <span v-if="esHoy" class="text-xs text-blue-600 font-medium">Hoy</span>
                        </div>

                        <button
                            @click="cambiarDia(1)"
                            class="p-2 hover:bg-gray-100 rounded-lg transition-colors"
                        >
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>

                        <button
                            @click="irAHoy"
                            class="px-3 py-2 text-sm bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg transition-colors"
                        >
                            Hoy
                        </button>
                    </div>

                    <!-- Filtros -->
                    <div class="flex gap-3 w-full md:w-auto">
                        <select
                            v-model="salonSeleccionado"
                            class="flex-1 md:flex-none px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                        >
                            <option value="all">Todas las Aulas</option>
                            <option
                            v-for="salon in salones"
                            :key="salon.id"
                            :value="salon.id"
                            >
                            {{ salon.nombre }}
                            </option>
                        </select>

                        <button
                            @click="abrirModalReserva"
                            class="px-4 py-2  text-white rounded-lg transition-colors text-sm font-medium whitespace-nowrap"
                            :style="{background:colorButton}"
                        >
                            + Nueva Reserva de Aula
                        </button>
                    </div>
                </div>
            </div>

                <!-- Leyenda -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-4">
                <div class="flex flex-wrap gap-4 items-center">
                    <span class="text-sm font-medium text-gray-700">Salones:</span>
                    <div
                    v-for="aula in aulasFiltradas"
                    :key="aula.id"
                    class="flex items-center gap-2"
                    >
                    <div
                        :class="aula.color"
                        class="w-3 h-3 rounded"
                    ></div>
                    <span class="text-sm text-gray-600">{{ aula.nombre }}</span>
                    </div>
                </div>
            </div>

            <!-- Loading -->
            <div v-if="cargando" class="text-center py-12 bg-white rounded-lg border border-gray-200">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            </div>

            <!-- Vista de calendario -->
            <div v-else class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <!-- Header de salones -->
                <div class="border-b border-gray-200 bg-gray-50 p-4">
                    <div class="grid gap-2" :style="gridStyle">
                        <div class="text-xs font-medium text-gray-500 text-center">Hora</div>
                        <div
                            v-for="aula in aulasFiltradas"
                            :key="aula.id"
                            class="text-xs font-medium text-gray-700 text-center"
                        >
                            {{ aula.nombre }}
                        </div>
                    </div>
                </div>

                <!-- Grid de horarios -->
                <div class="overflow-auto" style="max-height: 600px">
                    <div class="relative">
                            <div
                                v-for="hora in horas"
                                :key="hora"
                                class="grid gap-2 border-b border-gray-100"
                                :style="gridStyle"
                                style="height: 80px"
                            >
                            <!-- Columna de hora -->
                            <div class="flex items-start justify-center pt-1 text-xs text-gray-500 font-medium">
                            {{ hora }}:00
                            </div>


                        </div>
                    </div>
                </div>

            </div>

        </div>
    </MainLayoutDashboard>
</template>

<script setup>
    import { Head, Link } from '@inertiajs/vue3';
    import { ref , computed, onMounted } from 'vue';
    import MainLayoutDashboard from '@/Layouts/MainLayoutDashboard.vue';

    // Estadoa
    const fechaSeleccionada = ref(new Date());
    const aulaSeleccionada = ref('all');
    const aulas = ref([]);
    const reservas = ref([]);
    const cargando = ref(false);

    const horas = Array.from({ length: 12 }, (_, i) => i + 7); // 7:00 AM a 6:00 PM
    const colorText = ref('#2C2D2F');
    const colorButton = ref('#d93f3f');

    const coloresSalones = [
        'bg-blue-500',
        'bg-purple-500',
        'bg-green-500',
        'bg-orange-500',
        'bg-red-500',
        'bg-indigo-500',
        'bg-pink-500',
        'bg-teal-500'
    ];

    // Computed
    const fechaFormateada = computed(() => {
    return fechaSeleccionada.value.toLocaleDateString('es-ES', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    });

    const esHoy = computed(() => {
    const hoy = new Date();
    return fechaSeleccionada.value.toDateString() === hoy.toDateString();
    });

    const salonesFiltrados = computed(() => {
    if (salonSeleccionado.value === 'all') {
        return salones.value;
    }
    return salones.value.filter(s => s.id === parseInt(salonSeleccionado.value));
    });

    const gridStyle = computed(() => {
        const cols = salonesFiltrados.value.length + 1;
        return `grid-template-columns: 80px repeat(${salonesFiltrados.value.length}, 1fr)`;
    });

    const reservasDia = computed(() => {
        const fechaStr = fechaSeleccionada.value.toISOString().split('T')[0];
        return reservas.value.filter(r => r.fecha === fechaStr);
    });

    const totalReservas = computed(() => reservasDia.value.length);

    const reservasConfirmadas = computed(() => {
        return reservasDia.value.filter(r => r.estado === 'confirmada').length;
    });

    const reservasPendientes = computed(() => {
        return reservasDia.value.filter(r => r.estado === 'pendiente').length;
    });

</script>
