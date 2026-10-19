<template>
  <router-link to="/" class="lg:hidden">
    <img class="h-7 w-auto dark:hidden sm:h-8" src="/images/logo/logo.svg" alt="Traceflow" />
    <img
      class="hidden h-7 w-auto dark:block sm:h-8"
      src="/images/logo/logo-dark.svg"
      alt="Traceflow"
    />
  </router-link>
</template>

<script setup>
import { RouterLink } from 'vue-router'
</script>
