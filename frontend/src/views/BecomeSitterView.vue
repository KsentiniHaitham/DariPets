<script setup>
import { useI18n } from 'vue-i18n'

const { t, tm } = useI18n()
</script>

<template>
  <!-- Bandeau -->
  <section class="hero-gradient text-white text-center py-12">
    <v-container style="max-width: 780px">
      <h1 class="text-h3 font-weight-bold mb-3">{{ t('becomeSitter.title') }}</h1>
      <p class="text-h6 font-weight-regular mb-7" style="opacity:.92">{{ t('becomeSitter.subtitle') }}</p>
      <v-btn color="white" size="x-large" rounded="xl" class="text-primary font-weight-bold"
        :to="{ name: 'register', query: { type: 'sitter' } }">
        <v-icon start>mdi-paw</v-icon>
        {{ t('becomeSitter.cta') }}
      </v-btn>
    </v-container>
  </section>

  <!-- Avantages -->
  <section class="section">
    <v-container>
      <v-row>
        <v-col v-for="(b, i) in tm('becomeSitter.benefits')" :key="i" cols="12" sm="6" md="3">
          <v-card class="service-tile pa-6 text-center h-100" flat>
            <span class="service-icon mb-4">
              <v-icon size="32" color="primary">{{ b.icon }}</v-icon>
            </span>
            <h3 class="text-subtitle-1 font-weight-bold mb-2">{{ b.title }}</h3>
            <p class="text-body-2 text-medium-emphasis">{{ b.text }}</p>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </section>

  <!-- Étapes -->
  <section class="section" style="background: linear-gradient(180deg, #F8F7FC 0%, #F1EEFB 100%)">
    <v-container style="max-width: 720px">
      <h2 class="text-h4 font-weight-bold text-center mb-8">{{ t('becomeSitter.stepsTitle') }}</h2>
      <div v-for="(step, i) in tm('becomeSitter.steps')" :key="i" class="d-flex align-center mb-5">
        <v-avatar color="primary" size="44" class="me-4 flex-shrink-0">
          <span class="text-h6 font-weight-bold">{{ i + 1 }}</span>
        </v-avatar>
        <span class="text-body-1">{{ step }}</span>
      </div>
      <div class="text-center mt-8">
        <v-btn color="primary" size="x-large" rounded="xl" :to="{ name: 'register', query: { type: 'sitter' } }">
          {{ t('becomeSitter.cta') }}
        </v-btn>
      </div>
    </v-container>
  </section>
</template>
