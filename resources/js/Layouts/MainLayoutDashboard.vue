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
                <Link
                href="/dashboard"
                class="sidebar-link p-2 rounded flex items-center gap-2 transition-all duration-200"
                :class="{ 'bg-white/10 font-semibold': isActive('/dashboard') }"
                :style="{
                    color: colorText,
                    borderLeft: isActive('/dashboard') ? '3px solid #ffffff' : 'none'
                }">
                    <i class="fa-solid fa-grip"></i>
                    Dashboard
                </Link>

                <Link
                href="/dashboard/departamentos"
                class="sidebar-link p-2 rounded flex items-center gap-2 transition-all duration-200"
                :class="{ 'bg-white/10 font-semibold': isActive('/dashboard/departamentos') }"
                :style="{
                    color: colorText,
                    borderLeft: isActive('/dashboard/departamentos') ? '3px solid #ffffff' : 'none'
                }">
                    <i class="fa-solid fa-list"></i>
                    Departamentos
                </Link>

            <!-- Submenús -->
            <div v-for="(menu, index) in menus" :key="index">
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
                        :key="i"
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
                        <div class="relative">
                            <Link
                            href="#"
                            class="sidebar-link flex items-center gap-2 p-3 rounded-lg transition-colors duration-300
                                    hover:text-white shadow-lg hover:shadow-md min-w-[44px] min-h-[44px]"
                            :style="{ color: colorText }"
                            >
                                <!-- Icono con posición relativa para el badge en móvil -->
                                <div class="relative">
                                    <i class="fa-solid fa-bell text-base sm:text-lg"></i>
                                    <!-- Badge para móvil -->
                                    <span
                                        v-if="notificationCount > 0"
                                        class="absolute -top-1 -right-1 text-white text-xs font-bold flex items-center justify-center rounded-full
                                            transform hover:-translate-y-0.5 transition-all duration-200 shadow-md sm:hidden
                                            min-w-[16px] min-h-[16px] px-1"
                                        :style="{background:'#eb6238'}"
                                    >
                                        {{ notificationCount }}
                                    </span>
                                </div>
                                <span class="hidden sm:inline">Notificaciones</span>
                            </Link>
                            <!-- Badge para escritorio -->
                            <span
                                v-if="notificationCount > 0"
                                class="hidden sm:inline absolute top-2 right-0 text-white text-xs font-bold flex items-center justify-center rounded-full
                                    transform hover:-translate-y-0.5 transition-all duration-200 shadow-md
                                    min-w-[20px] min-h-[20px] px-1"
                                :style="{background:'#eb6238'}"
                            >
                                {{ notificationCount }}
                            </span>
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
    import { ref, reactive, onMounted, onBeforeUnmount } from "vue";
    import { Link } from "@inertiajs/vue3";
    import { router } from '@inertiajs/vue3';
    import { authService } from "@/Services/authService";

const user = ref(null);
const sidebarOpen = ref(false); // Control del sidebar en móviles
const notificationCount = ref(5); // simulando como se veria

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
});

onBeforeUnmount(() => {
    window.removeEventListener('popstate', updateCurrentPath);
});

    // =====| parte donde se trabaja la personalización |=====
    const colorSidebar = ref("#660D04");
    const colorNavbar = ref("#76180D");
    const colorText = ref('#FFFFFF');

    // =====| Parte donde se trabaja el menu |=====
    const menus = [
        {
                key: "Aulas",
                title: "Gestión de Aulas",
                icon: "fa-solid fa-school",
                items: [
                    {
                        label: "Catálogo.",
                        href: "/dashboard/catalogo",
                        icon: "fa-solid fa-list",
                    },
                    {
                        label: "Disponibilidad",
                        href: "/dashboard/disponibilidad",
                        icon: "fa-solid fa-calendar-days",
                    },
                                        {
                        label: "Sugerencias",
                        href: "/dashboard/sugerencia-aula",
                        icon: "fa-solid fa-lightbulb",
                    },
                    {
                        label: "Horarios",
                        href: "/dashboard/horarios",
                        icon: "fa-solid fa-clock",
                    },
                    {
                        label: "Tipos de Recursos",
                        href: "/dashboard/tipos-recursos",
                        icon: "fa-solid fa-list-check",
                    },
                    {
                        label: "Sesiones de Clase",
                        href: "/dashboard/sesiones-clase",
                        icon: "fa-solid fa-calendar-check",
                    },
                    {
                        label: "Mantenimiento",
                        href: "/dashboard/mantenimientos",
                        icon: "fa-solid fa-screwdriver-wrench",
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
                },
                {
                    label: "Estudiantes.",
                    href: "/dashboard/estudiantes",
                    icon: "fa-solid fa-user",
                },
                {
                    label: "Grupos",
                    href: "/dashboard/grupos",
                    icon: "fa-solid fa-layer-group",
                },
                {
                    label: "Solicitudes Inscripción",
                    href: "/dashboard/solicitudes-inscripcion",
                    icon: "fa-solid fa-file-signature",
                },
                {
                    label: "Roles",
                    href: "/dashboard/roles",
                    icon: "fa-solid fa-user-shield",
                },
                {
                    label: "Materias",
                    href: "/dashboard/materias",
                    icon: "fa-solid fa-book",
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
                },
                {
                    label: "Incidencias",
                    href: "#",
                    icon: "fa-solid fa-triangle-exclamation",
                },
            ]
        }
    ];

    // Estado para los submenús
    const openMenus = reactive({});

    // Inicializar estado de menús
    const initializeMenuStates = () => {
        menus.forEach((menu) => {
            // Abrir automáticamente el menú si tiene una subopción activa
            openMenus[menu.key] = isMenuActive(menu.items);
        });
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

    // Función que detecta si se ha hecho clic fuera del dropdown
    const handleClickOutside = (event) => {
        // Verifica si el clic fue fuera del dropdown
        if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
                dropdownOpen.value = false // Cierra el dropdown si el clic fue fuera
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
</style>
