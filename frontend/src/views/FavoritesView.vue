<script setup>
import { onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useFavoritesStore } from '@/stores/favorites.js'
import SitterCard from '@/components/SitterCard.vue'

const { t } = useI18n()
const favorites = useFavoritesStore()

const profiles = computed(() => favorites.items.map((f) => f.profile).filter(Boolean))

onMounted(() => favorites.load())
</script>

<template>
  <v-container class="py-8">
    <div class="d-flex align-center mb-6">
      <v-icon color="error" size="30" class="me-2">mdi-heart</v-icon>
      <h1 class="text-h4 font-weight-bold">{{ t('favorites.title') }}</h1>
    </div>

    <v-row v-if="profiles.length">
      <v-col v-for="p in profiles" :key="p.id" cols="12" sm="6" md="4">
        <SitterCard :profile="p" />
      </v-col>
    </v-row>
    <v-alert v-else type="info" variant="tonal">{{ t('favorites.empty') }}</v-alert>
  </v-container>
</template>
