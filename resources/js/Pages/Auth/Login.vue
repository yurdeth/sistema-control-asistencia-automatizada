<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>

        <Head title="Log in" />

        <!--Contenedor principal-->
        <div class="flex min-h-screen flex-col items-center bg-gray-100 pt-6 sm:justify-center sm:pt-0">
            <div class="log-container">
                <div class="left_panel">
                    <h1 class="title">SICA</h1>
                </div>

                <div class="right_panel">
                    <h2 class="mensaje">Iniciar sesión</h2>
                    <br>

                    <form @submit.prevent="submit">
                        <div>
                            <InputLabel for="email" value="Email" />

                            <TextInput
                                id="email"
                                type="email"
                                class="mt-1 block w-full"
                                v-model="form.email"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="example@gmail.com"
                            />

                            <InputError class="mt-2" :message="form.errors.email" />
                                </div>

                                <div class="mt-4">
                                    <InputLabel for="password" value="Password" />

                                    <TextInput
                                        id="password"
                                        type="password"
                                        class="mt-1 block w-full"
                                        v-model="form.password"
                                        required
                                        autocomplete="current-password"
                                        placeholder="********"
                            />

                            <InputError class="mt-2" :message="form.errors.password" />
                                </div>

                                <!-- <div class="mt-4 block">
                                    <label class="flex items-center">
                                        <Checkbox name="remember" v-model:checked="form.remember" />
                                        <span class="ms-2 text-sm text-gray-600"
                                            >Remember me</span
                                        >
                                    </label>
                                </div> -->

                                <!-- Button de inicio -->
                                <div class="btn_inicio">
                                    <Link
                                        href="#"
                                        class="btn-ini"
                                    >
                                        Editar
                                    </Link>
                                </div>


                                <div class="mt-4 flex items-center justify-end">
                                    <Link
                                        v-if="canResetPassword"
                                        :href="route('password.request')"
                                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    >
                                        ¿Has olvidado tu contraseña?
                                    </Link>

                        </div>
                    </form>

                </div>

            </div>
        </div>

</template>

<!--Estilos del login-->
<style scoped>
    .log-container{
        width: 600px;
        border-radius: 10px;
        overflow: hidden;
        display: flex;
        flex-direction: row;
        border: 1px solid #5B0B0B;
    }


    .left_panel {
        flex: 1.2;
        background: #5B0B0B;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    }


    .right_panel {
        flex: 1.4;
        background: #F4F4F4;
        padding: 60px 50px;
        flex-direction: column;
    }

    .title {
        color: white;
        font-size: 4rem;
        font-weight: 600;
        text-align: center;
        padding: 14px 14px;
    }

    .mensaje{
        font-size: 19px;
        font-weight: 500;
        text-align: center;
    }

    .btn_inicio{
        background: #5B0B0B;
        padding: 6px;
        text-align: center;
        border-radius: 6px;
        margin-top: 14px;
    }

    .btn-ini{
        color: white;
    }


</style>
