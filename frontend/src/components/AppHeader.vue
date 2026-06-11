<script setup>
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import { SUPPORTED_LOCALES } from '@/plugins/i18n.js'
import BrandLogo from '@/components/BrandLogo.vue'

const { t, locale } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const drawer = ref(false)

// Liens principaux selon le rôle de l'utilisateur
const navLinks = computed(() => {
  if (!auth.isAuthenticated) {
    return [
      { to: { name: 'search' }, label: t('nav.search'), icon: 'mdi-magnify' },
      { to: { name: 'how-it-works' }, label: t('nav.howItWorks'), icon: 'mdi-help-circle-outline' },
      { to: { name: 'become-sitter' }, label: t('nav.becomeSitter'), icon: 'mdi-paw' },
    ]
  }
  if (auth.isAdmin) {
    return [{ to: { name: 'admin' }, label: t('nav.admin'), icon: 'mdi-shield-account' }]
  }
  if (auth.isSitter) {
    return [
      { to: { name: 'sitter-profile' }, label: t('nav.myProfile'), icon: 'mdi-card-account-details' },
      { to: { name: 'dashboard' }, label: t('nav.requests'), icon: 'mdi-calendar-check' },
      { to: { name: 'sitter-earnings' }, label: t('nav.earnings'), icon: 'mdi-cash-multiple' },
    ]
  }
  // owner
  return [
    { to: { name: 'search' }, label: t('nav.search'), icon: 'mdi-magnify' },
    { to: { name: 'favorites' }, label: t('nav.favorites'), icon: 'mdi-heart-outline' },
    { to: { name: 'owner-pets' }, label: t('nav.myPets'), icon: 'mdi-dog' },
    { to: { name: 'dashboard' }, label: t('nav.bookings'), icon: 'mdi-calendar' },
  ]
})

function setLocale(code) { locale.value = code }
function logout() {
  auth.logout()
  router.push({ name: 'home' })
}
</script>

<template>
  <v-app-bar flat color="surface" border>
    <v-container class="d-flex align-center pa-0">
      <v-app-bar-nav-icon class="d-md-none" @click="drawer = !drawer" />

      <router-link :to="{ name: 'home' }" class="d-flex align-center text-decoration-none">
        <BrandLogo :size="34" class="me-2" />
        <span class="text-h6 font-weight-bold text-primary">{{ t('brand') }}</span>
      </router-link>

      <v-spacer />

      <div class="d-none d-md-flex align-center ga-1">
        <v-btn v-for="link in navLinks" :key="link.label" variant="text" :to="link.to">
          {{ link.label }}
        </v-btn>

        <template v-if="auth.isAuthenticated">
          <v-btn v-if="!auth.isAdmin" variant="text" :to="{ name: 'messages' }" icon="mdi-message-outline" />
          <v-menu>
            <template #activator="{ props }">
              <v-btn v-bind="props" variant="tonal" color="primary" prepend-icon="mdi-account-circle">
                {{ auth.user?.firstName }}
              </v-btn>
            </template>
            <v-list>
              <v-list-item v-if="auth.isAdmin" :to="{ name: 'admin' }" prepend-icon="mdi-shield-account" :title="t('nav.admin')" />
              <v-list-item v-else :to="{ name: 'dashboard' }" prepend-icon="mdi-view-dashboard" :title="t('nav.dashboard')" />
              <v-list-item @click="logout" prepend-icon="mdi-logout" :title="t('nav.logout')" />
            </v-list>
          </v-menu>
        </template>
        <template v-else>
          <v-btn variant="text" :to="{ name: 'login' }">{{ t('nav.login') }}</v-btn>
          <v-btn color="primary" :to="{ name: 'register' }">{{ t('nav.register') }}</v-btn>
        </template>

        <v-menu>
          <template #activator="{ props }">
            <v-btn v-bind="props" variant="text" icon="mdi-translate" />
          </template>
          <v-list>
            <v-list-item
              v-for="l in SUPPORTED_LOCALES" :key="l.code"
              :active="locale === l.code" @click="setLocale(l.code)" :title="l.label" />
          </v-list>
        </v-menu>
      </div>
    </v-container>
  </v-app-bar>

  <v-navigation-drawer v-model="drawer" temporary>
    <v-list>
      <v-list-item v-for="link in navLinks" :key="link.label" :to="link.to" :prepend-icon="link.icon" :title="link.label" />
      <v-divider />
      <template v-if="auth.isAuthenticated">
        <v-list-item v-if="!auth.isAdmin" :to="{ name: 'messages' }" prepend-icon="mdi-message-outline" :title="t('nav.messages')" />
        <v-list-item v-if="auth.isAdmin" :to="{ name: 'admin' }" prepend-icon="mdi-shield-account" :title="t('nav.admin')" />
        <v-list-item v-else :to="{ name: 'dashboard' }" prepend-icon="mdi-view-dashboard" :title="t('nav.dashboard')" />
        <v-list-item @click="logout" prepend-icon="mdi-logout" :title="t('nav.logout')" />
      </template>
      <template v-else>
        <v-list-item :to="{ name: 'login' }" prepend-icon="mdi-login" :title="t('nav.login')" />
        <v-list-item :to="{ name: 'register' }" prepend-icon="mdi-account-plus" :title="t('nav.register')" />
      </template>
      <v-divider />
      <v-list-item v-for="l in SUPPORTED_LOCALES" :key="l.code" @click="setLocale(l.code)" :title="l.label" prepend-icon="mdi-translate" />
    </v-list>
  </v-navigation-drawer>
</template>
