<template>
	<div class="relative" v-click-away="closeDropdown">
		<label v-if="label" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1">
			{{ label }}
			<span v-if="required" class="text-red-500">*</span>
		</label>
		
		<div class="relative">
			<input
				ref="searchInput"
				v-model="searchTerm"
				@focus="openDropdown"
				@input="openDropdown"
				type="text"
				:placeholder="placeholder"
				:class="[
					'w-full border rounded px-3 py-2 text-sm sm:text-base focus:outline-none focus:ring-2',
					error ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-blue-500',
					disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white'
				]"
				:disabled="disabled"
			/>
			
			<div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
				<i v-if="!isOpen" class="fas fa-chevron-down text-gray-400 text-sm"></i>
				<i v-else class="fas fa-chevron-up text-gray-400 text-sm"></i>
			</div>
		</div>

		<!-- Dropdown con opciones filtradas -->
		<div
			v-if="isOpen && filteredOptions.length > 0"
			class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-60 overflow-auto"
		>
			<div
				v-for="option in filteredOptions"
				:key="option[valueKey]"
				@click="selectOption(option)"
				class="px-3 py-2 hover:bg-blue-50 cursor-pointer transition-colors border-b border-gray-100 last:border-b-0"
			>
				<div class="flex items-center justify-between">
					<div class="flex-1">
						<p class="text-sm font-medium text-gray-900">{{ option[labelKey] }}</p>
						<p v-if="option[sublabelKey]" class="text-xs text-gray-500 mt-0.5">
							{{ option[sublabelKey] }}
						</p>
					</div>
					<i v-if="modelValue === option[valueKey]" class="fas fa-check text-blue-600 ml-2"></i>
				</div>
			</div>
		</div>

		<!-- No results -->
		<div
			v-if="isOpen && filteredOptions.length === 0 && searchTerm"
			class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg p-3"
		>
			<p class="text-sm text-gray-500 text-center">
				<i class="fas fa-search mr-2"></i>
				No se encontraron resultados
			</p>
		</div>

		<!-- Error message -->
		<p v-if="error" class="text-red-600 text-xs sm:text-sm mt-1">
			{{ error }}
		</p>
	</div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
	modelValue: [String, Number],
	options: {
		type: Array,
		default: () => []
	},
	label: String,
	placeholder: {
		type: String,
		default: 'Buscar...'
	},
	valueKey: {
		type: String,
		default: 'id'
	},
	labelKey: {
		type: String,
		default: 'nombre'
	},
	sublabelKey: String,
	error: String,
	required: Boolean,
	disabled: Boolean
});

const emit = defineEmits(['update:modelValue', 'change']);

const searchTerm = ref('');
const isOpen = ref(false);
const searchInput = ref(null);

// Actualizar el término de búsqueda cuando cambia el valor seleccionado
watch(() => props.modelValue, (newVal) => {
	if (newVal) {
		const selected = props.options.find(opt => opt[props.valueKey] === newVal);
		if (selected) {
			searchTerm.value = selected[props.labelKey];
		}
	} else {
		searchTerm.value = '';
	}
}, { immediate: true });

const filteredOptions = computed(() => {
	if (!searchTerm.value) return props.options;
	
	const term = searchTerm.value.toLowerCase();
	return props.options.filter(option => {
		const label = option[props.labelKey]?.toLowerCase() || '';
		const sublabel = props.sublabelKey ? (option[props.sublabelKey]?.toLowerCase() || '') : '';
		return label.includes(term) || sublabel.includes(term);
	});
});

const openDropdown = () => {
	if (!props.disabled) {
		isOpen.value = true;
	}
};

const closeDropdown = () => {
	isOpen.value = false;
	// Restaurar el texto si no hay selección válida
	if (!props.modelValue) {
		searchTerm.value = '';
	}
};

const selectOption = (option) => {
	emit('update:modelValue', option[props.valueKey]);
	emit('change', option);
	searchTerm.value = option[props.labelKey];
	closeDropdown();
};

// Directiva v-click-away
const vClickAway = {
	mounted(el, binding) {
		el.clickAwayEvent = (event) => {
			if (!(el === event.target || el.contains(event.target))) {
				binding.value();
			}
		};
		document.addEventListener('click', el.clickAwayEvent);
	},
	unmounted(el) {
		document.removeEventListener('click', el.clickAwayEvent);
	}
};
</script>
