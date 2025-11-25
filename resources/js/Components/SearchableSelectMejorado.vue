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
				@focus="handleFocus"
				@input="handleInput"
				@keydown.enter="preventSubmit"
				@keydown.down="highlightNext"
				@keydown.up="highlightPrev"
				@keydown.escape="closeDropdown"
				type="text"
				:placeholder="placeholder"
				:class="[
					'w-full border rounded px-3 py-2 text-sm sm:text-base focus:outline-none focus:ring-2',
					error ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-blue-500',
					disabled ? 'bg-gray-100 cursor-not-allowed' : 'bg-white',
					loading ? 'pr-10' : ''
				]"
				:disabled="disabled || loading"
			/>

			<!-- Loading indicator -->
			<div v-if="loading" class="absolute inset-y-0 right-0 flex items-center pr-3">
				<i class="fas fa-spinner fa-spin text-blue-500 text-sm"></i>
			</div>
			<!-- Dropdown arrow -->
			<div v-else class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
				<i v-if="!isOpen" class="fas fa-chevron-down text-gray-400 text-sm"></i>
				<i v-else class="fas fa-chevron-up text-gray-400 text-sm"></i>
			</div>
		</div>

		<!-- Dropdown con opciones cargadas dinámicamente -->
		<div
			v-if="isOpen"
			class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg"
		>
			<!-- Loading state -->
			<div v-if="loading && options.length === 0" class="p-4 text-center">
				<i class="fas fa-spinner fa-spin text-blue-500 text-lg mb-2"></i>
				<p class="text-sm text-gray-600">Cargando opciones...</p>
			</div>

			<!-- Opciones -->
			<div v-else-if="options.length > 0" class="max-h-60 overflow-auto">
				<div
					v-for="(option, index) in options"
					:key="option[valueKey]"
					@click="selectOption(option)"
					:class="[
						'px-3 py-2 cursor-pointer transition-colors border-b border-gray-100 last:border-b-0',
						highlightedIndex === index ? 'bg-blue-50' : 'hover:bg-blue-50'
					]"
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

				<!-- Load more button (scroll infinito) -->
				<div
					v-if="hasMorePages && !loading"
					@click="loadMore"
					class="px-3 py-2 text-center text-sm text-blue-600 hover:bg-blue-50 cursor-pointer border-t border-gray-100"
				>
					<i class="fas fa-plus mr-2"></i>
					Cargar más resultados
				</div>

				<!-- Loading more -->
				<div v-if="loading && options.length > 0" class="px-3 py-2 text-center text-sm text-gray-600 border-t border-gray-100">
					<i class="fas fa-spinner fa-spin mr-2"></i>
					Cargando más resultados...
				</div>
			</div>

			<!-- No results -->
			<div v-else-if="!loading && searchTerm" class="p-3 text-center">
				<p class="text-sm text-gray-500">
					<i class="fas fa-search mr-2"></i>
					No se encontraron resultados para "{{ searchTerm }}"
				</p>
			</div>
		</div>

		<!-- Error message -->
		<p v-if="error" class="text-red-600 text-xs sm:text-sm mt-1">
			{{ error }}
		</p>
	</div>
</template>

<script setup>
import { ref, computed, watch, onMounted, nextTick } from 'vue';
import axios from 'axios';

const props = defineProps({
	modelValue: [String, Number],
	apiUrl: {
		type: String,
		required: true
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
		default: 'label'
	},
	sublabelKey: {
		type: String,
		default: 'sublabel'
	},
	error: String,
	required: Boolean,
	disabled: Boolean,
	// Configuración personalizable
	debounceTime: {
		type: Number,
		default: 300
	},
	initialPerPage: {
		type: Number,
		default: 50
	},
	maxPerPage: {
		type: Number,
		default: 100
	},
	cacheMinutes: {
		type: Number,
		default: 5
	}
});

const emit = defineEmits(['update:modelValue', 'change', 'loading']);

// Estado del componente
const searchTerm = ref('');
const isOpen = ref(false);
const loading = ref(false);
const options = ref([]);
const highlightedIndex = ref(-1);
const searchInput = ref(null);

// Paginación
const currentPage = ref(1);
const hasMorePages = ref(false);
const currentSearchTerm = ref('');

// Cache local
const cache = ref(new Map());

// Debounce timer
let debounceTimer = null;

// Watch para cambios en el modelo
watch(() => props.modelValue, (newVal) => {
	if (newVal && options.value.length > 0) {
		const selected = options.value.find(opt => opt[props.valueKey] === newVal);
		if (selected) {
			searchTerm.value = selected[props.labelKey];
		}
	} else if (!newVal) {
		searchTerm.value = '';
	}
}, { immediate: true });

