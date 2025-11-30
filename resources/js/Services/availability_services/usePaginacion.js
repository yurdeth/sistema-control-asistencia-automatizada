// gestiona la lógica de paginación para cualquier conjunto de datos.
import { ref, computed } from 'vue';

export function usePaginacion(items, itemsPorPagina = 10) {
    // Estado principal
    const paginaActual = ref(1);

     // Computed: cálculos para la paginación

    // Total de páginas según cantidad de elementos e items por página
    const totalPaginas = computed(() =>
        Math.ceil(items.value.length / itemsPorPagina)
    );

    // Índice de inicio para el slice de items
    const indiceInicio = computed(() =>
        (paginaActual.value - 1) * itemsPorPagina
    );

    // Índice de fin para el slice de items
    const indiceFin = computed(() =>
        paginaActual.value * itemsPorPagina
    );

    // Items visibles en la página actual
    const itemsPaginados = computed(() =>
        items.value.slice(indiceInicio.value, indiceFin.value)
    );

    // Páginas visibles en la barra de paginación (máx. 5)
    const paginasVisibles = computed(() => {
        const paginas = [];
        const maxPaginas = 5;
        let inicio = Math.max(1, paginaActual.value - 2);
        let fin = Math.min(totalPaginas.value, inicio + maxPaginas - 1);

        if (fin - inicio < maxPaginas - 1) {
            inicio = Math.max(1, fin - maxPaginas + 1);
        }

        for (let i = inicio; i <= fin; i++) {
            paginas.push(i);
        }
        return paginas;
    });

     // Métodos de navegación

    // Ir a una página específica
    const irAPagina = (pagina) => {
        if (pagina >= 1 && pagina <= totalPaginas.value) {
            paginaActual.value = pagina;
        }
    };

     // Ir a la página anterior
    const paginaAnterior = () => {
        if (paginaActual.value > 1) {
            paginaActual.value--;
        }
    };

    // Ir a la página siguiente
    const paginaSiguiente = () => {
        if (paginaActual.value < totalPaginas.value) {
            paginaActual.value++;
        }
    };

    // Resetear a la primera página
    const resetearPagina = () => {
        paginaActual.value = 1;
    };

    // Exportamos los estados, computed y métodos
    return {
        paginaActual,
        totalPaginas,
        indiceInicio,
        indiceFin,
        itemsPaginados,
        paginasVisibles,
        irAPagina,
        paginaAnterior,
        paginaSiguiente,
        resetearPagina
    };
}
