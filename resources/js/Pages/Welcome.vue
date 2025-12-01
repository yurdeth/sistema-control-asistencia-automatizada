<template>
    <Head title="SICA - Sistema de Control de Asistencia" />
    <div class="min-h-screen bg-gray-50 flex flex-col font-sans">

        <!--Navbar-->
        <nav :style="{ backgroundColor: colorNavbar }" class="shadow-lg sticky top-0 z-50 transition-all duration-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <!-- Logo -->
                    <div class="flex-shrink-0 flex items-center space-x-3">
                        <div class="bg-white p-2 rounded-lg shadow-md">
                            <i class="fa-solid fa-qrcode text-2xl" :style="{ color: colorNavbar }"></i>
                        </div>
                        <h1 class="text-2xl font-bold text-white tracking-wide">SICA</h1>
                    </div>

                    <div class="flex-1"></div>

                    <!-- Desktop Menu -->
                    <div class="hidden md:flex items-center space-x-6">
                        <Link
                            :href="buttonRoute"
                            class="px-6 py-2.5 text-white font-medium rounded-full hover:bg-white hover:text-burgundy transition-all duration-300 shadow-md hover:shadow-lg border-2 border-transparent hover:border-white transform hover:-translate-y-0.5"
                            :style="{ backgroundColor: 'rgba(255,255,255,0.1)' }"
                        >
                            {{ buttonText }}
                        </Link>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button
                            @click="toggleMenu"
                            class="inline-flex items-center justify-center p-2 rounded-md text-white hover:bg-white/10 focus:outline-none transition-colors"
                        >
                            <span class="sr-only">Abrir menú</span>
                            <i :class="['fa-solid', isMenuOpen ? 'fa-times' : 'fa-bars', 'text-2xl']"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div v-show="isMenuOpen" class="md:hidden bg-burgundy-dark shadow-inner">
                    <div class="px-4 pt-4 pb-6 space-y-3">
                        <Link
                            :href="buttonRoute"
                            class="block w-full text-center px-4 py-3 text-white rounded-lg font-medium bg-white/10 hover:bg-white/20 transition-colors duration-200"
                        >
                            {{ buttonText }}
                        </Link>
                    </div>
                </div>
            </transition>
        </nav>

        <!--Contenido principal-->
        <main class="flex-grow">
            <!-- Hero Section -->
            <div class="relative bg-white overflow-hidden">
                <div class="max-w-7xl mx-auto">
                    <div class="relative z-10 pb-8 bg-white sm:pb-16 md:pb-20 lg:max-w-2xl lg:w-full lg:pb-28 xl:pb-32">
                        <svg class="hidden lg:block absolute right-0 inset-y-0 h-full w-48 text-white transform translate-x-1/2" fill="currentColor" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
                            <polygon points="50,0 100,0 50,100 0,100" />
                        </svg>

                        <div class="relative pt-6 px-4 sm:px-6 lg:px-8"></div>

                        <main class="mt-10 mx-auto max-w-7xl px-4 sm:mt-12 sm:px-6 md:mt-16 lg:mt-20 lg:px-8 xl:mt-28">
                            <div class="sm:text-center lg:text-left">
                                <h1 class="text-4xl tracking-tight font-extrabold text-gray-900 sm:text-5xl md:text-6xl">
                                    <span class="block xl:inline">Gestión Académica</span>
                                    <span class="block text-burgundy xl:inline"> Inteligente y Eficiente</span>
                                </h1>
                                <p class="mt-3 text-base text-gray-500 sm:mt-5 sm:text-lg sm:max-w-xl sm:mx-auto md:mt-5 md:text-xl lg:mx-0">
                                    Optimiza el control de asistencia, gestiona aulas y recursos, y mantén a toda la comunidad educativa conectada con SICA. La plataforma oficial de la Facultad Multidisciplinaria Oriental.
                                </p>
                                <div class="mt-5 sm:mt-8 sm:flex sm:justify-center lg:justify-start">
                                    <div class="rounded-md shadow">
                                        <Link
                                            :href="buttonRoute"
                                            class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-burgundy hover:bg-burgundy-dark md:py-4 md:text-lg transition-all duration-300 transform hover:scale-105"
                                        >
                                            {{ isAuthenticated ? 'Ir al Dashboard' : 'Comenzar Ahora' }}
                                        </Link>
                                    </div>
                                    <div class="mt-3 sm:mt-0 sm:ml-3">
                                        <a href="#features" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-burgundy bg-red-100 hover:bg-red-200 md:py-4 md:text-lg transition-all duration-300">
                                            Conocer más
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </main>
                    </div>
                </div>
                <div class="lg:absolute lg:inset-y-0 lg:right-0 lg:w-1/2 bg-gray-50 flex items-center justify-center">
                    <img class="w-full h-auto object-contain sm:h-72 md:h-96 lg:w-full lg:h-full p-4 sm:p-8" src="/Images/hero-illustration-Photoroom.png" alt="SICA Dashboard Illustration">
                </div>
            </div>

            <!-- Features Section -->
            <div id="features" class="py-12 bg-gray-50">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="lg:text-center">
                        <h2 class="text-base text-burgundy font-semibold tracking-wide uppercase">Características</h2>
                        <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                            Gestión académica integral y eficiente
                        </p>
                        <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
                            SICA centraliza y automatiza los procesos académicos de nuestra facultad.
                        </p>
                    </div>

                    <div class="mt-10">
                        <dl class="space-y-10 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-10">
                            <div v-for="feature in features" :key="feature.name" class="relative">
                                <dt>
                                    <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-burgundy text-white">
                                        <i :class="feature.icon" class="text-xl"></i>
                                    </div>
                                    <p class="ml-16 text-lg leading-6 font-medium text-gray-900">{{ feature.name }}</p>
                                </dt>
                                <dd class="mt-2 ml-16 text-base text-gray-500">
                                    {{ feature.description }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Stats Section -->
            <div class="bg-burgundy">
                <div class="max-w-7xl mx-auto py-12 px-4 sm:py-16 sm:px-6 lg:px-8 lg:py-20">
                    <div class="max-w-4xl mx-auto text-center">
                        <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
                            Compromiso Institucional
                        </h2>
                        <p class="mt-3 text-xl text-red-100 sm:mt-4">
                            Comprometidos con la excelencia académica y la transparencia administrativa.
                        </p>
                    </div>
                    <dl class="mt-10 text-center sm:max-w-3xl sm:mx-auto sm:grid sm:grid-cols-3 sm:gap-8">
                        <div class="flex flex-col">
                            <dt class="order-2 mt-2 text-lg leading-6 font-medium text-red-100">
                                Disponibilidad
                            </dt>
                            <dd class="order-1 text-5xl font-extrabold text-white">
                                24/7
                            </dd>
                        </div>
                        <div class="flex flex-col mt-10 sm:mt-0">
                            <dt class="order-2 mt-2 text-lg leading-6 font-medium text-red-100">
                                Datos Protegidos
                            </dt>
                            <dd class="order-1 text-5xl font-extrabold text-white">
                                Privacidad
                            </dd>
                        </div>
                        <div class="flex flex-col mt-10 sm:mt-0">
                            <dt class="order-2 mt-2 text-lg leading-6 font-medium text-red-100">
                                Acceso Total
                            </dt>
                            <dd class="order-1 text-5xl font-extrabold text-white">
                                Web / Móvil
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-gray-800 text-white">
            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">SICA</h3>
                        <p class="text-gray-400 text-sm">
                            Sistema de Control de Asistencia Automatizada.
                            Facilitando la gestión académica de la FMO.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Enlaces Rápidos</h3>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><Link href="/login" class="hover:text-white transition-colors">Iniciar Sesión</Link></li>
                            <li><a href="#features" class="hover:text-white transition-colors">Características</a></li>
                            <li><Link href="/team" class="hover:text-white transition-colors">Equipo de desarrollo</Link></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Contacto</h3>
                        <p class="text-gray-400 text-sm">
                            Universidad de El Salvador<br>
                            Facultad Multidisciplinaria Oriental
                        </p>
                    </div>
                </div>
                <div class="mt-8 border-t border-gray-700 pt-8 text-center">
                    <p class="text-gray-400 text-sm">
                        &copy; {{ new Date().getFullYear() }} UNIVERSIDAD DE EL SALVADOR - FACULTAD MULTIDISCIPLINARIA ORIENTAL. Todos los derechos reservados.
                    </p>
                </div>
            </div>
        </footer>

    </div>
</template>

<script setup>
    import { ref, computed, onMounted } from 'vue'
    import { Head, Link } from '@inertiajs/vue3';

    // Configuración de colores
    const colorNavbar = ref("#76180D");
    const colorText = ref('#FFFFFF');
    const colorButton = ref('#882317');

    // Menú móvil
    const isMenuOpen = ref(false)
    const toggleMenu = () => {
        isMenuOpen.value = !isMenuOpen.value
    }

    // Autenticación
    const isAuthenticated = ref(false)

    onMounted(() => {
        const token = localStorage.getItem('token')
        const user = localStorage.getItem('user')
        if (token && user) {
            isAuthenticated.value = true
        }
    })

    const buttonText = computed(() => {
        return isAuthenticated.value ? 'Ir al Dashboard' : 'Iniciar Sesión'
    })

    const buttonRoute = computed(() => {
        return isAuthenticated.value ? '/dashboard' : '/login'
    })

    // Características
    const features = [
        {
            name: 'Control de Asistencia QR',
            description: 'Registro de asistencia rápido y seguro mediante códigos QR únicos por aula y sesión.',
            icon: 'fa-solid fa-qrcode',
        },
        {
            name: 'Gestión de Aulas',
            description: 'Administración eficiente de espacios físicos, recursos y disponibilidad en tiempo real.',
            icon: 'fa-solid fa-chalkboard-user',
        },
        {
            name: 'Notificaciones Instantáneas',
            description: 'Alertas automáticas sobre clases, cambios de horario y anuncios importantes.',
            icon: 'fa-solid fa-bell',
        },
        {
            name: 'App Móvil Integrada',
            description: 'Acceso total para docentes y estudiantes desde cualquier dispositivo móvil.',
            icon: 'fa-solid fa-mobile-screen',
        },
    ]
</script>

<style scoped>
.text-burgundy {
    color: #76180D;
}
.bg-burgundy {
    background-color: #76180D;
}
.bg-burgundy-dark {
    background-color: #5a120a;
}
.hover\:bg-burgundy-dark:hover {
    background-color: #5a120a;
}
</style>
