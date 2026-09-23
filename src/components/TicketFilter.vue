<script setup>
const props = defineProps({ modelValue: { type: String, required: true } })
const emit = defineEmits(['update:modelValue'])
</script>
<template>
  <label for="status-filter">Filter status</label>
  <select id="status-filter" :value="props.modelValue"
    @change="emit('update:modelValue', $event.target.value)">
    <option value="all">Semua</option>
    <option value="open">Terbuka</option>
    <option value="pending">Diproses</option>
    <option value="closed">Selesai</option>
  </select>
</template>
