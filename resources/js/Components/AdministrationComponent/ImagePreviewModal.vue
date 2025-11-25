<template>
  <Teleport to="body">
    <div
      v-if="visible"
      data-modal="image-preview"
      class="image-preview-overlay"
      @click="closeOnBackdrop"
    >
      <div class="image-preview-container" @click.stop>
      <!-- Botón cerrar -->
      <button @click="close" class="image-preview-close" title="Cerrar (ESC)">
        ×
      </button>

      <!-- Contenedor de imagen -->
      <div class="image-preview-image-container">
        <!-- Imagen principal -->
        <img
          :src="currentImage.url"
          :alt="currentImage.name"
          class="image-preview-main-image"
        />

        <!-- Overlay con controles -->
        <div class="image-preview-controls">
          <!-- Botón anterior -->
          <button
            v-if="images.length > 1"
            @click="previous"
            class="image-preview-nav-btn"
            title="Imagen anterior"
          >
            <i class="fa-solid fa-chevron-left"></i>
          </button>

          <!-- Indicador -->
          <div class="image-preview-indicator">
            {{ currentIndex + 1 }} / {{ images.length }}
          </div>

          <!-- Botón siguiente -->
          <button
            v-if="images.length > 1"
            @click="next"
            class="image-preview-nav-btn"
            title="Siguiente imagen"
          >
            <i class="fa-solid fa-chevron-right"></i>
          </button>
        </div>
      </div>

      <!-- Miniaturas -->
      <div v-if="images.length > 1" class="image-preview-thumbnails">
        <div
          v-for="(image, index) in images"
          :key="index"
          :class="[
            'image-preview-thumbnail-wrapper',
            index === currentIndex ? 'active' : ''
          ]"
        >
          <button
            @click="goToImage(index)"
            class="image-preview-thumbnail"
          >
            <img
              :src="image.url"
              :alt="`Miniatura ${index + 1}`"
              class="thumbnail-img"
            />
          </button>
          <button
            @click="removeImage(index)"
            class="thumbnail-delete-btn"
            title="Eliminar imagen"
          >
            <i class="fa-solid fa-times"></i>
          </button>
        </div>
      </div>

      <!-- Información del archivo -->
      <div class="image-preview-info">
        <p class="image-info-name">{{ currentImage.name }}</p>
        <p class="image-info-size">{{ formatFileSize(currentImage.size) }}</p>
      </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted, watch, nextTick } from 'vue';

const props = defineProps({
  visible: {
    type: Boolean,
    required: true
  },
  images: {
    type: Array,
    required: true
  },
  initialIndex: {
    type: Number,
    default: 0
  }
});

const emit = defineEmits(['close', 'remove-image']);

// Calcular el z-index más alto en la página
const getHighestZIndex = () => {
  const elements = Array.from(document.querySelectorAll('*'));
  let highest = 0;

  elements.forEach(el => {
    const styles = window.getComputedStyle(el);
    const zIndex = parseInt(styles.zIndex);
    if (zIndex > highest) {
      highest = zIndex;
    }
  });

  return highest;
};

// Forzar el z-index más alto
const forceHighestZIndex = () => {
  nextTick(() => {
    const modal = document.querySelector('[data-modal="image-preview"]');
    if (modal) {
      const highestZ = getHighestZIndex();
      const newZ = Math.max(highestZ + 100, 999999);
      modal.style.zIndex = newZ.toString();

      // Aplicar también a los hijos
      const container = modal.querySelector('.image-preview-container');
      if (container) {
        container.style.zIndex = (newZ + 1).toString();
      }

      const closeButton = modal.querySelector('.image-preview-close');
      if (closeButton) {
        closeButton.style.zIndex = (newZ + 2).toString();
      }

      console.log(`Z-index forzado a: ${newZ} (más alto era: ${highestZ})`);
    }
  });
};

// Watch para debug cuando el modal se hace visible
watch(() => props.visible, async (newValue) => {
  if (newValue) {
    console.log('Modal se está mostrando');
    await nextTick();

    // Forzar z-index más alto
    forceHighestZIndex();

    // Prevenir scroll del body
    document.body.style.overflow = 'hidden';

    // Logs de depuración
    logDebugInfo();
  } else {
    console.log('Modal se está ocultando');
    document.body.style.overflow = '';
  }
});

