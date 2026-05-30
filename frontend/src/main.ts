import { createApp } from 'vue'
import { createPinia } from 'pinia'

import App from './App.vue'
import router from './router'
import './css/app.css'
import { loadStorefront } from './services/storefront'

await loadStorefront()

const app = createApp(App)

app.use(createPinia())
app.use(router)

app.mount('#app')
