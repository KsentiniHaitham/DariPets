<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/services/api.js'
import { useAuthStore } from '@/stores/auth.js'

const { t } = useI18n()
const auth = useAuthStore()

const animalTypes = ['dog', 'cat', 'bird', 'rodent', 'other']
const services = ref([])
const profile = ref(null)
const loading = ref(true)
const saving = ref(false)
const saved = ref(false)

const form = ref({
  headline: '', description: '', dailyRate: null, hourlyRate: null,
  experienceYears: null, serviceRadius: 10, animals: [], serviceIds: [],
})

async function load() {
  loading.value = true
  const sv = await api.get('/services')
  services.value = sv.data

  // Le profil du gardien connecté (via le user)
  const me = await api.get('/users', { params: { email: auth.user.email } })
  const user = Array.isArray(me.data) ? me.data[0] : me.data
  const prof = user?.sitterProfile
  if (prof) {
    const { data } = await api.get(`/pet_sitter_profiles/${prof.id ?? prof.split('/').pop()}`)
    profile.value = data
    form.value = {
      headline: data.headline || '',
      description: data.description || '',
      dailyRate: data.dailyRate ? Number(data.dailyRate) : null,
      hourlyRate: data.hourlyRate ? Number(data.hourlyRate) : null,
      experienceYears: data.experienceYears,
      serviceRadius: data.serviceRadius ?? 10,
      animals: (data.acceptedAnimalTypes || '').split(',').filter(Boolean),
      serviceIds: (data.services || []).map((s) => s.id),
    }
  }
  loading.value = false
}

async function save() {
  saving.value = true
  saved.value = false
  try {
    const payload = {
      headline: form.value.headline,
      description: form.value.description,
      dailyRate: form.value.dailyRate != null ? String(form.value.dailyRate) : null,
      hourlyRate: form.value.hourlyRate != null ? String(form.value.hourlyRate) : null,
      experienceYears: form.value.experienceYears,
      serviceRadius: form.value.serviceRadius,
      acceptedAnimalTypes: form.value.animals.join(','),
      services: form.value.serviceIds.map((id) => `/api/services/${id}`),
    }
    if (profile.value) {
      const { data } = await api.patch(`/pet_sitter_profiles/${profile.value.id}`, payload, {
        headers: { 'Content-Type': 'application/merge-patch+json' },
      })
      profile.value = data
    } else {
      payload.user = `/api/users/${auth.user.id}`
      const { data } = await api.post('/pet_sitter_profiles', payload)
      profile.value = data
    }
    saved.value = true
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <v-container class="py-8" style="max-width: 760px" v-if="!loading">
    <div class="d-flex align-center mb-2">
      <h1 class="text-h4 font-weight-bold">{{ t('sitterProfile.title') }}</h1>
      <v-spacer />
      <v-chip v-if="profile?.verified" color="info" prepend-icon="mdi-check-decagram">{{ t('sitterProfile.verified') }}</v-chip>
    </div>
    <v-alert v-if="profile && !profile.verified" type="warning" variant="tonal" class="mb-4">
      {{ t('sitterProfile.notVerified') }}
    </v-alert>

    <v-card border flat class="pa-6">
      <v-text-field v-model="form.headline" :label="t('sitterProfile.headline')" class="mb-2" />
      <v-textarea v-model="form.description" :label="t('sitterProfile.description')" rows="4" class="mb-2" />
      <v-row dense>
        <v-col cols="6"><v-text-field v-model.number="form.dailyRate" type="number" :label="t('sitterProfile.dailyRate')" /></v-col>
        <v-col cols="6"><v-text-field v-model.number="form.hourlyRate" type="number" :label="t('sitterProfile.hourlyRate')" /></v-col>
      </v-row>
      <v-row dense>
        <v-col cols="6"><v-text-field v-model.number="form.experienceYears" type="number" :label="t('sitterProfile.experience')" /></v-col>
        <v-col cols="6"><v-text-field v-model.number="form.serviceRadius" type="number" :label="t('sitterProfile.radius')" /></v-col>
      </v-row>
      <v-select
        v-model="form.animals" multiple chips :label="t('sitterProfile.animalTypes')"
        :items="animalTypes.map(a => ({ title: t('animals.' + a), value: a }))" class="mb-2" />
      <v-select
        v-model="form.serviceIds" multiple chips :label="t('sitterProfile.services')"
        :items="services" item-title="name" item-value="id" />

      <v-alert v-if="saved" type="success" variant="tonal" class="mt-4">{{ t('sitterProfile.saved') }}</v-alert>
      <v-btn color="primary" size="large" block class="mt-4" :loading="saving" @click="save">
        {{ profile ? t('common.save') : t('sitterProfile.create') }}
      </v-btn>
    </v-card>
  </v-container>

  <v-container v-else class="py-16 text-center">
    <v-progress-circular indeterminate color="primary" size="48" />
  </v-container>
</template>
