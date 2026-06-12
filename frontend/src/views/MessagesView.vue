<script setup>
import { ref, onMounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import api from '@/services/api.js'
import { useAuthStore } from '@/stores/auth.js'

const { t } = useI18n()
const route = useRoute()
const auth = useAuthStore()

const conversations = ref([])
const active = ref(null)
const draft = ref('')
const loading = ref(true)

const otherParticipant = (c) => c.participants?.find((p) => p.id !== auth.user.id) || c.participants?.[0]

async function load() {
  const { data } = await api.get('/conversations')
  conversations.value = data
  if (data.length && !active.value) {
    // Ouvre la conversation demandée via ?c=ID (depuis le bouton « Contacter »)
    const wanted = route.query.c ? data.find((x) => x.id === Number(route.query.c)) : null
    active.value = wanted || data[0]
  }
  loading.value = false
}

async function send() {
  if (!draft.value.trim() || !active.value) return
  await api.post('/messages', {
    conversation: `/api/conversations/${active.value.id}`,
    sender: `/api/users/${auth.user.id}`,
    body: draft.value,
  })
  draft.value = ''
  const { data } = await api.get(`/conversations/${active.value.id}`)
  active.value = data
  await load()
}

onMounted(load)
</script>

<template>
  <v-container class="py-8">
    <h1 class="text-h5 font-weight-bold mb-4">{{ t('messages.title') }}</h1>
    <v-alert type="info" variant="tonal" density="compact" class="mb-4" icon="mdi-shield-lock">
      Pour votre sécurité, les numéros de téléphone, e-mails et liens sont masqués
      dans les messages tant qu'aucune réservation payée n'existe entre vous.
    </v-alert>
    <v-card border flat v-if="conversations.length">
      <v-row no-gutters style="min-height: 480px">
        <v-col cols="12" md="4" class="border-e">
          <v-list>
            <v-list-item v-for="c in conversations" :key="c.id" :active="active?.id === c.id" @click="active = c">
              <template #prepend>
                <v-avatar color="primary"><span>{{ otherParticipant(c)?.firstName?.[0] }}</span></v-avatar>
              </template>
              <v-list-item-title>{{ otherParticipant(c)?.fullName }}</v-list-item-title>
              <v-list-item-subtitle>{{ c.messages?.at(-1)?.body }}</v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-col>

        <v-col cols="12" md="8" class="d-flex flex-column">
          <div class="flex-grow-1 pa-4" style="overflow-y:auto; max-height: 420px">
            <div v-for="m in active?.messages" :key="m.id"
              class="d-flex mb-2" :class="m.sender?.id === auth.user.id ? 'justify-end' : 'justify-start'">
              <v-sheet rounded="lg" class="pa-2 px-3" :color="m.sender?.id === auth.user.id ? 'primary' : 'grey-lighten-3'"
                :class="m.sender?.id === auth.user.id ? 'text-white' : ''" max-width="70%">
                {{ m.body }}
              </v-sheet>
            </div>
          </div>
          <v-divider />
          <div class="pa-3 d-flex ga-2">
            <v-text-field v-model="draft" :placeholder="t('messages.placeholder')" hide-details density="comfortable"
              @keyup.enter="send" />
            <v-btn color="primary" icon="mdi-send" @click="send" />
          </div>
        </v-col>
      </v-row>
    </v-card>
    <v-alert v-else-if="!loading" type="info" variant="tonal">{{ t('messages.noConversations') }}</v-alert>
  </v-container>
</template>
