<script setup>
import {inject, onMounted, ref, watchEffect} from 'vue';

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
                <h1 class="mb-6 text-2xl font-semibold tracking-tighter text-black sm:text-5xl title-font dark:text-white">
                    Ping Results <font-awesome-icon :icon="['fas', 'spinner']" spin size="2xs" class="ml-2 text-green-500" v-if="loading"></font-awesome-icon>
                </h1>
                <div class="flex flex-col w-full mb-12 text-left lg:text-center">
                    <table class="table-auto w-full text-left whitespace-no-wrap">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 dark:bg-slate-700 dark:text-white text-sm bg-gray-100 rounded-tl rounded-bl">Sequence</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 dark:bg-slate-700 dark:text-white text-sm bg-gray-100">TTL</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 dark:bg-slate-700 dark:text-white text-sm bg-gray-100">Time</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(result, index) in results.pings" :key="result.sequence">
                                <td class="px-4 py-3 dark:text-white">{{ result.sequence }}</td>
                                <td class="px-4 py-3 dark:text-white">{{ result.ttl ?? 'N/A' }}</td>
                                <td class="px-4 py-3 dark:text-white">{{ result.time ?? 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <section v-if="results?.statistics?.transmitted">
                    <!-- Ping Statistics Summary -->
                    <h1 class="mb-6 text-2xl font-semibold tracking-tighter text-black sm:text-5xl title-font dark:text-white">
                        Ping Statistics
                    </h1>
                    <div class="flex flex-col w-full mb-12 text-left lg:text-center">
                        <table class="table-auto w-full text-left whitespace-no-wrap">
                            <thead>
                            <tr>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 dark:text-white dark: bg-slate-700 text-sm bg-gray-100 rounded-tl rounded-bl">Transmited</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 dark:text-white dark: bg-slate-700 text-sm bg-gray-100">Received</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 dark:text-white dark: bg-slate-700 text-sm bg-gray-100">Packet Loss</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 dark:text-white dark: bg-slate-700 text-sm bg-gray-100">RTT Min</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 dark:text-white dark: bg-slate-700 text-sm bg-gray-100">RTT Avg</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 dark:text-white dark: bg-slate-700 text-sm bg-gray-100">RTT Max</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 dark:text-white dark: bg-slate-700 text-sm bg-gray-100">RTT Mdev</th>
                            </tr>
                            </thead>

                            <tbody>
                            <tr>
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
