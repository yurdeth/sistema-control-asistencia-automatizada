// gestiona la lógica de las solicitudes de aula.
import { ref, computed } from 'vue';

export function useSolicitudes() {
     // Estados principales
    const solicitudes = ref([]);
    const cargandoSolicitudes = ref(false);
    const busquedaSolicitud = ref('');
    const estadoSolicitudFiltro = ref('todas');

    // Computed: contar solicitudes pendientes
    const solicitudesPendientesCount = computed(() =>
        solicitudes.value.filter(s => s.estado === 'pendiente').length
    );

    // Computed: filtrar solicitudes por texto y estado
    const solicitudesFiltradas = computed(() => {
        let resultado = solicitudes.value;
        // Filtra por búsqueda de texto
        if (busquedaSolicitud.value) {
            const busqueda = busquedaSolicitud.value.toLowerCase();
            resultado = resultado.filter(s =>
                s.solicitante_nombre.toLowerCase().includes(busqueda) ||
                s.aula_nombre.toLowerCase().includes(busqueda) ||
                s.actividad.toLowerCase().includes(busqueda)
            );
        }

        // Filtra por estado
        if (estadoSolicitudFiltro.value !== 'todas') {
            resultado = resultado.filter(s => s.estado === estadoSolicitudFiltro.value);
        }

        return resultado;
    });

    // Métodos: cargar solicitudes
    const cargarSolicitudes = async () => {
        cargandoSolicitudes.value = true;
        try {
            // acá iria el endpoint real para solicitudes

            // Datos de ejemplo (TEMPORAL)
            solicitudes.value = [
                {
                    id: 1,
                    solicitante_nombre: 'Juan Pérez',
                    departamento: 'Ingeniería',
                    aula_nombre: 'Aula 101',
                    aula_codigo: 'A-101',
                    fecha: '2024-01-15',
                    hora_inicio: '08:00',
                    hora_fin: '10:00',
                    actividad: 'Clase de Programación',
                    estado: 'pendiente'
                },
                {
                    id: 2,
                    solicitante_nombre: 'María González',
                    departamento: 'Ciencias',
                    aula_nombre: 'Laboratorio 203',
                    aula_codigo: 'L-203',
                    fecha: '2024-01-16',
                    hora_inicio: '10:00',
                    hora_fin: '12:00',
                    actividad: 'Práctica de Laboratorio',
                    estado: 'pendiente'
                },
                {
                    id: 3,
                    solicitante_nombre: 'Carlos Ramírez',
                    departamento: 'Matemáticas',
                    aula_nombre: 'Aula 305',
                    aula_codigo: 'A-305',
                    fecha: '2024-01-14',
                    hora_inicio: '14:00',
                    hora_fin: '16:00',
                    actividad: 'Seminario de Álgebra',
                    estado: 'aprobada'
                }
            ];
        } catch (error) {
            console.error('Error al cargar solicitudes:', error);
            solicitudes.value = [];
        } finally {
            cargandoSolicitudes.value = false;
        }
    };

    // Aprobar solicitud
    const aprobarSolicitud = async (solicitud, recargarAulas) => {
        try {
            // Acá iria el endpoint

            // Actualizar localmente
            const index = solicitudes.value.findIndex(s => s.id === solicitud.id);
            if (index !== -1) {
                solicitudes.value[index].estado = 'aprobada';
            }

            alert('Solicitud aprobada exitosamente');

            // Recargar aulas si se proporciona la función
            if (recargarAulas) {
                await recargarAulas();
            }
        } catch (error) {
            console.error('Error al aprobar solicitud:', error);
            alert('Error al aprobar la solicitud');
        }
    };

     // Rechazar solicitud
    const rechazarSolicitud = async (solicitud) => {
        const motivo = prompt('Ingrese el motivo del rechazo (opcional):');

        try {
            //  endpoint

            // Actualizar localmente
            const index = solicitudes.value.findIndex(s => s.id === solicitud.id);
            if (index !== -1) {
                solicitudes.value[index].estado = 'rechazada';
            }

            alert('Solicitud rechazada');
        } catch (error) {
            console.error('Error al rechazar solicitud:', error);
            alert('Error al rechazar la solicitud');
        }
    };

    const verDetallesSolicitud = (solicitud) => {

        alert(
            `Detalles de la solicitud:
            Solicitante: ${solicitud.solicitante_nombre}
            Departamento: ${solicitud.departamento}
            Aula: ${solicitud.aula_nombre} (${solicitud.aula_codigo})
            Fecha: ${solicitud.fecha}
            Horario: ${solicitud.hora_inicio} - ${solicitud.hora_fin}
            Actividad: ${solicitud.actividad}
            Estado: ${solicitud.estado}`
        );
    };

    return {
        // Estados
        solicitudes,
        cargandoSolicitudes,
        busquedaSolicitud,
        estadoSolicitudFiltro,

        // Computed
        solicitudesPendientesCount,
        solicitudesFiltradas,

        // Métodos
        cargarSolicitudes,
        aprobarSolicitud,
        rechazarSolicitud,
        verDetallesSolicitud
    };
}
