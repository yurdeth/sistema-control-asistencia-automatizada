//gestiona toda la lógica relacionada con la asignación de aulas y la creación de reservas de clases
import { ref } from 'vue';
import axios from 'axios';

export function useAsignacionAula() {
    // Estados principales
    const aula = ref({});
    const selectedSubject = ref('');
    const selectedGroup = ref('');
    const selectedResponsible = ref('');
    const selectedSchedule = ref('');
    const selectedDate = ref('');

    const subjects = ref([]);
    const groups = ref([]);
    const responsible = ref({});
    const schedules = ref([]);

    const assignClassrooms = ref(false);

    // Obtiene los grupos por materia
    const fetchGroups = async (subjectId) => {
        try {
            const response = await axios.get(`/api/groups/get/subject/${subjectId}`, {
                headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
            });

            if (!response.data.success) {
                alert(response.data.message || "Error al obtener grupos");
                groups.value = [];
                return;
            }

            groups.value = response.data.data;
        } catch (error) {
            console.error('Error fetching groups:', error);
            const errorMessage = error.response?.data?.message || "Error al obtener grupos";
            alert(errorMessage);
            groups.value = [];
        }
    };

    // Obtiene el profesor responsable según materia y grupo
    const fetchSubjectProfessors = async (subjectId) => {
        try {
            const response = await axios.post('/api/groups/get/professor/',
                {
                    grupo_id: selectedGroup.value,
                    materia_id: subjectId
                },
                {
                    headers: {
                        'Authorization': `Bearer ${localStorage.getItem('token')}`,
                        'Content-Type': 'application/json'
                    }
                }
            );

            if (!response.data.success) {
                alert("No se encontraron profesores");
                responsible.value = {};
                return;
            }

            responsible.value = response.data.data;
        } catch (error) {
            console.error('Error fetching professors:', error);
            responsible.value = {};
        }
    };

    // Obtiene los horarios por grupo
    const fetchSchedules = async (groupId) => {
        try {
            const response = await axios.get(`/api/schedules/get/group/${groupId}`, {
                headers: { 'Authorization': `Bearer ${localStorage.getItem('token')}` }
            });

            if (!response.data.success) {
                alert(response.data.message || "Error al obtener los horarios");
                schedules.value = [];
                return;
            }

            schedules.value = response.data.data;
        } catch (error) {
            console.error('Error fetching schedules:', error);
            const errorMessage = error.response?.data?.message || "Error al obtener horarios";
            alert(errorMessage);
            schedules.value = [];
        }
    };

    // Envia la reserva del aula
    const sendReservation = async (onSuccess) => {
        // Validaciones
        if (!aula.value?.id) {
            alert("No se ha seleccionado un aula válida");
            return false;
        }

        if (!selectedSchedule.value) {
            alert("Debe seleccionar un horario");
            return false;
        }

        if (!selectedDate.value) {
            alert("Debe seleccionar una fecha");
            return false;
        }

        try {
            const payload = {
                horario_id: selectedSchedule.value,
                fecha_clase: selectedDate.value
            };

            const response = await axios.post('/api/class-sessions/new', payload, {
                headers: {
                    'Authorization': `Bearer ${localStorage.getItem('token')}`,
                    'Content-Type': 'application/json'
                }
            });

            if (!response.data.success) {
                alert(response.data.message || "No se pudo crear la reserva");
                return false;
            }

            alert("Reserva creada exitosamente");

            // Callback para refrescar datos externamente
            if (onSuccess) {
                await onSuccess();
            }

            resetForm();
            return true;
        } catch (error) {
            console.error('Error creating reservation:', error);
            // Manejo de errores de validación
            if (error.response?.data?.errors) {
                const errores = Object.values(error.response.data.errors).flat().join('\n');
                alert(`Errores de validación:\n${errores}`);
            } else if (error.response?.data?.message) {
                alert(error.response.data.message);
            } else {
                alert("Ocurrió un error al crear la reserva");
            }
            return false;
        }
    };

    // Reinicia el formulario
    const resetForm = () => {
        aula.value = {};
        subjects.value = [];
        groups.value = [];
        schedules.value = [];
        responsible.value = {};
        selectedSubject.value = '';
        selectedGroup.value = '';
        selectedResponsible.value = '';
        selectedSchedule.value = '';
        selectedDate.value = '';
        assignClassrooms.value = false;
    };

    // Abre el modal de asignación
    const openAssignClassroomModal = (aulaData) => {
        aula.value = aulaData;
        assignClassrooms.value = true;
    };

    // Cierra el modal y reinicia todo
    const closeModal = () => {
        resetForm();
    };

    // Exporta los estados y métodos
    return {
        //Estados
        aula,
        selectedSubject,
        selectedGroup,
        selectedResponsible,
        selectedSchedule,
        selectedDate,
        subjects,
        groups,
        responsible,
        schedules,
        assignClassrooms,

        // Métodos
        fetchGroups,
        fetchSubjectProfessors,
        fetchSchedules,
        sendReservation,
        resetForm,
        openAssignClassroomModal,
        closeModal
    };
}
