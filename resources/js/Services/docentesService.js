import axios from "axios";

// Definimos una constante para almacenar el endpoint de la API
const API_URL = '/api/users/';

// Función para obtener todos los departamentos
export const getDocentesAll = async() =>{
    try {
        const token = localStorage.getItem("token");
        if (!token) throw new Error("Faltan datos de autenticación");

        const response = await axios.get(`${API_URL}get/professors/all`, {
            headers: {
                Authorization: `Bearer ${token}`,
            },
        });

        return response.data;

    } catch (error) {
        throw error;
    }
}