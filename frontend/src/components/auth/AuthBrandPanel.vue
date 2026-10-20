<template>
  <div
    class="relative hidden h-screen w-full items-center overflow-hidden bg-gradient-to-br from-brand-900 via-brand-950 to-slate-950 lg:flex lg:w-1/2"
  >
    <div
      class="pointer-events-none absolute -left-20 -top-24 h-80 w-80 rounded-full bg-brand-500/30 blur-3xl auth-orb"
      style="animation-duration: 14s"
    ></div>
    <div
      class="pointer-events-none absolute -bottom-28 -right-24 h-96 w-96 rounded-full bg-theme-purple-500/25 blur-3xl auth-orb"
      style="animation-duration: 18s; animation-delay: -7s"
    ></div>
    <div
      class="pointer-events-none absolute right-24 top-1/4 h-44 w-44 rounded-full bg-brand-400/20 blur-2xl auth-orb"
      style="animation-duration: 11s; animation-delay: -4s"
    ></div>

    <common-grid-shape />

    <div class="relative z-10 flex w-full max-w-sm flex-col items-center px-6 mx-auto">
      <router-link to="/" class="block mb-4">
        <img width="200" height="36" src="/images/logo/auth-logo.svg" alt="Traceflow" />
      </router-link>
      <p class="mb-6 text-base font-medium text-center text-white/80">From issue to insight.</p>

      <ul class="w-full space-y-3">
        <li v-for="feature in features" :key="feature" class="flex items-start gap-2.5">
          <span
            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-brand-500/20 ring-1 ring-brand-400/30"
          >
            <svg
              class="h-3 w-3 text-brand-300"
              viewBox="0 0 14 14"
              fill="none"
              xmlns="http://www.w3.org/2000/svg"
            >
              <path
                d="M11.6666 3.5L5.24992 9.91667L2.33325 7"
                stroke="currentColor"
                stroke-width="1.94437"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </span>
          <span class="text-sm leading-snug text-white/70">{{ feature }}</span>
        </li>
      </ul>

      <p
        class="mt-10 flex w-full items-center gap-2 border-t border-white/10 pt-6 text-xs text-white/40"
      >
        <svg
          class="h-3.5 w-3.5 shrink-0"
          viewBox="0 0 20 20"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M13.3333 8.33333H6.66667C5.74619 8.33333 5 9.07952 5 10V15C5 15.9205 5.74619 16.6667 6.66667 16.6667H13.3333C14.2538 16.6667 15 15.9205 15 15V10C15 9.07952 14.2538 8.33333 13.3333 8.33333Z"
            stroke="currentColor"
            stroke-width="1.5"
          />
          <path
            d="M6.66667 8.33333V6.66667C6.66667 5.74619 7.0359 4.86213 7.69236 4.20567C8.34882 3.54921 9.23246 3.18182 10.1544 3.18182C11.0763 3.18182 11.9599 3.54921 12.6164 4.20567C13.2729 4.86213 13.6421 5.74619 13.6421 6.66667V8.33333"
            stroke="currentColor"
            stroke-width="1.5"
            stroke-linecap="round"
          />
        </svg>
        Secure sign-in protected by industry-standard encryption.
      </p>
    </div>
  </div>
</template>

<script setup>
import CommonGridShape from '@/components/common/CommonGridShape.vue'

const features = [
  'Real-time issue tracking with live activity streams',
  'AI-powered insights that surface what matters most',
  'Built for teams that ship — from issue to insight',
]
</script>

<style scoped>
@keyframes auth-panel-float {
  0%,
  100% {
    transform: translate3d(0, 0, 0) scale(1);
  }
  50% {
    transform: translate3d(0, -30px, 0) scale(1.05);
  }
}

.auth-orb {
  animation: auth-panel-float 14s ease-in-out infinite;
}
</style>
