<template>
    <div class="bg-black bg-opacity-90 text-white flex flex-col items-center justify-center relative p-6">
        <!-- Botón cerrar -->
        <button
            class="absolute top-4 right-4 text-white text-4xl hover:text-gray-400"
            @click="$emit('close')"
        >
            &times;
        </button>

        <!-- Imagen principal -->
        <div class="max-w-4xl max-h-[80vh] flex items-center justify-center">
            <img
                :src="imagenes[indiceActual]?.url"
                alt="Imagen del aula"
                class="w-full h-full object-contain rounded-lg"
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
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    imagenes: Array,
    indiceInicial: Number
});

const emit = defineEmits(['close']);
const indiceActual = ref(props.indiceInicial || 0);

const siguiente = () => {
    indiceActual.value = (indiceActual.value + 1) % props.imagenes.length;
};
const anterior = () => {
    indiceActual.value =
        (indiceActual.value - 1 + props.imagenes.length) % props.imagenes.length;
};

// Teclado
const handleKey = (e) => {
    if (e.key === 'Escape') emit('close');
    if (e.key === 'ArrowRight') siguiente();
    if (e.key === 'ArrowLeft') anterior();
};

onMounted(() => window.addEventListener('keydown', handleKey));
onUnmounted(() => window.removeEventListener('keydown', handleKey));
</script>
