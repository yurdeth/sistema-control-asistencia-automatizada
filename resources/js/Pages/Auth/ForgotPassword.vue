<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const isEmailValid = ref(false);

const validateEmail = (email) => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    isEmailValid.value = emailRegex.test(email);
};

const handleEmailChange = (e) => {
    form.email = e.target.value;
    validateEmail(form.email);
};

const submit = () => {
    if (isEmailValid.value) {
        form.post(route('password.email'));
    }
};
</script>

<template>

        <Head title="Recuperar Contraseña" />

        <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 flex items-center justify-center p-4">
            <div class="w-full max-w-3xl">
                <!-- Card Principal -->
                <div class="bg-white rounded-xl shadow-xl overflow-hidden border border-gray-100">
                    <!-- Header con icono -->
                    <div class="bg-gradient-to-r from-red-600 to-red-700 px-6 py-8 text-center" style="background: linear-gradient(135deg, #d93f3f 0%, #BD3838 100%);">
                        <div class="flex justify-center mb-4">
                            <div class="bg-white/20 p-4 rounded-full">
                                <i class="fas fa-lock text-white text-3xl"></i>
                            </div>
                        </div>
                        <h1 class="text-2xl font-bold text-white">Recuperar Contraseña</h1>
                        <p class="text-red-100 text-sm mt-2">Restablece el acceso a tu cuenta</p>
                    </div>

                    <!-- Contenido -->
                    <div class="p-6 md:p-8">
                        <!-- Mensaje de estado exitoso -->
                        <div
                            v-if="status"
                            class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-800 rounded-lg text-sm flex items-start gap-3"
                        >
                            <i class="fas fa-check-circle text-green-600 mt-0.5 flex-shrink-0"></i>
                            <div>
                                <p class="font-semibold">¡Éxito!</p>
                                <p>{{ status }}</p>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="mb-6">
                            <p class="text-gray-600 text-sm leading-relaxed">
                                ¿Olvidaste tu contraseña? No te preocupes, ingresa tu correo electrónico registrado y te enviaremos un enlace para restablecer tu contraseña.
                            </p>
                        </div>

                        <!-- Formulario -->
                        <form @submit.prevent="submit" class="space-y-5">
                            <div>
                                <InputLabel 
                                    for="email" 
                                    value="Correo Electrónico" 
                                    class="text-gray-700 font-semibold mb-2 block"
                                />

                                <div class="relative">
                                    <div class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                    <TextInput
                                        id="email"
                                        type="email"
                                        class="mt-1 block w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all"
                                        :class="{'border-green-500 focus:ring-green-500': isEmailValid && form.email}"
                                        v-model="form.email"
                                        @input="handleEmailChange"
                                        @keyup.enter="submit"
                                        required
                                        autofocus
                                        autocomplete="email"
                                        placeholder="nombre@ejemplo.com"
                                    />
                                    <!-- Indicador de validación -->
                                    <div v-if="form.email" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                                        <i 
                                            v-if="isEmailValid" 
                                            class="fas fa-check-circle text-green-500 text-lg"
                                        ></i>
                                        <i 
                                            v-else 
                                            class="fas fa-exclamation-circle text-red-500 text-lg"
                                        ></i>
                                    </div>
                                </div>

                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>

                            <!-- Botones de acción -->
                            <div class="flex gap-3 pt-4">
                                <Link
                                    href="/login"
                                    class="flex-1 px-4 py-3 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-50 transition-colors flex items-center justify-center gap-2"
                                >
                                    <i class="fas fa-arrow-left"></i>
                                    <span>Atrás</span>
                                </Link>
                                
                                <PrimaryButton
                                    @click="submit"
                                    :class="{ 'opacity-50 cursor-not-allowed': form.processing || !isEmailValid }"
                                    :disabled="form.processing || !isEmailValid"
                                    class="flex-1 px-4 py-3 rounded-lg font-semibold flex items-center justify-center gap-2 transition-all hover:shadow-lg"
                                    :style="{
                                        background: form.processing || !isEmailValid ? '#ccc' : 'linear-gradient(135deg, #d93f3f 0%, #BD3838 100%)',
                                        color: 'white'
                                    }"
                                >
                                    <i v-if="form.processing" class="fas fa-spinner fa-spin"></i>
                                    <i v-else class="fas fa-paper-plane"></i>
                                    <span>{{ form.processing ? 'Enviando...' : 'Enviar Enlace' }}</span>
                                </PrimaryButton>
                            </div>
                        </form>

                        <!-- Ayuda adicional -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <div class="flex items-center gap-2 text-sm text-gray-600 mb-3">
                                <i class="fas fa-lightbulb text-yellow-500"></i>
                                <span class="font-semibold">Consejos:</span>
                            </div>
                            <ul class="text-sm text-gray-600 space-y-2 ml-6">
                                <li class="flex items-start gap-2">
                                    <span class="text-red-500 mt-1">•</span>
                                    <span>Verifica que el correo sea correcto (ej: usuario@ues.edu.sv)</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-red-500 mt-1">•</span>
                                    <span>Revisa tu carpeta de spam si no recibes el correo</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-red-500 mt-1">•</span>
                                    <span>El enlace expirará en 24 horas por seguridad</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Footer -->
                        <div class="mt-6 pt-6 border-t border-gray-200 text-center">
                            <p class="text-xs text-gray-500">
                                ¿Recordaste tu contraseña? 
                                <Link 
                                    href="/login" 
                                    class="text-red-600 hover:text-red-700 font-semibold"
                                >
                                    Inicia sesión aquí
                                </Link>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Información de seguridad -->
                <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                    <p class="text-xs text-blue-800 flex items-center justify-center gap-2">
                        <i class="fas fa-shield-alt"></i>
                        <span>Tu información está protegida con encriptación de nivel universitario</span>
                    </p>
                </div>
            </div>
        </div>
</template>

<style scoped>
/* Animación de entrada suave */
@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

div {
    animation: slideInUp 0.3s ease-out;
}

/* Efecto hover en enlaces */
a {
    transition: all 0.3s ease;
}

/* Validación visual */
input:valid {
    border-color: #10b981;
}

input:invalid:not(:placeholder-shown) {
    border-color: #ef4444;
}
</style>
