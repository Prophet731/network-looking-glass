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

function getMtrResults(ip) {
    if (results.value) {
        results.value = [];
    }

    axios.get(`/api/mtr/${ip}`)
    .then(response => {
        results.value = response.data?.hubs;
    }).catch(error => {
        // If we get a 429 error, it means we've hit the rate limit and we need to throttle our requests
        if (error.response.status === 429) {
            // Wait 5 seconds and try again
            setTimeout(() => {
                getMtrResults(ip);
            }, 10000);
        }
    });
}

watchEffect(() => {
    if (props.hostname) {
        // Wait until user stops typing
        clearTimeout(timeoutRequest.value);
        timeoutRequest.value = setTimeout(() => {
            getMtrResults(props.hostname);
        }, 1000);
    }
});

</script>

<template>
    <!-- MTR Table Results -->
    <section class="relative w-full px-8 text-gray-700 bg-white body-font">
        <div class="container flex flex-col flex-wrap items-center justify-between py-5 mx-auto md:flex-row max-w-7xl">
            <div class="flex flex-col w-full mb-12 text-left lg:text-center">
                <h1 class="mb-6 text-2xl font-semibold tracking-tighter text-black sm:text-5xl title-font">
                    MTR Results
                </h1>
                <div class="flex flex-col w-full mb-12 text-left lg:text-center">
                    <table class="table-auto w-full text-left whitespace-no-wrap">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100 rounded-tl rounded-bl">Hop</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100">Host</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100">Loss</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100">Snt</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100">Last</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100">Avg</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100">Best</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100">Wrst</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm bg-gray-100">StDev</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(result, index) in results" :key="index">
                                <td class="px-4 py-3">{{ index+1 }}</td>
                                <td class="px-4 py-3">{{ result.host }}</td>
                                <td class="px-4 py-3">{{ result['Loss%'] }}</td>
                                <td class="px-4 py-3">{{ result.Snt }}</td>
                                <td class="px-4 py-3">{{ result.Last }}</td>
                                <td class="px-4 py-3">{{ result.Avg }}</td>
                                <td class="px-4 py-3">{{ result.Best }}</td>
                                <td class="px-4 py-3">{{ result.Wrst }}</td>
                                <td class="px-4 py-3">{{ result.StDev }}</td>
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
