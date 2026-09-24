<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { errorText } from '../api/client'

const auth = useAuthStore()
const router = useRouter()
const busy = ref(false)
const message = ref('')

async function logout() {
  if (busy.value) return
  busy.value = true
  message.value = ''
  try {
    await auth.logout()
    await router.replace('/login')
  } catch (e) {
    message.value = errorText(e)
  } finally {
    busy.value = false
  }
}
</script>

<template>
  <div>
    <h1>Helpdesk</h1>
    <nav>
      <RouterLink to="/tickets">Daftar</RouterLink>
      <RouterLink to="/tickets/new">Buat tiket</RouterLink>
      <span>{{ auth.user?.name }}</span>
      <button :disabled="busy" @click="logout">{{ busy ? 'Keluar...' : 'Logout' }}</button>
    </nav>
    <p v-if="message" role="alert">{{ message }}</p>
    <RouterView />
  </div>
</template>
