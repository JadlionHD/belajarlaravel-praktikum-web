<script setup>
import { ref, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { safeTarget } from '../router'
import { errorText } from '../api/client'

const form = reactive({ email: '', password: '' })
const busy = ref(false)
const message = ref('')
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

async function submit() {
  if (busy.value) return
  busy.value = true
  message.value = ''
  try {
    await auth.login({ ...form })
    await router.replace(safeTarget(route.query.redirect))
  } catch (e) {
    message.value = e.response?.status === 401 ? 'Email atau password salah.' : errorText(e)
  } finally {
    busy.value = false
    form.password = ''
  }
}
</script>

<template>
  <div>
    <h1>Login Helpdesk</h1>
    <p v-if="message" role="alert">{{ message }}</p>
    <form @submit.prevent="submit">
      <label>Email <input v-model="form.email" type="email" autocomplete="username" required /></label>
      <label>Password <input v-model="form.password" type="password" autocomplete="current-password" required /></label>
      <button :disabled="busy">{{ busy ? 'Memeriksa...' : 'Login' }}</button>
    </form>
  </div>
</template>
