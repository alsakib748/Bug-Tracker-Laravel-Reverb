<template>
  <Avatar :src="resolvedSrc" :alt="alt" :size="size" :status="status" @error="onError" />
</template>

<script setup>
import { computed, ref } from 'vue'
import Avatar from '@/components/ui/Avatar.vue'

const props = defineProps({
  src: {
    type: String,
    default: null,
  },
  alt: {
    type: String,
    default: 'User avatar',
  },
  size: {
    type: String,
    default: 'medium',
  },
  status: {
    type: String,
    default: 'none',
  },
  fallback: {
    type: String,
    default: '/images/avatar.jpg',
  },
})

const hasErrored = ref(false)

const resolvedSrc = computed(() => {
  if (hasErrored.value) {
    return props.fallback
  }
  const avatar = props.src
  if (!avatar) {
    return props.fallback
  }
  if (/^(https?:)?\/\//.test(avatar)) {
    return avatar
  }
  if (avatar.startsWith('/')) {
    return avatar
  }
  return `/storage/${avatar.replace(/^storage\//, '')}`
})

const onError = () => {
  hasErrored.value = true
}
</script>
