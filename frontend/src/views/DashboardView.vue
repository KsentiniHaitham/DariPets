<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/services/api.js'
import { useAuthStore } from '@/stores/auth.js'

const { t } = useI18n()
const auth = useAuthStore()

const bookings = ref([])
const loading = ref(true)

const asOwner = computed(() => bookings.value.filter((b) => b.owner?.id === auth.user.id))
const asSitter = computed(() => bookings.value.filter((b) => b.sitter?.id === auth.user.id))

const statusColor = {
  pending: 'warning', accepted: 'info', paid: 'success',
  completed: 'secondary', cancelled: 'grey', rejected: 'error',
}

async function load() {
  loading.value = true
  const { data } = await api.get('/bookings')
  bookings.value = data
  loading.value = false
}

async function setStatus(b, status) {
  await api.patch(`/bookings/${b.id}`, { status }, { headers: { 'Content-Type': 'application/merge-patch+json' } })
  await load()
}

async function pay(b) {
  // Initialise le paiement CMI : le backend renvoie l'URL de la passerelle + params signés.
  // On construit un formulaire caché et on le POSTe vers CMI (page de paiement hostée).
  const { data } = await api.post(`/bookings/${b.id}/pay`, {})
  if (!data.action) return
  const form = document.createElement('form')
  form.method = 'POST'
  form.action = data.action
  for (const [k, v] of Object.entries(data.params)) {
    const input = document.createElement('input')
    input.type = 'hidden'
    input.name = k
    input.value = v
    form.appendChild(input)
  }
  document.body.appendChild(form)
  form.submit()
}

onMounted(load)
</script>

<template>
  <v-container class="py-8">
    <h1 class="text-h4 font-weight-bold mb-6">{{ t('dashboard.welcome', { name: auth.user?.firstName }) }}</h1>

    <v-progress-linear v-if="loading" indeterminate color="primary" />

    <template v-else>
      <!-- En tant que gardien -->
      <section v-if="asSitter.length" class="mb-8">
        <h2 class="text-h6 font-weight-bold mb-3">{{ t('dashboard.asSitter') }}</h2>
        <v-row>
          <v-col v-for="b in asSitter" :key="b.id" cols="12" md="6">
            <v-card border flat class="pa-4">
              <div class="d-flex align-center mb-2">
                <strong>{{ b.service?.name }}</strong>
                <v-spacer />
                <v-chip :color="statusColor[b.status]" size="small">{{ t('booking.status' + b.status.charAt(0).toUpperCase() + b.status.slice(1)) }}</v-chip>
              </div>
              <div class="text-body-2 text-medium-emphasis">
                {{ b.owner?.fullName }} · {{ b.animal?.name }}<br>
                {{ b.startDate?.slice(0, 10) }} → {{ b.endDate?.slice(0, 10) }} ·
                <strong class="text-success">{{ b.sitterPayout || b.totalPrice }} {{ t('currency') }}</strong>
                <span v-if="b.sitterPayout" class="text-caption"> ({{ t('earnings.net') }})</span>
              </div>
              <div v-if="b.status === 'pending'" class="mt-3 d-flex ga-2">
                <v-btn color="success" size="small" @click="setStatus(b, 'accepted')">{{ t('booking.accept') }}</v-btn>
                <v-btn color="error" variant="outlined" size="small" @click="setStatus(b, 'rejected')">{{ t('booking.reject') }}</v-btn>
              </div>
            </v-card>
          </v-col>
        </v-row>
      </section>

      <!-- En tant que propriétaire -->
      <section>
        <h2 class="text-h6 font-weight-bold mb-3">{{ t('dashboard.asOwner') }}</h2>
        <v-row v-if="asOwner.length">
          <v-col v-for="b in asOwner" :key="b.id" cols="12" md="6">
            <v-card border flat class="pa-4">
              <div class="d-flex align-center mb-2">
                <strong>{{ b.service?.name }}</strong>
                <v-spacer />
                <v-chip :color="statusColor[b.status]" size="small">{{ t('booking.status' + b.status.charAt(0).toUpperCase() + b.status.slice(1)) }}</v-chip>
              </div>
              <div class="text-body-2 text-medium-emphasis">
                {{ b.sitter?.fullName }} · {{ b.animal?.name }}<br>
                {{ b.startDate?.slice(0, 10) }} → {{ b.endDate?.slice(0, 10) }} · <strong>{{ b.totalPrice }} {{ t('currency') }}</strong>
              </div>
              <div v-if="b.status === 'accepted'" class="mt-3">
                <v-btn color="primary" size="small" block @click="pay(b)">
                  <v-icon start>mdi-credit-card</v-icon>{{ t('booking.payNow') }}
                </v-btn>
              </div>
            </v-card>
          </v-col>
        </v-row>
        <v-alert v-else type="info" variant="tonal">{{ t('dashboard.noBookings') }}</v-alert>
      </section>
    </template>
  </v-container>
</template>
