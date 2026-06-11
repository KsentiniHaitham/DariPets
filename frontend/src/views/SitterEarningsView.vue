<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/services/api.js'
import { useAuthStore } from '@/stores/auth.js'

const { t } = useI18n()
const auth = useAuthStore()

const bookings = ref([])
const loading = ref(true)

const mine = computed(() => bookings.value.filter((b) => b.sitter?.id === auth.user.id))
const paid = computed(() => mine.value.filter((b) => ['paid', 'completed'].includes(b.status)))
const pending = computed(() => mine.value.filter((b) => ['pending', 'accepted'].includes(b.status)))
// Montants NETS : ce que touche réellement le gardien après commission plateforme
const payout = (b) => Number(b.sitterPayout ?? b.totalPrice)
const totalEarned = computed(() => paid.value.reduce((s, b) => s + payout(b), 0).toFixed(2))
const totalPending = computed(() => pending.value.reduce((s, b) => s + payout(b), 0).toFixed(2))
const completed = computed(() => mine.value.filter((b) => b.status === 'completed'))

onMounted(async () => {
  const { data } = await api.get('/bookings')
  bookings.value = data
  loading.value = false
})
</script>

<template>
  <v-container class="py-8">
    <h1 class="text-h4 font-weight-bold mb-6">{{ t('earnings.title') }}</h1>

    <v-progress-linear v-if="loading" indeterminate color="primary" />

    <template v-else>
      <v-row class="mb-2">
        <v-col cols="12" sm="6" md="3">
          <v-card color="success" variant="tonal" class="pa-4 text-center">
            <div class="text-caption">{{ t('earnings.total') }}</div>
            <div class="text-h5 font-weight-bold">{{ totalEarned }} {{ t('currency') }}</div>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card color="warning" variant="tonal" class="pa-4 text-center">
            <div class="text-caption">{{ t('earnings.pendingAmount') }}</div>
            <div class="text-h5 font-weight-bold">{{ totalPending }} {{ t('currency') }}</div>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card variant="tonal" class="pa-4 text-center">
            <div class="text-caption">{{ t('earnings.paidCount') }}</div>
            <div class="text-h5 font-weight-bold">{{ paid.length }}</div>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card variant="tonal" class="pa-4 text-center">
            <div class="text-caption">{{ t('earnings.completedCount') }}</div>
            <div class="text-h5 font-weight-bold">{{ completed.length }}</div>
          </v-card>
        </v-col>
      </v-row>

      <v-alert type="info" variant="tonal" class="mb-4" icon="mdi-percent">
        {{ t('earnings.commissionInfo', { rate: 15 }) }}
      </v-alert>

      <v-card border flat v-if="paid.length">
        <v-table>
          <thead>
            <tr>
              <th>{{ t('booking.animal') }}</th>
              <th>{{ t('booking.service') }}</th>
              <th>Dates</th>
              <th class="text-end">{{ t('earnings.gross') }}</th>
              <th class="text-end">{{ t('earnings.net') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="b in paid" :key="b.id">
              <td>{{ b.owner?.fullName }}<span v-if="b.animal"> · {{ b.animal.name }}</span></td>
              <td>{{ b.service?.name }}</td>
              <td>{{ b.startDate?.slice(0, 10) }} → {{ b.endDate?.slice(0, 10) }}</td>
              <td class="text-end text-medium-emphasis">{{ b.totalPrice }} {{ t('currency') }}</td>
              <td class="text-end font-weight-bold text-success">{{ b.sitterPayout }} {{ t('currency') }}</td>
            </tr>
          </tbody>
        </v-table>
      </v-card>
      <v-alert v-else type="info" variant="tonal">{{ t('earnings.empty') }}</v-alert>
    </template>
  </v-container>
</template>
