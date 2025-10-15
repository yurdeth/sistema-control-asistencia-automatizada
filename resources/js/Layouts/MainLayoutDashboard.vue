<template>
    <div class="flex h-screen bg-gray-100">

        <!--Sidebar-->
        <aside
            :style="{
                backgroundColor: colorSidebar,
                '--sidebar-text-color': colorText
            }"
            class="w-64 p-4 pr-0 overflow-x-hidden"
        >
            <div class="content-logo">
                <img class="Logosystem" src="/Images/Idea Logo Proyecto.png" alt="Logo">
            </div>
            <nav
                class="flex flex-col space-y-4"
                style="padding-bottom: 5rem"
            >
                <Link
                href="/dashboard"
                class="sidebar-link p-2 rounded flex items-center gap-2"
                :style="{ color: colorText }">
                    <i class="fa-solid fa-grip"></i>
                    Dashboard
                </Link>

                <Link
                href="/departamentos"
                class="sidebar-link p-2 rounded flex items-center gap-2"
                :style="{ color: colorText }">
                    <i class="fa-solid fa-list"></i>
                    Departamentos
                </Link>

            <!-- Submenús -->
            <div v-for="(menu, index) in menus" :key="index">
                <button
                    @click="toggleMenu(menu.key)"
                    class="sidebar-link p-2 rounded flex justify-between items-center w-full "
                    :style="{color: colorText }"
                >
                    <span class="flex items-center gap-2">
                        <i :class="menu.icon" :style="{ color: colorText }"></i>
                        {{ menu.title }}
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
                        class="sidebar-submenu-link block p-2 rounded flex items-center gap-2"
                        :style="{ color: colorText }"
                    >
                        <i :class="item.icon" :style="{ color: colorText }"></i>
                        {{ item.label }}
                    </Link>

                </div>
            </div>
            </nav>
        </aside>

        <!-- navbar -->
        <div class="flex-1 flex flex-col">
            <header :style="{ backgroundColor: colorNavbar }" class="text-white p-4 h-13">
                <nav class="flex items-center justify-between">
                    <div></div>
                    <div class="sm:ms-6 sm:flex sm:items-center" v-if="user && user.nombre_completo">
                        <!-- Custom Dropdown -->
                        <div class="relative" ref="dropdownRef">
                            <button
                                @click="toggleDropdown"
                                type="button"
                                class="inline-flex items-center rounded-md border border-transparent bg-[#76180D] text-white px-3 py-2 text-sm font-medium leading-4 transition duration-150 ease-in-out hover:bg-[#5f130b] focus:outline-none"
                            >
                                {{ user.nombre_completo }}
                                <svg class="ms-2 h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div
                                v-if="dropdownOpen"
                                class="absolute right-0 mt-2 w-48 bg-white text-black rounded-md shadow-lg z-50"
                            >
                                <div class="px-4 py-2 text-xs text-gray-500 border-b">
                                    {{ user.nombre_completo }}
                                </div>

                                <button
                                    @click="handleLogout"
                                    class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100"
                                >
                                    Cerrar sesión
                                </button>
                            </div>
                        </div>
                    </div>
                </nav>
            </header>

            <!--Contenido de inicio-->
            <main class="flex-1 overflow-y-auto bg-gray-100">
                <!-- Mostrar mensaje de bienvenida si no hay contenido en el slot -->
                <template v-if="$slots.default">
                    <slot />
                </template>
                <!-- <template v-else>
                    <div class="flex items-center justify-center mensaje">
                        <h1 class="text-4xl text-blue-500 text-center">
                        👋 Bienvenido al sistema. <br />Selecciona una opción del menú para
                        comenzar.
                        </h1>
                    </div>
                </template> -->
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

onMounted(() => {
    const storedUser = localStorage.getItem('user');
    if (storedUser) {
        try {
            user.value = JSON.parse(storedUser);
        } catch (e) {
            console.error('Error parsing localStorage user:', e);
        }
    }
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
                        label: "Catalogo.",
                        href: "/catalogo",
                        icon: "fa-solid fa-list",
                    },
                    {
                        label: "Disponibilidad",
                        href: "/disponibilidad",
                        icon: "fa-solid fa-calendar-days",
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
                    href: "#",
                    icon: "fa-solid fa-chalkboard-user",
                },
                {
                    label: "Estudiantes.",
                    href: "#",
                    icon: "fa-solid fa-user",
                }
            ]
        },
                {
            key: "Reportes-sistema",
            title: "Reportes",
            icon: "fa-solid fa-folder-open",
            items: [
                {
                    label: "Aulas Fuera de Servicio",
                    href: "#",
                    icon: "fa-solid fa-file",
                },
                {
                    label: "Asignaciones Recurrentes",
                    href: "#",
                    icon: "fa-solid fa-file",
                },
                {
                    label: "Exportar reportes",
                    href: "#",
                    icon: "fa-solid fa-file-import",
                }
            ]
        }
    ];

    // Estado para los submenús
    const openMenus = reactive({});
        menus.forEach((menu) => {
        openMenus[menu.key] = false;
    });

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

</style>

