<script setup>
import { useI18n } from 'vue-i18n'

const { t, tm } = useI18n()
const stepIcons = ['mdi-magnify', 'mdi-calendar-check', 'mdi-heart']
</script>

<template>
  <!-- Bandeau -->
  <section class="hero-gradient text-white text-center py-12">
    <v-container style="max-width: 760px">
      <h1 class="text-h3 font-weight-bold mb-3">{{ t('how.title') }}</h1>
      <p class="text-h6 font-weight-regular" style="opacity:.92">{{ t('how.subtitle') }}</p>
    </v-container>
  </section>

  <!-- Étapes -->
  <section class="section">
    <v-container>
      <v-row>
        <v-col v-for="(step, i) in ['1', '2', '3']" :key="step" cols="12" md="4" class="text-center">
          <span class="step-badge mb-5">
            <v-icon size="30">{{ stepIcons[i] }}</v-icon>
          </span>
          <h3 class="text-h6 font-weight-bold mb-2">{{ t('home.step' + step + 'Title') }}</h3>
          <p class="text-body-1 text-medium-emphasis px-6">{{ t('home.step' + step) }}</p>
        </v-col>
      </v-row>
    </v-container>
  </section>

  <!-- FAQ -->
  <section class="section" style="background: linear-gradient(180deg, #F8F7FC 0%, #F1EEFB 100%)">
    <v-container style="max-width: 860px">
      <div class="text-center mb-8">
        <span class="section-eyebrow">FAQ</span>
        <h2 class="text-h4 font-weight-bold">{{ t('how.faqTitle') }}</h2>
      </div>
      <v-expansion-panels variant="accordion" class="rounded-xl" elevation="0">
        <v-expansion-panel v-for="(item, i) in tm('how.faq')" :key="i" class="mb-2 rounded-lg" style="border: 1px solid #E6E1F5">
          <v-expansion-panel-title class="font-weight-semibold" style="font-weight:600">
            {{ item.q }}
          </v-expansion-panel-title>
          <v-expansion-panel-text class="text-medium-emphasis">
            {{ item.a }}
          </v-expansion-panel-text>
        </v-expansion-panel>
      </v-expansion-panels>
    </v-container>
  </section>

  <!-- CTA -->
  <section class="text-center py-10">
    <v-btn color="primary" size="x-large" rounded="xl" :to="{ name: 'search' }">
      {{ t('nav.search') }}
      <v-icon end>mdi-arrow-right</v-icon>
    </v-btn>
  </section>
</template>
