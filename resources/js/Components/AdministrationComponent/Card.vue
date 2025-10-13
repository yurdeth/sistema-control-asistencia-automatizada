<template>
    <!-- Contenedor principal del card -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
        <!--Parte donde iría la imagen-->
        <div class="relative h-40 overflow-hidden">
            <img src="#" alt="">
            <div class="absolute top-2 right-2">
                <span
                :class="getEstadoColor(aula.estado)"
                class="px-2 py-1 rounded-full text-xs font-semibold"
                >
                {{ getEstadoTexto(aula.estado) }}
                </span>
            </div>
        </div>

        <!--Contenido principal -->
        <div class="p-4">
            <h3 class="text-lg font-bold text-gray-900 mb-2">{{ aula.nombre }}</h3>

            <div class="flex items-center text-gray-600 mb-2">
                <i class="fa-solid fa-people-group"></i>
                <span class="text-sm">Capacidad: {{ aula.capacidad }} personas</span>
            </div>

            <div class="text-sm text-gray-600 mb-3">
                <span class="font-semibold">Ubicación:</span> {{ aula.ubicacion }}
            </div>
            <hr>

            <!--Equipamiento / Recursos-->
            <div class="mb-3">
                <p class="text-xs text-gray-500 mb-2 font-semibold">Recursos del Aula:</p>
                <div class="flex flex-wrap gap-1">
                    <span
                        v-for="(recurso, idx) in aula.recurso.slice(0, 3)"
                        :key="idx"
                        class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs"
                    >
                        {{ recurso }}
                    </span>

                    <!-- Si el aula tiene más de tres recursos, mostrar la cantidad extra -->
                    <span
                        v-if="aula.recurso.length > 3"
                        class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-xs"
                    >
                        +{{ aula.recurso.length - 3 }}
                    </span>
                </div>
            </div>

            <!-- Botones para las acciones a realizar -->
            <div class="grid grid-cols-3 gap-2">
                <button
                    @click="abrirModal({ aula, modo: 'ver' })"
                    class="bg-blue-50 text-blue-600 hover:bg-blue-100 px-2 py-2 rounded flex items-center justify-center gap-1 transition-colors text-xs"
                >
                    <i class="fa-solid fa-eye"></i>
                    ver
                </button>

                <button
                    @click="abrirModal({ aula, modo: 'editar' })"
                    class="bg-gray-50 text-gray-700 hover:bg-gray-100 px-2 py-2 rounded flex items-center justify-center gap-1 transition-colors text-xs"
                >
                    <i class="fa-solid fa-eye"></i>
                    Editar
                </button>

                <button
                    @click="abrirModal({ aula, modo: 'reservar' })"
                    class="bg-green-50 text-green-600 hover:bg-green-100 px-2 py-2 rounded flex items-center justify-center gap-1 transition-colors text-xs"
                >
                    <i class="fa-solid fa-calendar-days"></i>
                    Reservar
                </button>
            </div>

        </div>
    </div>

    <!-- Modal que se abre para realizar acciones sobre el aula -->
    <Modal :show="showModal" @close="cerrarModal">
        <!-- Componente que muestra el contenido dinámico del aula según el modo (ver, editar, reservar) -->
        <AulaModalContent
            :aula="selectedAula"
            :modo="modoActual"
            @update="actualizarAula"
            @close="cerrarModal"
        />
    </Modal>
</template>

<script setup>
    import { ref } from 'vue';
    import Modal from '../Modal.vue';
    import AulaModalContent from './AulaModalContent.vue';

    // const reactivas para controlar la visibilidad del modal y el aula seleccionada
    const showModal = ref(false);
    const selectedAula = ref(null);
    const modoActual = ref('ver'); // 'ver', 'editar', 'reservar'

    // Función para abrir el modal con el aula seleccionada y el modo correspondiente
    const abrirModal = ({ aula, modo }) => {
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

    // Definimos las propiedades que el componente recibe desde su componente padre
    defineProps(['aula'])

    const getEstadoColor = (estado) => {
        // Asignamos un color específico para cada estado del aula
        const tipo = {
            disponible: 'bg-green-100 text-green-800',
            ocupado: 'bg-red-100 text-red-800',
            mantenimiento: 'bg-yellow-100 text-yellow-800'
        };
        return tipo[estado] || 'bg-gray-100 text-gray-800';
    }

    // Función para obtener el texto correspondiente al estado del aula
    const getEstadoTexto = (estado) =>{
        // Texto representativo para cada estado del aula
        const texto = {
            disponible: 'Disponible',
            ocupado: 'Ocupado',
            mantenimiento:'En mantenimiento'
        }
        return texto[estado] || estado;
    };
</script>
