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

function getMtrResults(ip) {
    if (results.value) {
        results.value = [];
    }

    loading.value = true;

    window.axios.get(`/api/mtr/${ip}`)
    .then(response => {
        results.value = response.data.hubs;
    }).catch(error => {
        // If we get a 429 error, it means we've hit the rate limit and we need to throttle our requests
        if (error.response.status === 429) {
            // Wait 5 seconds and try again
            setTimeout(() => {
                getMtrResults(ip);
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
            getMtrResults(props.hostname);
        }, 1000);
    }
});

onMounted(() => {
    window.Echo.connector.pusher.connection.bind('connected', function () {
        getMtrResults(props.clientIp);
        Echo.channel(`mtr-request.${Echo.socketId()}`)
        .listen('UserMtrRequestEvent', (e) => {
            console.log(e);
        });
    });
});

</script>

<template>
    <!-- MTR Table Results -->
    <section class="relative w-full px-8 text-gray-700 bg-white body-font dark:bg-slate-900">
        <div class="container flex flex-col flex-wrap items-center justify-between py-5 mx-auto md:flex-row max-w-7xl">
            <div class="flex flex-col w-full mb-12 text-left lg:text-center">
                <h1 class="mb-6 text-2xl font-semibold tracking-tighter text-black sm:text-5xl title-font dark:text-white">
                    MTR Results <font-awesome-icon :icon="['fas', 'spinner']" spin size="2xs" class="ml-2 text-green-500" v-if="loading"></font-awesome-icon>
                </h1>
                <div class="flex flex-col w-full mb-12 text-left lg:text-center">
                    <table class="table-auto w-full text-left whitespace-no-wrap">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100 rounded-tl rounded-bl">Hop</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">ASN</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Host</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Packet Loss</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Sent</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Last</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Avg</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Best</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Worst</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Std Deviation</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(result, index) in results" :key="index">
                                <td class="px-4 py-3 dark:text-white">{{ result.count }}</td>
                                <td class="px-4 py-3 dark:text-white"><a :href="`https://iplocation.io/asn-whois-lookup/${result.ASN}`" target="_blank" class="text-blue-500" v-text="result.ASN" v-if="result.ASN !== 'AS???'"></a></td>
                                <td class="px-4 py-3 dark:text-white">{{ result.host }}</td>
                                <td class="px-4 py-3" v-bind:class="{'text-green-500': result['Loss%'] === 0, 'text-orange-500': result['Loss%'] >= 5, 'text-red-500': result['Loss%'] >= 70}">{{ result['Loss%'] }}&percnt;</td>
                                <td class="px-4 py-3 dark:text-white">{{ result.Snt }}</td>
                                <td class="px-4 py-3 dark:text-white">{{ result.Last }}</td>
                                <td class="px-4 py-3 dark:text-white">{{ result.Avg }}</td>
                                <td class="px-4 py-3 dark:text-white">{{ result.Best }}</td>
                                <td class="px-4 py-3 dark:text-white">{{ result.Wrst }}</td>
                                <td class="px-4 py-3 dark:text-white">{{ result.StDev }}</td>
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
