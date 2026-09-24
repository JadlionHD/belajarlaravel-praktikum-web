<script setup>
import { useRoute } from 'vue-router'
import { safeTarget } from '../router'

const route = useRoute()
</script>

<template>
  <div>
    <h1>Sesi belum dapat diperiksa</h1>
    <p role="alert">Server atau jaringan bermasalah. Ini belum berarti Anda logout.</p>
    <RouterLink :to="safeTarget(route.query.redirect)">Coba periksa lagi</RouterLink>
  </div>
</template>
