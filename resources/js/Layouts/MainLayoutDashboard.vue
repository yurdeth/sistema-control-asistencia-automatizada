<template>
    <div class="flex h-screen bg-gray-100">

        <!-- Overlay para móviles cuando el sidebar está abierto -->
        <div
            v-if="sidebarOpen"
            @click="sidebarOpen = false"
            class="fixed inset-0 bg-black bg-opacity-50 z-20 lg:hidden"
        ></div>

        <!--Sidebar-->
        <aside
            :style="{
                backgroundColor: colorSidebar,
                '--sidebar-text-color': colorText
            }"
            :class="[
                'fixed lg:static inset-y-0 left-0 z-30 w-64 p-4 pr-0 overflow-x-hidden overflow-y-auto',
                'transform transition-transform duration-300 ease-in-out',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
            ]"
        >

            <!-- Botón cerrar para móviles -->
            <div class="flex items-center justify-between mb-2 lg:hidden pr-4">
                <div class="w-6"></div> <!-- Espaciador para centrar -->
                <button
                    @click="sidebarOpen = false"
                    class="p-2 rounded-md hover:bg-white/10 transition-colors"
                    :style="{ color: colorText }"
                >
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="content-logo">
                <img class="Logosystem" src="/Images/Idea Logo Proyecto.png" alt="Logo">
            </div>
            <nav
                class="flex flex-col space-y-4"
                style="padding-bottom: 5rem"
            >
            <!-- Menús dinámicos completos -->
            <div v-for="(menu, index) in menus" :key="menu.key || index">
                <!-- Enlace simple (sin submenús) -->
                <Link
                    v-if="!menu.items && menu.href"
                    :href="menu.href"
                    class="sidebar-link p-2 rounded flex items-center gap-2 transition-all duration-200"
                    :class="{ 'bg-white/10 font-semibold': isActive(menu.href) }"
                    :style="{
                        color: colorText,
                        borderLeft: isActive(menu.href) ? '3px solid #ffffff' : 'none'
                    }"
                >
                    <i :class="menu.icon" :style="{ color: colorText }"></i>
                    {{ menu.title }}
                </Link>

                <!-- Menú con submenús -->
                <div v-else-if="menu.items && menu.items.length > 0">
                    <button
                        @click="toggleMenu(menu.key)"
                        class="sidebar-link p-2 rounded flex justify-between items-center w-full transition-all duration-200"
                        :class="{ 'bg-white/10 font-semibold': isMenuActive(menu.items) }"
                        :style="{
                            color: colorText,
                            borderLeft: isMenuActive(menu.items) ? '3px solid #ffffff' : 'none'
                        }"
                    >
                        <span class="flex items-center gap-2">
                            <i :class="menu.icon" :style="{ color: colorText }"></i>
                            <span>{{ menu.title }}</span>
                        </span>

                        <svg
                            :class="{ 'rotate-90': openMenus[menu.key] }"
                            class="w-4 h-4 transform transition-transform"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                            :style="{ color: colorText }"
                        >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <div v-if="openMenus[menu.key]" class="pl-4 mt-2 space-y-2">
                        <Link
                            v-for="(item, i) in menu.items"
                            :key="item.href || i"
                            :href="item.href"
                            class="sidebar-submenu-link block p-2 rounded flex items-center gap-2 transition-all duration-200"
                            :class="{ 'bg-white/20 font-semibold': isActive(item.href) }"
                            :style="{
                                color: colorText,
                                borderLeft: isActive(item.href) ? '3px solid #ffffff' : 'none',
                                marginLeft: isActive(item.href) ? '4px' : '0'
                            }"
                        >
                            <i :class="item.icon" :style="{ color: colorText }"></i>
                            {{ item.label }}
                        </Link>
                    </div>
                </div>
            </div>
            </nav>
        </aside>

        <!-- navbar -->
        <div class="flex-1 flex flex-col min-w-0">
            <header :style="{ backgroundColor: colorNavbar }" class="text-white p-3 sm:p-4 h-auto">
                <nav class="flex items-center justify-between flex-wrap gap-2">
                    <!--Button para el responsive-->
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="lg:hidden p-3 rounded-md hover:bg-white/10 transition-colors"
                        :style="{ color: colorText }"
                    >
                        <i class="fa-solid fa-bars text-lg"></i>
                    </button>

                    <div class="hidden lg:block"></div>

                    <div class="flex items-center gap-2 sm:gap-4" v-if="user && user.nombre_completo">
                        <!-- Notificaciones -->
                        <div class="relative" ref="notificationsRef">
                            <!-- Botón que abre el panel de notificaciones -->
                            <button
                                @click.prevent="toggleNotifications"
                                class="sidebar-link flex items-center gap-2 p-3 rounded-lg transition-colors duration-300
                                       hover:text-white shadow-lg hover:shadow-md min-w-[44px] min-h-[44px] bg-transparent border-0"
                                :style="{ color: colorText }"
                                type="button"
                            >
                                <div class="relative flex items-center">
                                    <i class="fa-solid fa-bell text-base sm:text-lg"></i>
                                    <!-- Badge (colocado sobre la campana) -->
                                    <span
                                        v-if="notificationCount > 0"
                                        class="notification-badge text-white text-xs font-bold flex items-center justify-center rounded-full
                                            min-w-[18px] min-h-[18px] px-1"
                                    >
                                        {{ notificationCount }}
                                    </span>
                                </div>
                                <span class="hidden sm:inline">Notificaciones</span>
                            </button>

                            <!-- Panel provisional de notificaciones -->
                            <div
                                v-if="notificationOpen"
                                class="notifications-panel absolute right-0 mt-2 w-80 sm:w-96 bg-white text-black rounded-md shadow-lg z-50"
                                @click.stop
                            >
                                <div class="px-4 py-2 text-sm font-semibold border-b flex items-center justify-between">
                                    <span>Notificaciones ({{ notificationCount }})</span>
                                    <button class="text-xs text-gray-500" @click="markAllRead">Marcar leídas</button>
                                </div>
                                <div class="max-h-60 overflow-y-auto">
                                    <div v-if="unreadList.length === 0" class="p-4 text-sm text-gray-600">
                                        No tienes notificaciones sin leer.
                                    </div>
                                    <div v-else>
                                        <div v-for="(n, idx) in unreadList" :key="n.id ?? idx" class="notification-item px-4 py-3 border-b last:border-b-0">
                                            <div class="text-sm font-medium">{{ n.titulo ?? n.title ?? 'Sin título' }}</div>
                                            <div class="text-xs text-gray-500 mt-1">{{ n.mensaje ?? n.body ?? '' }}</div>
                                        </div>
                                        <div v-if="totalUnread > unreadLimit" class="px-4 py-2 text-xs text-gray-500">
                                            Mostrando {{ unreadLimit }} de {{ totalUnread }} no leídas.
                                        </div>
                                    </div>
                                </div>
                                <div class="px-4 py-2 border-t text-right">
                                    <button class="text-sm text-[#660D04] hover:underline" @click="goToAllNotifications">Ver todas</button>
                                </div>
                            </div>
                        </div>


                        <!-- Custom Dropdown -->
                        <div class="relative" ref="dropdownRef">
                            <button
                                @click="toggleDropdown"
                                type="button"
                                class="inline-flex items-center justify-between w-full sm:w-auto rounded-md border border-transparent bg-[#76180D] text-white px-3 sm:px-4 py-2 sm:py-3 text-sm font-medium leading-4 transition duration-150 ease-in-out hover:bg-[#5f130b] focus:outline-none min-w-[44px] min-h-[44px] truncate"
                            >
                                <span class="truncate max-w-[120px] sm:max-w-none">{{ user.nombre_completo }}</span>
                                <svg class="ms-2 h-4 w-4 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd"                                    />
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div
                                v-if="dropdownOpen"
                                class="absolute right-0 mt-2 w-48 sm:w-56 bg-white text-black rounded-md shadow-lg z-50"
                            >
                                <div class="px-4 py-2 text-xs text-gray-500 border-b">
                                    {{ user.nombre_completo }}
                                </div>

                                <button
                                    @click="handleLogout"
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 flex items-center gap-2"
                                >
                                    <i class="fa-solid fa-sign-out-alt"></i>
                                    Cerrar sesión
                                </button>
                            </div>
                        </div>
                    </div>
                </nav>
            </header>

            <!--Contenido de inicio-->
            <main class="flex-1 overflow-y-auto bg-gray-100 p-3 sm:p-4 md:p-6">
                <!-- Mostrar mensaje de bienvenida si no hay contenido en el slot -->
                <template v-if="$slots.default">
                    <slot />
                </template>
            </main>

        </div>
    </div>
