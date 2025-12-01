import '../css/app.css';
import './bootstrap';

import { createInertiaApp, router } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import axios from 'axios';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

router.on('before', (event) => {
    const token = localStorage.getItem('token');
    if (token) {
        event.detail.visit.headers = {
            ...event.detail.visit.headers,
            'Authorization': `Bearer ${token}`
        };
    }
});

// Interceptor para manejar errores 403 y redirigir invitados al catálogo
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 403) {
            const user = JSON.parse(localStorage.getItem('user') || '{}');

            // Si es un invitado (rol 7), redirigir automáticamente al catálogo
            if (user.role_id === 7) {
                console.log('🔒 Acceso denegado para invitado, redirigiendo al catálogo...');
                router.visit('/dashboard/catalogo');
                return Promise.reject({ ...error, handled: true });
            }
        }
        return Promise.reject(error);
    }
);

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
