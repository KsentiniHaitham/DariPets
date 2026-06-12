<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { Line, Bar, Doughnut } from 'vue-chartjs'
import api from '@/services/api.js'
import { palette, statusColors } from '@/plugins/charts.js'

const { t } = useI18n()

const tab = ref('overview')
const stats = ref(null)
const sitters = ref([])
const reviews = ref([])
const reports = ref([])
const loading = ref(true)

const pendingReports = computed(() => reports.value.filter((r) => r.status === 'pending').length)

async function loadAll() {
  loading.value = true
  const [s, sit, rev, rep] = await Promise.all([
    api.get('/admin/stats'),
    api.get('/pet_sitter_profiles', { params: { 'order[verified]': 'asc', pagination: false } }),
    api.get('/reviews'),
    api.get('/reports'),
  ])
  stats.value = s.data
  sitters.value = sit.data
  reviews.value = rev.data
  reports.value = rep.data
  loading.value = false
}

async function setReportStatus(report, status) {
  const { data } = await api.patch(`/reports/${report.id}`, { status }, {
    headers: { 'Content-Type': 'application/merge-patch+json' },
  })
  report.status = data.status
}

const reasonLabels = {
  contournement: 'Contact hors plateforme',
  comportement: 'Comportement inapproprié',
  fraude: 'Fraude / arnaque',
  spam: 'Spam',
  autre: 'Autre',
}

const kycError = ref('')

async function toggleVerify(sitter) {
  kycError.value = ''
  try {
    const { data } = await api.post(`/admin/sitters/${sitter.id}/verify`, {})
    sitter.verified = data.verified
    if (stats.value) {
      stats.value.sittersVerified += data.verified ? 1 : -1
      stats.value.sittersPending += data.verified ? -1 : 1
    }
  } catch (e) {
    if (e.response?.status === 422) {
      kycError.value = e.response.data.error
    } else {
      throw e
    }
  }
}

async function showDocument(sitter) {
  const { data } = await api.get(`/admin/sitters/${sitter.id}/document`)
  alert(`Pièce d'identité de ${data.sitter} :\n${data.idDocument}`)
}

async function deleteReview(r) {
  if (!confirm(t('admin.deleteConfirm'))) return
  await api.delete(`/reviews/${r.id}`)
  reviews.value = reviews.value.filter((x) => x.id !== r.id)
}

const statCards = computed(() => {
  const s = stats.value
  if (!s) return []
  return [
    { label: t('admin.users'), value: s.users, icon: 'mdi-account-group', color: 'primary' },
    { label: t('admin.sitters'), value: s.sitters, icon: 'mdi-paw', color: 'info' },
    { label: t('admin.verified'), value: s.sittersVerified, icon: 'mdi-check-decagram', color: 'success' },
    { label: t('admin.pending'), value: s.sittersPending, icon: 'mdi-clock-alert', color: 'warning' },
    { label: t('admin.bookings'), value: s.bookings, icon: 'mdi-calendar', color: 'secondary' },
    { label: t('admin.reviews'), value: s.reviews, icon: 'mdi-star', color: 'amber-darken-2' },
    { label: t('admin.revenue'), value: s.revenueMad + ' ' + t('currency'), icon: 'mdi-cash-multiple', color: 'info' },
    { label: t('admin.platformRevenue'), value: s.commissionMad + ' ' + t('currency'), icon: 'mdi-percent', color: 'success' },
  ]
})

const baseOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { display: false } },
}
const baseOptionsLegend = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: { legend: { position: 'bottom' } },
}

// --- Données des graphiques ---
const c = computed(() => stats.value?.charts || {})

const registrationsData = computed(() => ({
  labels: c.value.monthLabels || [],
  datasets: [{
    label: t('admin.users'),
    data: c.value.registrations || [],
    borderColor: palette.primary,
    backgroundColor: palette.primarySoft,
    fill: true,
    tension: 0.35,
    pointBackgroundColor: palette.primary,
  }],
}))

const bookingsMonthData = computed(() => ({
  labels: c.value.monthLabels || [],
  datasets: [{
    label: t('admin.bookings'),
    data: c.value.bookingsByMonth || [],
    backgroundColor: palette.info,
    borderRadius: 6,
  }],
}))

const revenueData = computed(() => ({
  labels: c.value.monthLabels || [],
  datasets: [
    {
      label: t('admin.revenue'),
      data: c.value.revenueByMonth || [],
      borderColor: palette.info,
      backgroundColor: 'rgba(59,130,246,0.10)',
      fill: true,
      tension: 0.35,
      pointBackgroundColor: palette.info,
    },
    {
      label: t('admin.platformRevenue'),
      data: c.value.commissionByMonth || [],
      borderColor: palette.success,
      backgroundColor: 'rgba(16,185,129,0.18)',
      fill: true,
      tension: 0.35,
      pointBackgroundColor: palette.success,
    },
  ],
}))

