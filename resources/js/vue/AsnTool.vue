<script setup>
import {onMounted, ref} from 'vue'

const lookup = ref('8.8.8.8')

const output = ref('')

async function getAsn() {
    await fetch(`http://lg.local/asn/${lookup.value}`)
        .then(response => response.text())
        .then(data => {
            // Get the contents within the <pre> tag from the response and set it to the output
            const parser = new DOMParser()
            const doc = parser.parseFromString(data, 'text/html')
            const pre = doc.querySelector('pre')
            data = pre.innerHTML

            output.value = data
        })
        .catch(error => {
            console.error(error)
        })
}

onMounted(() => {
    console.log('mounted')

    Echo.channel('asn')
    .listen('IpLookup', (data) => {
        try {
            if (data.socket_id !== window.Echo.socketId()) {
                return
            }

            console.log(data)
        } catch (error) {
            console.error(error)
        }
    })
})

</script>

<template>
  <div>
      <p>Targeting: {{ lookup }}</p>
      <div class="col-span-full">
          <div class="mt-2">
              <div class="relative">
                  <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                      <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                          <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                      </svg>
                  </div>
                  <input v-model="lookup" type="search" id="search" class="block w-full p-4 pl-10 text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Search" required>
                  <button v-on:click="getAsn" type="button" class="text-white absolute right-2.5 bottom-2.5 bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Search</button>
              </div>
          </div>
      </div>

      <div class="w-full h-screen overflow-y-scroll overflow-y-auto" id="output">
          <pre v-html="output"></pre>
      </div>
  </div>
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
