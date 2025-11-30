// gestiona la lógica de reservas de aulas.
import { ref, computed } from 'vue';
import axios from 'axios';

export function useReservas() {
    // Estados principales
    const reservasRegistradas = ref([]);
    const cargandoReservas = ref(false);
    const busquedaReserva = ref('');
    const filtroFechaReserva = ref('');

    // Computed: filtrar reservas
    const reservasFiltradas = computed(() => {
        let resultado = reservasRegistradas.value;

        // Filtrar por búsqueda de texto
        if (busquedaReserva.value) {
            const busqueda = busquedaReserva.value.toLowerCase();
            resultado = resultado.filter(r =>
                r.aula_nombre.toLowerCase().includes(busqueda) ||
                (r.materia && r.materia.toLowerCase().includes(busqueda)) ||
                (r.profesor && r.profesor.toLowerCase().includes(busqueda))
            );
        }

        // Filtrar por fecha
        if (filtroFechaReserva.value) {
            resultado = resultado.filter(r => r.fecha === filtroFechaReserva.value);
        }

        return resultado;
    });

    // Computed: contar total de reservas
    const totalReservas = computed(() => reservasRegistradas.value.length);

    // Función auxiliar: mapear reservas desde la API
    const mapearReserva = (reserva) => ({
        id: reserva.id,
        aula_id: reserva.aula_id,
        aula_nombre: reserva.aula?.nombre || 'N/A',
        aula_codigo: reserva.aula?.codigo || 'N/A',
        fecha: reserva.fecha_clase,
        hora_inicio: reserva.horario?.hora_inicio || 'N/A',
        hora_fin: reserva.horario?.hora_fin || 'N/A',
        materia: reserva.horario?.materia?.nombre || null,
        profesor: reserva.horario?.profesor?.nombre || null,
        grupo: reserva.horario?.grupo?.nombre || null,
        estado: reserva.estado
    });

    // Cargar todas las reservas desde la API
    const cargarReservas = async () => {
        cargandoReservas.value = true;
        try {
            const response = await axios.get('/api/reservations/get/all', {
                headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
            });

            if (!response.data.success) {
                console.error('Error al cargar reservas:', response.data.message);
                reservasRegistradas.value = [];
                return;
            }

            reservasRegistradas.value = response.data.data.map(mapearReserva);
        } catch (error) {
            console.error('Error al cargar reservas:', error);
            reservasRegistradas.value = [];
        } finally {
            cargandoReservas.value = false;
        }
    };

    // Ver detalles de una reserva
    const verDetallesReserva = (reserva) => {
        const detalles = `Detalles de la reserva:

        Aula: ${reserva.aula_nombre} (${reserva.aula_codigo})
        Fecha: ${formatearFecha(reserva.fecha)}
        Horario: ${reserva.hora_inicio} - ${reserva.hora_fin}
        ${reserva.materia ? `Materia: ${reserva.materia}` : ''}
        ${reserva.profesor ? `Profesor: ${reserva.profesor}` : ''}
        ${reserva.grupo ? `Grupo: ${reserva.grupo}` : ''}`;

        alert(detalles);
    };

     // Cancelar reserva
    const cancelarReserva = async (reserva, recargarAulas) => {
        if (!confirm(`¿Está seguro que desea cancelar la reserva del aula ${reserva.aula_nombre}?`)) {
            return false;
        }

        try {
            const response = await axios.delete(`/api/reservations/delete/${reserva.id}`, {
                headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
            });

            if (!response.data.success) {
                alert(response.data.message || 'Error al cancelar la reserva');
                return false;
            }

            // Eliminar de la lista local
            const index = reservasRegistradas.value.findIndex(r => r.id === reserva.id);
            if (index !== -1) {
                reservasRegistradas.value.splice(index, 1);
            }

            alert('Reserva cancelada exitosamente');

            // Recargar aulas si se proporciona la función
            if (recargarAulas) {
                await recargarAulas();
            }

            return true;
        } catch (error) {
            console.error('Error al cancelar reserva:', error);
            const errorMessage = error.response?.data?.message || 'Error al cancelar la reserva';
            alert(errorMessage);
            return false;
        }
    };

    // Formatea la fecha a dd/mm/yyyy
    const formatearFecha = (fecha) => {
        const date = new Date(fecha + 'T00:00:00');
        return date.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };

    // Exportamos los estados, computed y métodos
    return {
        // Estados
        reservasRegistradas,
        cargandoReservas,
        busquedaReserva,
        filtroFechaReserva,

        // Computed
        reservasFiltradas,
        totalReservas,

        // Métodos
        cargarReservas,
        verDetallesReserva,
        cancelarReserva,
        formatearFecha
    };
}
