<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api.js'
import { useAuthStore } from '@/stores/auth.js'

const props = defineProps({ id: { type: [String, Number], required: true } })
const { t } = useI18n()
const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const profile = ref(null)
const reviews = ref([])
const loading = ref(true)
const contacting = ref(false)

// Contacter le gardien SANS réservation : retrouve la conversation existante
// ou en crée une, puis ouvre la messagerie dessus.
async function contact() {
  if (!auth.isAuthenticated) {
    return router.push({ name: 'login', query: { redirect: route.fullPath } })
  }
  contacting.value = true
  try {
    const sitterUserId = profile.value.user.id
    const { data: convs } = await api.get('/conversations')
    const existing = convs.find((c) => c.participants?.some((p) => p.id === sitterUserId))
    if (existing) {
      return router.push({ name: 'messages', query: { c: existing.id } })
    }
    const { data: created } = await api.post('/conversations', {
      participants: [`/api/users/${auth.user.id}`, `/api/users/${sitterUserId}`],
    })
    router.push({ name: 'messages', query: { c: created.id } })
  } finally {
    contacting.value = false
  }
}

const user = computed(() => profile.value?.user || {})
const animalChips = computed(() => (profile.value?.acceptedAnimalTypes || '').split(',').filter(Boolean))
const avatarText = computed(() => (user.value.firstName?.[0] || '') + (user.value.lastName?.[0] || ''))

onMounted(async () => {
  const { data } = await api.get(`/pet_sitter_profiles/${props.id}`)
  profile.value = data
  if (data.user?.id) {
    const r = await api.get('/reviews', { params: { target: data.user.id } })
    reviews.value = r.data
  }
  loading.value = false
})
</script>

<template>
  <v-container class="py-8" v-if="profile">
    <v-row>
      <v-col cols="12" md="8">
        <v-card class="pa-6" border flat>
          <div class="d-flex align-center mb-4">
            <span class="avatar-ring me-4">
              <v-avatar size="92">
                <v-img v-if="user.avatar" :src="user.avatar" :alt="user.fullName" cover eager />
                <span v-else class="text-h4 text-primary font-weight-bold">{{ avatarText }}</span>
              </v-avatar>
            </span>
            <div>
              <div class="d-flex align-center">
                <h1 class="text-h4 font-weight-bold">{{ user.fullName }}</h1>
                <v-icon v-if="profile.verified" color="info" class="ms-2" :title="t('sitter.verified')">mdi-check-decagram</v-icon>
              </div>
              <div class="text-body-1 text-medium-emphasis">
                <v-icon size="18">mdi-map-marker</v-icon> {{ user.city?.name }}
                <span v-if="profile.experienceYears" class="ms-3">
                  <v-icon size="18">mdi-briefcase</v-icon> {{ t('sitter.experience', { years: profile.experienceYears }) }}
                </span>
              </div>
              <div class="d-flex align-center mt-1">
                <v-rating :model-value="profile.rating" color="amber" density="compact" half-increments readonly size="20" />
                <span class="ms-2">{{ profile.rating }} · {{ profile.reviewCount }} {{ t('sitter.reviews') }}</span>
              </div>
            </div>
          </div>

          <v-divider class="my-4" />
          <h2 class="text-h6 font-weight-bold mb-2">{{ t('sitter.about') }}</h2>
          <p class="text-body-1 mb-4" style="white-space: pre-line">{{ profile.description }}</p>

          <h2 class="text-h6 font-weight-bold mb-2">{{ t('sitter.services') }}</h2>
          <div class="d-flex flex-wrap ga-2 mb-4">
            <v-chip v-for="s in profile.services" :key="s.id" color="primary" variant="tonal">
              <v-icon start>{{ s.icon || 'mdi-paw' }}</v-icon>{{ s.name }}
            </v-chip>
          </div>

          <h2 class="text-h6 font-weight-bold mb-2">{{ t('sitter.animals') }}</h2>
          <div class="d-flex flex-wrap ga-2">
            <v-chip v-for="a in animalChips" :key="a" variant="outlined">{{ t('animals.' + a) }}</v-chip>
          </div>
        </v-card>

        <!-- Avis -->
        <v-card class="pa-6 mt-6" border flat>
          <h2 class="text-h6 font-weight-bold mb-4">{{ t('sitter.reviews') }} ({{ reviews.length }})</h2>
          <div v-if="reviews.length">
            <div v-for="r in reviews" :key="r.id" class="mb-4">
              <div class="d-flex align-center">
                <v-avatar color="secondary" size="36" class="me-2">
                  <span>{{ r.author?.firstName?.[0] }}</span>
                </v-avatar>
                <strong>{{ r.author?.firstName }}</strong>
                <v-rating :model-value="r.rating" color="amber" density="compact" readonly size="14" class="ms-2" />
              </div>
              <p class="text-body-2 mt-1 ms-10">{{ r.comment }}</p>
            </div>
          </div>
          <p v-else class="text-medium-emphasis">{{ t('sitter.noReviews') }}</p>
        </v-card>
      </v-col>

      <!-- Carte réservation -->
      <v-col cols="12" md="4">
        <v-card class="pa-6 position-sticky" border flat style="top: 88px">
          <div class="text-center mb-4">
            <span class="text-h4 text-primary font-weight-bold">{{ profile.dailyRate }} {{ t('currency') }}</span>
            <span class="text-medium-emphasis">{{ t('search.perDay') }}</span>
            <div v-if="profile.hourlyRate" class="text-body-2 text-medium-emphasis">
              {{ profile.hourlyRate }} {{ t('currency') }} {{ t('search.perHour') }}
            </div>
          </div>
          <v-btn color="primary" size="large" block class="mb-2"
            :to="{ name: 'booking-create', params: { sitterId: profile.id } }">
            <v-icon start>mdi-calendar-check</v-icon>{{ t('sitter.book') }}
          </v-btn>
          <v-btn variant="outlined" size="large" block :loading="contacting" @click="contact">
            <v-icon start>mdi-message-outline</v-icon>{{ t('sitter.contact') }}
          </v-btn>
        </v-card>
      </v-col>
    </v-row>
  </v-container>

  <v-container v-else class="py-16 text-center">
    <v-progress-circular indeterminate color="primary" size="48" />
  </v-container>
</template>
