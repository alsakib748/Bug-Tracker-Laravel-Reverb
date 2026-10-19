import pluginVue from 'eslint-plugin-vue'
import skipFormatting from '@vue/eslint-config-prettier/skip-formatting'

// This project is JavaScript-only (no TypeScript tooling installed), so the
// @vue/eslint-config-typescript preset is intentionally not used here.
export default [
  {
    name: 'app/files-to-lint',
    files: ['**/*.{js,mjs,jsx,vue}'],
  },

  {
    name: 'app/files-to-ignore',
    ignores: ['**/dist/**', '**/dist-ssr/**', '**/coverage/**', '**/node_modules/**'],
  },

  ...pluginVue.configs['flat/essential'],
  skipFormatting,

  // Route-level views are named by convention (Index, Show, Create, Edit...).
  // Renaming them would break the router, so the multi-word rule is relaxed here.
  {
    name: 'app/views-override',
    files: ['src/views/**/*.vue'],
    rules: {
      'vue/multi-word-component-names': 'off',
    },
  },

  // Established single-word UI-kit / layout component names (Alert, Avatar,
  // Modal, Can...). Renaming would break imports across the app.
  {
    name: 'app/generic-component-names',
    files: [
      'src/components/ui/Alert.vue',
      'src/components/ui/Avatar.vue',
      'src/components/ui/Modal.vue',
      'src/components/profile/Modal.vue',
      'src/components/permissions/Can.vue',
      'src/components/layout/Backdrop.vue',
      'src/components/forms/FormElements/Dropzone.vue',
    ],
    rules: {
      'vue/multi-word-component-names': 'off',
    },
  },
]
