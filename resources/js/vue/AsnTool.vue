<script setup>
import {onMounted, ref, watchEffect} from 'vue';
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

const output = ref('')

const timeoutRequest = ref(null);

const loading = ref(false);

const requestError = ref(false);

async function getAsn(ip) {
    if (output.value !== '') {
        output.value = '';
    }

    Toastify({
        text: 'Looking up ASN. Please wait...',
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

    loading.value = true;

    await fetch(`/asn/${ip}`)
        .then(response => {
            // Check if the response is ok
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return response.text()
        })
        .then(data => {
            // Get the contents within the <pre> tag from the response and set it to the output
            const parser = new DOMParser()
            const doc = parser.parseFromString(data, 'text/html')
            const pre = doc.querySelector('pre')
            data = pre.innerHTML

            output.value = data

            Toastify({
                text: 'ASN lookup complete.',
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

            requestError.value = false;
            loading.value = false;
        })
        .catch(error => {
            requestError.value = true;
            loading.value = false;
            Toastify({
                text: `ASN Lookup Error: ${error.message}`,
                duration: 3000,
                newWindow: true,
                close: true,
                gravity: "bottom",
                position: "right",
                stopOnFocus: true,
                style: {
                    background: "red",
                },
                onClick: function(){} // Callback after click
            }).showToast();
        })
}

watchEffect(() => {
    if (props.hostname) {
        // Wait until user stops typing
        clearTimeout(timeoutRequest.value);
        timeoutRequest.value = setTimeout(() => {
            getAsn(props.hostname);
        }, 5000);
    }
});

onMounted(() => {
    if (props.clientIp) {
        getAsn(props.clientIp);
    }
})

</script>

<template>

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
                <div class="flex flex-col w-full mb-12 text-left" v-else>
                    <!-- Alert if there is an error -->
                    <div class="lg:text-center flex items-center p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert" v-if="requestError">
                        <svg class="flex-shrink-0 inline w-4 h-4 mr-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5ZM9.5 4a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3ZM12 15H8a1 1 0 0 1 0-2h1v-3H8a1 1 0 0 1 0-2h2a1 1 0 0 1 1 1v4h1a1 1 0 0 1 0 2Z"/>
                        </svg>
                        <span class="sr-only">Info</span>
                        <div>
                            <span class="font-medium">Error!</span> There was an error with your request. Please try again.
                        </div>
                    </div>
                    <div class="w-full h-screen overflow-y-auto" id="output">
                        <pre v-html="output"></pre>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
#output > table {
    border: 1px solid #afaf00 !important ;
    margin-left: auto !important ;
    margin-right: auto !important ;
}
#output th { text-align: center !important; background-color: #00afd7 !important; color: black !important; }
#output td { text-align: center !important; color: #cccccc !important; width: 20% !important; }
#output .center { margin-left: auto !important; margin-right: auto !important; }
#output .textoutput {
    opacity:1 !important;
    transition:opacity 200ms !important;
    font-size: 0.8em !important;
}
#output .hidden {
    opacity: 0 !important;
}
/* cheers https://css-tricks.com/snippets/css/make-pre-text-wrap/ */
#output pre {
    white-space: pre-wrap !important;       /* css-3 */
    white-space: -moz-pre-wrap !important;  /* Mozilla, since 1999 */
    white-space: -pre-wrap;      /* Opera 4-6 */
    white-space: -o-pre-wrap !important;    /* Opera 7 */
    word-wrap: break-word !important;       /* Internet Explorer 5.5+ */
}
</style>
