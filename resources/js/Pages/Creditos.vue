<template>
    <Head title="Créditos - SICA" />
    <div class="min-h-screen bg-gray-50 flex flex-col font-sans">

        <!-- Navbar -->
        <nav class="bg-[#76180D] shadow-lg sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-20">
                    <div class="flex-shrink-0 flex items-center space-x-3">
                        <div class="bg-white p-2 rounded-lg shadow-md">
                            <i class="fa-solid fa-qrcode text-2xl text-[#76180D]"></i>
                        </div>
                        <h1 class="text-2xl font-bold text-white tracking-wide">SICA</h1>
                    </div>
                    <Link href="/" class="text-white hover:text-red-100 transition-colors flex items-center gap-2">
                        <i class="fa-solid fa-arrow-left"></i>
                        Volver
                    </Link>
                </div>
            </div>
        </nav>

        <!-- Contenido Principal -->
        <main class="flex-grow py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-6xl mx-auto">
                <!-- Encabezado -->
                <div class="text-center mb-12">
                    <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                        Somos SICA
                    </h1>
                    <div class="space-y-3">
                        <p class="text-lg text-gray-700 font-medium">
                            Proyecto académico realizado por estudiantes de la materia
                        </p>
                        <p class="text-2xl font-bold text-[#76180D]">
                            Administración de Proyectos Informáticos
                        </p>
                        <div class="flex items-center justify-center gap-2 text-gray-600 mt-4">
                            <i class="fa-solid fa-graduation-cap text-xl"></i>
                            <p class="text-base">
                                Bajo la supervisión del <span class="font-semibold text-gray-800">Ing. César Misael Rodríguez Franco</span>
                            </p>
                        </div>
                    </div>
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-lg text-gray-600">
                            Aquí encontrarás a los creadores y colaboradores que hicieron posible esta plataforma
                        </p>
                    </div>
                </div>

                <!-- Equipos -->
                <div class="space-y-12">
                    <!-- Backend & Database Developers -->
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-[#76180D] to-[#660D04] px-6 py-4">
                            <h2 class="text-2xl font-bold text-white">
                                <i class="fa-solid fa-server mr-2"></i>Backend & Database Developers
                            </h2>
                        </div>
                        <div class="grid md:grid-cols-2 gap-6 p-6">
                            <div v-for="dev in backendDevelopers" :key="dev.id" class="flex items-center gap-4 p-4 hover:bg-gray-50 rounded-lg transition-colors">
                                <a v-if="dev.github" :href="dev.github" target="_blank" rel="noopener noreferrer" class="flex-shrink-0">
                                    <img 
                                        :src="`https://avatars.githubusercontent.com/${dev.github_username}`" 
                                        :alt="dev.nombre"
                                        class="w-16 h-16 rounded-full border-2 border-[#76180D] hover:border-[#660D04] transition-colors object-cover"
                                        @error="onImageError"
                                    />
                                </a>
                                <div v-else class="w-16 h-16 bg-gradient-to-br from-[#76180D] to-[#660D04] rounded-full flex items-center justify-center text-white text-2xl flex-shrink-0">
                                    <i :class="dev.icon"></i>
                                </div>
                                <div class="flex-grow">
                                    <h3 class="font-semibold text-gray-900 text-lg">{{ dev.nombre }}</h3>
                                    <p class="text-sm text-gray-600">{{ dev.roles.join(', ') }}</p>
                                    <a v-if="dev.github" :href="dev.github" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-[#76180D] hover:text-[#660D04] font-medium mt-2 transition-colors">
                                        <i class="fa-brands fa-github"></i>
                                        {{ dev.github_username }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Frontend Developer -->
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                            <h2 class="text-2xl font-bold text-white">
                                <i class="fa-solid fa-mobile-screen mr-2"></i>Frontend Developer (Aplicación Móvil)
                            </h2>
                        </div>
                        <div class="grid md:grid-cols-1 gap-6 p-6 justify-items-center">
                            <div v-for="dev in frontendDevelopers" :key="dev.id" class="flex items-center gap-4 p-4 hover:bg-gray-50 rounded-lg transition-colors w-full md:w-96">
                                <a v-if="dev.github" :href="dev.github" target="_blank" rel="noopener noreferrer" class="flex-shrink-0">
                                    <img 
                                        :src="`https://avatars.githubusercontent.com/${dev.github_username}`" 
                                        :alt="dev.nombre"
                                        class="w-16 h-16 rounded-full border-2 border-blue-600 hover:border-blue-700 transition-colors object-cover"
                                        @error="onImageError"
                                    />
                                </a>
                                <div v-else class="w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-700 rounded-full flex items-center justify-center text-white text-2xl flex-shrink-0">
                                    <i :class="dev.icon"></i>
                                </div>
                                <div class="flex-grow">
                                    <h3 class="font-semibold text-gray-900 text-lg">{{ dev.nombre }}</h3>
                                    <p class="text-sm text-gray-600">{{ dev.roles.join(', ') }}</p>
                                    <a v-if="dev.github" :href="dev.github" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-700 font-medium mt-2 transition-colors">
                                        <i class="fa-brands fa-github"></i>
                                        {{ dev.github_username }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- UI Developers -->
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                            <h2 class="text-2xl font-bold text-white">
                                <i class="fa-solid fa-palette mr-2"></i>UI Developers
                            </h2>
                        </div>
                        <div class="grid md:grid-cols-2 gap-6 p-6">
                            <div v-for="dev in uiDevelopers" :key="dev.id" class="flex items-center gap-4 p-4 hover:bg-gray-50 rounded-lg transition-colors">
                                <a v-if="dev.github" :href="dev.github" target="_blank" rel="noopener noreferrer" class="flex-shrink-0">
                                    <img 
                                        :src="`https://avatars.githubusercontent.com/${dev.github_username}`" 
                                        :alt="dev.nombre"
                                        class="w-16 h-16 rounded-full border-2 border-purple-600 hover:border-purple-700 transition-colors object-cover"
                                        @error="onImageError"
                                    />
                                </a>
                                <div v-else class="w-16 h-16 bg-gradient-to-br from-purple-600 to-purple-700 rounded-full flex items-center justify-center text-white text-2xl flex-shrink-0">
                                    <i :class="dev.icon"></i>
                                </div>
                                <div class="flex-grow">
                                    <h3 class="font-semibold text-gray-900 text-lg">{{ dev.nombre }}</h3>
                                    <p class="text-sm text-gray-600">{{ dev.roles.join(', ') }}</p>
                                    <a v-if="dev.github" :href="dev.github" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-purple-600 hover:text-purple-700 font-medium mt-2 transition-colors">
                                        <i class="fa-brands fa-github"></i>
                                        {{ dev.github_username }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Software Testers -->
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4">
                            <h2 class="text-2xl font-bold text-white">
                                <i class="fa-solid fa-flask mr-2"></i>Software Testers
                            </h2>
                        </div>
                        <div class="grid md:grid-cols-2 gap-6 p-6">
                            <div v-for="dev in testers" :key="dev.id" class="flex items-center gap-4 p-4 hover:bg-gray-50 rounded-lg transition-colors">
                                <a v-if="dev.github" :href="dev.github" target="_blank" rel="noopener noreferrer" class="flex-shrink-0">
                                    <img 
                                        :src="`https://avatars.githubusercontent.com/${dev.github_username}`" 
                                        :alt="dev.nombre"
                                        class="w-16 h-16 rounded-full border-2 border-green-600 hover:border-green-700 transition-colors object-cover"
                                        @error="onImageError"
                                    />
                                </a>
                                <div v-else class="w-16 h-16 bg-gradient-to-br from-green-600 to-green-700 rounded-full flex items-center justify-center text-white text-2xl flex-shrink-0">
                                    <i :class="dev.icon"></i>
                                </div>
                                <div class="flex-grow">
                                    <h3 class="font-semibold text-gray-900 text-lg">{{ dev.nombre }}</h3>
                                    <p class="text-sm text-gray-600">{{ dev.roles.join(', ') }}</p>
                                    <a v-if="dev.github" :href="dev.github" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-green-600 hover:text-green-700 font-medium mt-2 transition-colors">
                                        <i class="fa-brands fa-github"></i>
                                        {{ dev.github_username }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Documentadores -->
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                        <div class="bg-gradient-to-r from-yellow-600 to-yellow-700 px-6 py-4">
                            <h2 class="text-2xl font-bold text-white">
                                <i class="fa-solid fa-book mr-2"></i>Documentadores
                            </h2>
                        </div>
                        <div class="grid md:grid-cols-2 gap-6 p-6">
                            <div v-for="dev in documentadores" :key="dev.id" class="flex items-center gap-4 p-4 hover:bg-gray-50 rounded-lg transition-colors">
                                <a v-if="dev.github" :href="dev.github" target="_blank" rel="noopener noreferrer" class="flex-shrink-0">
                                    <img 
                                        :src="`https://avatars.githubusercontent.com/${dev.github_username}`" 
                                        :alt="dev.nombre"
                                        class="w-16 h-16 rounded-full border-2 border-yellow-600 hover:border-yellow-700 transition-colors object-cover"
                                        @error="onImageError"
                                    />
                                </a>
                                <div v-else class="w-16 h-16 bg-gradient-to-br from-yellow-600 to-yellow-700 rounded-full flex items-center justify-center text-white text-2xl flex-shrink-0">
                                    <i :class="dev.icon"></i>
                                </div>
                                <div class="flex-grow">
                                    <h3 class="font-semibold text-gray-900 text-lg">{{ dev.nombre }}</h3>
                                    <p class="text-sm text-gray-600">{{ dev.roles.join(', ') }}</p>
                                    <a v-if="dev.github" :href="dev.github" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-yellow-600 hover:text-yellow-700 font-medium mt-2 transition-colors">
                                        <i class="fa-brands fa-github"></i>
                                        {{ dev.github_username }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Proyecto -->
                <div class="mt-12 bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">
                        <i class="fa-solid fa-info-circle mr-2 text-[#76180D]"></i>Sobre el Proyecto
                    </h2>
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-2">SICA</h3>
                            <p class="text-gray-600">
                                Sistema de Control de Asistencia Automatizada - Una plataforma integral que optimiza el control de asistencia, gestiona aulas y recursos, y mantiene a toda la comunidad educativa conectada con SICA. La plataforma oficial de la Facultad Multidisciplinaria Oriental.
                            </p>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-2">Tecnologías</h3>
                            <ul class="text-gray-600 space-y-1">
                                <li><strong>Backend:</strong> Laravel</li>
                                <li><strong>Frontend:</strong> Vue 3 + Inertia.js</li>
                                <li><strong>Mobile:</strong> Flutter</li>
                                <li><strong>Database:</strong> MySQL</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-gray-800 text-white mt-12">
            <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-gray-400">
                    &copy; {{ new Date().getFullYear() }} UNIVERSIDAD DE EL SALVADOR - FACULTAD MULTIDISCIPLINARIA ORIENTAL. Todos los derechos reservados.
                </p>
            </div>
        </footer>

    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';

const onImageError = (event) => {
    // Si la imagen no carga, mantener el elemento como está
    console.warn('Error cargando imagen de GitHub:', event.target.src);
};

const backendDevelopers = [
    {
        id: 1,
        nombre: 'Hugo Alexander Ulloa Serpas',
        roles: ['Backend Developer', 'Database Developer'],
        github: 'https://github.com/alexus21',
        github_username: 'alexus21',
        icon: 'fa-solid fa-code'
    },
    {
        id: 2,
        nombre: 'Ángel Gabriel Vásquez Crespo',
        roles: ['Backend Developer', 'Database Developer'],
        github: 'https://github.com/yurdeth',
        github_username: 'yurdeth',
        icon: 'fa-solid fa-code'
    },
    {
        id: 3,
        nombre: 'Ángel Fernando Iraheta Sánchez',
        roles: ['Backend Developer'],
        github: 'https://github.com/AngeLSanchez210',
        github_username: 'AngeLSanchez210',
        icon: 'fa-solid fa-code'
    }
];

const frontendDevelopers = [
    {
        id: 4,
        nombre: 'Diego Isaac García Pimentel',
        roles: ['Frontend Developer', 'Aplicación Móvil'],
        github: 'https://github.com/DIGP1',
        github_username: 'DIGP1',
        icon: 'fa-solid fa-mobile-screen'
    }
];

const uiDevelopers = [
    {
        id: 5,
        nombre: 'John Elvis Arbaiza Velásquez',
        roles: ['UI Developer, Designer'],
        github: 'https://github.com/JohnArbaiza1',
        github_username: 'JohnArbaiza1',
        icon: 'fa-solid fa-palette'
    },
    {
        id: 6,
        nombre: 'Luis Emilio González Molina',
        roles: ['UI Developer', 'Software Tester'],
        github: 'https://github.com/gnz-e',
        github_username: 'gnz-e',
        icon: 'fa-solid fa-palette'
    },
    {
        id: 7,
        nombre: 'Josué Isaac Herrera Campos',
        roles: ['UI Developer', 'Software Tester'],
        github: 'https://github.com/Isaac684',
        github_username: 'Isaac684',
        icon: 'fa-solid fa-palette'
    },
    {
        id: 8,
        nombre: 'Jaime Enrique Serrano',
        roles: ['UI Developer'],
        github: 'https://github.com/EnriqueGranados',
        github_username: 'EnriqueGranados',
        icon: 'fa-solid fa-palette'
    }
];

const testers = [
    {
        id: 9,
        nombre: 'Luis Emilio González Molina',
        roles: ['Software Tester'],
        github: 'https://github.com/gnz-e',
        github_username: 'gnz-e',
        icon: 'fa-solid fa-flask'
    },
    {
        id: 10,
        nombre: 'Josué Isaac Herrera Campos',
        roles: ['Software Tester'],
        github: 'https://github.com/Isaac684',
        github_username: 'Isaac684',
        icon: 'fa-solid fa-flask'
    }
];

const documentadores = [
    {
        id: 11,
        nombre: 'Erick Adony López Meléndez',
        roles: ['Documentador'],
        github: 'https://github.com/lm21009UES',
        github_username: 'lm21009UES',
        icon: 'fa-solid fa-book'
    },
    {
        id: 12,
        nombre: 'Karla Patricia Orellana',
        roles: ['Documentadora'],
        github: 'https://github.com/KarlaOH20003',
        github_username: 'KarlaOH20003',
        icon: 'fa-solid fa-book'
    }
];
</script>

<style scoped>
/* Estilos personalizados */
</style>
