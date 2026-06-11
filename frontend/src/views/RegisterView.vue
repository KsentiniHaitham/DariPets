<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'
import api from '@/services/api.js'

const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const cities = ref([])
const form = ref({
  email: '', plainPassword: '', firstName: '', lastName: '', phone: '',
  city: null, type: route.query.type === 'sitter' ? 'sitter' : 'owner',
})
const loading = ref(false)
const error = ref('')

onMounted(async () => {
  const { data } = await api.get('/cities', { params: { pagination: false } })
  cities.value = data
})

async function submit() {
  loading.value = true
  error.value = ''
  try {
    const payload = { ...form.value }
    if (payload.city) payload.city = `/api/cities/${payload.city}`
    await auth.register(payload)
    // Un nouveau gardien est invité à compléter son profil ; un proprio va à son espace
    router.push(auth.isSitter ? { name: 'sitter-profile' } : { name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.detail || e.response?.data?.['hydra:description'] || 'Erreur lors de l\'inscription.'
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <v-container class="py-12" style="max-width: 560px">
    <v-card class="pa-6" border flat>
      <h1 class="text-h5 font-weight-bold text-center mb-6">{{ t('auth.registerTitle') }}</h1>
      <v-form @submit.prevent="submit">
        <v-btn-toggle v-model="form.type" color="primary" mandatory class="mb-4 d-flex" divided>
          <v-btn value="owner" class="flex-grow-1"><v-icon start>mdi-account</v-icon>{{ t('auth.owner') }}</v-btn>
          <v-btn value="sitter" class="flex-grow-1"><v-icon start>mdi-paw</v-icon>{{ t('auth.sitter') }}</v-btn>
        </v-btn-toggle>

        <v-row dense>
          <v-col cols="6"><v-text-field v-model="form.firstName" :label="t('auth.firstName')" required /></v-col>
          <v-col cols="6"><v-text-field v-model="form.lastName" :label="t('auth.lastName')" required /></v-col>
        </v-row>
        <v-text-field v-model="form.email" :label="t('auth.email')" type="email" required />
        <v-text-field v-model="form.plainPassword" :label="t('auth.password')" type="password" required />
        <v-row dense>
          <v-col cols="6"><v-text-field v-model="form.phone" :label="t('auth.phone')" /></v-col>
          <v-col cols="6">
            <v-select v-model="form.city" :items="cities" item-title="name" item-value="id" :label="t('auth.city')" />
          </v-col>
        </v-row>

        <v-alert v-if="error" type="error" variant="tonal" class="mb-4">{{ error }}</v-alert>
        <v-btn type="submit" color="primary" size="large" block :loading="loading">{{ t('auth.submitRegister') }}</v-btn>
      </v-form>
      <div class="text-center mt-4 text-body-2">
        {{ t('auth.haveAccount') }}
        <router-link :to="{ name: 'login' }" class="text-primary">{{ t('auth.submitLogin') }}</router-link>
      </div>
    </v-card>
  </v-container>
</template>
