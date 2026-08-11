<template>
  <ThemeProvider>
    <SidebarProvider>
      <RouterView />
    </SidebarProvider>
  </ThemeProvider>
</template>

<script setup lang="ts">
import ThemeProvider from '@/components/layout/ThemeProvider.vue'
import SidebarProvider from '@/components/layout/SidebarProvider.vue'
import { useAuthStore } from '@/stores/auth'
import { onMounted } from 'vue'
import { getCsrfToken } from '@/services/api'

const authStore = useAuthStore()

onMounted(async () => {
  // Prime CSRF cookie so Echo's /api/broadcasting/auth requests pass CSRF check
  try {
    await getCsrfToken()
  } catch (_) {}
  await authStore.checkAuth()
})
</script>
