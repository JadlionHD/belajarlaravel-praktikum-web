<script setup>
import { reactive, ref } from 'vue'
import { useRouter, onBeforeRouteLeave } from 'vue-router'
import { getCategories, createTicket } from '../api/tickets'
import { useRead } from '../composables/useRead'
import { errorText } from '../api/client'
import ReadState from '../components/ReadState.vue'

const router = useRouter()
const { data: categories, state, message, run } = useRead(getCategories)
run()

const form = reactive({
  subject: '',
  description: '',
  category_id: '',
  is_urgent: false,
  note: '',
})
const busy = ref(false)
const error = ref('')
const errors = ref({})

onBeforeRouteLeave(to => {
  if (busy.value && to.name !== 'login') return false
})

async function submit() {
  if (busy.value) return
  busy.value = true
  error.value = ''
  errors.value = {}
  let ticket
  try {
    ticket = await createTicket({
      ...form,
      subject: form.subject.trim(),
      description: form.description.trim(),
      note: form.note.trim(),
      category_id: Number(form.category_id),
    })
  } catch (e) {
    errors.value = e.response?.data?.errors || {}
    error.value = errorText(e)
    if (!e.response || e.response.status >= 500) {
      error.value += ' Hasil simpan belum pasti. Periksa daftar sebelum mengirim ulang.'
    }
  } finally {
    busy.value = false
  }
  if (ticket) {
    await router.push({ name: 'ticket-detail', params: { id: ticket.id } })
  }
}
</script>

<template>
  <div>
    <h2>Buat tiket</h2>
    <ReadState :state="state" :message="message" @retry="run()" />
    <p v-if="state === 'empty'">Minta dosen menyiapkan kategori; form belum dapat digunakan.</p>
    <form v-if="state === 'success'" @submit.prevent="submit" novalidate>
      <p v-if="error" role="alert">{{ error }}</p>
      <ul v-if="Object.keys(errors).length" role="alert">
        <li v-for="(messages, field) in errors" :key="field">{{ field }}: {{ messages.join(' ') }}</li>
      </ul>
      <fieldset :disabled="busy">
        <legend>Isi tiket</legend>
        <label>Judul <input v-model="form.subject" maxlength="150" /></label>
        <label>Uraian <textarea v-model="form.description" maxlength="5000" /></label>
        <label>Kategori
          <select v-model="form.category_id">
            <option disabled value="">Pilih kategori</option>
            <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </label>
        <label><input v-model="form.is_urgent" type="checkbox" /> Mendesak</label>
        <label>Catatan awal <textarea v-model="form.note" maxlength="1000" /></label>
        <button>{{ busy ? 'Menyimpan...' : 'Simpan tiket' }}</button>
      </fieldset>
    </form>
  </div>
</template>