</template>

<script setup>
    import { ref, reactive, onMounted, onBeforeUnmount, computed } from "vue";
    import { Link } from "@inertiajs/vue3";
    import { router } from '@inertiajs/vue3';
    import { authService } from "@/Services/authService";
    import axios from 'axios';

const user = ref(null);
const sidebarOpen = ref(false); // Control del sidebar en móviles
const notificationCount = ref(0); // contador real de no leídas

// Obtener la ruta actual para el estado activo
const currentPath = ref(window.location.pathname);

// Función para verificar si un enlace está activo
const isActive = (href) => {
    return currentPath.value === href;
};

// Función para verificar si un menú principal tiene una subopción activa
const isMenuActive = (menuItems) => {
    return menuItems.some(item => isActive(item.href));
};

// Detectar cambios de ruta
const updateCurrentPath = () => {
    currentPath.value = window.location.pathname;
};

onMounted(() => {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
        try {
            user.value = JSON.parse(storedUser);
        } catch (e) {
            console.error('Error parsing localStorage user:', e);
        }
    }

    // Inicializar ruta actual
    updateCurrentPath();

    // Inicializar estados de menús después de tener la ruta actual
    initializeMenuStates();

    // Escuchar cambios de ruta (para SPA)
    window.addEventListener('popstate', updateCurrentPath);

    // Para Inertia.js, necesitamos detectar cambios de navegación
    const originalPushState = history.pushState;
    history.pushState = function(...args) {
        originalPushState.apply(history, args);
        updateCurrentPath();
        initializeMenuStates(); // Re-inicializar menús al cambiar de ruta
    };

    // intentamos cargar notificaciones no leídas
    fetchUnreadNotifications();
});

