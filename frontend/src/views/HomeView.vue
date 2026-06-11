<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import api from '@/services/api.js'
import SitterCard from '@/components/SitterCard.vue'

const { t } = useI18n()
const router = useRouter()

const cities = ref([])
const services = ref([])
const topSitters = ref([])
const selCity = ref(null)
const selService = ref(null)

onMounted(async () => {
  const [c, s, sit] = await Promise.all([
    api.get('/cities', { params: { pagination: false } }),
    api.get('/services'),
    api.get('/pet_sitter_profiles', { params: { itemsPerPage: 3, 'order[rating]': 'desc' } }),
  ])
  cities.value = c.data
  services.value = s.data
  // L'API ne limite pas via itemsPerPage (pagination client désactivée) : on tronque ici
  topSitters.value = sit.data.slice(0, 3)
})

function doSearch() {
  router.push({ name: 'search', query: { city: selCity.value, service: selService.value } })
}
</script>

<template>
  <!-- ============ HERO ============ -->
  <section class="hero-gradient text-white">
    <v-container class="py-16">
      <v-row align="center">
        <v-col cols="12" md="7">
          <div class="hero-pill mb-5">
            <v-icon size="16">mdi-shield-check</v-icon>
            Gardiens vérifiés · Paiement sécurisé CMI
          </div>

          <h1 class="text-h3 text-md-h2 font-weight-bold mb-4 text-balance" style="line-height:1.12">
            {{ t('home.heroTitle') }}
          </h1>
          <p class="text-h6 font-weight-regular mb-8 text-balance" style="opacity:.92; max-width: 560px">
            {{ t('home.heroSubtitle') }}
          </p>

          <!-- Recherche « verre dépoli » -->
          <v-card class="glass-card pa-4" rounded="xl" flat>
            <v-row dense align="center">
              <v-col cols="12" sm="5">
                <v-select v-model="selCity" :items="cities" item-title="name" item-value="id"
                  :label="t('home.searchCity')" prepend-inner-icon="mdi-map-marker" hide-details clearable />
              </v-col>
              <v-col cols="12" sm="5">
                <v-select v-model="selService" :items="services" item-title="name" item-value="id"
                  :label="t('home.searchService')" prepend-inner-icon="mdi-paw" hide-details clearable />
              </v-col>
              <v-col cols="12" sm="2">
                <v-btn color="primary" size="large" block height="52" @click="doSearch">
                  <v-icon class="me-1">mdi-magnify</v-icon>
                  <span class="d-sm-none">{{ t('home.searchBtn') }}</span>
                </v-btn>
              </v-col>
            </v-row>
          </v-card>

          <!-- Mini-stats de confiance -->
          <div class="d-flex flex-wrap ga-6 mt-8">
            <div>
              <div class="text-h5 font-weight-bold">500+</div>
              <div class="text-body-2" style="opacity:.85">{{ t('admin.sitters') }}</div>
            </div>
            <div>
              <div class="text-h5 font-weight-bold">4.8 ★</div>
              <div class="text-body-2" style="opacity:.85">{{ t('sitter.reviews') }}</div>
            </div>
            <div>
              <div class="text-h5 font-weight-bold">25+</div>
              <div class="text-body-2" style="opacity:.85">{{ t('search.city') }}s</div>
            </div>
          </div>
        </v-col>

        <v-col cols="12" md="5" class="text-center d-none d-md-block">
          <div class="position-relative d-inline-block">
            <v-icon size="300" style="opacity:.22">mdi-dog-side</v-icon>
            <v-icon size="120" color="white" style="opacity:.35; position:absolute; bottom:-8px; right:-30px">mdi-cat</v-icon>
          </div>
        </v-col>
      </v-row>
    </v-container>
  </section>

  <!-- ============ SERVICES ============ -->
  <section class="section">
    <v-container>
      <div class="text-center mb-10">
        <span class="section-eyebrow">DariPets</span>
        <h2 class="text-h4 font-weight-bold">{{ t('home.servicesTitle') }}</h2>
      </div>
      <v-row justify="center">
        <v-col v-for="s in services" :key="s.id" cols="6" md="4" lg="2">
          <v-card class="service-tile text-center pa-5 h-100" flat
            :to="{ name: 'search', query: { service: s.id } }">
            <span class="service-icon mb-3">
              <v-icon size="32" color="primary">{{ s.icon || 'mdi-paw' }}</v-icon>
            </span>
            <div class="text-body-2 font-weight-semibold mt-2" style="font-weight:600">{{ s.name }}</div>
          </v-card>
        </v-col>
      </v-row>
    </v-container>
  </section>

  <!-- ============ COMMENT ÇA MARCHE ============ -->
  <section class="section" style="background: linear-gradient(180deg, #F8F7FC 0%, #F1EEFB 100%)">
    <v-container>
      <div class="text-center mb-12">
        <span class="section-eyebrow">Simple & rapide</span>
        <h2 class="text-h4 font-weight-bold">{{ t('home.howTitle') }}</h2>
      </div>
      <v-row>
        <v-col v-for="(step, i) in ['1', '2', '3']" :key="step" cols="12" md="4" class="text-center">
          <span class="step-badge mb-5">
            <v-icon size="30">{{ ['mdi-magnify', 'mdi-calendar-check', 'mdi-heart'][i] }}</v-icon>
          </span>
          <h3 class="text-h6 font-weight-bold mb-2">{{ t('home.step' + step + 'Title') }}</h3>
          <p class="text-body-1 text-medium-emphasis px-6">{{ t('home.step' + step) }}</p>
        </v-col>
      </v-row>
    </v-container>
  </section>

  <!-- ============ TOP GARDIENS ============ -->
  <section class="section">
    <v-container>
      <div class="text-center mb-10">
        <span class="section-eyebrow">⭐ Top notés</span>
        <h2 class="text-h4 font-weight-bold">{{ t('home.topSittersTitle') }}</h2>
      </div>
      <v-row>
        <v-col v-for="p in topSitters" :key="p.id" cols="12" sm="6" md="4">
          <SitterCard :profile="p" />
        </v-col>
      </v-row>
      <div class="text-center mt-8">
        <v-btn color="primary" size="x-large" variant="flat" rounded="xl" :to="{ name: 'search' }">
          {{ t('nav.search') }}
          <v-icon end>mdi-arrow-right</v-icon>
        </v-btn>
      </div>
    </v-container>
  </section>

  <!-- ============ CTA DEVENIR GARDIEN ============ -->
  <section class="hero-gradient text-white text-center py-14">
    <v-container style="max-width: 760px">
      <h2 class="text-h4 font-weight-bold mb-3">{{ t('home.ctaTitle') }}</h2>
      <p class="text-h6 font-weight-regular mb-7" style="opacity:.92">{{ t('home.ctaText') }}</p>
      <v-btn color="white" size="x-large" rounded="xl" class="text-primary font-weight-bold"
        :to="{ name: 'register', query: { type: 'sitter' } }">
        <v-icon start>mdi-paw</v-icon>
        {{ t('home.ctaBtn') }}
      </v-btn>
    </v-container>
  </section>
</template>
