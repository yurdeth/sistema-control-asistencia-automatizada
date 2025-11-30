//gestiona la lógica de modales y lightboxes de imágenes.
import { ref } from 'vue';

export function useModal() {
    // Estados principales
    const showModal = ref(false);
    const mostrarLightbox = ref(false);
    const imagenActual = ref(0);

    // Métodos para modal genérico
    const abrirModal = () => {
        showModal.value = true;
    };

    const cerrarModal = () => {
        showModal.value = false;
    };

    // Métodos para lightbox de imágenes
    const abrirLightbox = (index = 0) => {
        imagenActual.value = index;
        mostrarLightbox.value = true;
    };

    const cerrarLightbox = () => {
        mostrarLightbox.value = false;
        imagenActual.value = 0;
    };

    // Mostrar la siguiente imagen en el lightbox
    const imagenSiguiente = (totalImagenes) => {
        if (imagenActual.value < totalImagenes - 1) {
            imagenActual.value++;
        } else {
            imagenActual.value = 0;
        }
    };

    // Mostrar la imagen anterior en el lightbox
    const imagenAnterior = (totalImagenes) => {
        if (imagenActual.value > 0) {
            imagenActual.value--;
        } else {
            imagenActual.value = totalImagenes - 1;
        }
    };

    // Exportamos los estados y métodos
    return {
        showModal,
        mostrarLightbox,
        imagenActual,
        abrirModal,
        cerrarModal,
        abrirLightbox,
        cerrarLightbox,
        imagenSiguiente,
        imagenAnterior
    };
}
