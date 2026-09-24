<script setup>
defineProps({ state: String, message: String })
defineEmits(['retry'])
</script>
<template>
  <p v-if="state === 'loading'" role="status">Memuat data...</p>
  <div v-else-if="state === 'error'" role="alert">
    <p>{{ message }}</p><button @click="$emit('retry')">Coba lagi</button>
  </div>
  <p v-else-if="state === 'empty'">Belum ada data untuk halaman ini.</p>
</template>
