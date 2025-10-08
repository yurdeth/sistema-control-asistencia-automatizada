<script setup>
import { ref } from 'vue';
import MainLayoutDashboard from './MainLayoutDashboard.vue';

const showingNavigationDropdown = ref(false);

defineProps({
    title: {
        type: String,
        default: "SICA",
    },
});
</script>



<template>

<MainLayoutDashboard>
    <slot/>
</MainLayoutDashboard>

</template>
