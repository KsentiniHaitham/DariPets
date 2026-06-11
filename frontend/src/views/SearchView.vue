<script setup>
import { ref, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import api from '@/services/api.js'
import SitterCard from '@/components/SitterCard.vue'

const { t } = useI18n()
const route = useRoute()

const cities = ref([])
const services = ref([])
const animalTypes = ['dog', 'cat', 'bird', 'rodent', 'other']
const results = ref([])
const loading = ref(false)

const filters = ref({
  city: route.query.city ? Number(route.query.city) : null,
  service: route.query.service ? Number(route.query.service) : null,
  animalType: null,
  maxPrice: null,
  verifiedOnly: false,
  from: null,
  to: null,
})

async function fetchResults() {
  loading.value = true
  const params = { 'order[rating]': 'desc' }
  if (filters.value.city) params['user.city'] = filters.value.city
  if (filters.value.service) params['services'] = filters.value.service
  if (filters.value.animalType) params['acceptedAnimalTypes'] = filters.value.animalType
  if (filters.value.maxPrice) params['dailyRate[lte]'] = filters.value.maxPrice
  if (filters.value.verifiedOnly) params['verified'] = true
  try {
    const requests = [api.get('/pet_sitter_profiles', { params })]
    // Filtre disponibilité : on récupère les gardiens occupés sur la période
    const hasDates = filters.value.from && filters.value.to
    if (hasDates) {
      requests.push(api.get('/sitters/busy', { params: { from: filters.value.from, to: filters.value.to } }))
    }
    const [profilesRes, busyRes] = await Promise.all(requests)
    let list = profilesRes.data
    if (hasDates) {
      const busy = new Set(busyRes.data.busySitterIds || [])
      list = list.filter((p) => !busy.has(p.user?.id))
    }
    results.value = list
  } finally {
    loading.value = false
  }
}

function reset() {
  filters.value = { city: null, service: null, animalType: null, maxPrice: null, verifiedOnly: false, from: null, to: null }
}

onMounted(async () => {
  const [c, s] = await Promise.all([
    api.get('/cities', { params: { pagination: false } }),
    api.get('/services'),
  ])
  cities.value = c.data
  services.value = s.data
  await fetchResults()
})

watch(filters, fetchResults, { deep: true })
</script>

<template>
  <v-container class="py-8">
    <v-row>
      <!-- Filtres -->
      <v-col cols="12" md="3">
        <v-card class="pa-4" border flat>
          <div class="d-flex align-center mb-3">
            <v-icon class="me-2">mdi-filter-variant</v-icon>
            <span class="text-h6">{{ t('search.filters') }}</span>
            <v-spacer />
            <v-btn variant="text" size="small" @click="reset">{{ t('search.reset') }}</v-btn>
          </div>
          <v-select v-model="filters.city" :items="cities" item-title="name" item-value="id"
            :label="t('search.city')" clearable class="mb-2" />
          <v-select v-model="filters.service" :items="services" item-title="name" item-value="id"
            :label="t('search.service')" clearable class="mb-2" />
          <v-select v-model="filters.animalType" :items="animalTypes.map(a => ({ title: t('animals.' + a), value: a }))"
            :label="t('search.animalType')" clearable class="mb-2" />
          <v-text-field v-model.number="filters.maxPrice" type="number" :label="t('search.maxPrice')"
            suffix="MAD" clearable class="mb-2" />
          <!-- Disponibilité par dates -->
          <div class="text-caption text-medium-emphasis mb-1">
            <v-icon size="14">mdi-calendar-search</v-icon> {{ t('availability.hint') }}
          </div>
          <v-row dense>
            <v-col cols="6"><v-text-field v-model="filters.from" type="date" :label="t('availability.from')" hide-details density="compact" /></v-col>
            <v-col cols="6"><v-text-field v-model="filters.to" type="date" :label="t('availability.to')" hide-details density="compact" /></v-col>
          </v-row>
          <v-switch v-model="filters.verifiedOnly" :label="t('search.verifiedOnly')" color="primary" hide-details class="mt-2" />
        </v-card>
      </v-col>

      <!-- Résultats -->
      <v-col cols="12" md="9">
        <div class="d-flex align-center mb-4">
          <h1 class="text-h5 font-weight-bold">{{ t('search.title') }}</h1>
          <v-spacer />
          <span class="text-medium-emphasis">{{ t('search.results', { count: results.length }) }}</span>
        </div>

        <v-progress-linear v-if="loading" indeterminate color="primary" class="mb-4" />

        <v-row v-if="results.length">
          <v-col v-for="p in results" :key="p.id" cols="12" sm="6" lg="4">
            <SitterCard :profile="p" />
          </v-col>
        </v-row>
        <v-alert v-else-if="!loading" type="info" variant="tonal">{{ t('search.noResults') }}</v-alert>
      </v-col>
    </v-row>
  </v-container>
</template>
