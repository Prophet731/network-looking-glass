<script setup>
import {inject, onMounted, ref, watchEffect} from 'vue';
import Asn from './Asn.vue';
import { initFlowbite } from 'flowbite'
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import MarkerIcon from 'leaflet/dist/images/marker-icon.png';
import MarkerShadow from 'leaflet/dist/images/marker-shadow.png';

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
const geoCords = ref([]);

const timeoutRequest = ref(null);
const map = ref(null);

const loading = ref(false);

const accessKey = ref(import.meta.env.VITE_IP_INFO_KEY);

function getMtrResults(ip) {
    if (results.value) {
        results.value = [];
    }

    loading.value = true;

    window.axios.get(`/api/mtr/${ip}`)
    .then(response => {
        results.value = response.data.hubs;
        createMap();
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

function parseIpFromHost(host) {
    const regex = /(?:.*\s)?((?:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})|(?:[0-9a-fA-F]{1,4}:){7,7}[0-9a-fA-F]{1,4}|(?:[0-9a-fA-F]{1,4}:){1,7}:|(?:[0-9a-fA-F]{1,4}:){1,6}:[0-9a-fA-F]{1,4}|(?:[0-9a-fA-F]{1,4}:){1,5}:(?:[0-9a-fA-F]{1,4}:){1,2}|(?:[0-9a-fA-F]{1,4}:){1,4}:(?:[0-9a-fA-F]{1,4}:){1,3}|(?:[0-9a-fA-F]{1,4}:){1,3}:(?:[0-9a-fA-F]{1,4}:){1,4}|(?:[0-9a-fA-F]{1,4}:){1,2}:(?:[0-9a-fA-F]{1,4}:){1,5}|[0-9a-fA-F]{1,4}:((?::[0-9a-fA-F]{1,4}){1,6})|:(?::[0-9a-fA-F]{1,4}){1,7}|:|fe80:(?::[0-9a-fA-F]{0,4}){0,4}%[0-9a-zA-Z]{1,}|::(?:ffff(?::0{1,4}){0,1}:){0,1}((25[0-5]|(?:2[0-4]|1{0,1}[0-9])?[0-9])\.){3,3}(25[0-5]|(?:2[0-4]|1{0,1}[0-9])?[0-9])|(?:[0-9a-fA-F]{1,4}:){1,4}:((25[0-5]|(?:2[0-4]|1{0,1}[0-9])?[0-9])\.){3,3}(25[0-5]|(?:2[0-4]|1{0,1}[0-9])?[0-9]))(?:\s.*)?/;
    const match = host.match(regex);
    return match ? match[1] : null;
}

async function destroyMap() {
    if(map.value) {
        map.value = map.value.off();
        map.value = map.value.remove()
    }

    return map.value;
}

async function createMap() {
    await getGeoCordsForIps();

    await destroyMap();

    map.value = L.map('map', {
        renderer: L.canvas()
    }).setView(geoCords.value[0] ?? [0,0], 1);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: 'Map data &copy; OpenStreetMap contributors',
    }).addTo(map.value);

    map.value.invalidateSize();

    // Fix for broken image paths
    let DefaultIcon = L.icon({
        iconUrl: MarkerIcon,
        shadowUrl: MarkerShadow,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
    });

    // Add markers to map
    geoCords.value.forEach((geoCord) => {
        L.marker(geoCord, {
            icon: DefaultIcon,
            title: `Hop ${geoCords.value.indexOf(geoCord) + 1}`
        }).addTo(map.value);
    });
    L.control.scale().addTo(map.value);

    let poly = L.polyline(geoCords.value, { color: '#0F172A' }).addTo(map.value);
    map.value.fitBounds(poly.getBounds());
}

async function getGeoCordsForIps() {
    // Reset the geoCords array
    geoCords.value = [];
    // Loop through the results and get the geo cords for each IP. We'll use this to draw the map.
    for (let x in results.value) {
        try {
            let host = parseIpFromHost(results.value[x].host);
            if (host === null) {
                continue;
            }
            let request = await fetch(`https://ipinfo.io/${host}/json?token=${accessKey.value}`)
            let jsonResponse = await request.json()
            // Check if jsonResponse has key "loc"
            if (!jsonResponse.hasOwnProperty('loc')) {
                continue;
            }
            geoCords.value.push(jsonResponse.loc.split(','));
        } catch (error) {
            console.error(error);
        }
    }

    return geoCords.value;
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
    initFlowbite();
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
                                <th class="px-4 py-3 title-font rounded-tl-lg tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Hop</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">ASN</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Host</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Packet Loss</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Sent (ms)</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Last (ms)</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Avg (ms)</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Best (ms)</th>
                                <th class="px-4 py-3 title-font tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Worst (ms)</th>
                                <th class="px-4 py-3 title-font rounded-tr-lg tracking-wider font-medium text-gray-900 text-sm dark:bg-slate-700 dark:text-white bg-gray-100">Std Deviation (ms)</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="(result, index) in results" :key="index" v-bind:class="{'dark:bg-gray-900 dark:border-gray-700': result.count % 2 === 0, 'dark:bg-gray-700 dark:border-gray-700': result.count % 2 === 1}" class="border-b">
                                <td class="px-4 py-3 dark:text-white">{{ result.count }}</td>
                                <td class="px-4 py-3 dark:text-white">
                                    <a :href="`https://iplocation.io/asn-whois-lookup/${result.ASN}`" target="_blank" class="text-blue-500" v-text="result.ASN" v-if="result.ASN !== 'AS???'"></a>
                                    <asn v-if="result.ASN !== 'AS???'" :asn="parseInt(result.ASN.substring(2))"></asn>
                                    <span v-else>N/A</span>
                                </td>
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

            <div class="flex flex-col w-full mb-12">
                <div id="map" class="h-screen w-screen"></div>
            </div>

        </div>


    </section>

</template>

<style scoped>
#map {
    height: 500px;
    width: 100%;
}
</style>
