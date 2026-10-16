<template>
  <ThemeProvider>
    <SidebarProvider>
      <RouterView />
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup>
import ThemeProvider from '@/components/layout/ThemeProvider.vue'
import SidebarProvider from '@/components/layout/SidebarProvider.vue'
import { onMounted } from 'vue'
import { getCsrfToken } from '@/services/api'

onMounted(async () => {
  // Prime CSRF cookie so Echo's /api/broadcasting/auth requests pass CSRF check
  try {
    await getCsrfToken()
  } catch (_) { }
})
</script>
