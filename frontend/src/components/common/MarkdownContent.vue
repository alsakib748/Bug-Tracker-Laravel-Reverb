<template>
  <div
    v-if="html"
    class="tf-markdown prose prose-sm max-w-none prose-headings:font-semibold prose-headings:text-gray-800 prose-a:text-brand-500 prose-code:text-gray-800 dark:prose-invert dark:prose-headings:text-white/90 dark:prose-code:text-white/80"
    v-html="html"
  ></div>
  <p v-else class="text-sm italic text-gray-500 dark:text-gray-400">{{ placeholder }}</p>
</template>

<script setup>
import { computed } from 'vue'
import { marked } from 'marked'
import DOMPurify from 'dompurify'

const props = defineProps({
  source: { type: String, default: '' },
  placeholder: { type: String, default: 'No description provided.' },
})

marked.use({ gfm: true, breaks: true })

const html = computed(() => {
  const md = (props.source || '').trim()
  if (!md) return ''
  return DOMPurify.sanitize(marked.parse(md))
})
</script>
