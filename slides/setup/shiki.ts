import { defineShikiSetup } from '@slidev/types'

// Code panels remain dark on both light and dark slides.
export default defineShikiSetup(() => ({
  themes: { dark: 'github-dark', light: 'github-dark' },
}))
