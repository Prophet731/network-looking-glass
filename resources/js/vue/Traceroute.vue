<script setup>
import axios from 'axios';
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

function getTracerouteResults(ip) {
    if (results.value) {
        results.value = [];
    }

    axios.get(`/api/traceroute/${ip}`)
    .then(response => {
        results.value = response.data;
    }).catch(error => {
        // If we get a 429 error, it means we've hit the rate limit and we need to throttle our requests
        if (error.response.status === 429) {
            // Wait 5 seconds and try again
            setTimeout(() => {
                getTracerouteResults(ip);
            }, 10000);
        }
    });
}

watchEffect(() => {
    if (props.hostname) {
        // Wait until user stops typing
        clearTimeout(timeoutRequest.value);
        timeoutRequest.value = setTimeout(() => {
            getTracerouteResults(props.hostname);
        }, 1000);
    }
});

</script>

<template>
    <!-- Traceroute Table Results -->
    <section class="relative w-full px-8 text-gray-700 bg-white body-font">
        <div class="container flex flex-col flex-wrap items-center justify-between py-5 mx-auto md:flex-row max-w-7xl">
            <div class="flex flex-col w-full mb-12 text-left lg:text-center">
                <h1 class="mb-6 text-2xl font-semibold tracking-tighter text-black sm:text-5xl title-font">
                    Traceroute Results
                </h1>
                <div class="flex flex-col w-full mb-12 text-left lg:text-center">
                    <table class="table-auto w-full text-left whitespace-no-wrap">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tl rounded-bl">Hop</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100">Hostname</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100">IP</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100">Ping 1</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100">Ping 2</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100">Ping 3</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(result, index) in results" :key="result.hop">
                                <td class="px-4 py-3">{{ result.hop }}</td>
                                <td class="px-4 py-3">{{ result.hostname ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ result.ip ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ result.times[0] ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ result.times[1] ?? 'N/A' }}</td>
                                <td class="px-4 py-3">{{ result.times[2] ?? 'N/A' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

</template>

<style scoped>

</style>
