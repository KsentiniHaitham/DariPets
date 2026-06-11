<script setup>
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth.js'
import { useFavoritesStore } from '@/stores/favorites.js'

const props = defineProps({ profile: { type: Object, required: true } })
const { t } = useI18n()
const auth = useAuthStore()
const favorites = useFavoritesStore()
const favBusy = ref(false)

const user = computed(() => props.profile.user || {})
const avatarText = computed(() => (user.value.firstName?.[0] || '?') + (user.value.lastName?.[0] || ''))
const animalChips = computed(() =>
  (props.profile.acceptedAnimalTypes || '').split(',').filter(Boolean),
)
const isFav = computed(() => favorites.isFavorite(props.profile.id))
const canFav = computed(() => auth.isAuthenticated && !auth.isAdmin)

async function toggleFav(e) {
  e.preventDefault()
  e.stopPropagation()
  if (favBusy.value) return
  favBusy.value = true
  try {
    await favorites.toggle(props.profile)
  } finally {
    favBusy.value = false
  }
}
</script>

<template>
  <v-card class="sitter-card h-100" :to="{ name: 'sitter', params: { id: profile.id } }" flat>
    <div class="d-flex align-center pa-4 pb-2">
      <span class="avatar-ring me-4">
        <v-avatar size="62">
          <v-img v-if="user.avatar" :src="user.avatar" :alt="user.fullName" cover eager />
          <span v-else class="text-h6 text-primary font-weight-bold">{{ avatarText }}</span>
        </v-avatar>
      </span>
      <div class="flex-grow-1">
        <div class="d-flex align-center">
          <span class="text-subtitle-1 font-weight-bold">{{ user.fullName }}</span>
          <v-icon v-if="profile.verified" color="info" size="18" class="ms-1" :title="t('sitter.verified')">mdi-check-decagram</v-icon>
        </div>
        <div class="text-body-2 text-medium-emphasis">
          <v-icon size="15">mdi-map-marker</v-icon> {{ user.city?.name }}
        </div>
        <div class="d-flex align-center mt-1">
          <v-rating :model-value="profile.rating" color="amber" density="compact" half-increments readonly size="15" />
          <span class="text-caption ms-1 font-weight-medium">{{ profile.rating }} ({{ profile.reviewCount }})</span>
        </div>
      </div>
      <!-- Cœur favori -->
      <v-btn v-if="canFav" icon variant="text" size="small" :loading="favBusy" @click="toggleFav">
        <v-icon :color="isFav ? 'error' : 'grey-lighten-1'" size="22">
          {{ isFav ? 'mdi-heart' : 'mdi-heart-outline' }}
        </v-icon>
      </v-btn>
    </div>

    <v-card-text class="pt-1">
      <p class="text-body-2 text-truncate-2 text-medium-emphasis mb-3">{{ profile.headline }}</p>
      <div class="d-flex flex-wrap ga-1">
        <v-chip v-for="a in animalChips" :key="a" size="x-small" color="primary" variant="tonal">
          {{ t('animals.' + a) }}
        </v-chip>
        <v-chip v-if="profile.experienceYears" size="x-small" color="secondary" variant="tonal">
          {{ t('sitter.experience', { years: profile.experienceYears }) }}
        </v-chip>
      </div>
    </v-card-text>

    <v-divider />
    <v-card-actions class="px-4 py-3">
      <div>
        <span class="text-h6 text-primary font-weight-bold">{{ Math.round(profile.dailyRate) }} {{ t('currency') }}</span>
        <span class="text-caption text-medium-emphasis"> {{ t('search.perDay') }}</span>
      </div>
      <v-spacer />
      <v-btn color="primary" variant="flat" size="small" rounded="xl">
        {{ t('search.viewProfile') }}
      </v-btn>
    </v-card-actions>
  </v-card>
</template>

<style scoped>
.text-truncate-2 {
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}
</style>