// Cargar opciones iniciales
onMounted(async () => {
	if (props.modelValue) {
		// Si hay un valor seleccionado, cargar detalles específicos
		await loadSelectedOption();
	} else {
		// Cargar primeras opciones sin búsqueda
		await loadOptions('', 1, false);
	}
});

const handleFocus = () => {
	if (!props.disabled) {
		isOpen.value = true;
		highlightedIndex.value = -1;
	}
};

const handleInput = () => {
	isOpen.value = true;
	highlightedIndex.value = -1;

	// Limpiar timer anterior
	if (debounceTimer) {
		clearTimeout(debounceTimer);
	}

	// Setear nuevo timer
	debounceTimer = setTimeout(() => {
		currentPage.value = 1;
		loadOptions(searchTerm.value, 1, true);
	}, props.debounceTime);
};

const loadOptions = async (search, page = 1, reset = false) => {
	try {
		loading.value = true;
		emit('loading', true);

		// Check cache
		const cacheKey = `${props.apiUrl}_${search}_${page}`;
		const cacheData = cache.value.get(cacheKey);

		if (cacheData && (Date.now() - cacheData.timestamp) < (props.cacheMinutes * 60 * 1000)) {
			applyOptions(cacheData.data, reset);
			return;
		}

		// Fetch from API
		const response = await axios.get(props.apiUrl, {
			params: {
				search: search,
				page: page,
				per_page: page === 1 ? props.initialPerPage : props.maxPerPage
			},
			headers: {
				'Authorization': `Bearer ${localStorage.getItem('token')}`
			}
		});

		if (response.data.success) {
			const data = response.data.data;
			const pagination = response.data.pagination;

			// Guardar en cache
			cache.value.set(cacheKey, {
				data: {
					options: data,
					pagination: pagination
				},
				timestamp: Date.now()
			});

			applyOptions({ options: data, pagination }, reset);
		}
	} catch (error) {
		console.error('Error loading options:', error);
		if (reset) {
			options.value = [];
		}
	} finally {
		loading.value = false;
		emit('loading', false);
	}
};

const applyOptions = (data, reset) => {
	if (reset) {
		options.value = data.options;
		currentPage.value = 1;
	} else {
		options.value = [...options.value, ...data.options];
	}

	hasMorePages.value = data.pagination.has_more;
	currentSearchTerm.value = searchTerm.value;
};

const loadMore = () => {
	if (!loading.value && hasMorePages.value) {
		const nextPage = currentPage.value + 1;
		currentPage.value = nextPage;
		loadOptions(currentSearchTerm.value, nextPage, false);
	}
};

const loadSelectedOption = async () => {
	try {
		loading.value = true;

		const response = await axios.get(`${props.apiUrl}?search=${props.modelValue}&per_page=1`, {
			headers: {
				'Authorization': `Bearer ${localStorage.getItem('token')}`
			}
		});

		if (response.data.success && response.data.data.length > 0) {
			const selectedOption = response.data.data[0];
			options.value = [selectedOption];
			searchTerm.value = selectedOption[props.labelKey];
		}
	} catch (error) {
		console.error('Error loading selected option:', error);
	} finally {
		loading.value = false;
	}
};

const selectOption = (option) => {
	emit('update:modelValue', option[props.valueKey]);
	emit('change', option);
	searchTerm.value = option[props.labelKey];
	closeDropdown();
};

const closeDropdown = () => {
	isOpen.value = false;
	highlightedIndex.value = -1;

	// Restaurar el texto si no hay selección válida
	if (!props.modelValue) {
		searchTerm.value = '';
	}
};

const highlightNext = () => {
	if (isOpen.value && options.value.length > 0) {
		highlightedIndex.value = (highlightedIndex.value + 1) % options.value.length;
		scrollToHighlighted();
	}
};

const highlightPrev = () => {
	if (isOpen.value && options.value.length > 0) {
		highlightedIndex.value = highlightedIndex.value <= 0
			? options.value.length - 1
			: highlightedIndex.value - 1;
		scrollToHighlighted();
	}
};

const scrollToHighlighted = () => {
	if (highlightedIndex.value >= 0) {
		nextTick(() => {
			const highlightedElement = searchInput.value?.parentElement?.querySelector('.bg-blue-50');
			if (highlightedElement) {
				highlightedElement.scrollIntoView({ block: 'nearest' });
			}
		});
	}
};

const preventSubmit = () => {
	if (highlightedIndex.value >= 0 && options.value[highlightedIndex.value]) {
		selectOption(options.value[highlightedIndex.value]);
	}
	return false;
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