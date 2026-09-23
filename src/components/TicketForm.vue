<script setup>
import { reactive, ref, onMounted } from 'vue'
const props = defineProps({ categories: { type: Array, required: true } })
const emit = defineEmits(['submit'])
const form = reactive({
  subject: '', description: '', category_id: '', is_urgent: false, note: '',
})
const error = ref('')
const subjectInput = ref(null)
onMounted(() => subjectInput.value?.focus())
function submit() {
  error.value = ''
  const payload = {
    subject: form.subject.trim(),
    description: form.description.trim(),
    category_id: Number(form.category_id),
    is_urgent: form.is_urgent,
    note: form.note.trim(),
  }
  if (!payload.subject || payload.subject.length > 150 ||
      !payload.description || payload.description.length > 5000 ||
      !payload.note || payload.note.length > 1000 ||
      !props.categories.some(c => c.id === payload.category_id)) {
    error.value = 'Lengkapi judul, uraian, kategori, dan catatan sesuai batas.'
    return
  }
  emit('submit', payload)
}
</script>
<template>
  <form novalidate @submit.prevent="submit">
    <p v-if="error" role="alert">{{ error }}</p>
    <label for="subject">Judul (maksimal 150 karakter)</label>
    <input id="subject" ref="subjectInput" v-model="form.subject" maxlength="150" required />
    <label for="description">Uraian (maksimal 5000 karakter)</label>
    <textarea id="description" v-model="form.description" maxlength="5000" required />
    <label for="category">Kategori</label>
    <select id="category" v-model="form.category_id" required>
      <option disabled value="">Pilih kategori</option>
      <option v-for="category in props.categories" :key="category.id"
        :value="category.id">{{ category.name }}</option>
    </select>
    <label><input type="checkbox" v-model="form.is_urgent" /> Mendesak</label>
    <label for="note">Catatan awal (maksimal 1000 karakter)</label>
    <textarea id="note" v-model="form.note" maxlength="1000" required />
    <button type="submit">Tambah tiket</button>
  </form>
</template>
<style scoped>
form { display: grid; gap: .6rem; }
input:not([type="checkbox"]), textarea, select { width: 100%; box-sizing: border-box; }
</style>
