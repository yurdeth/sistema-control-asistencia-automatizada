<template>
	<div>
		<label class="block text-xs sm:text-sm font-medium text-gray-700 mb-2">
			{{ label }}
			<span v-if="required" class="text-red-500">*</span>
		</label>

		<!-- Bloques de tiempo predefinidos -->
		<div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-3">
			<button
				v-for="bloque in bloquesComunes"
				:key="bloque.label"
				type="button"
				@click="selectBloque(bloque)"
				:class="[
					'px-3 py-2 text-xs sm:text-sm rounded-lg border-2 transition-all',
					isSelected(bloque) 
						? 'bg-blue-500 text-white border-blue-600 shadow-md' 
						: 'bg-white text-gray-700 border-gray-300 hover:border-blue-400 hover:bg-blue-50'
				]"
			>
				<i class="far fa-clock mr-1"></i>
				{{ bloque.label }}
			</button>
		</div>

		<!-- Selector manual (desplegable) -->
		<details class="bg-gray-50 rounded-lg border border-gray-200">
			<summary class="px-3 py-2 cursor-pointer text-sm text-gray-600 hover:bg-gray-100 rounded-lg">
				<i class="fas fa-sliders-h mr-2"></i>
				Horario personalizado
			</summary>
			<div class="p-3 grid grid-cols-2 gap-3">
				<div>
					<label class="block text-xs text-gray-600 mb-1">Hora inicio</label>
					<input
						type="time"
						v-model="horaInicio"
						@change="emitCustomTime"
						class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
					/>
				</div>
				<div>
					<label class="block text-xs text-gray-600 mb-1">Hora fin</label>
					<input
						type="time"
						v-model="horaFin"
						@change="emitCustomTime"
						class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-2 focus:ring-blue-500"
					/>
				</div>
			</div>
		</details>

		<!-- Errores -->
		<p v-if="errorInicio" class="text-red-600 text-xs mt-1">{{ errorInicio }}</p>
		<p v-if="errorFin" class="text-red-600 text-xs mt-1">{{ errorFin }}</p>
	</div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
	modelValueInicio: String,
	modelValueFin: String,
	label: {
		type: String,
		default: 'Horario'
	},
	errorInicio: String,
	errorFin: String,
	required: Boolean
});

const emit = defineEmits(['update:modelValueInicio', 'update:modelValueFin']);

const horaInicio = ref(props.modelValueInicio || '');
const horaFin = ref(props.modelValueFin || '');

watch(() => props.modelValueInicio, (val) => horaInicio.value = val || '');
watch(() => props.modelValueFin, (val) => horaFin.value = val || '');

const bloquesComunes = [
	{ label: '7:00 - 9:00', inicio: '07:00', fin: '09:00' },
	{ label: '9:00 - 11:00', inicio: '09:00', fin: '11:00' },
	{ label: '11:00 - 13:00', inicio: '11:00', fin: '13:00' },
	{ label: '13:00 - 15:00', inicio: '13:00', fin: '15:00' },
	{ label: '15:00 - 17:00', inicio: '15:00', fin: '17:00' },
	{ label: '17:00 - 19:00', inicio: '17:00', fin: '19:00' },
];

const isSelected = (bloque) => {
	return horaInicio.value === bloque.inicio && horaFin.value === bloque.fin;
};

const selectBloque = (bloque) => {
	horaInicio.value = bloque.inicio;
	horaFin.value = bloque.fin;
	emit('update:modelValueInicio', bloque.inicio);
	emit('update:modelValueFin', bloque.fin);
};

const emitCustomTime = () => {
	emit('update:modelValueInicio', horaInicio.value);
	emit('update:modelValueFin', horaFin.value);
};
</script>
