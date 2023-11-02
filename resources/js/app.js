import './bootstrap';
import 'flowbite';
import '../css/app.css';

import {createApp} from 'vue/dist/vue.esm-bundler';
import App from './vue/App/App.vue';

import { library } from '@fortawesome/fontawesome-svg-core'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
import { fas } from '@fortawesome/free-solid-svg-icons'

library.add(fas)

const app = createApp(App)
app.component('font-awesome-icon', FontAwesomeIcon)
app.mount('#app');
