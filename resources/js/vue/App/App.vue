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

</template>

