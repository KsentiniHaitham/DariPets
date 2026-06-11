import { createI18n } from 'vue-i18n'
import fr from '@/locales/fr.js'
import ar from '@/locales/ar.js'

const saved = localStorage.getItem('locale') || 'fr'

export const i18n = createI18n({
  legacy: false,
  locale: saved,
  fallbackLocale: 'fr',
  messages: { fr, ar },
})

export const SUPPORTED_LOCALES = [
  { code: 'fr', label: 'Français', dir: 'ltr' },
  { code: 'ar', label: 'العربية', dir: 'rtl' },
]
