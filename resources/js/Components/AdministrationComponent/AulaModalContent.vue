<template>
    <!-- Contenedor principal del formulario-->
    <div class="p-6 space-y-4">

        <!-- Título dinámico que se ajusta según el modo -->
        <h2 class="text-xl font-bold mb-4">
            {{ titulo }}
        </h2>

        <!-- Bloque para visualizar los detalles del aula (modo ver) -->
        <template v-if="modo === 'ver'">
            <p><strong>Nombre:</strong> {{ aula.nombre }}</p>
            <p><strong>Capacidad:</strong> {{ aula.capacidad_pupitres }}</p>
            <p><strong>Ubicación:</strong> {{ aula.ubicacion }}</p>
            <p><strong>Recursos:</strong> {{ aula.recurso }}</p>
            <p><strong>Estado:</strong> {{ aula.estado }}</p>

            <p class="mt-4"><strong>Imágenes del aula:</strong></p>

            <!-- Grid de imágenes con scroll horizontal -->
            <div class="overflow-x-auto overflow-y-hidden">
                <div class="flex gap-2 pb-2">
                    <div
                        v-for="(foto, index) in aula.fotos"
                        :key="foto.id"
                        class="flex-shrink-0"
                    >
                        <img
                            :src="foto.url"
                            alt="Imagen del aula"
                            class="w-32 h-32 object-cover rounded cursor-pointer hover:opacity-80 transition-opacity border-2 border-gray-200 hover:border-blue-500"
                            @click="abrirLightbox(index)"
                        />
                    </div>
                </div>
            </div>

            <!-- Lightbox/Carrusel -->
            <Modal :show="mostrarLightbox" @close="cerrarLightbox">
                <LightboxModal
                    :imagenes="aula.fotos"
                    :indiceInicial="imagenActual"
                    @close="cerrarLightbox"
                />
            </Modal>
        </template>

        <!-- Bloque para editar el aula (modo editar) -->
        <template v-else-if="modo === 'editar'">
            <form @submit.prevent="guardar">
                <div>
                    <label>Nombre</label>
                    <input v-model="form.nombre" class="input"/>
                </div>

                <div>
                    <label>Capacidad</label>
                    <input type="number" v-model="form.capacidad_pupitres" class="input"/>
                </div>

                <div>
                    <label>Ubicación</label>
                    <input v-model="form.ubicacion" class="input"/>
                </div>

                <div>
                    <label>Recursos</label>
                    <textarea v-model="form.recurso" class="input"></textarea>
                </div>

                <div>
                    <label>Estado</label>
                    <select v-model="form.estado" class="input">
                        <option value="disponible">Disponible</option>
                        <option value="ocupado">Ocupado</option>
                        <option value="mantenimiento">Mantenimiento</option>
                    </select>
                </div>

                <!-- parte donde están los Botones para cancelar o guardar -->
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" class="btn" @click="$emit('close')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </template>

        <!-- Bloque para realizar una reserva (modo reservar) -->
        <template v-else-if="modo === 'reservar'">
            <form @submit.prevent="reservar">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Fecha</label>
                        <input type="date" v-model="reserva.fecha" class="input" required/>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Hora de inicio</label>
                        <input type="time" v-model="reserva.horaInicio" class="input" required/>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Hora de fin</label>
                        <input type="time" v-model="reserva.horaFin" class="input" required/>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Responsable</label>
                        <input type="text" v-model="reserva.responsable" class="input" required/>
                    </div>

                    <!-- Botones para cancelar o reservar -->
                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" class="btn" @click="$emit('close')">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Reservar</button>
                    </div>
                </div>
            </form>
        </template>

    </div>
</template>

<script setup>
import { computed, reactive, watch, ref, onMounted, onUnmounted } from 'vue';
import Modal from "@/Components/Modal.vue";
import LightboxModal from "@/Components/AdministrationComponent/LightboxModal.vue";


// Definimos las propiedades que recibe el componente
const props = defineProps({
    aula: Object,
    modo: String, // 'ver', 'editar', 'reservar'
});

const emit = defineEmits(['close', 'update']);

// =======| Lightbox y Carrusel |=======
const mostrarLightbox = ref(false);
const imagenActual = ref(0);

const abrirLightbox = (index) => {
    imagenActual.value = index;
    mostrarLightbox.value = true;
};
const cerrarLightbox = () => (mostrarLightbox.value = false);

const imagenSiguiente = () => {
    if (imagenActual.value < props.aula.fotos.length - 1) {
        imagenActual.value++;
    } else {
        imagenActual.value = 0;
    }
};

const imagenAnterior = () => {
    if (imagenActual.value > 0) {
        imagenActual.value--;
    } else {
        imagenActual.value = props.aula.fotos.length - 1;
    }
};

const handleKeydown = (e) => {
    if (!mostrarLightbox.value) return;

    if (e.key === 'Escape') cerrarLightbox();
    if (e.key === 'ArrowRight') imagenSiguiente();
    if (e.key === 'ArrowLeft') imagenAnterior();
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});

// =======| parte donde se trabaja el formulario de editar |=======
const form = reactive({
    nombre: '',
    capacidad: '',
    ubicacion: '',
    recurso: '',
    estado: '',
});

watch(
    () => props.aula,
    (aula) => {
        if (aula) {
            Object.assign(form, aula);
        }
    },
    { immediate: true }
);

const titulo = computed(() => {
    switch (props.modo) {
        case 'ver':
            return 'Detalles del Aula';
        case 'editar':
            return 'Editar Aula';
        case 'reservar':
            return 'Reservar Aula';
        default:
            return '';
    }
});

const guardar = () => {
    emit('update', { ...form });
};

// =======| parte donde se trabaja el formulario de reserva |=======
const reserva = reactive({
    fecha: '',
    horaInicio: '',
    horaFin: '',
    responsable: '',
});

const reservar = () => {
    const datosReserva = {
        aulaId: props.aula?.id,
        ...reserva,
    };

    emit('close');
};

</script>

<style scoped>
.input {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid #ccc;
    border-radius: 0.375rem;
}

.btn {
    padding: 0.5rem 1rem;
}

.btn-primary {
    background-color: #2563eb;
    color: white;
    border-radius: 8px;
}

/* Asegurar que el lightbox esté por encima de todo */
:deep(.lightbox-container) {
    z-index: 9999 !important;
}
</style>
