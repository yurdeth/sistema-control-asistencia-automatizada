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

// Estados para Departamentos (solo activo/inactivo)
const ESTADOS_DEPARTAMENTOS_COLORES = {
    activo: 'bg-green-100 text-green-800',
    inactivo: 'bg-gray-200 text-gray-800'
}

const ESTADOS_DEPARTAMENTOS_TEXTOS = {
    activo: 'Activo',
    inactivo: 'Inactivo'
}

// Funciones para Aulas
export const getEstadoColor = (estado) => {
    return ESTADOS_COLORES[estado] || 'bg-gray-100 text-gray-800'
}

export const getEstadoTexto = (estado) => {
    return ESTADOS_TEXTOS[estado] || estado
}

// Funciones para Departamentos
export const getEstadoDepartamentoColor = (estado) => {
    return ESTADOS_DEPARTAMENTOS_COLORES[estado] || 'bg-gray-100 text-gray-800'
}

export const getEstadoDepartamentoTexto = (estado) => {
    return ESTADOS_DEPARTAMENTOS_TEXTOS[estado] || estado
}

// Exporta las constantes si las necesitas en otro lugar
export {
    ESTADOS_COLORES,
    ESTADOS_TEXTOS,
    ESTADOS_DEPARTAMENTOS_COLORES,
    ESTADOS_DEPARTAMENTOS_TEXTOS
}
