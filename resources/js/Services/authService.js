import axios from 'axios';

const API_BASE_URL = '/api';

export const authService ={

    setAxiosToken(token) {
        if(token){
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        }else{
            delete axios.defaults.headers.common['Authorization'];
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
