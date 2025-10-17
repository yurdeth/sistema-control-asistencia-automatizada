// javascript
import axios from "axios";

const API_URL = '/api/users/';

export const getDocentesAll = async () => {
  try {
    const token = localStorage.getItem("token");
    if (!token) throw new Error("Faltan datos de autenticación");

    const response = await axios.get(`${API_URL}get/professors/all`, {
      headers: {
        Authorization: `Bearer ${token}`,
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      // accept 404 responses so Axios doesn't throw automatically
      validateStatus: status => status < 500
    });

    if (response.status === 404) {
      // backend reports "No se encontraron docentes" — return empty result instead of throwing
      return { success: false, message: response.data?.message ?? 'Not Found', data: [] };
    }

    return response.data;
  } catch (error) {
    // normalize thrown error to include backend response data when available
    if (error.response && error.response.data) throw error.response.data;
    throw error;
  }
};