const statusData = computed(() => {
  const keys = c.value.bookingStatusKeys || []
  return {
    labels: keys.map((k) => t('booking.status' + k.charAt(0).toUpperCase() + k.slice(1))),
    datasets: [{
      data: c.value.bookingStatusValues || [],
      backgroundColor: keys.map((k) => statusColors[k] || palette.grey),
      borderWidth: 0,
    }],
  }
})

const cityData = computed(() => ({
  labels: c.value.sittersByCity?.labels || [],
  datasets: [{
    label: t('admin.sitters'),
    data: c.value.sittersByCity?.data || [],
    backgroundColor: palette.secondary,
    borderRadius: 6,
  }],
}))

const ratingData = computed(() => ({
  labels: ['★1', '★2', '★3', '★4', '★5'],
  datasets: [{
    label: t('admin.reviews'),
    data: c.value.ratingDistribution || [],
    backgroundColor: [palette.error, palette.warning, palette.accent, palette.info, palette.success],
    borderRadius: 6,
  }],
}))

onMounted(loadAll)
</script>

<template>
  <v-container fluid class="py-8 px-md-8">
    <div class="d-flex align-center mb-6">
      <v-icon color="primary" size="32" class="me-2">mdi-shield-account</v-icon>
      <h1 class="text-h4 font-weight-bold">{{ t('admin.title') }}</h1>
    </div>

    <v-tabs v-model="tab" color="primary" class="mb-6">
      <v-tab value="overview" prepend-icon="mdi-chart-box">{{ t('admin.overview') }}</v-tab>
      <v-tab value="sitters" prepend-icon="mdi-paw">{{ t('admin.sitters') }}</v-tab>
      <v-tab value="reviews" prepend-icon="mdi-star">{{ t('admin.moderation') }}</v-tab>
      <v-tab value="reports" prepend-icon="mdi-flag">
        Signalements
        <v-badge v-if="pendingReports" :content="pendingReports" color="error" inline />
      </v-tab>
    </v-tabs>

    <v-progress-linear v-if="loading" indeterminate color="primary" />

    <v-window v-else v-model="tab">
      <!-- ============ DASHBOARD ============ -->
      <v-window-item value="overview">
        <!-- KPI cards -->
        <v-row class="mb-2">
          <v-col v-for="k in statCards" :key="k.label" cols="6" md="3">
            <v-card flat border class="pa-4 d-flex align-center h-100">
              <v-avatar :color="k.color" size="48" class="me-3"><v-icon size="26">{{ k.icon }}</v-icon></v-avatar>
              <div class="overflow-hidden">
                <div class="text-h6 font-weight-bold text-truncate">{{ k.value }}</div>
                <div class="text-caption text-medium-emphasis">{{ k.label }}</div>
              </div>
            </v-card>
          </v-col>
        </v-row>

        <!-- Charts -->
        <v-row>
          <v-col cols="12" md="8">
            <v-card flat border class="pa-4">
              <div class="text-subtitle-1 font-weight-bold mb-2">
                <v-icon size="18" class="me-1" color="primary">mdi-account-multiple-plus</v-icon>
                Inscriptions (6 mois)
              </div>
              <div style="height: 280px"><Line :data="registrationsData" :options="baseOptions" /></div>
            </v-card>
          </v-col>
          <v-col cols="12" md="4">
            <v-card flat border class="pa-4">
              <div class="text-subtitle-1 font-weight-bold mb-2">
                <v-icon size="18" class="me-1" color="info">mdi-chart-donut</v-icon>
                {{ t('admin.bookings') }} / statut
              </div>
              <div style="height: 280px"><Doughnut :data="statusData" :options="baseOptionsLegend" /></div>
            </v-card>
          </v-col>

          <v-col cols="12" md="6">
            <v-card flat border class="pa-4">
              <div class="text-subtitle-1 font-weight-bold mb-2">
                <v-icon size="18" class="me-1" color="success">mdi-cash-multiple</v-icon>
                {{ t('admin.revenue') }} vs {{ t('admin.platformRevenue') }} (MAD / mois)
              </div>
              <div style="height: 260px"><Line :data="revenueData" :options="baseOptionsLegend" /></div>
            </v-card>
          </v-col>
          <v-col cols="12" md="6">
            <v-card flat border class="pa-4">
              <div class="text-subtitle-1 font-weight-bold mb-2">
                <v-icon size="18" class="me-1" color="info">mdi-calendar-month</v-icon>
                {{ t('admin.bookings') }} / mois
              </div>
              <div style="height: 260px"><Bar :data="bookingsMonthData" :options="baseOptions" /></div>
            </v-card>
          </v-col>

          <v-col cols="12" md="7">
            <v-card flat border class="pa-4">
              <div class="text-subtitle-1 font-weight-bold mb-2">
                <v-icon size="18" class="me-1" color="secondary">mdi-map-marker</v-icon>
                {{ t('admin.sitters') }} / ville
              </div>
              <div style="height: 260px"><Bar :data="cityData" :options="baseOptions" /></div>
            </v-card>
          </v-col>
          <v-col cols="12" md="5">
            <v-card flat border class="pa-4">
              <div class="text-subtitle-1 font-weight-bold mb-2">
                <v-icon size="18" class="me-1" color="amber-darken-2">mdi-star</v-icon>
                Répartition des notes
              </div>
              <div style="height: 260px"><Bar :data="ratingData" :options="baseOptions" /></div>
            </v-card>
          </v-col>
        </v-row>
      </v-window-item>

      <!-- ============ GARDIENS ============ -->
      <v-window-item value="sitters">
        <v-alert v-if="kycError" type="error" variant="tonal" density="compact" class="mb-3" closable @click:close="kycError = ''">
          {{ kycError }}
        </v-alert>
        <v-card border flat>
          <v-table>
            <thead>
              <tr>
                <th>{{ t('admin.sitters') }}</th>
                <th>{{ t('search.city') }}</th>
                <th>{{ t('sitter.rate') }}</th>
                <th>{{ t('sitter.reviews') }}</th>
                <th>KYC</th>
                <th>Statut</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in sitters" :key="s.id">
                <td>{{ s.user?.fullName }}</td>
                <td>{{ s.user?.city?.name }}</td>
                <td>{{ s.dailyRate }} {{ t('currency') }}</td>
                <td>{{ s.rating }} ({{ s.reviewCount }})</td>
                <td>
                  <v-chip v-if="s.idDocumentSubmitted" color="info" size="small" prepend-icon="mdi-card-account-details"
                    style="cursor:pointer" @click="showDocument(s)">Document fourni</v-chip>
                  <v-chip v-else color="grey" size="small" variant="outlined">Aucun document</v-chip>
                </td>
                <td>
                  <v-chip v-if="s.verified" color="success" size="small" prepend-icon="mdi-check-decagram">{{ t('sitterProfile.verified') }}</v-chip>
                  <v-chip v-else color="warning" size="small">{{ t('admin.pending') }}</v-chip>
                </td>
                <td class="text-end">
                  <v-btn :color="s.verified ? 'grey' : 'success'" size="small" variant="tonal" @click="toggleVerify(s)">
                    {{ s.verified ? t('admin.unverify') : t('admin.verify') }}
                  </v-btn>
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-card>
      </v-window-item>

      <!-- ============ MODÉRATION AVIS ============ -->
      <v-window-item value="reviews">
        <v-card border flat v-if="reviews.length">
          <v-list>
            <template v-for="(r, i) in reviews" :key="r.id">
              <v-list-item>
                <template #prepend>
                  <v-rating :model-value="r.rating" color="amber" density="compact" readonly size="16" />
                </template>
                <v-list-item-title class="ms-2">
                  <strong>{{ r.author?.firstName }}</strong> → {{ r.target?.fullName }}
                </v-list-item-title>
                <v-list-item-subtitle class="ms-2">{{ r.comment }}</v-list-item-subtitle>
                <template #append>
                  <v-btn icon="mdi-delete" variant="text" color="error" size="small" @click="deleteReview(r)" />
                </template>
              </v-list-item>
              <v-divider v-if="i < reviews.length - 1" />
            </template>
          </v-list>
        </v-card>
        <v-alert v-else type="info" variant="tonal">{{ t('sitter.noReviews') }}</v-alert>
      </v-window-item>

      <!-- ============ SIGNALEMENTS ============ -->
      <v-window-item value="reports">
        <v-card border flat v-if="reports.length">
          <v-table>
            <thead>
              <tr>
                <th>Date</th>
                <th>Signalé par</th>
                <th>Utilisateur signalé</th>
                <th>Motif</th>
                <th>Détails</th>
                <th>Statut</th>
                <th class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="r in reports" :key="r.id">
                <td>{{ new Date(r.createdAt).toLocaleDateString('fr-FR') }}</td>
                <td>{{ r.reporter?.fullName }}</td>
                <td><strong>{{ r.reported?.fullName }}</strong></td>
                <td>{{ reasonLabels[r.reason] || r.reason }}</td>
                <td class="text-caption" style="max-width: 220px">{{ r.details }}</td>
                <td>
                  <v-chip v-if="r.status === 'pending'" color="warning" size="small">En attente</v-chip>
                  <v-chip v-else-if="r.status === 'resolved'" color="success" size="small">Traité</v-chip>
                  <v-chip v-else color="grey" size="small">Classé</v-chip>
                </td>
                <td class="text-end">
                  <template v-if="r.status === 'pending'">
                    <v-btn color="success" size="small" variant="tonal" class="me-1" @click="setReportStatus(r, 'resolved')">Traiter</v-btn>
                    <v-btn color="grey" size="small" variant="tonal" @click="setReportStatus(r, 'dismissed')">Classer</v-btn>
                  </template>
                </td>
              </tr>
            </tbody>
          </v-table>
        </v-card>
        <v-alert v-else type="info" variant="tonal">Aucun signalement.</v-alert>
      </v-window-item>
    </v-window>
  </v-container>
</template>
