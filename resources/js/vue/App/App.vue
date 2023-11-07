<script setup>
import {computed, onMounted, provide, ref, watch} from 'vue';
import axios from 'axios';
import AppHeader from "./AppHeader.vue";
import Mtr from '../Mtr.vue';
import Traceroute from '../Traceroute.vue';
import Ping from '../Ping.vue';
import { initFlowbite } from 'flowbite'
import AsnTool from '../AsnTool.vue';

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
    initFlowbite();
    getClientIp();
});

</script>

<template>

    <app-header></app-header>

    <!-- Section 2 -->
    <section class="h-auto bg-white dark:bg-slate-900 ">
        <div class="px-10 py-24 mx-auto max-w-7xl">
            <div class="w-full mx-auto text-left md:text-center">
                <h1 class="mb-6 text-5xl font-extrabold leading-none max-w-5xl mx-auto tracking-normal text-gray-900 sm:text-6xl md:text-6xl lg:text-7xl md:tracking-tight dark:text-white">
                    {{ titleBanner }}<br class="lg:block hidden">
                    <span class="w-full text-transparent bg-clip-text bg-gradient-to-r from-green-400 via-blue-500 to-purple-500 lg:inline" v-text="hostname ?? clientIp"></span>
                </h1>
            </div>

            <div class="col-span-full">
                <label for="hostname" class="block text-sm font-medium leading-6 text-gray-900 dark:text-white">Hostname or IP</label>
                <div class="mt-2">
                    <input type="text" id="hostname" v-model="hostname" v-bind:placeholder="'Enter a hostname/ip to target or leave blank to use '+clientIp"
                           class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6"
                    />
                </div>
            </div>
        </div>
    </section>

    <div class="w-full bg-white border border-gray-200 rounded-lg shadow dark:bg-gray-800 dark:border-gray-700">
        <div class="sm:hidden">
            <label for="tabs" class="sr-only">Select tab</label>
            <select id="tabs" class="bg-gray-50 border-0 border-b border-gray-200 text-gray-900 text-sm rounded-t-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                <option>MTR</option>
                <option>Traceroute</option>
                <option>Ping</option>
                <option>ASN</option>
            </select>
        </div>
        <ul class="hidden text-sm font-medium text-center text-gray-500 divide-x divide-gray-200 rounded-lg sm:flex dark:divide-gray-600 dark:text-gray-400" id="fullWidthTab" data-tabs-toggle="#fullWidthTabContent" role="tablist">
            <li class="w-full">
                <button id="mtr-tab" data-tabs-target="#mtr" type="button" role="tab" aria-controls="stats" aria-selected="true" class="inline-block w-full p-4 rounded-tl-lg bg-gray-50 hover:bg-gray-100 focus:outline-none dark:bg-gray-700 dark:hover:bg-gray-600">MTR</button>
            </li>
            <li class="w-full">
                <button id="tr-tab" data-tabs-target="#traceroute" type="button" role="tab" aria-controls="about" aria-selected="false" class="inline-block w-full p-4 bg-gray-50 hover:bg-gray-100 focus:outline-none dark:bg-gray-700 dark:hover:bg-gray-600">Traceroute</button>
            </li>
            <li class="w-full">
                <button id="ping-tab" data-tabs-target="#ping" type="button" role="tab" aria-controls="faq" aria-selected="false" class="inline-block w-full p-4 rounded-tr-lg bg-gray-50 hover:bg-gray-100 focus:outline-none dark:bg-gray-700 dark:hover:bg-gray-600">Ping</button>
            </li>
            <li class="w-full">
                <button id="asn-tab" data-tabs-target="#asn" type="button" role="tab" aria-controls="faq" aria-selected="false" class="inline-block w-full p-4 rounded-tr-lg bg-gray-50 hover:bg-gray-100 focus:outline-none dark:bg-gray-700 dark:hover:bg-gray-600">ASN</button>
            </li>
        </ul>
        <div id="fullWidthTabContent" class="border-t border-gray-200 dark:border-gray-600">
            <div class="hidden p-4 bg-white rounded-lg md:p-8 dark:bg-slate-900" id="mtr" role="tabpanel" aria-labelledby="mtr-tab">
                <mtr v-if="clientIp" :client-ip="clientIp" :hostname="hostname"></mtr>
            </div>
            <div class="hidden p-4 bg-white rounded-lg md:p-8 dark:bg-slate-900" id="traceroute" role="tabpanel" aria-labelledby="tr-tab">
                <traceroute v-if="clientIp" :client-ip="clientIp" :hostname="hostname"></traceroute>
            </div>
            <div class="hidden p-4 bg-white rounded-lg dark:bg-slate-900" id="ping" role="tabpanel" aria-labelledby="ping-tab">
                <ping v-if="clientIp" :client-ip="clientIp" :hostname="hostname"></ping>
            </div>

            <div class="hidden p-4 bg-white rounded-lg dark:bg-slate-900" id="asn" role="tabpanel" aria-labelledby="asn-tab">
                <asn-tool></asn-tool>
            </div>
        </div>
    </div>


</template>

