<script setup>
import {inject, onMounted, ref, watchEffect} from 'vue';
import Toastify from 'toastify-js';

const props = defineProps({
    hostname: {
        type: String,
        default: null
    },
    clientIp: {
        type: String,
        default: null
    }
})

const results = ref([]);

const timeoutRequest = ref(null);

const loading = ref(false);

function getPingResults(ip) {
    if (results.value) {
        results.value = [];
    }

    loading.value = true;

    window.axios.get(`/api/ping/${ip}`)
    .then(response => {
        results.value = response.data;

        Toastify({
            text: "Ping results updated successfully",
            duration: 3000,
            newWindow: true,
            close: true,
            gravity: "bottom",
            position: "right",
            stopOnFocus: true,
            style: {
                background: "linear-gradient(to right, #00b09b, #96c93d)",
            },
            onClick: function(){} // Callback after click
        }).showToast();
    }).catch(error => {
        // If we get a 429 error, it means we've hit the rate limit and we need to throttle our requests
        if (error.response.status === 429) {
            // Wait 5 seconds and try again
            setTimeout(() => {
                getPingResults(ip);
            }, 10000);
        }
    }).finally(() => {
        loading.value = false;
    });
}

watchEffect(() => {
    if (props.hostname) {
        // Wait until user stops typing
        clearTimeout(timeoutRequest.value);
        timeoutRequest.value = setTimeout(() => {
            getPingResults(props.hostname);
        }, 1000);
    }
});

onMounted(() => {
    if (props.clientIp) {
        window.Echo.connector.pusher.connection.bind('connected', function () {
            getPingResults(props.clientIp);
        });
    }
});

</script>

<template>
    <!-- Ping Table Results -->
    <section class="relative w-full px-8 text-gray-700 bg-white body-font dark:bg-slate-900">
        <div class="container flex flex-col flex-wrap items-center justify-between py-5 mx-auto md:flex-row max-w-7xl">
            <div class="flex flex-col w-full mb-12 text-left lg:text-center">
                <div role="status" class="max-w-2xl animate-pulse" v-if="loading">
                    <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-700 w-40 mb-4"></div>
                    <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700 max-w-[360px] mb-2.5"></div>
                    <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700 mb-2.5"></div>
                    <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700 max-w-[330px] mb-2.5"></div>
                    <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700 max-w-[300px] mb-2.5"></div>
                    <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700 max-w-[360px]"></div>
                    <span class="sr-only">Loading...</span>
                </div>
                <div class="flex flex-col w-full mb-12 text-left lg:text-center" v-else>
                    <table class="table-auto w-full text-left whitespace-no-wrap">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 title-font rounded-tl-lg tracking-wider font-medium text-gray-900 dark:bg-slate-700 dark:text-white text-sm bg-gray-100">Sequence</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 dark:bg-slate-700 dark:text-white text-sm bg-gray-100">TTL</th>
                                <th class="px-4 py-3 title-font rounded-tr-lg tracking-wider font-medium text-gray-900 dark:bg-slate-700 dark:text-white text-sm bg-gray-100">Time</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(result, index) in results.pings" :key="result.sequence" v-bind:class="{'dark:bg-gray-900 dark:border-gray-700': result.sequence % 2 === 0, 'dark:bg-gray-700 dark:border-gray-700': result.sequence % 2 === 1}" class="border-b">
                                <td class="px-4 py-3 dark:text-white">{{ result.sequence }}</td>
                                <td class="px-4 py-3 dark:text-white">{{ result.ttl ?? 'N/A' }}</td>
                                <td class="px-4 py-3 dark:text-white">{{ result.time ?? 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <section v-if="!loading && results?.statistics?.transmitted">
                    <!-- Ping Statistics Summary -->
                    <h1 class="mb-6 text-2xl font-semibold tracking-tighter text-black sm:text-5xl title-font dark:text-white">
                        Summary
                    </h1>
                    <div class="flex flex-col w-full mb-12 text-left lg:text-center">
                        <table class="table-auto w-full text-left whitespace-no-wrap">
                            <thead>
                            <tr>
                                <th class="px-4 py-3 title-font rounded-tl-lg tracking-wider font-medium text-gray-900 dark:text-white dark: bg-slate-700 text-sm bg-gray-100">Transmited</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 dark:text-white dark: bg-slate-700 text-sm bg-gray-100">Received</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 dark:text-white dark: bg-slate-700 text-sm bg-gray-100">Packet Loss</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 dark:text-white dark: bg-slate-700 text-sm bg-gray-100">RTT Min</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 dark:text-white dark: bg-slate-700 text-sm bg-gray-100">RTT Avg</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 dark:text-white dark: bg-slate-700 text-sm bg-gray-100">RTT Max</th>
                                <th class="px-4 py-3 title-font rounded-tr-lg tracking-wider font-medium text-gray-900 dark:text-white dark: bg-slate-700 text-sm bg-gray-100">RTT Mdev</th>
                            </tr>
                            </thead>

                            <tbody>
                            <tr class="dark:bg-gray-800 dark:border-gray-700 border-b">
                                <td class="px-4 py-3 dark:text-white">{{ results?.statistics?.transmitted ?? '--' }}</td>
                                <td class="px-4 py-3 dark:text-white">{{ results?.statistics?.received ?? '--' }}</td>
                                <td class="px-4 py-3 dark:text-white">{{ results?.statistics?.packet_loss ?? '--' }}</td>
                                <td class="px-4 py-3 dark:text-white">{{ results?.statistics?.rtt?.min ?? '--' }}</td>
                                <td class="px-4 py-3 dark:text-white">{{ results?.statistics?.rtt?.avg ?? '--' }}</td>
                                <td class="px-4 py-3 dark:text-white">{{ results?.statistics?.rtt?.max ?? '--' }}</td>
                                <td class="px-4 py-3 dark:text-white">{{ results?.statistics?.rtt?.mdev ?? '--' }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

            </div>
        </div>
    </section>

</template>

<style scoped>

</style>
