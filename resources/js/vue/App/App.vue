<script setup>
import {computed, onMounted, provide, ref, watch} from 'vue';
import axios from 'axios';
import AppHeader from "./AppHeader.vue";
import Mtr from '../Mtr.vue';
import Traceroute from '../Traceroute.vue';
import Ping from '../Ping.vue';

const title = ref(app.name);
const app_url = ref(app.url);

const hostname = ref(null);

const clientIp = ref(null);

const titleBanner = computed(() => {
    if (hostname.value) {
        return `Results for`;
    }

    return `Your IP is`;
});

const messages = ref({
    'ping': [],
    'mtr': [],
    'traceroute': []
});

provide('title', title);
provide('app_url', app_url);
provide('hostname', hostname);
provide('client_ip', clientIp);

function getPingResults() {
    axios.get(`/api/ping/${hostname.value}`)
    .then(response => {
        messages.value.ping = response.data.output;
    });
}

function getTracerouteResults() {
    axios.get(`/api/traceroute/${hostname.value}`)
    .then(response => {
        messages.value.traceroute = response.data.output;
    });
}

function getAll() {
    getPingResults();
    getTracerouteResults();
}

async function getClientIp() {
    try {
        clientIp.value = await axios.get(`/api/ip`).then(response => response.data.ip);
        return clientIp.value;
    } catch (error) {
        console.error(error);
    }
}

watch(hostname, (newValue, oldValue) => {
    if (newValue === '') {
        hostname.value = null;
    }
});

onMounted(() => {
    getClientIp();
});

</script>

<template>

    <app-header></app-header>

    <!-- Section 2 -->
    <section class="h-auto bg-white">
        <div class="px-10 py-24 mx-auto max-w-7xl">
            <div class="w-full mx-auto text-left md:text-center">
                <h1 class="mb-6 text-5xl font-extrabold leading-none max-w-5xl mx-auto tracking-normal text-gray-900 sm:text-6xl md:text-6xl lg:text-7xl md:tracking-tight">
                    {{ titleBanner }}<br class="lg:block hidden">
                    <span class="w-full text-transparent bg-clip-text bg-gradient-to-r from-green-400 via-blue-500 to-purple-500 lg:inline" v-text="hostname ?? clientIp"></span>
                </h1>
            </div>

            <div class="col-span-full">
                <label for="about" class="block text-sm font-medium leading-6 text-gray-900">Hostname or IP</label>
                <div class="mt-2">
                    <input type="text" v-model="hostname" v-bind:placeholder="'Enter a hostname/ip to target or leave blank to use '+clientIp"
                           class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6" />
                </div>
            </div>
        </div>
    </section>

    <mtr v-if="clientIp" :client-ip="clientIp" :hostname="hostname"></mtr>
    <traceroute v-if="clientIp" :client-ip="clientIp" :hostname="hostname"></traceroute>
    <ping v-if="clientIp" :client-ip="clientIp" :hostname="hostname"></ping>

    <!-- Section 3 -->
    <section class="flex items-center justify-center py-10 text-white bg-white sm:py-16 md:py-24 lg:py-32">
        <div class="relative max-w-3xl px-10 text-center text-white auto lg:px-0">
            <div class="flex flex-col w-full md:flex-row">

                <!-- Top Text -->
                <div class="flex justify-between">
                    <h1 class="relative flex flex-col text-6xl font-extrabold text-left text-black">
                        Crafting
                        <span>Powerful</span>
                        <span>Experiences</span>
                    </h1>
                </div>
                <!-- Right Image -->
                <div class="relative top-0 right-0 h-64 mt-12 md:-mt-16 md:absolute md:h-96">
                    <img src="https://cdn.devdojo.com/images/december2020/designs3d.png" class="object-cover mt-3 mr-5 h-80 lg:h-96">
                </div>
            </div>

            <!-- Separator -->
            <div class="my-16 border-b border-gray-300 lg:my-24"></div>

            <!-- Bottom Text -->
            <h2 class="text-left text-gray-500 xl:text-xl">
                Building beautiful designs for your next project. We've unlocked the secret to converting visitors into customers. Download our re-usable and extandable components today.
            </h2>
        </div>
    </section>

    <!-- Section 4 -->
    <section class="py-20 bg-white">
        <div class="px-4 mx-auto text-center max-w-7xl sm:px-6 lg:px-8">
            <h2 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl md:text-5xl xl:text-6xl">
                The New Standard for Design
            </h2>
            <p class="max-w-md mx-auto mt-3 text-base text-gray-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                Use our award-winning tools to help you maximize your profits. We've uncovered the correct recipe for converting visitors into customers.
            </p>
            <div class="flex justify-center mt-8 space-x-3">
                <a href="#" class="inline-flex items-center justify-center px-5 py-3 text-base font-medium text-white bg-gray-800 border border-transparent shadow hover:bg-gray-900 rounded-full">Sign Up Today</a>
                <a href="#" class="inline-flex items-center justify-center px-5 py-3 text-base font-medium text-gray-900 bg-gray-300 border border-transparent hover:bg-gray-400 rounded-full">Learn more</a>
            </div>
        </div>
    </section>

</template>

