<script setup>
defineProps({ title: { type: String, required: true } })
</script>
<template>
  <section class="panel">
    <h2>{{ title }}</h2>
    <slot />
    <footer v-if="$slots.footer"><slot name="footer" /></footer>
  </section>
</template>
<style scoped>
.panel { border: 1px solid #cbd5e1; padding: 1rem; border-radius: .5rem; }
footer { margin-top: 1rem; color: #475569; }
</style>
