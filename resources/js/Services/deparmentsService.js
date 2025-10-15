import axios from "axios";

// Definimos una constante para almacenar el endpoint de la API
const API_URL = '/api/departaments/';

// Función para obtener todos los departamentos
export const getDeparmentsAll = async() =>{
    try {
        const token = localStorage.getItem("token");
        if (!token) throw new Error("Faltan datos de autenticación");

        const response = await axios.get(`${API_URL}get/all`, {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        });

        return response.data;

    } catch (error) {
        throw error;
    }
}

// Función para crea nuevo departamento
export const createDeparments = async(departamentsDataNew) =>{
    try {
        const token = localStorage.getItem("token");
        if (!token) throw new Error("Faltan datos de autenticación");

        const response = await axios.post(`${API_URL}new`, departamentsDataNew,
            {
                headers: {
                    Authorization: `Bearer ${token}`,
                },
            }
        );

        return response.data;

    } catch (error) {
        throw error;
    }
}

// Función para actualizar departamento
export const updateDepartment = async(id, departmentData) => {
    try {
        const token = localStorage.getItem("token");
        if (!token) throw new Error("Faltan datos de autenticación");

        const response = await axios.patch(`${API_URL}edit/${id}`, departmentData, {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        });

        return response.data;

    } catch (error) {
        console.error('Error en updateDepartment:', error);
        throw error;
    }
}

// Función para eliminar departamento
export const deleteDepartment = async(id) => {
    try {
        const token = localStorage.getItem("token");
        if (!token) throw new Error("Faltan datos de autenticación");

        const response = await axios.delete(`${API_URL}delete/${id}`, {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        });

        return response.data;

    } catch (error) {
        console.error('Error en deleteDepartment:', error);
        throw error;
    }
}