// Logs de depuración
const logDebugInfo = () => {
  console.log('=== DEBUG ImagePreviewModal ===');
  console.log('Visible:', props.visible);
  console.log('Images:', props.images);
  console.log('Current Index:', currentIndex.value);
  console.log('Current Image:', currentImage.value);

  // Verificar si el modal está en el DOM
  const modalElement = document.querySelector('[data-modal="image-preview"]');
  if (modalElement) {
    console.log('Modal encontrado en DOM:', modalElement);

    // Verificar contenido del modal
    const container = modalElement.querySelector('.image-preview-container');
    console.log('Container:', container);

    const imageElement = modalElement.querySelector('.image-preview-main-image');
    console.log('Image element:', imageElement);
    if (imageElement) {
      console.log('Image src:', imageElement.src);
      console.log('Image loaded:', imageElement.complete);
    }

    // Verificar hijos del modal
    console.log('Modal children:', Array.from(modalElement.children));

    const styles = window.getComputedStyle(modalElement);
    console.log('Computed styles:');
    console.log('- z-index:', styles.zIndex);
    console.log('- position:', styles.position);
    console.log('- display:', styles.display);
    console.log('- visibility:', styles.visibility);
    console.log('- opacity:', styles.opacity);

    // Verificar posición
    const rect = modalElement.getBoundingClientRect();
    console.log('Bounding rectangle:', rect);

    // Verificar overflow de body
    console.log('Body overflow:', window.getComputedStyle(document.body).overflow);

  } else {
    console.log('Modal NO encontrado en DOM');
  }

  // Verificar elementos con z-index alto
  const highZIndexElements = [];
  document.querySelectorAll('*').forEach(el => {
    const styles = window.getComputedStyle(el);
    const zIndex = parseInt(styles.zIndex);
    if (zIndex > 100) {
      highZIndexElements.push({ element: el, zIndex, position: styles.position });
    }
  });
  console.log('Elementos con z-index > 100:', highZIndexElements);

  console.log('=== FIN DEBUG ===');
};

const currentIndex = ref(props.initialIndex);

// Imagen actual
const currentImage = computed(() => {
  return props.images[currentIndex.value] || props.images[0] || {};
});

// Navegación
const next = () => {
  if (props.images.length > 1) {
    currentIndex.value = (currentIndex.value + 1) % props.images.length;
  }
};

const previous = () => {
  if (props.images.length > 1) {
    currentIndex.value = currentIndex.value === 0 ? props.images.length - 1 : currentIndex.value - 1;
  }
};

const goToImage = (index) => {
  currentIndex.value = index;
};

// Eliminar imagen
const removeImage = (index) => {
  emit('remove-image', index);

  // Si eliminamos la imagen actual, ajustar el índice
  if (index === currentIndex.value && currentIndex.value > 0) {
    currentIndex.value--;
  } else if (index < currentIndex.value) {
    currentIndex.value--;
  }
};

// Cerrar modal
const close = () => {
  emit('close');
};

// Cerrar al hacer click en el fondo
const closeOnBackdrop = (event) => {
  if (event.target === event.currentTarget) {
    close();
  }
};

// Formatear tamaño de archivo
const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Manejo de teclado
const handleKeydown = (event) => {
  if (!props.visible) return;

  switch (event.key) {
    case 'Escape':
      close();
      break;
    case 'ArrowRight':
      event.preventDefault();
      next();
      break;
    case 'ArrowLeft':
      event.preventDefault();
      previous();
      break;
  }
};

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
  // Prevenir scroll del body
  document.body.style.overflow = 'hidden';
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
  // Restaurar scroll del body
  document.body.style.overflow = '';
});

// Actualizar índice cuando cambia el prop
watch(() => props.initialIndex, (newIndex) => {
  currentIndex.value = newIndex;
});

// Watch para debug cuando el modal se hace visible
watch(() => props.visible, async (newValue) => {
  if (newValue) {
    console.log('Modal se está mostrando');
    await nextTick();
    logDebugInfo();
  } else {
    console.log('Modal se está ocultando');
  }
});
</script>

<style>
/* Estilos simplificados - solo un modal activo a la vez */
.image-preview-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100vw;
  height: 100vh;
  background-color: rgba(0, 0, 0, 0.9);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0;
  padding: 16px;
  box-sizing: border-box;
  z-index: 99999;
}

.image-preview-container {
  position: relative;
  max-width: 90vw;
  max-height: 90vh;
  width: 100%;
  height: auto;
  background: transparent;
  display: flex;
  flex-direction: column;
  z-index: 100000;
}