onBeforeUnmount(() => {
    window.removeEventListener('popstate', updateCurrentPath);
});

    // =====| parte donde se trabaja la personalización |=====
    const colorSidebar = ref("#660D04");
    const colorNavbar = ref("#76180D");
    const colorText = ref('#FFFFFF');

    // =====| Parte donde se trabaja el menu |=====
    const baseMenus = [
        {
            key: "dashboard",
            title: "Dashboard",
            icon: "fa-solid fa-grip",
            href: "/dashboard",
            roles: [1,2,3,4,5,6] // Visible para todos los roles autenticados
        },
        {
            key: "departamentos",
            title: "Departamentos",
            icon: "fa-solid fa-list",
            href: "/dashboard/departamentos",
            roles: [1, 2] // ROOT y Admin Académico
        },
        {
            key: "notificaciones",
            title: "Notificaciones",
            icon: "fa-solid fa-inbox",
            href: "/dashboard/notificaciones",
            roles: [1,2,3,4,5,6] //Todos menos invitado
        },
        {
            key: "Aulas",
            title: "Gestión de Aulas",
            icon: "fa-solid fa-school",
            items: [
                {
                    label: "Catálogo.",
                    href: "/dashboard/catalogo",
                    icon: "fa-solid fa-list",
                    roles: [1, 6, 7] // ROOT, Estudiantes e Invitados
                },
                {
                    label: "Disponibilidad",
                    href: "/dashboard/disponibilidad",
                    icon: "fa-solid fa-calendar-days",
                    roles: [1, 2, 3, 4] // ROOT, Admin Académico, Gestores
                },
                {
                    label: "Sugerencias",
                    href: "/dashboard/sugerencia-aula",
                    icon: "fa-solid fa-lightbulb",
                    roles: [1, 2, 5] // ROOT, Admin Académico, Docentes
                },
                {
                    label: "Horarios",
                    href: "/dashboard/horarios",
                    icon: "fa-solid fa-clock",
                    roles: [1, 2] // ROOT y Admin Académico
                },
                {
                    label: "Tipos de Recursos",
                    href: "/dashboard/tipos-recursos",
                    icon: "fa-solid fa-list-check",
                    roles: [1, 2] // ROOT y Admin Académico
                },
                {
                    label: "Sesiones de Clase",
                    href: "/dashboard/sesiones-clase",
                    icon: "fa-solid fa-calendar-check",
                    roles: [1, 2, 3, 4, 5] // Administrativos y Docentes
                },
                {
                    label: "Consultar Disponibilidad",
                    href: "/dashboard/consultar-disponibilidad",
                    icon: "fa-solid fa-search",
                    roles: [5], // Solo para docentes
                },
                {
                    label: "Mi Historial de Aulas",
                    href: "/dashboard/mi-historial-aulas",
                    icon: "fa-solid fa-history",
                    roles: [5], // Solo para docentes
                },
                {
                    label: "Mantenimiento",
                    href: "/dashboard/mantenimientos",
                    icon: "fa-solid fa-screwdriver-wrench",
                    roles: [1, 2, 3] // ROOT, Admin Académico, Gestor Departamento
                }
            ]
        },
        {
            key: "Usuarios-sistema",
            title: "Usuarios",
            icon: "fa-solid fa-users",
            items: [
                {
                    label: "Docentes.",
                    href: "/dashboard/docentes",
                    icon: "fa-solid fa-chalkboard-user",
                    roles: [1, 2] // ROOT y Admin Académico
                },
                {
                    label: "Estudiantes.",
                    href: "/dashboard/estudiantes",
                    icon: "fa-solid fa-user",
                    roles: [1, 2] // ROOT y Admin Académico
                },
                {
                    label: "Grupos",
                    href: "/dashboard/grupos",
                    icon: "fa-solid fa-layer-group",
                    roles: [1, 2] // ROOT y Admin Académico
                },
                {
                    label: "Solicitudes Inscripción",
                    href: "/dashboard/solicitudes-inscripcion",
                    icon: "fa-solid fa-file-signature",
                    roles: [1, 2, 5] // ROOT, Admin Académico, Docentes
                },
                {
                    label: "Roles",
                    href: "/dashboard/roles",
                    icon: "fa-solid fa-user-shield",
                    roles: [1] // Solo ROOT
                },
                {
                    label: "Materias",
                    href: "/dashboard/materias",
                    icon: "fa-solid fa-book",
                    roles: [1, 2, 3, 4] // ROOT, Admin Académico, Gestores
                }
            ]
        },
        {
            key: "Reportes-sistema",
            title: "Reportes",
            icon: "fa-solid fa-folder-open",
            items: [
                {
                    label: "Gestión de reportes",
                    href: "/dashboard/informes",
                    icon: "fa-solid fa-file",
                    roles: [1, 2] // ROOT y Admin Académico
                },
                {
                    label: "Incidencias",
                    href: "/dashboard/incidencias",
                    icon: "fa-solid fa-triangle-exclamation",
                    roles: [1, 2, 3, 4] // Administrativos y Docentes
                },
            ]
        }
    ];

    // Filtrar menús según el rol del usuario
    const menus = computed(() => {
        if (!user.value || !user.value.role_id) {
            console.log('Usuario no tiene rol_id:', user.value);
            return [];
        }

        const userRoleId = user.value.role_id;
        console.log('Rol del usuario:', userRoleId);

        const filteredMenus = baseMenus.map(menu => {
            // Para menús sin items (como Dashboard), verificar si tiene acceso directo
            if (!menu.items) {
                const hasAccess = !menu.roles || menu.roles.length === 0 || menu.roles.includes(userRoleId);
                console.log('Menú sin items:', menu.key, 'Acceso:', hasAccess);
                return hasAccess ? menu : null;
            }

            // Para menús con items, filtrar los items visibles
            const filteredItems = menu.items.filter(item => {
                // Si el item no tiene roles definidos, es visible para todos
                if (!item.roles || item.roles.length === 0) {
                    return true;
                }
                // Verificar si el rol del usuario está en los roles permitidos
                const hasAccess = item.roles.includes(userRoleId);
                console.log('Item:', item.label, 'Acceso:', hasAccess);
                return hasAccess;
            });

            console.log('Menú:', menu.key, 'Items filtrados:', filteredItems.length);

            return filteredItems.length > 0 ? { ...menu, items: filteredItems } : null;
        }).filter(menu => menu !== null); // Ocultar menús sin items visibles

        console.log('Menús finales filtrados:', filteredMenus.length);
        return filteredMenus;
    });

    // Función helper para verificar si el usuario tiene acceso a una ruta
    const hasAccessToRoute = (href, allowedRoles = []) => {
        if (!user.value || !user.value.role_id) return false;

        // Si no se especifican roles permitidos, verificar en la estructura del menú
        if (allowedRoles.length === 0) {
            // Buscar en todos los menús y submenús
            for (const menu of baseMenus) {
                if (menu.items) {
                    for (const item of menu.items) {
                        if (item.href === href) {
                            if (!item.roles || item.roles.length === 0) return true;
                            return item.roles.includes(user.value.role_id);
                        }
                    }
                }
            }
            return false;
        }

        // Verificar roles específicos permitidos
        return allowedRoles.includes(user.value.role_id);
    };

    // Estado para los submenús
    const openMenus = reactive({});

    // Inicializar estado de menús - CORREGIDO
    const initializeMenuStates = () => {
        if (!user.value || !user.value.role_id) {
            console.log('No se pueden inicializar menús - usuario sin rol');
            return;
        }

        const userRoleId = user.value.role_id;
        console.log('🔧 Inicializando estados de menús para rol:', userRoleId);

        // Iterar sobre baseMenus (NO sobre la computed property)
        baseMenus.forEach(menu => {
            // Para menús sin items (enlaces directos)
            if (!menu.items) {
                const hasAccess = !menu.roles || menu.roles.length === 0 || menu.roles.includes(userRoleId);
                if (hasAccess && menu.href) {
                    // Verificar si este enlace simple está activo
                    openMenus[menu.key] = isActive(menu.href);
                }
            } else {
                // Para menús con submenús, filtrar items según rol
                const filteredItems = menu.items.filter(item => {
                    return !item.roles || item.roles.length === 0 || item.roles.includes(userRoleId);
                });

                // Abrir automáticamente si tiene una subopción activa
                openMenus[menu.key] = filteredItems.length > 0 && isMenuActive(filteredItems);

                console.log(`📋 Menú "${menu.key}": ${filteredItems.length} items, activo: ${openMenus[menu.key]}`);
            }
        });

        console.log('✅ Estados de menús inicializados:', openMenus);
    };

    function toggleMenu(key) {
        openMenus[key] = !openMenus[key];
    }

    // =====| parte donde se trabaja el dropdown |=====
    //para controlar si el dropdown está abierto o cerrado
    const dropdownOpen = ref(false);

    const dropdownRef = ref(null)

    // Función para alternar el estado del dropdown (abrir/cerrar)
    const toggleDropdown = () => {
        dropdownOpen.value = !dropdownOpen.value
    }

    // Función que se ejecuta cuando el usuario hace clic en "Cerrar sesión"
    const handleLogout = async () => {
        try {
            await authService.logout(); // llamamos al servicio
            window.location.href = '/login'      // redirige al login
        } catch (error) {
            console.error('Error al cerrar sesión:', error);
            alert('Ocurrió un error al cerrar sesión');
            window.location.href = '/login'
        }
    }

    // Estado del panel de notificaciones
    const notificationOpen = ref(false);
    const notificationsRef = ref(null);
    const notifications = ref([]); // lista completa (o limitada) de no leídas

    const API_URL = '/api';
    const unreadLimit = ref(5); // límite de items a mostrar en el panel

    const getAuthHeaders = () => ({ headers: { Authorization: `Bearer ${localStorage.getItem('token')}` } });

    const totalUnread = computed(() => notifications.value.length > 0 && notifications._totalCount ? notifications._totalCount : notifications.value.length);

    // lista limitada para mostrar en panel
    const unreadList = computed(() => {
        // notifications.value puede venir ya limitada; aquí aseguramos el slice por seguridad
        return (notifications.value || []).slice(0, unreadLimit.value);
    });

    function toggleNotifications(e) {
        notificationOpen.value = !notificationOpen.value;
        if (e && e.stopPropagation) e.stopPropagation();
    }

    async function fetchUnreadNotifications() {
        const token = authService.getToken();
        if (!token) {
            // no hay token; no intentamos llamada
            notificationCount.value = 0;
            notifications.value = [];
            return;
        }
        try {
            const res = await axios.get(`${API_URL}/notifications/get/my/unread`, getAuthHeaders());
            if (res.data?.success) {
                // backend devuelve array; guardamos los items y el conteo real
                const data = res.data.data || [];
                // Guardamos total en una propiedad no reactiva formal (simple hack)
                notifications.value = data.slice(0, unreadLimit.value); // mostrar limitado
                // si el backend devolviera más, queremos el conteo real; intentar leer header o usar longitud real
                // asumimos que data contiene sólo las no leídas; si desea contar total real, ajustar backend.
                notificationCount.value = (res.data.data ?? []).length;
            } else {
                notifications.value = [];
                notificationCount.value = 0;
            }
        } catch (e) {
            console.error('Error fetching unread notifications', e);
            notifications.value = [];
            notificationCount.value = 0;
        }
    }

    function markAllRead() {
        // Marcar todas las no leídas visibles como leídas (llamar endpoint para cada id)
        const token = authService.getToken();
        if (!token) {
            notifications.value = [];
            notificationCount.value = 0;
            notificationOpen.value = false;
            return;
        }
        const ids = (notifications.value || []).map(n => n.id).filter(Boolean);
        if (!ids.length) {
            // nada que marcar
            notifications.value = [];
            notificationCount.value = 0;
            notificationOpen.value = false;
            return;
        }
        Promise.all(ids.map(id => axios.patch(`${API_URL}/notifications/mark-read/${id}`, {}, getAuthHeaders()).catch(()=>null)))
            .then(()=> {
                // refrescar bandeja y contador
                notifications.value = [];
                notificationCount.value = 0;
                notificationOpen.value = false;
            })
            .catch((err)=> {
                console.error('Error marcando notificaciones como leídas', err);
            });
     }

    function goToAllNotifications() {
        // temporalmente redirigir a una ruta de notificaciones si existe
        notificationOpen.value = false;
        router.get('/dashboard/notificaciones');
    }

    // Función que detecta si se ha hecho clic fuera del dropdown
    const handleClickOutside = (event) => {
        const target = event.target;
        // Cerrar dropdown usuario si clic fuera
        if (dropdownRef.value && !dropdownRef.value.contains(target)) {
            dropdownOpen.value = false;
        }
        // Cerrar panel de notificaciones si clic fuera
        if (notificationsRef.value && !notificationsRef.value.contains(target)) {
            notificationOpen.value = false;
        }
    }

    onMounted(() => {
        // Se añade un listener al documento para detectar clics en cualquier parte de la página
        document.addEventListener('click', handleClickOutside)
    })

    onBeforeUnmount(() => {
        // Se elimina el listener de clics para evitar fugas de memoria
        document.removeEventListener('click', handleClickOutside)
    })
