<script setup>
import { ref, computed, watch, onUnmounted } from 'vue'
import BasePanel from './components/BasePanel.vue'
import TicketList from './components/TicketList.vue'
import TicketFilter from './components/TicketFilter.vue'
import TicketForm from './components/TicketForm.vue'
import { categories, initialTickets } from './data.js'

const tickets = ref(initialTickets.map(ticket => ({ ...ticket })))
const selectedStatus = ref('all')
const showForm = ref(true)
const formVersion = ref(0)
const message = ref('')
const lastSubmission = ref(null)
let nextId = Math.max(...initialTickets.map(ticket => ticket.id), 0) + 1

const filteredTickets = computed(() =>
  selectedStatus.value === 'all'
    ? tickets.value
    : tickets.value.filter(ticket => ticket.status === selectedStatus.value)
)

const previousTitle = document.title
watch(() => tickets.value.length, count => {
  document.title = 'Helpdesk latihan (' + count + ')'
}, { immediate: true })
onUnmounted(() => { document.title = previousTitle })

function addTicket(payload) {
  const valid = payload &&
    typeof payload.subject === 'string' && payload.subject.trim().length > 0 &&
    payload.subject.trim().length <= 150 &&
    typeof payload.description === 'string' && payload.description.trim().length > 0 &&
    payload.description.trim().length <= 5000 &&
    typeof payload.note === 'string' && payload.note.trim().length > 0 &&
    payload.note.trim().length <= 1000 &&
    typeof payload.is_urgent === 'boolean' &&
    categories.some(category => category.id === payload.category_id)
  if (!valid) {
    message.value = 'Payload ditolak. Periksa form dan kontrak event.'
    return
  }
  const ticket = {
    id: nextId++, subject: payload.subject.trim(),
    description: payload.description.trim(), category_id: payload.category_id,
    is_urgent: payload.is_urgent, status: 'open',
  }
  tickets.value = [...tickets.value, ticket]
  lastSubmission.value = { ...payload }
  selectedStatus.value = 'all'
  message.value = 'Tiket #' + ticket.id + ' ditambahkan ke state lokal.'
  formVersion.value++
}

function advanceTicket(id) {
  const ticket = tickets.value.find(item => item.id === id)
  const next = { open: 'pending', pending: 'closed' }
  if (!ticket || !next[ticket.status]) return
  tickets.value = tickets.value.map(item =>
    item.id === id ? { ...item, status: next[item.status] } : item
  )
  message.value = 'Status tiket #' + id + ' berubah.'
}
</script>
<template>
  <main>
    <h1>Helpdesk - Latihan Komponen</h1>
    <p>Data lokal di memori. Refresh mengembalikan data awal.</p>
    <p role="status">{{ message }}</p>
    <BasePanel title="Daftar tiket">
      <TicketFilter v-model="selectedStatus" />
      <p>Menampilkan {{ filteredTickets.length }} dari {{ tickets.length }} tiket.</p>
      <TicketList :tickets="filteredTickets" :categories="categories"
        @advance="advanceTicket" />
      <template #footer>Props turun, event naik, App mengubah data.</template>
    </BasePanel>
    <button type="button" @click="showForm = !showForm" :aria-expanded="showForm">
      {{ showForm ? 'Tutup form (draft hilang)' : 'Buka form' }}
    </button>
    <BasePanel v-if="showForm" title="Tiket baru">
      <TicketForm :key="formVersion" :categories="categories" @submit="addTicket" />
      <template #footer>Form dikosongkan setelah parent menerima payload.</template>
    </BasePanel>
  </main>
</template>
<style scoped>
main { max-width: 860px; margin: 2rem auto; padding: 1rem; font-family: sans-serif; }
main > * { margin-bottom: 1rem; }
button { cursor: pointer; }
</style>
