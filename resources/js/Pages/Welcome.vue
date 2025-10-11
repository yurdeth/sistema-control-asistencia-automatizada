<template>
    <Head title="SICA" />
    <div class="min-h-screen bg-gray-50">

        <!--Navbar-->
        <div :style="{ backgroundColor: colorNavbar , color: colorText}" class="shadow-md sticky top-0 z-50" >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!--Parte donde debe ir el logo-->
                    <div class="flex-shrink-0 flex items-center">
                        <h1 :style="{color: colorText, fontWeight:600}" class="text-2xl font-bold">SICA</h1>
                    </div>

                    <div class="flex-1"></div>

                    <!--Opciones-->
                    <div class="hidden md:flex items-center space-x-4">
                        <Link
                            href="/login"
                            class="px-6 py-2 text-white font-medium hover:bg-blue-700 transition-colors duration-200 shadow-sm hover:shadow-md"
                            :style="{backgroundColor:colorButton, borderRadius: '6px', border: '1px solid #F2F2F2'}"
                        >
                            Iniciar Sesión
                        </Link>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="md:hidden">
                        <button
                            @click="toggleMenu"
                            class="inline-flex items-center justify-center p-2 rounded-md text-white-700 hover:text-gray-900
                            hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 transition-colors"
                        >
                            <span class="sr-only">Abrir menú</span>
                            <!-- Icono hamburguesa -->
                            <svg
                                v-if="!isMenuOpen"
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                            </svg>

                            <!-- Icono X para cerrar -->
                            <svg
                                v-else
                                class="h-6 w-6"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="opacity-0 max-h-0"
                enter-to-class="opacity-100 max-h-48"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100 max-h-48"
                leave-to-class="opacity-0 max-h-0"
            >
                <div v-show="isMenuOpen" class="md:hidden">
                    <div class="px-4 pt-2 pb-4 space-y-2 bg-gray-50 border-t border-gray-200">
                        <Link
                            href="/login"
                            class="block w-full text-center px-4 py-3 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors duration-200 shadow-sm"
                            :style="{backgroundColor:colorButton}"
                            >
                            Iniciar Sesión
                        </Link>
                    </div>
                </div>
            </transition>
        </div>

        <!--Contenido principal-->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center">
                <p class="text-xl text-gray-600 mb-8">
                    Gestione y controle las aulas de manera más eficiente con la plataforma SICA
                </p>
            </div>

                <!-- Hero section con imagen o contenido -->
                <div class="bg-white rounded-lg shadow-xl p-8 mt-12">
                    <div class="grid md:grid-cols-2 gap-8 items-center">
                        <div class="text-left">
                            <h2 class="text-3xl font-bold text-gray-800 mb-4">
                                Sistema de Gestión
                            </h2>
                            <p class="text-gray-600 mb-6">
                                Facilita la coordinación entre docentes, estudiantes y espacios. Inicia sesión y gestiona
                                las aulas de una manera diferente.
                            </p>
                            <div class="flex justify-center md:justify-start w-full">
                                <Link
                                    href="/login"
                                    class="inline-block px-8 py-3 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors duration-200 shadow-lg hover:shadow-xl"
                                    :style="{backgroundColor:colorButton}"
                                >
                                    Comenzar Ahora
                                </Link>
                            </div>
                        </div>
                        <div class="flex justify-center">
                            <div class="w-64 h-64 bg-gradient-to-br  to-blue-700 rounded-full flex items-center justify-center shadow-2xl"
                                :style="{backgroundColor:colorButton}"
                            >
                                <i class="fa-solid fa-chalkboard" style="font-size: 120px; color: white;"></i>
                            </div>
                        </div>
                    </div>
                </div>

        </main>



        <!-- Footer -->
        <footer class=" border-t border-gray-200 mt-20 text-white"
            :style="{backgroundColor:colorButton}"
        >
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <p class="text-center">
                    © UNIVERSIDAD DE EL SALVADOR - FACULTAD MULTIDISCIPLINARIA ORIENTAL
                    <br> SICA
                </p>
            </div>
        </footer>

    </div>
</template>

<script setup>
    import { ref } from 'vue'
    import { Head, Link } from '@inertiajs/vue3';

    // =====| parte donde se trabaja la personalización |=====
    const colorNavbar = ref("#76180D");
    const colorText = ref('#FFFFFF');
    const colorButton = ref('#882317');

    // =====| parte donde se trabaja el menú móvil |=====
    // Estado para controlar el menú móvil
    const isMenuOpen = ref(false)

    // Función para toggle del menú
    const toggleMenu = () => {
        isMenuOpen.value = !isMenuOpen.value
    }
</script>
