<script setup>
import { watch } from 'vue'
import { useRoute } from 'vue-router'
import { getTicket } from '../api/tickets'
import { useRead } from '../composables/useRead'
import ReadState from '../components/ReadState.vue'

const route = useRoute()
const { data, state, message, run } = useRead(getTicket)

watch(() => route.params.id, id => run(id), { immediate: true })
</script>

<template>
  <div>
    <h2>Detail tiket</h2>
    <ReadState :state="state" :message="message" @retry="run(route.params.id)" />
    <article v-if="state === 'success' && data">
      <h3>{{ data.subject }}</h3>
      <p>{{ data.description }}</p>
      <p>{{ data.status }} - {{ data.category?.name }} - {{ data.owner?.name }}</p>
      <strong v-if="data.is_urgent">Mendesak</strong>
    </article>
    <p>
      <RouterLink to="/tickets">Kembali ke daftar</RouterLink>
    </p>
  </div>
</template>
