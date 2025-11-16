<template>
    <div class="bg-black bg-opacity-90 text-white flex flex-col items-center justify-center relative p-6">
        <!-- Botón cerrar -->
        <button
            class="absolute top-4 right-4 text-white text-4xl hover:text-gray-400 z-30"
            @click="$emit('close')"
        >
            &times;
        </button>

        <!-- Imagen principal -->
        <div class="max-w-4xl max-h-[80vh] flex items-center justify-center relative group">
            <img
                :src="imagenes[indiceActual]?.url"
                alt="Imagen del aula"
                class="w-full h-full object-contain rounded-lg cursor-default group-hover:cursor-zoom-in"
                @click="toggleFullscreen"
                ref="mainImage"
            />
        </div>

        <!-- Controles -->
        <div class="flex items-center justify-center mt-4 gap-4">
            <button
                v-if="imagenes.length > 1"
                @click="anterior"
                class="text-5xl hover:text-gray-400"
            >&#8249;
            </button>

            <span class="text-lg font-semibold">{{ indiceActual + 1 }} / {{ imagenes.length }}</span>

            <button
                v-if="imagenes.length > 1"
                @click="siguiente"
                class="text-5xl hover:text-gray-400"
            >&#8250;
            </button>
        </div>

        <!-- Miniaturas -->
        <div v-if="imagenes.length > 1" class="flex gap-2 mt-4 overflow-x-auto pb-2">
            <img
                v-for="(foto, index) in imagenes"
                :key="foto.id"
                :src="foto.url"
                @click="indiceActual = index"
                class="w-20 h-20 object-cover rounded cursor-pointer transition-all"
                :class="index === indiceActual
          ? 'ring-2 ring-white scale-110'
          : 'opacity-60 hover:opacity-100'"
            />
        </div>
    </div>

    <!-- Modo pantalla completa real -->
    <div
        v-if="isFullscreen"
        class="fixed inset-0 bg-black flex items-center justify-center z-[9999] cursor-pointer"
        @click.self="exitFullscreen"
    >
        <!-- Imagen a pantalla completa completa -->
        <img
            :src="imagenes[indiceActual]?.url"
            alt="Imagen del aula en pantalla completa"
            class="w-screen h-screen object-contain cursor-zoom-out"
            @click="exitFullscreen"
            ref="fullscreenImage"
        />

      
        <!-- Flechas de navegación -->
        <button
            v-if="imagenes.length > 1"
            @click.stop="siguiente"
            class="absolute right-8 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-4 rounded-full hover:bg-opacity-70 transition-all"
            title="Siguiente imagen"
        >
            <i class="fa-solid fa-chevron-right text-xl"></i>
        </button>

        <button
            v-if="imagenes.length > 1"
            @click.stop="anterior"
            class="absolute left-8 top-1/2 transform -translate-y-1/2 bg-black bg-opacity-50 text-white p-4 rounded-full hover:bg-opacity-70 transition-all"
            title="Imagen anterior"
        >
            <i class="fa-solid fa-chevron-left text-xl"></i>
        </button>

        <!-- Indicador de imágenes -->
        <div
            v-if="imagenes.length > 1"
            class="absolute bottom-8 left-1/2 transform -translate-x-1/2 bg-black bg-opacity-50 text-white px-4 py-2 rounded-full"
        >
            {{ indiceActual + 1 }} / {{ imagenes.length }}
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    imagenes: Array,
    indiceInicial: Number
});

const emit = defineEmits(['close']);
const indiceActual = ref(props.indiceInicial || 0);
const mainImage = ref(null);
const fullscreenImage = ref(null);
const isFullscreen = ref(false);

const siguiente = () => {
    indiceActual.value = (indiceActual.value + 1) % props.imagenes.length;
};

const anterior = () => {
    indiceActual.value =
        (indiceActual.value - 1 + props.imagenes.length) % props.imagenes.length;
};

// Función para activar/desactivar pantalla completa
const toggleFullscreen = () => {
    if (!isFullscreen.value) {
        enterFullscreen();
    } else {
        exitFullscreen();
    }
};

const enterFullscreen = () => {
    // Prevenir scroll del body
    document.body.style.overflow = 'hidden';
    isFullscreen.value = true;
};

const exitFullscreen = () => {
    // Restaurar scroll del body
    document.body.style.overflow = '';
    isFullscreen.value = false;
};

// Teclado
const handleKey = (e) => {
    if (e.key === 'Escape') {
        if (isFullscreen.value) {
            exitFullscreen();
        } else {
            emit('close');
        }
    }
    if (e.key === 'ArrowRight') siguiente();
    if (e.key === 'ArrowLeft') anterior();
};

onMounted(() => {
    window.addEventListener('keydown', handleKey);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKey);

    // Restaurar scroll y salir de pantalla completa si el componente se desmonta
    if (isFullscreen.value) {
        document.body.style.overflow = '';
        isFullscreen.value = false;
    }
});
</script>
