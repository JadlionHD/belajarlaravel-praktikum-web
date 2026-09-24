<script setup>
import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { listTickets } from '../api/tickets'
import { useRead } from '../composables/useRead'
import ReadState from '../components/ReadState.vue'

const route = useRoute()
const router = useRouter()

const page = computed(() => {
  const n = Number(route.query.page)
  return Number.isSafeInteger(n) && n > 0 ? n : 1
})

const { data, state, message, run } = useRead(listTickets)

watch(page, n => run(n), { immediate: true })

function changePage(n) {
  router.push({ name: 'tickets', query: { page: n } })
}
</script>

<template>
  <div>
    <h2>Daftar tiket saya</h2>
    <ReadState :state="state" :message="message" @retry="run(page)" />
    <template v-if="state === 'success' || state === 'empty'">
      <article v-for="ticket in data?.data" :key="ticket.id">
        <RouterLink :to="{ name: 'ticket-detail', params: { id: ticket.id } }">{{ ticket.subject }}</RouterLink>
        <p>{{ ticket.status }} - {{ ticket.category?.name }} - {{ ticket.owner?.name }}</p>
      </article>
      <div v-if="data?.meta">
        <p>Halaman {{ data.meta.current_page }} / {{ data.meta.last_page }}</p>
        <button :disabled="page <= 1" @click="changePage(page - 1)">Sebelumnya</button>
        <button :disabled="page >= data.meta.last_page" @click="changePage(page + 1)">Berikutnya</button>
      </div>
      <p v-if="state === 'empty'">
        <RouterLink to="/tickets/new">Buat tiket</RouterLink>
      </p>
    </template>
  </div>
</template>
