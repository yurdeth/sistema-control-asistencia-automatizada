// gestiona la información de las aulas en la aplicación.
import { ref, computed } from 'vue';
import axios from 'axios';

export function useAulas() {
    // Estados principales
    const todasLasAulas = ref([]);
    const aulas = ref([]);
    const cargando = ref(false);
    const busquedaAula = ref('');
    const capacidadSeleccionada = ref('all');
    const estadoFiltro = ref('disponibles');

    // Computed: aulas filtradas según criterios
    const aulasFiltradas = computed(() => {
        let resultado = aulas.value;

        // Filtrado por estado
        if (estadoFiltro.value === 'disponibles') {
            resultado = resultado.filter(a => a.disponible);
        } else if (estadoFiltro.value === 'ocupadas') {
            resultado = resultado.filter(a => !a.disponible);
        }

        // Filtrado por búsqueda de texto
        if (busquedaAula.value) {
            const busqueda = busquedaAula.value.toLowerCase();
            resultado = resultado.filter(a =>
                a.nombre.toLowerCase().includes(busqueda) ||
                a.codigo.toLowerCase().includes(busqueda) ||
                a.sector.toLowerCase().includes(busqueda)
            );
        }

        // Filtrado por capacidad
        if (capacidadSeleccionada.value !== 'all') {
            resultado = resultado.filter(a => {
                const capacidad = a.capacidad_pupitres || 0;
                switch (capacidadSeleccionada.value) {
                    case 'pequena': return capacidad >= 1 && capacidad <= 30;
                    case 'mediana': return capacidad >= 31 && capacidad <= 70;
                    case 'grande': return capacidad >= 71 && capacidad <= 100;
                    case 'muy-grande': return capacidad > 100;
                    default: return true;
                }
            });
        }
        return resultado;
    });

    // Computed: contar aulas disponibles y ocupadas
    const aulasDisponiblesCount = computed(() =>
        todasLasAulas.value.filter(a => a.estado === 'disponible').length
    );

    const aulasOcupadasCount = computed(() =>
        todasLasAulas.value.filter(a => a.estado === 'ocupada' || a.estado === 'ocupado').length
    );

    // Mapear aula
    const mapearAula = (classroom) => ({
        id: classroom.id,
        nombre: classroom.nombre,
        codigo: classroom.codigo,
        sector: classroom.sector ?? '',
        capacidad_pupitres: classroom.capacidad_pupitres,
        ubicacion: classroom.ubicacion,
        estado: classroom.estado,
        recurso: classroom.recurso,
        fotos: classroom.fotos || [],
        disponible: classroom.estado === 'disponible'
    });

    // ===========| Métodos: consumo de API |===========

    // Obtenemos todas las aulas
    const fetchAllClassrooms = async () => {
        try {
            const response = await axios.get("/api/classrooms/get/all", {
                headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
            });

            if (!response.data.success) {
                alert("No se pudieron cargar las aulas");
                console.debug(response.data.message);
                return [];
            }

            return response.data.data;
        } catch (error) {
            console.error('Error fetching all classrooms:', error);
            return [];
        }
    };

     // Obtenemos todas las aulas disponibles
    const fetchAllAvailableClassrooms = async () => {
        try {
            const response = await axios.get("/api/classrooms/get/available/all", {
                headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
            });

            if (!response.data.success) {
                alert("No se encontraron aulas disponibles");
                console.debug(response.data.message);
                return [];
            }

            return response.data.data;
        } catch (error) {
            console.error('Error fetching available classrooms:', error);
            return [];
        }
    };

    // Obtenemos la información de un aula específica
    const fetchAula = async (id) => {
        try {
            const response = await axios.get(`/api/classrooms/get/${id}`, {
                headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
            });

            if (!response.data.success) {
                alert("No se pudo obtener la información del aula");
                console.debug(response.data.message);
                return null;
            }

            return response.data.data;
        } catch (error) {
            console.error('Error fetching classroom:', error);
            const errorMessage = error.response?.data?.message || "Error al obtener el aula";
            alert(errorMessage);
            return null;
        }
    };

    // Recargamos todas las aulas y las disponibles
    const recargarAulas = async () => {
        try {
            cargando.value = true;
            console.log('Recargando aulas...');

            const todasResponse = await fetchAllClassrooms();
            todasLasAulas.value = todasResponse.map(mapearAula);

            const disponiblesResponse = await fetchAllAvailableClassrooms();
            aulas.value = disponiblesResponse.map(c => ({
                ...mapearAula(c),
                disponible: true
            }));

            console.log('Aulas recargadas');
        } catch (error) {
            console.error('Error al recargar aulas:', error);
        } finally {
            cargando.value = false;
        }
    };

    // Libera aula (marcar como disponible)
    const liberarAula = async (aula) => {
        try {


            // Por ahora solo actualiza localmente
            const index = aulas.value.findIndex(a => a.id === aula.id);
            if (index !== -1) {
                aulas.value[index].disponible = true;
                aulas.value[index].estado = 'disponible';
            }

            alert('Aula liberada exitosamente');
            console.log('Aula liberada:', aula);

            // Recargar para sincronizar con el servidor
            await recargarAulas();
        } catch (error) {
            console.error('Error al liberar aula:', error);
            alert('Error al liberar el aula');
        }
    };

    // Cargar aulas iniciales al iniciar la vista
    const cargarAulasIniciales = async () => {
        try {
            cargando.value = true;

            const todasResponse = await fetchAllClassrooms();
            todasLasAulas.value = todasResponse.map(mapearAula);

            const disponiblesResponse = await fetchAllAvailableClassrooms();
            aulas.value = disponiblesResponse.map(c => ({
                ...mapearAula(c),
                disponible: true
            }));
        } catch (error) {
            console.error('Error al cargar aulas iniciales:', error);
            todasLasAulas.value = [];
            aulas.value = [];
        } finally {
            cargando.value = false;
        }
    };

    // Exportamos los estados, computed y métodos
    return {
        // Estados
        todasLasAulas,
        aulas,
        cargando,
        busquedaAula,
        capacidadSeleccionada,
        estadoFiltro,

        // Computed
        aulasFiltradas,
        aulasDisponiblesCount,
        aulasOcupadasCount,

        // Métodos
        fetchAllClassrooms,
        fetchAllAvailableClassrooms,
        fetchAula,
        recargarAulas,
        liberarAula,
        cargarAulasIniciales,
        mapearAula
    };
}
