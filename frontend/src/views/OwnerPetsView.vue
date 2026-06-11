<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import api from '@/services/api.js'
import { useAuthStore } from '@/stores/auth.js'

const { t } = useI18n()
const auth = useAuthStore()

const animalTypes = ['dog', 'cat', 'bird', 'rodent', 'other']
const typeIcon = { dog: 'mdi-dog', cat: 'mdi-cat', bird: 'mdi-bird', rodent: 'mdi-rodent', other: 'mdi-paw' }

const pets = ref([])
const loading = ref(true)
const dialog = ref(false)
const saving = ref(false)
const editing = ref(null)
const form = ref({ name: '', type: 'dog', breed: '', age: null, notes: '' })

async function load() {
  loading.value = true
  const { data } = await api.get('/animals', { params: { owner: auth.user.id } })
  pets.value = data
  loading.value = false
}

function openAdd() {
  editing.value = null
  form.value = { name: '', type: 'dog', breed: '', age: null, notes: '' }
  dialog.value = true
}
function openEdit(p) {
  editing.value = p
  form.value = { name: p.name, type: p.type, breed: p.breed, age: p.age, notes: p.notes }
  dialog.value = true
}

async function save() {
  saving.value = true
  try {
    const payload = { ...form.value }
    if (editing.value) {
      await api.put(`/animals/${editing.value.id}`, payload)
    } else {
      payload.owner = `/api/users/${auth.user.id}`
      await api.post('/animals', payload)
    }
    dialog.value = false
    await load()
  } finally {
    saving.value = false
  }
}

async function remove(p) {
  if (!confirm(t('pets.deleteConfirm'))) return
  await api.delete(`/animals/${p.id}`)
  await load()
}

onMounted(load)
</script>

<template>
  <v-container class="py-8" style="max-width: 900px">
    <div class="d-flex align-center mb-6">
      <h1 class="text-h4 font-weight-bold">{{ t('pets.title') }}</h1>
      <v-spacer />
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openAdd">{{ t('pets.add') }}</v-btn>
    </div>

    <v-progress-linear v-if="loading" indeterminate color="primary" />

    <v-row v-else-if="pets.length">
      <v-col v-for="p in pets" :key="p.id" cols="12" sm="6">
        <v-card border flat class="pa-4 d-flex align-center">
          <v-avatar color="primary" size="56" class="me-4">
            <v-icon size="30">{{ typeIcon[p.type] }}</v-icon>
          </v-avatar>
          <div class="flex-grow-1">
            <div class="text-h6">{{ p.name }}</div>
            <div class="text-body-2 text-medium-emphasis">
              {{ t('animals.' + p.type) }}<span v-if="p.breed"> · {{ p.breed }}</span><span v-if="p.age"> · {{ p.age }} {{ t('pets.age').toLowerCase().includes('ans') ? 'ans' : '' }}</span>
            </div>
            <div v-if="p.notes" class="text-caption mt-1">{{ p.notes }}</div>
          </div>
          <div class="d-flex flex-column ga-1">
            <v-btn icon="mdi-pencil" variant="text" size="small" @click="openEdit(p)" />
            <v-btn icon="mdi-delete" variant="text" size="small" color="error" @click="remove(p)" />
          </div>
        </v-card>
      </v-col>
    </v-row>

    <v-alert v-else type="info" variant="tonal">{{ t('pets.empty') }}</v-alert>

    <!-- Dialog ajout/édition -->
    <v-dialog v-model="dialog" max-width="500">
      <v-card class="pa-2">
        <v-card-title>{{ editing ? t('pets.edit') : t('pets.add') }}</v-card-title>
        <v-card-text>
          <v-text-field v-model="form.name" :label="t('pets.name')" />
          <v-select v-model="form.type" :items="animalTypes.map(a => ({ title: t('animals.' + a), value: a }))" :label="t('pets.type')" />
          <v-text-field v-model="form.breed" :label="t('pets.breed')" />
          <v-text-field v-model.number="form.age" type="number" :label="t('pets.age')" />
          <v-textarea v-model="form.notes" :label="t('pets.notes')" rows="2" />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="dialog = false">{{ t('common.cancel') }}</v-btn>
          <v-btn color="primary" :loading="saving" :disabled="!form.name" @click="save">{{ t('common.save') }}</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
  </v-container>
</template>
