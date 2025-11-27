const ESTADOS_COLORES = {
    disponible: 'bg-green-100 text-green-800',
    ocupada: 'bg-red-100 text-red-800',
    ocupado: 'bg-red-100 text-red-800',
    mantenimiento: 'bg-yellow-100 text-yellow-800',
    inactiva: 'bg-gray-100 text-gray-800'
}

const ESTADOS_TEXTOS = {
    disponible: 'Disponible',
    ocupada: 'Ocupado',
    ocupado: 'Ocupado',
    mantenimiento: 'En mantenimiento',
    inactiva: 'Inactiva'
}

// Funciones que usan las constantes
export const getEstadoColor = (estado) => {
    return ESTADOS_COLORES[estado] || 'bg-gray-100 text-gray-800'
}

export const getEstadoTexto = (estado) => {
    return ESTADOS_TEXTOS[estado] || estado
}

// Exporta las constantes si las necesitas en otro lugar
export { ESTADOS_COLORES, ESTADOS_TEXTOS }