</script>

<style scoped>
    .content-logo{
        width: 100%;
        height: 90px;
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    .Logosystem{
        width: 170px;
        display: block;
    }

    /* Scrollbar general */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    /* Fondo de la scrollbar */
    ::-webkit-scrollbar-track {
        background: #660D04;
        border-radius: 10px;
    }

    /* Barra deslizante */
    ::-webkit-scrollbar-thumb {
        background: #b3b3b3;
        border-radius: 10px;
    }

    /* Asegura que el sidebar-link sea visible en móviles */
    .sidebar-link {
        white-space: nowrap;
    }

    /* Para Mejorar la experiencia táctil en móviles */
    @media (max-width: 1024px) {
        .sidebar-link,
        .sidebar-submenu-link {
            min-height: 44px;
        }
    }

    /* Estilos para enlaces activos del sidebar */
    .sidebar-link,
    .sidebar-submenu-link {
        position: relative;
        transition: all 0.2s ease-in-out;
    }

    .sidebar-link:hover,
    .sidebar-submenu-link:hover {
        background-color: rgba(255, 255, 255, 0.05);
        transform: translateX(2px);
    }

    /* Efecto de brillo para enlaces activos */
    .sidebar-link.font-semibold,
    .sidebar-submenu-link.font-semibold {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    /* Animación suave para el borde izquierdo */
    .sidebar-link[style*="border-left"],
    .sidebar-submenu-link[style*="border-left"] {
        animation: slideInBorder 0.3s ease-out;
    }

    @keyframes slideInBorder {
        from {
            border-left-width: 0;
            transform: translateX(-3px);
        }
        to {
            border-left-width: 3px;
            transform: translateX(0);
        }
    }

    /* Badge sobre la campana */
    .notification-badge {
        background: #eb6238;
        position: absolute;
        top: -6px;
        right: -8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        box-shadow: 0 1px 3px rgba(0,0,0,0.25);
    }

    .notifications-panel {
        min-width: 18rem;
    }

    .notification-item {
        background: white;
    }
</style>
