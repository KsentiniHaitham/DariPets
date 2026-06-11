import 'vuetify/styles'
import '@mdi/font/css/materialdesignicons.css'
import { createVuetify } from 'vuetify'

// Thème DariPets — moderne : violet électrique + sarcelle, fonds doux lavande
const daripetsLight = {
  dark: false,
  colors: {
    primary: '#7C3AED',        // violet électrique (marque)
    'primary-darken-1': '#6D28D9',
    secondary: '#14B8A6',      // sarcelle vive
    accent: '#F59E0B',         // ambre chaud
    background: '#F8F7FC',     // blanc lavande très doux
    surface: '#FFFFFF',
    'surface-variant': '#F1EEFB',
    success: '#10B981',
    info: '#3B82F6',
    warning: '#F59E0B',
    error: '#EF4444',
    'on-background': '#1E1B2E',
    'on-surface': '#1E1B2E',
  },
  variables: {
    'border-color': '#E6E1F5',
    'border-opacity': 1,
  },
}

export default createVuetify({
  theme: {
    defaultTheme: 'daripetsLight',
    themes: { daripetsLight },
  },
  defaults: {
    VBtn: { rounded: 'xl', style: 'text-transform: none; font-weight: 600; letter-spacing: 0;' },
    VCard: { rounded: 'xl' },
    VChip: { rounded: 'lg' },
    VTextField: { variant: 'outlined', density: 'comfortable', rounded: 'lg' },
    VSelect: { variant: 'outlined', density: 'comfortable', rounded: 'lg' },
    VTextarea: { variant: 'outlined', rounded: 'lg' },
    VAlert: { rounded: 'xl' },
    VDialog: { rounded: 'xl' },
  },
  // RTL géré dynamiquement via le locale (voir main.js)
  locale: {
    rtl: { ar: true },
  },
})
