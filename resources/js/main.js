import { createApp } from 'vue';
import { createPinia } from 'pinia';
import './bootstrap';
import App from './App.vue';
import router from './router';

// CSS sudah di-handle sebagai entry point terpisah di vite.config.js
// Tidak perlu import lagi di sini untuk menghindari duplikasi

const app = createApp(App);

app.use(createPinia());
app.use(router);

app.mount('#app');
