<script setup>
import {inject, onMounted, ref, watchEffect} from 'vue';
import { initFlowbite } from 'flowbite'


const props = defineProps({
    asn: {
        type: Number,
        required: true
    }
})

const results = ref([]);

const timeoutRequest = ref(null);

const loading = ref(false);

function getAsnDetails(asn) {
    if (results.value) {
        results.value = [];
    }

    axios.get(`api/asn/${asn}`)
    .then(response => {
        results.value = response.data;
    }).catch(error => {
        // If we get a 429 error, it means we've hit the rate limit and we need to throttle our requests
        if (error.response.status === 429) {
            // Wait 5 seconds and try again
            setTimeout(() => {
                getAsnDetails(asn);
            }, 10000);
        }
    });
}

onMounted(() => {
    initFlowbite();
    getAsnDetails(props.asn);
});

</script>

<template>
    <!-- MTR Table Results -->
    <button v-bind:data-popover-target="`asn-${props.asn}`" type="button" data-popover-placement="right">
        <svg class="w-4 h-4 ml-2 text-gray-400 hover:text-gray-500" aria-hidden="true" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path></svg>
        <span class="sr-only">Show ASN information</span>
    </button>
    <div data-popover v-bind:id="`asn-${props.asn}`" role="tooltip" class="absolute z-10 invisible inline-block w-64 text-sm text-gray-500 transition-opacity duration-300 bg-white border border-gray-200 rounded-lg shadow-sm opacity-0 dark:text-gray-400 dark:border-gray-600 dark:bg-gray-800">
        <div class="px-3 py-2 bg-gray-100 border-b border-gray-200 rounded-t-lg dark:border-gray-600 dark:bg-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">{{ results?.description_short ?? 'N/A'}}</h3>

        </div>
        <div class="px-3 py-2">
            <ul>
                <li>Last Updated: {{ results?.date_updated ?? 'N/A'}}</li>
            </ul>
        </div>
        <div data-popper-arrow></div>
    </div>

</template>

<style scoped>

</style>
