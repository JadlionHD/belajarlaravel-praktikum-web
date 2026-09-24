import { createApp } from 'vue'
import { createPinia } from 'pinia'
import App from './App.vue'
import { makeRouter } from './router'
import { useAuthStore } from './stores/auth'
import { installInterceptors } from './api/client'
import './style.css'

const app = createApp(App)
const pinia = createPinia()
app.use(pinia)

const router = makeRouter(pinia)

installInterceptors(() => {
  useAuthStore(pinia).clear()
  const route = router.currentRoute.value
  if (route.meta?.requiresAuth) {
    router.replace({ name: 'login', query: { redirect: route.fullPath } })
  }
})

app.use(router)
app.mount('#app')
