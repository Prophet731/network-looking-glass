<script setup>
import {inject, onMounted, ref} from 'vue';

// get title from App.vue
const title = inject('title');
const app_url = inject('app_url');
</script>

<template>
    <!-- Section 1 -->
    <section class="relative w-full px-8 text-gray-700 bg-white dark:bg-slate-900 body-font">
        <div class="container flex flex-col flex-wrap items-center justify-between py-5 mx-auto md:flex-row max-w-7xl">
            <a v-bind:href="app_url" class="relative z-10 flex items-center w-auto text-2xl font-extrabold leading-none dark:text-white text-black select-none">{{ title }}</a>
        </div>
    </section>
</template>

<style scoped>

</style>
