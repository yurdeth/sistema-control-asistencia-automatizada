<template>
    <!-- Contenedor principal del formulario-->
    <div class="p-6 space-y-4">

        <!-- Título dinámico que se ajusta según el modo -->
        <h2 class="text-xl font-bold mb-4">
            {{ titulo }}
        </h2>

        <!-- Bloque para visualizar los detalles del aula (modo ver) -->
        <template v-if="modo === 'ver'">
            <p><strong>Nombre:</strong> {{ aula.nombre }}</p>
            <p><strong>Capacidad:</strong> {{ aula.capacidad }}</p>
            <p><strong>Ubicación:</strong> {{ aula.ubicacion }}</p>
            <p><strong>Recursos:</strong> {{ aula.recurso }}</p>
            <p><strong>Estado:</strong> {{ aula.estado }}</p>
        </template>

        <!-- Bloque para editar el aula (modo editar) -->
        <template v-else-if="modo === 'editar'">
            <form @submit.prevent="guardar">
                <div>
                    <label>Nombre</label>
                    <input v-model="form.nombre" class="input" />
                </div>

                <div>
                    <label>Capacidad</label>
                    <input type="number" v-model="form.capacidad" class="input" />
                </div>

                <div>
                    <label>Ubicación</label>
                    <input v-model="form.ubicacion" class="input" />
                </div>

                <div>
                    <label>Recursos</label>
                    <textarea v-model="form.recurso" class="input"></textarea>
                </div>

                <div>
                    <label>Estado</label>
                    <select v-model="form.estado" class="input">
                        <option value="disponible">Disponible</option>
                        <option value="ocupado">Ocupado</option>
                        <option value="mantenimiento">Mantenimiento</option>
                    </select>
                </div>

                <!-- parte donde están los Botones para cancelar o guardar -->
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" class="btn" @click="$emit('close')">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </template>

        <!-- Bloque para realizar una reserva (modo reservar) -->
        <template v-else-if="modo === 'reservar'">
            <form @submit.prevent="reservar">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Fecha</label>
                        <input type="date" v-model="reserva.fecha" class="input" required />
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Hora de inicio</label>
                        <input type="time" v-model="reserva.horaInicio" class="input" required />
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Hora de fin</label>
                        <input type="time" v-model="reserva.horaFin" class="input" required />
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Responsable</label>
                        <input type="text" v-model="reserva.responsable" class="input" required />
                    </div>

                    <!-- Botones para cancelar o reservar -->
                    <div class="flex justify-end gap-2 mt-4">
                        <button type="button" class="btn" @click="$emit('close')">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Reservar</button>
                    </div>
                </div>
            </form>
        </template>


    </div>
</template>

<script setup>
    import { reactive, watch, computed } from 'vue';

    // Definimos las propiedades que recibe el componente
    const props = defineProps({
        aula: Object,
        modo: String, // 'ver', 'editar', 'reservar'
    });

    const emit = defineEmits(['close', 'update']);

    // =======| parte donde se trabaja el formulario de editar |=======
    // Objeto reactivo para los datos del formulario
    const form = reactive({
        nombre: '',
        capacidad: '',
        ubicacion: '',
        recurso: '',
        estado: '',
    });

    watch(
        () => props.aula,
        (aula) => {
            if (aula) {
                Object.assign(form, aula);
            }
        },
        { immediate: true }
    );

    // Computar el título basado en el modo actual
    const titulo = computed(() => {
        switch (props.modo) {
            case 'ver':
                return 'Detalles del Aula';
            case 'editar':
                return 'Editar Aula';
            case 'reservar':
                return 'Reservar Aula';
            default:
                return '';
        }
    });

     // Función para guardar los cambios
    const guardar = () => {
        emit('update', { ...form });
    };

    // =======| parte donde se trabaja el formulario de reserva |=======
    // Objeto reactivo para los datos del formulario
    const reserva = reactive({
        fecha: '',
        horaInicio: '',
        horaFin: '',
        responsable: '',
    });

    // Función para realizar la reserva
    const reservar = () => {
        // para cuando se use la API
        const datosReserva = {
            aulaId: props.aula?.id,
            ...reserva,
        };

        emit('close');
    };

</script>

<style scoped>
    .input {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ccc;
        border-radius: 0.375rem;
    }
    .btn {
        padding: 0.5rem 1rem;
    }
    .btn-primary {
        background-color: #2563eb;
        color: white;
        border-radius: 8px;
    }
</style>
