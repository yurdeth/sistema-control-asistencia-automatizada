<template>
    <!-- Contenedor principal del card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
        <!--Contenido principal -->
        <div class="p-4">
            <!-- Título con badge de estado -->
            <div class="flex justify-between items-start mb-3">
                <h3 class="text-lg font-bold text-gray-900">{{ aula.nombre }}</h3>
                <span
                    :class="getEstadoColor(aula.estado)"
                    class="px-2 py-1 rounded-full text-xs font-semibold"
                >
                    {{ getEstadoTexto(aula.estado) }}
                </span>
            </div>

            <div class="flex items-center text-gray-600 mb-2">
                <i class="fa-solid fa-people-group mr-2"></i>
                <span class="text-sm">Capacidad: {{ aula.capacidad_pupitres }} personas</span>
            </div>

            <div class="text-sm text-gray-600 mb-3">
                <span class="font-semibold">Ubicación:</span> {{ ubicacionTruncada }}
            </div>

            <hr>

            <!--Equipamiento / Recursos-->
            <div class="mb-3 mt-3">
                <p class="text-xs text-gray-500 mb-2 font-semibold">Recursos del Aula:</p>

                <!-- Si tiene recursos -->
                <div v-if="aula.recursos && aula.recursos.length > 0" class="flex flex-wrap gap-1">
                    <span
                        v-for="(recurso, idx) in aula.recursos.slice(0, 3)"
                        :key="idx"
                        class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs"
                    >
                        {{ recurso.nombre }}
                    </span>

                    <!-- Si el aula tiene más de tres recursos, mostrar la cantidad extra -->
                    <span
                        v-if="aula.recursos.length > 3"
                        class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs"
                    >
                        +{{ aula.recursos.length - 3 }}
                    </span>
                </div>

                <!-- Si no tiene recursos -->
                <div v-else class="text-xs text-gray-400 italic">
                    Sin recursos asignados
                </div>

                <div>
                    <img v-if="aula.imagen_url" :src="aula.imagen_url" alt="Imagen del aula" class="mt-2 w-full h-32 object-cover rounded">
                </div>
            </div>

            <!-- Botones para las acciones a realizar -->
            <!-- ====== GRID DINÁMICO: 3 columnas si puede editar, 2 si no ====== -->
            <div :class="canEdit ? 'grid-cols-3' : 'grid-cols-2'" class="grid gap-2">
                <!-- Botón VER  -->
                <button
                    @click="abrirModal({ aula, modo: 'ver' })"
                    class="text-white hover:bg-blue-700 px-3 py-2 rounded flex items-center justify-center gap-1 transition-colors text-sm font-medium"
                    :style="{ background: colors.btn_ver_detalle }"
                >
                    <i class="fa-solid fa-eye"></i>
                    Ver
                </button>

                <!-- Botón EDITAR  -->
                <button
                    v-if="canEdit"
                    @click="abrirModal({ aula, modo: 'editar' })"
                    class="text-white hover:bg-yellow-600 px-3 py-2 rounded flex items-center justify-center gap-1 transition-colors text-sm font-medium"
                    :style="{ background: colors.btn_editar }"
                >
                    <i class="fa-solid fa-pen-to-square"></i>
                    Editar
                </button>

                <!-- Botón RESERVAR  -->
                <button
                    @click="abrirModal({ aula, modo: 'reservar' })"
                    class="text-white hover:bg-green-700 px-3 py-2 rounded flex items-center justify-center gap-1 transition-colors text-sm font-medium"
                    :style="{ background: colors.btn_reservar}"
                >
                    <i class="fa-solid fa-calendar-days"></i>
                    Reservar
                </button>
            </div>

        </div>
    </div>

    <!-- Modal que se abre para realizar acciones sobre el aula -->
    <Modal :show="showModal" @close="cerrarModal" >
        <!-- Componente que muestra el contenido dinámico del aula según el modo (ver, editar, reservar) -->
        <AulaModalContent
            :aula="selectedAula"
            :modo="modoActual"
            :can-edit="canEdit"
            @update="actualizarAula"
            @close="cerrarModal"
        />
    </Modal>
</template>

<script setup>
import {computed, ref} from 'vue'
import Modal from '../Modal.vue';
import AulaModalContent from './AulaModalContent.vue';
import { colors } from '@/UI/color';
import { getEstadoColor, getEstadoTexto } from '@/UI/estadosAulas';

// const reactivas para controlar la visibilidad del modal y el aula seleccionada
const showModal = ref(false);
const selectedAula = ref(null);
const modoActual = ref('ver'); // 'ver', 'editar', 'reservar'

// Función para abrir el modal con el aula seleccionada y el modo correspondiente
const abrirModal = ({aula, modo}) => {
    selectedAula.value = aula;
    modoActual.value = modo;
    showModal.value = true;
};

const cerrarModal = () => {
    showModal.value = false;
};

const actualizarAula = (aulaActualizada) => {
    console.log('Actualizar aula:', aulaActualizada);
    cerrarModal();
};

// ====== PROPS DEL COMPONENTE ======
const props = defineProps({
    aula: {
        type: Object,
        required: true
    },
    // permisos de edicion creados
    canEdit: {
        type: Boolean,
        default: true // Por defecto puede editar
    }
})

const ubicacionTruncada = computed(() => {
    const texto = props.aula.ubicacion || ''
    const limite = 220
    return texto.length > limite ? texto.slice(0, limite) + '...' : texto
})

</script>
