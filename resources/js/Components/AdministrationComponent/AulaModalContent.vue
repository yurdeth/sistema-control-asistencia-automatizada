<template>
    <!-- Contenedor principal del formulario-->
    <div class="p-6 space-y-4 ovrflow-y-auto max-h-screen">

        <!-- Bloque para visualizar los detalles del aula (modo ver) -->
        <template v-if="modo === 'ver'">
            <h2 class="text-xl font-bold mb-4 text-center text-gray-600"> {{ titulo }} </h2>

            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-3xl font-extralight text-gray-900 tracking-tight mb-3">{{ aula.nombre }}</h1>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-2 h-2 rounded-full transition-all"
                            :class="aula.estado === 'Activo' ? 'bg-gray-900 shadow-[0_0_8px_rgba(17,23,39,0.4)]' : 'bg-gray-400'"
                        />
                        <span class="text-sm text-gray-600 font-light">{{ aula.estado }}</span>
                    </div>
                </div>

                <div class="text-right">
                    <div class="text-3xl font-extralight text-gray-900">{{ aula.capacidad_pupitres }}</div>
                    <div class="text-xs text-gray-400 uppercase tracking-wider mt-2 font-light">Estudiantes</div>
                </div>
            </div>

            <!-- Línea divisoria -->
            <div class="border-t border-gray-100" />

            <!-- DATOS DEL AULA EN TARJETAS -->
            <div class="grid gap-4">
                <div class="p-3 bg-gray-50 rounded-lg shadow-sm">
                    <p class="text-gray-600 text-sm">Ubicación</p>
                    <p class="font-semibold text-gray-800">{{ aula.ubicacion }}</p>
                </div>

                <div class="p-3 bg-gray-50 rounded-lg shadow-sm">
                    <p class="text-gray-600 text-sm">Recursos</p>
                    <p class="font-semibold text-gray-800 text-gray-500 italic">
                        {{ aula.recurso || 'No hay recursos asignados' }}
                    </p>
                </div>
            </div>

            <!-- Sección de QR -->
            <div v-if="aula.qrCodeDataUrl" class="text-center">
                <label class="block text-sm font-medium">Codigo QR</label>
                <img
                    :src="aula.qrCodeDataUrl"
                    alt="Código QR"
                    class="mx-auto mb-2"
                />
                <p class="text-gray-600 text-sm">
                    Escanea para ver el aula:
                    <strong>{{ aula.nombre }}</strong>
                </p>
            </div>
            <div v-else class="text-center text-gray-400">
                <p class="text-sm">Generando código QR...</p>
            </div>

            <!-- GALERÍA DE IMÁGENES -->
            <div class="pt-2 border-t">
                <h3 class="text-lg font-semibold mb-2">Imágenes del aula</h3>
                <!-- Miniaturas -->
                <div class="overflow-x-auto scrollbar-hide">
                    <div class="flex gap-2">
                        <img
                            v-for="(foto, index) in aula.fotos"
                            :key="foto.id"
                            :src="foto.url"
                            alt="Aula"
                            class="w-20 h-20 object-cover cursor-pointer transition-all duration-300 border-2 border-transparent hover:border-gray-900 flex-shrink-0"
                            @click="abrirLightbox(index)"
                        />
                    </div>
                </div>
            </div>

            <!-- Título dinámico que se ajusta según el modo -->
            <!-- <h2 class="text-xl font-bold mb-4">
                {{ titulo }}
            </h2>
            <p><strong>Nombre:</strong> {{ aula.nombre }}</p>
            <p><strong>Capacidad:</strong> {{ aula.capacidad_pupitres }}</p>
            <p><strong>Ubicación:</strong> {{ aula.ubicacion }}</p>
            <p><strong>Recursos:</strong> {{ aula.recurso }}</p>
            <p><strong>Estado:</strong> {{ aula.estado }}</p>

            <p class="mt-4"><strong>Imágenes del aula:</strong></p> -->

            <!-- Grid de imágenes con scroll horizontal -->
            <!-- <div class="overflow-x-auto overflow-y-hidden">
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
            </div> -->

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
                    <label>Código</label>
                    <input v-model="form.codigo" class="input"/>
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
                        <option value="ocupada">Ocupada</option>
                        <option value="mantenimiento">Mantenimiento</option>
                        <option value="inactiva">Inactiva</option>
                    </select>
                </div>

                <!-- parte donde están los Botones para cancelar o guardar -->
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" class="btn" @click="$emit('close')">Cancelar</button>
                    <button type="button" class="btn btn-primary" @click="handleEditClassroomInfo()">Guardar</button>
                </div>
            </form>
        </template>

        <!-- Bloque para realizar una reserva (modo reservar) -->
        <template v-else-if="modo === 'reservar'">
            <form @submit.prevent="reservar">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Fecha</label>
                        <input type="date" v-model="reserva.fecha" class="input" :min="new Date().toISOString().split('T')[0]" required/>
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
    codigo: '',
    capacidad: '',
    ubicacion: '',
    recurso: '',
    estado: '',
});

/*watch(
    () => props.aula,
    (aula) => {
        if (aula) {
            Object.assign(form, aula);
        }
    },
    { immediate: true }
);*/

import { toDataURL } from 'qrcode';

watch(
    () => props.aula,
    async (aula) => {
        if (aula) {
            Object.assign(form, aula);

            // Generate QR code if not already present
            if (aula.qr_code && !aula.qrCodeDataUrl) {
                try {
                    // const baseUrl = window.location.origin;
                    const qrText = `${aula.qr_code}`;

                    aula.qrCodeDataUrl = await toDataURL(qrText, {
                        errorCorrectionLevel: 'H',
                        width: 250,
                        margin: 2,
                        color: {
                            dark: '#000000',
                            light: '#FFFFFF'
                        }
                    });

                    console.log(aula.qrCodeDataUrl);
                } catch (err) {
                    console.error('Error generating QR:', err);
                    aula.qrCodeDataUrl = '';
                }
            }
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

const handleEditClassroomInfo = () => {
    try {

        const body = JSON.stringify({
            nombre: form.nombre,
            codigo: form.codigo,
            capacidad_pupitres: form.capacidad_pupitres,
            ubicacion: form.ubicacion,
            estado: form.estado,
            recursos: form.recursos.map(recurso => recurso.id),
            imagen_url: form.imagen_url
        });

        axios.patch(`/api/classrooms/edit/${form.id}`, body, {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
        }}).then(response => {
            const data = response.data;
            console.log(data);

            if(!data.success){
                alert("Error al actualizar la información del aula: " + data.message);
                return;
            }

            alert("Información del aula actualizada correctamente.");
            emit('close');
            window.location.reload();
        });

    } catch (error) {
        console.error('Error updating classroom info:', error);
    }
}

const handleSendReservation = () => {
    try {

        const body = JSON.stringify({
            nombre: form.nombre,
            codigo: form.codigo,
            capacidad_pupitres: form.capacidad_pupitres,
            ubicacion: form.ubicacion,
            estado: form.estado,
            recursos: form.recursos.map(recurso => recurso.id),
            imagen_url: form.imagen_url
        });

        axios.patch(`/api/classrooms/edit/${form.id}`, body, {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`
        }}).then(response => {
            const data = response.data;
            console.log(data);

            if(!data.success){
                alert("Error al actualizar la información del aula: " + data.message);
                return;
            }

            alert("Información del aula actualizada correctamente.");
            emit('close');
            window.location.reload();
        });

    } catch (error) {
        console.error('Error updating classroom info:', error);
    }
}

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
