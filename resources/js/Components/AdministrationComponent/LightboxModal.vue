<template>
    <!-- Vista Normal del Lightbox -->
    <div class="bg-black bg-opacity-95 text-white flex flex-col items-center justify-center relative p-8 min-h-[600px]">
        <!-- Botón cerrar -->
        <button
            class="absolute top-6 right-6 w-12 h-12 flex items-center justify-center bg-white/10 hover:bg-white/20 transition-colors z-30 group"
            @click="$emit('close')"
        >
            <i class="fa-solid fa-xmark group-hover:rotate-90 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"></i>
        </button>

        <!-- Contador superior -->
        <div class="absolute top-6 left-6 text-white text-sm font-light z-10">
            {{ indiceActual + 1 }} / {{ imagenes.length }}
        </div>

        <!-- Imagen principal -->
        <div class="w-full max-w-5xl h-[500px] flex items-center justify-center relative bg-black/20">
            <img
                :src="imagenes[indiceActual]?.url"
                alt="Imagen del aula"
                class="max-w-full max-h-full object-contain cursor-zoom-in transition-opacity duration-300"
                @click="toggleFullscreen"
                ref="mainImage"
            />
        </div>

                <!-- Navegación con flechas laterales -->
        <button
            v-if="imagenes.length > 1"
            @click="anterior"
            class="absolute left-4 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white/10 hover:bg-white/20 transition-colors"
        >
            <i class="fa-solid fa-angle-left" :style="{fontSize:'30px'}"></i>
        </button>

        <button
            v-if="imagenes.length > 1"
            @click="siguiente"
            class="absolute right-4 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white/10 hover:bg-white/20 transition-colors"
        >
            <i class="fa-solid fa-angle-right" :style="{fontSize:'30px'}"></i>
        </button>

        <!-- Miniaturas -->
        <div v-if="imagenes.length > 1" class="flex gap-2 mt-6 overflow-x-auto pb-2 max-w-5xl scrollbar-hide">
            <img
                v-for="(foto, index) in imagenes"
                :key="foto.id"
                :src="foto.url"
                @click="indiceActual = index"
                class="w-16 h-16 object-cover cursor-pointer transition-all flex-shrink-0 grayscale hover:grayscale-0"
                :class="index === indiceActual
                    ? 'ring-2 ring-white scale-110 grayscale-0'
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
        <div class="flex items-center justify-center">
            <img
                :src="imagenes[indiceActual]?.url"
                alt="Imagen"
                class="w-[500px] h-[400px] object-cover cursor-zoom-out"
                @click="exitFullscreen"
            />
        </div>
        
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