.image-preview-close {
  position: absolute;
  top: -48px;
  right: 16px;
  color: white;
  font-size: 32px;
  background: none;
  border: none;
  cursor: pointer;
  z-index: 100001;
  padding: 8px;
  border-radius: 4px;
  transition: color 0.2s;
}

.image-preview-close:hover {
  color: #ccc;
  background: rgba(255, 255, 255, 0.1);
}

.image-preview-image-container {
  position: relative;
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 8px;
  overflow: hidden;
  min-height: 500px;
  max-width: 100%;
}

.image-preview-main-image {
  max-width: 90%;
  max-height: 90%;
  object-fit: contain;
  display: block;
  border-radius: 4px;
}

.image-preview-controls {
  position: absolute;
  top: 50%;
  left: 0;
  right: 0;
  display: flex;
  justify-content: space-between;
  align-items: center;
  pointer-events: none;
  z-index: 10;
  transform: translateY(-50%);
}

.image-preview-nav-btn {
  background: rgba(255, 255, 255, 0.1);
  color: rgba(255, 255, 255, 0.8);
  border: 1px solid rgba(255, 255, 255, 0.2);
  padding: 8px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 14px;
  pointer-events: auto;
  transition: all 0.2s;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 20px;
  backdrop-filter: blur(4px);
}

.image-preview-nav-btn:hover {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  transform: scale(1.05);
}

.image-preview-indicator {
  background: rgba(0, 0, 0, 0.6);
  color: rgba(255, 255, 255, 0.9);
  padding: 6px 12px;
  border-radius: 12px;
  font-size: 12px;
  font-weight: 400;
  backdrop-filter: blur(4px);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

.image-preview-thumbnails {
  display: flex;
  gap: 8px;
  justify-content: center;
  margin-top: 12px;
  overflow-x: auto;
  padding: 8px 0;
}

.image-preview-thumbnail-wrapper {
  position: relative;
  flex-shrink: 0;
  transition: all 0.2s;
}

.image-preview-thumbnail {
  width: 48px;
  height: 48px;
  border: 2px solid rgba(255, 255, 255, 0.2);
  border-radius: 6px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.2s;
  background: rgba(255, 255, 255, 0.05);
  padding: 0;
  opacity: 0.7;
  display: block;
}

.image-preview-thumbnail-wrapper.active .image-preview-thumbnail {
  border-color: rgba(255, 255, 255, 0.8);
  opacity: 1;
  transform: scale(1.1);
  box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.3);
}

.image-preview-thumbnail:hover {
  opacity: 0.9;
  border-color: rgba(255, 255, 255, 0.5);
  transform: scale(1.05);
}

.thumbnail-delete-btn {
  position: absolute;
  top: -6px;
  right: -6px;
  width: 20px;
  height: 20px;
  background: rgba(239, 68, 68, 0.9);
  color: white;
  border: 2px solid rgba(255, 255, 255, 0.8);
  border-radius: 50%;
  cursor: pointer;
  display: none;
  align-items: center;
  justify-content: center;
  font-size: 10px;
  padding: 0;
  transition: all 0.2s;
  z-index: 10;
  backdrop-filter: blur(4px);
}

.image-preview-thumbnail-wrapper:hover .thumbnail-delete-btn {
  display: flex;
}

.thumbnail-delete-btn:hover {
  background: rgba(220, 38, 38, 0.95);
  transform: scale(1.1);
}

.thumbnail-img {
  width: 100% !important;
  height: 100% !important;
  object-fit: cover !important;
  display: block !important;
}

.image-preview-info {
  text-align: center !important;
  margin-top: 16px !important;
  color: white !important;
}

.image-info-name {
  font-size: 14px;
  font-weight: 400;
  margin: 0 0 2px 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  color: rgba(255, 255, 255, 0.9);
}

.image-info-size {
  font-size: 12px;
  color: rgba(255, 255, 255, 0.6);
  margin: 0;
}

/* Scroll para miniaturas */
.image-preview-thumbnails::-webkit-scrollbar {
  height: 6px !important;
}

.image-preview-thumbnails::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.1) !important;
  border-radius: 3px !important;
}

.image-preview-thumbnails::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3) !important;
  border-radius: 3px !important;
}

.image-preview-thumbnails::-webkit-scrollbar-thumb:hover {
  background: rgba(255, 255, 255, 0.5) !important;
}
</style>