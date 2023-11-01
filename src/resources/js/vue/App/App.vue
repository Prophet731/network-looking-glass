<script setup>
import {ref} from 'vue';
import axios from 'axios';

const title = ref('Hello World');

const hostname = ref('');

const messages = ref({
    'ping': [],
    'mtr': [],
    'traceroute': []
});

function getPingResults() {
    axios.get(`/api/ping/${hostname.value}`)
    .then(response => {
        messages.value.ping = response.data.output;
    });
}

function getMtrResults() {
    axios.get(`/api/mtr/${hostname.value}`)
    .then(response => {
        messages.value.mtr = response.data.output;
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
    getMtrResults();
    getTracerouteResults();
}

</script>

<template>
    <div class="grid grid-flow-row auto-rows-max">
        <h1 class="text-4xl font-bold text-gray-800">{{ title }}</h1>

        <input type="text" class="px-4 py-2 mt-4 text-gray-800 bg-gray-200 rounded" v-model="hostname" v-on:keyup.enter="getAll" />
        <button class="px-4 py-2 mt-4 text-white bg-blue-500 rounded hover:bg-blue-600" @click="getAll">Get</button>
    </div>

    <!-- Websocket output -->
    <div class="grid grid-flow-row auto-rows-max">
        <h1 class="text-4xl font-bold text-gray-800">Results</h1>

        <!-- Ping Results Code Block -->
        <div class="px-4 py-2 mt-4 text-white bg-blue-500 rounded hover:bg-blue-600">
            <ul>
                <li v-for="(message,key) in messages.ping" :key="'ping'+key">
                    {{ message }}
                </li>
            </ul>
        </div>

        <!-- MTR Results Code Block -->
        <div class="px-4 py-2 mt-4 text-white bg-blue-500 rounded hover:bg-blue-600">
            <ul>
                <li v-for="(message,key) in messages.mtr" :key="'mtr'+key">
                    {{ message }}
                </li>
            </ul>
        </div>

        <h4 class="text-2xl font-bold text-gray-800">Traceroutes Results</h4>
        <!-- Traceroutes Results Code Block -->
        <div class="w-3/4 overflow-x-auto">
            <pre class="whitespace-pre">{{ messages.traceroute.join("\n\r") }}</pre>
        </div>

    </div>
</template>

