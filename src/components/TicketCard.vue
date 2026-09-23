<script setup>
import TicketStatus from './TicketStatus.vue'
const props = defineProps({
  ticket: { type: Object, required: true },
  categoryName: { type: String, default: 'Tanpa kategori' },
})
const emit = defineEmits(['advance'])
</script>
<template>
  <article class="ticket">
    <h3>{{ props.ticket.subject }}</h3>
    <p>{{ props.ticket.description }}</p>
    <p>Kategori: {{ props.categoryName }}</p>
    <TicketStatus :status="props.ticket.status" />
    <strong v-if="props.ticket.is_urgent"> Mendesak</strong>
    <p><button type="button" :disabled="props.ticket.status === 'closed'"
      @click="emit('advance', props.ticket.id)">Lanjutkan status</button></p>
  </article>
</template>
<style scoped>
.ticket { border-bottom: 1px solid #cbd5e1; padding: .5rem 0; }
</style>
