<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const email = ref('proprio@daripets.ma')
const password = ref('password')
const loading = ref(false)
const error = ref('')

async function submit() {
  loading.value = true
  error.value = ''
  try {
    await auth.login(email.value, password.value)
    router.push(route.query.redirect || auth.homeRoute)
  } catch (e) {
    error.value = t('auth.loginError')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <v-container class="py-12" style="max-width: 460px">
    <v-card class="pa-6" border flat>
      <h1 class="text-h5 font-weight-bold text-center mb-6">{{ t('auth.loginTitle') }}</h1>
      <v-form @submit.prevent="submit">
        <v-text-field v-model="email" :label="t('auth.email')" type="email" prepend-inner-icon="mdi-email" required />
        <v-text-field v-model="password" :label="t('auth.password')" type="password" prepend-inner-icon="mdi-lock" required />
        <v-alert v-if="error" type="error" variant="tonal" class="mb-4">{{ error }}</v-alert>
        <v-btn type="submit" color="primary" size="large" block :loading="loading">{{ t('auth.submitLogin') }}</v-btn>
      </v-form>
      <div class="text-center mt-4 text-body-2">
        {{ t('auth.noAccount') }}
        <router-link :to="{ name: 'register' }" class="text-primary">{{ t('auth.submitRegister') }}</router-link>
      </div>
    </v-card>
  </v-container>
</template>
