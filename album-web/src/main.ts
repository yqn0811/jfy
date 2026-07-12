import { createApp } from 'vue'
import { createPinia } from 'pinia'
import 'vue-sonner/style.css'
import './styles/global.css'

import App from './App.vue'
import { installAppRouter } from './router'

installAppRouter()

createApp(App).use(createPinia()).mount('#app')
