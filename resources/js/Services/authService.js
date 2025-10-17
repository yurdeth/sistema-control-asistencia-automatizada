import axios from 'axios';
import {router} from "@inertiajs/vue3";

const API_BASE_URL = '/api';

export const authService ={

    setAxiosToken(token) {
        if(token){
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        }else{
            delete axios.defaults.headers.common['Authorization'];
        }
    },

    async verifyToken(token) {
        try {
            const response = await fetch('/api/verify-token', {
                headers: {
                    'Authorization': `Bearer ${token}`,
                    'Accept': 'application/json',
                }
            });

            await new Promise(resolve => setTimeout(resolve, 1000));

            if (!response.ok) {
                // Token inválido, limpiar y redirigir
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                router.visit('/login');
            }
        } catch (error) {
            console.error('Error verificando token:', error);
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            router.visit('/login');
        }
    },

    async login(email, password){
        try {
                const response = await axios.post(`${API_BASE_URL}/login`, {
                    email,
                    password
                });
                const { token, user } = response.data;

                // Guarda el token y usuario en localStorage
                localStorage.setItem('token', token);
                localStorage.setItem('user', JSON.stringify(user));

                // Configura el token en axios
                this.setAxiosToken(token);

                return user;
            } catch (error) {
                    throw error;
                }
    },

    async logout(){
        const token = localStorage.getItem('token');

        try {
            // Intenta hacer logout en el servidor
            const response = await axios.post(`${API_BASE_URL}/logout`, {}, {
                headers: {
                    Authorization: `Bearer ${token}`,
                },
        });

            console.log('Logout del servidor:', response.data.message);
        }catch (error){
            console.error('Error al cerrar sesión del servidor:', error.response?.data || error.message);
        }finally {
            // eliminamos los datos locales, aunque falle el logout del backend
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            this.setAxiosToken(null);
        }
    },

    // Obteniendo el token
    getToken() {
        return localStorage.getItem('token');
    },

    //Obteniendo usuario
    getUser() {
        const user = localStorage.getItem('user');
        return user ? JSON.parse(user) : null;
    },

    //Obtenemos el rol
    getUserRole() {
        const user = this.getUser();
        return user ? user.role_id : null;
    },

    //verificando autenticación
    isAuthenticated() {
        return !!this.getToken();
    }
}
