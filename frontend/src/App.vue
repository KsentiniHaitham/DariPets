<script setup>
import { watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useLocale, useRtl } from 'vuetify'
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
import { SUPPORTED_LOCALES } from '@/plugins/i18n.js'
import { useAuthStore } from '@/stores/auth.js'
import { useFavoritesStore } from '@/stores/favorites.js'

const auth = useAuthStore()
const favorites = useFavoritesStore()
const { locale } = useI18n()
const vLocale = useLocale()
const { isRtl } = useRtl()

function applyLocale(code) {
  const conf = SUPPORTED_LOCALES.find((l) => l.code === code) || SUPPORTED_LOCALES[0]
  vLocale.current.value = code
  isRtl.value = conf.dir === 'rtl'
  document.documentElement.setAttribute('dir', conf.dir)
  document.documentElement.setAttribute('lang', code)
  localStorage.setItem('locale', code)
}

onMounted(() => {
  applyLocale(locale.value)
  if (auth.isAuthenticated && !auth.isAdmin) {
    favorites.load()
  }
})
watch(locale, (c) => applyLocale(c))
</script>

<template>
  <v-app>
    <AppHeader />
    <v-main>
      <!-- Pas de <transition> ici : les vues ont plusieurs éléments racine,
           ce qui casse le rendu lors des navigations SPA (Vue 3 exige une racine unique). -->
      <router-view />
    </v-main>
    <AppFooter />
  </v-app>
</template>

<style>
.fade-enter-active, .fade-leave-active { transition: opacity .15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
