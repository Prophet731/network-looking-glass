import './bootstrap';
import '../css/app.css';

import {createApp} from 'vue/dist/vue.esm-bundler';
import App from './vue/App/App.vue';

const app = createApp(App)
app.mount('#app');
