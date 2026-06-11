<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import api from '@/services/api.js'
import { useAuthStore } from '@/stores/auth.js'

const props = defineProps({ sitterId: { type: [String, Number], required: true } })
const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()

const profile = ref(null)
const animals = ref([])
const loading = ref(true)
const submitting = ref(false)
const error = ref('')

const form = ref({ service: null, animal: null, startDate: '', endDate: '', address: '', note: '' })

const nights = computed(() => {
  if (!form.value.startDate || !form.value.endDate) return 0
  const d = (new Date(form.value.endDate) - new Date(form.value.startDate)) / 86400000
  return Math.max(1, Math.round(d))
})
const total = computed(() => (profile.value ? (Number(profile.value.dailyRate) * nights.value).toFixed(2) : 0))

onMounted(async () => {
  const { data } = await api.get(`/pet_sitter_profiles/${props.sitterId}`)
  profile.value = data
  form.value.service = data.services?.[0]?.id ?? null
  // Animaux du propriétaire connecté
  const a = await api.get('/animals', { params: { owner: auth.user.id } })
  animals.value = a.data
  form.value.animal = animals.value[0]?.id ?? null
  loading.value = false
})

async function submit() {
  submitting.value = true
  error.value = ''
  try {
    const payload = {
      owner: `/api/users/${auth.user.id}`,
      sitter: `/api/users/${profile.value.user.id}`,
      service: `/api/services/${form.value.service}`,
      startDate: form.value.startDate,
      endDate: form.value.endDate,
      address: form.value.address || null,
      note: form.value.note || null,
    }
    if (form.value.animal) payload.animal = `/api/animals/${form.value.animal}`
    await api.post('/bookings', payload)
    router.push({ name: 'dashboard' })
  } catch (e) {
    error.value = e.response?.data?.detail || 'Erreur lors de la réservation.'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <v-container class="py-8" style="max-width: 720px" v-if="!loading">
    <h1 class="text-h5 font-weight-bold mb-2">{{ t('booking.title') }}</h1>
    <p class="text-medium-emphasis mb-6">{{ profile.user.fullName }} · {{ profile.user.city?.name }}</p>

    <v-card class="pa-6" border flat>
      <v-form @submit.prevent="submit">
        <v-select v-model="form.service" :items="profile.services" item-title="name" item-value="id"
          :label="t('booking.service')" class="mb-2" />
        <v-select v-model="form.animal" :items="animals" :item-title="a => `${a.name} (${t('animals.' + a.type)})`" item-value="id"
          :label="t('booking.animal')" class="mb-2" no-data-text="—" />
        <v-row dense>
          <v-col cols="6"><v-text-field v-model="form.startDate" type="date" :label="t('booking.startDate')" /></v-col>
          <v-col cols="6"><v-text-field v-model="form.endDate" type="date" :label="t('booking.endDate')" /></v-col>
        </v-row>
        <v-text-field v-model="form.address" :label="t('booking.address')" prepend-inner-icon="mdi-map-marker" class="mb-2" />
        <v-textarea v-model="form.note" :label="t('booking.note')" rows="3" />

        <v-card variant="tonal" color="primary" class="pa-4 my-4">
          <div class="d-flex justify-space-between">
            <span>{{ profile.dailyRate }} {{ t('currency') }} × {{ t('booking.nights', { n: nights }) }}</span>
            <strong class="text-h6">{{ total }} {{ t('currency') }}</strong>
          </div>
        </v-card>

        <v-alert v-if="error" type="error" variant="tonal" class="mb-4">{{ error }}</v-alert>
        <v-btn type="submit" color="primary" size="large" block :loading="submitting"
          :disabled="!form.startDate || !form.endDate">
          {{ t('booking.submit') }}
        </v-btn>
      </v-form>
    </v-card>
  </v-container>
</template>
