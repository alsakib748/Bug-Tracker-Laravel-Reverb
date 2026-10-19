# Traceflow brand kit

Tagline: **From issue to insight.**
Starting design concept, not trademark-cleared. Confirm name availability before public launch.

## Folder map

| Folder | Contents | Copy to |
|---|---|---|
| `svg/` | Primary logo (light + dark), icon-only mark (light + dark), app icon. Wordmark is outlined, so no font install is needed. | `frontend/src/assets/brand/` |
| `png/` | Same logos as PNG (`@2x` = 2x resolution), plus `traceflow-app-icon-512.png` | `frontend/src/assets/brand/` |
| `favicon/` | `favicon.svg`, `favicon.ico` (16/32/48), PNG favicons, `apple-touch-icon.png`, `icon-192/512.png`, `icon-maskable-512.png`, `site.webmanifest` | `frontend/public/` |
| `spec/` | Brand spec sheet (palette, logo rules, typography) as SVG and PDF | docs / design reference |

## Which file where

| Use | File |
|---|---|
| Website header, login page | `svg/traceflow-logo-primary.svg` (dark theme: `-dark`) |
| Sidebar, compact header | `svg/traceflow-mark.svg` (dark theme: `-dark`) |
| Browser tab | `favicon/favicon.svg` + `favicon/favicon.ico` |
| Installable app / launch screen | `favicon/icon-512.png`, `icon-maskable-512.png` |

## Colors

| Name | Hex | Role |
|---|---|---|
| Midnight Navy | `#172554` | Logo, sidebar, strong headings |
| Brand Indigo | `#5369E8` | Primary buttons, active links |
| Soft Indigo | `#A5B4FC` | Secondary strokes, accents |
| Cloud | `#F8FAFC` | Light backgrounds, cards |
| Slate | `#64748B` | Secondary text, labels |
| Dark-mode node | `#7C8EF5` | Brand Indigo lifted for dark surfaces |

Status colors are separate: red `#DC2626` critical/overdue, amber `#D97706` warning, green `#16A34A` resolved, blue `#2563EB` in progress. Always pair status color with an icon and label.

## `index.html` (Vue / Vite)

```html
<link rel="icon" href="/favicon.ico" sizes="48x48" />
<link rel="icon" href="/favicon.svg" type="image/svg+xml" />
<link rel="apple-touch-icon" href="/apple-touch-icon.png" />
<link rel="manifest" href="/site.webmanifest" />
<meta name="theme-color" content="#172554" />
```

## Theme-aware logo in Vue

```vue
<script setup>
import logoLight from '@/assets/brand/svg/traceflow-logo-primary.svg'
import logoDark from '@/assets/brand/svg/traceflow-logo-primary-dark.svg'
const isDark = document.documentElement.classList.contains('dark')
</script>

<template>
  <img :src="isDark ? logoDark : logoLight" alt="Traceflow" height="32" />
</template>
```

For a reactive dark-mode flag, use your existing TailAdmin theme state instead of the one-time check above.

## Rules of thumb

- Clear space around the logo: at least the height of the letter T.
- Minimum width: primary logo 120 px; below 24 px use the favicon.
- Do not recolor, stretch, rotate, add gradients or shadows.
- Keep Lucide for interface icons; reserve the custom mark for brand identity.
