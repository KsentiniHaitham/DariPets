<script setup>
import { useI18n } from 'vue-i18n'
import BrandLogo from '@/components/BrandLogo.vue'
const { t } = useI18n()
const year = new Date().getFullYear()
</script>

<template>
  <v-footer class="text-white" style="background: linear-gradient(135deg, #1E1B2E 0%, #2A1E4F 100%)">
    <v-container>
      <v-row>
        <v-col cols="12" md="4">
          <div class="d-flex align-center mb-2">
            <BrandLogo :size="30" class="me-2" />
            <span class="text-h6 font-weight-bold">{{ t('brand') }}</span>
          </div>
          <p class="text-grey-lighten-1">{{ t('footer.tagline') }}</p>
        </v-col>
        <v-col cols="6" md="4">
          <h4 class="mb-2">{{ t('home.servicesTitle') }}</h4>
          <div class="text-grey-lighten-1 mb-3">Casablanca · Rabat · Marrakech · Fès · Tanger · Agadir</div>
          <div class="d-flex flex-column ga-1">
            <router-link :to="{ name: 'how-it-works' }" class="text-grey-lighten-1 text-decoration-none">{{ t('nav.howItWorks') }}</router-link>
            <router-link :to="{ name: 'become-sitter' }" class="text-grey-lighten-1 text-decoration-none">{{ t('nav.becomeSitter') }}</router-link>
            <router-link :to="{ name: 'search' }" class="text-grey-lighten-1 text-decoration-none">{{ t('nav.search') }}</router-link>
          </div>
        </v-col>
        <v-col cols="6" md="4">
          <h4 class="mb-2">Contact</h4>
          <div class="text-grey-lighten-1">contact@daripets.ma</div>
          <div class="text-grey-lighten-1">+212 5 22 00 00 00</div>
        </v-col>
      </v-row>
      <v-divider class="my-4" />
      <div class="d-flex flex-wrap justify-center ga-4 mb-2">
        <router-link :to="{ name: 'cgv' }" class="text-grey-lighten-1 text-decoration-none text-caption">Conditions Générales de Vente</router-link>
        <router-link :to="{ name: 'confidentialite' }" class="text-grey-lighten-1 text-decoration-none text-caption">Politique de Confidentialité</router-link>
      </div>
      <div class="text-center text-grey-lighten-1">
        © {{ year }} {{ t('brand') }}. {{ t('footer.rights') }}
      </div>
    </v-container>
  </v-footer>
</template>
