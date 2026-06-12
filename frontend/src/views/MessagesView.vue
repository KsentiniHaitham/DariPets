<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import api from '@/services/api.js'
import { useAuthStore } from '@/stores/auth.js'
import { useUnreadStore } from '@/stores/unread.js'

const { t } = useI18n()
const route = useRoute()
const auth = useAuthStore()
const unread = useUnreadStore()

const conversations = ref([])
const active = ref(null)
const draft = ref('')
const loading = ref(true)
const sending = ref(false)
const messagesBox = ref(null)

const otherParticipant = (c) => c.participants?.find((p) => p.id !== auth.user.id) || c.participants?.[0]

function scrollToBottom() {
  nextTick(() => {
    if (messagesBox.value) messagesBox.value.scrollTop = messagesBox.value.scrollHeight
  })
}

// Marque la conversation comme lue côté serveur et rafraîchit le badge navbar
async function markRead(c) {
  if (!c) return
  try {
    await api.post(`/conversations/${c.id}/read`, {})
    unread.fetch()
  } catch {
    // non bloquant
  }
}

// Date du dernier message d'une conversation (0 si vide)
const lastActivity = (c) => {
  const last = c.messages?.at(-1)
  return last ? new Date(last.sentAt).getTime() : 0
}

// Conversation contenant le message non lu le plus récent (reçu, pas envoyé)
function latestUnreadConversation(list) {
  let best = null
  let bestTime = 0
  for (const c of list) {
    for (const m of c.messages || []) {
      if (!m.isRead && m.sender?.id !== auth.user.id) {
        const t = new Date(m.sentAt).getTime()
        if (t > bestTime) {
          bestTime = t
          best = c
        }
      }
    }
  }
  return best
}

async function load() {
  const { data } = await api.get('/conversations')
  // Conversations les plus récentes en premier
  conversations.value = [...data].sort((a, b) => lastActivity(b) - lastActivity(a))
  if (data.length && !active.value) {
    // Priorité : ?c=ID (bouton « Contacter ») > conversation avec non-lu récent > plus récente
    const wanted = route.query.c ? data.find((x) => x.id === Number(route.query.c)) : null
    active.value = wanted || latestUnreadConversation(data) || conversations.value[0]
    scrollToBottom()
    markRead(active.value)
  }
  loading.value = false
}

function selectConversation(c) {
  active.value = c
  scrollToBottom()
  markRead(c)
}

async function send() {
  if (!draft.value.trim() || !active.value || sending.value) return
  sending.value = true
  const body = draft.value
  draft.value = ''
  try {
    // Le POST retourne le message créé (avec masquage éventuel côté serveur) :
    // on l'ajoute directement à la discussion, sans recharger la page
    const { data: msg } = await api.post('/messages', {
      conversation: `/api/conversations/${active.value.id}`,
      body,
    })
    if (!active.value.messages) active.value.messages = []
    active.value.messages.push(msg)
    scrollToBottom()
  } catch (e) {
    draft.value = body // restaure le brouillon en cas d'échec
    throw e
  } finally {
    sending.value = false
  }
}

// Rafraîchit la conversation active toutes les 12 s pour voir les réponses
// de l'interlocuteur sans recharger la page
async function refreshActive() {
  if (!active.value || sending.value) return
  try {
    const { data } = await api.get(`/conversations/${active.value.id}`)
    if ((data.messages?.length ?? 0) > (active.value.messages?.length ?? 0)) {
      active.value.messages = data.messages
      scrollToBottom()
      markRead(active.value)
    }
  } catch {
    // silencieux : on retentera au prochain tick
  }
}

let pollTimer = null
onMounted(() => {
  load()
  pollTimer = setInterval(refreshActive, 12000)
})
onUnmounted(() => clearInterval(pollTimer))

// --- Emojis ---
const emojiMenu = ref(false)
const emojis = [
  '😀', '😂', '🥰', '😍', '😊', '😉', '🤗', '😎',
  '👍', '👋', '🙏', '👏', '💪', '🤝', '✅', '❤️',
  '🐶', '🐱', '🐦', '🐰', '🐾', '🦴', '🏠', '🌳',
  '😅', '😢', '😮', '🤔', '⏰', '📅', '💰', '🎉',
]

function addEmoji(e) {
  draft.value += e
}

// --- Signalement d'utilisateur ---
const reportDialog = ref(false)
const reportReason = ref('comportement')
const reportDetails = ref('')
const reportSent = ref(false)
const reportReasons = [
  { value: 'contournement', title: 'Tentative de contact hors plateforme' },
  { value: 'comportement', title: 'Comportement inapproprié' },
  { value: 'fraude', title: 'Fraude ou arnaque' },
  { value: 'spam', title: 'Spam' },
  { value: 'autre', title: 'Autre' },
]

async function sendReport() {
  const other = otherParticipant(active.value)
  if (!other) return
  await api.post('/reports', {
    reported: `/api/users/${other.id}`,
    reason: reportReason.value,
    details: reportDetails.value,
  })
  reportDialog.value = false
  reportDetails.value = ''
  reportSent.value = true
}
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
            <v-list-item v-for="c in conversations" :key="c.id" :active="active?.id === c.id" @click="selectConversation(c)">
              <template #prepend>
                <v-avatar color="primary"><span>{{ otherParticipant(c)?.firstName?.[0] }}</span></v-avatar>
              </template>
              <v-list-item-title>{{ otherParticipant(c)?.fullName }}</v-list-item-title>
              <v-list-item-subtitle>{{ c.messages?.at(-1)?.body }}</v-list-item-subtitle>
            </v-list-item>
          </v-list>
        </v-col>

        <v-col cols="12" md="8" class="d-flex flex-column">
          <div v-if="active" class="d-flex align-center px-4 py-2 border-b">
            <span class="font-weight-medium">{{ otherParticipant(active)?.fullName }}</span>
            <v-spacer />
            <v-btn size="small" variant="text" color="error" prepend-icon="mdi-flag" @click="reportDialog = true">
              Signaler
            </v-btn>
          </div>
          <div ref="messagesBox" class="flex-grow-1 pa-4" style="overflow-y:auto; max-height: 420px">
            <div v-for="m in active?.messages" :key="m.id"
              class="d-flex mb-2" :class="m.sender?.id === auth.user.id ? 'justify-end' : 'justify-start'">
              <v-sheet rounded="lg" class="pa-2 px-3" :color="m.sender?.id === auth.user.id ? 'primary' : 'grey-lighten-3'"
                :class="m.sender?.id === auth.user.id ? 'text-white' : ''" max-width="70%">
                {{ m.body }}
              </v-sheet>
            </div>
          </div>
          <v-divider />
          <div class="pa-3 d-flex ga-2 align-center">
            <v-menu v-model="emojiMenu" :close-on-content-click="false" location="top start">
              <template #activator="{ props }">
                <v-btn v-bind="props" variant="text" icon="mdi-emoticon-outline" />
              </template>
              <v-card class="pa-2" max-width="260">
                <div class="d-flex flex-wrap">
                  <v-btn v-for="e in emojis" :key="e" variant="text" density="comfortable" size="small"
                    class="text-h6 pa-0" min-width="32" @click="addEmoji(e)">{{ e }}</v-btn>
                </div>
              </v-card>
            </v-menu>
            <v-text-field v-model="draft" :placeholder="t('messages.placeholder')" hide-details density="comfortable"
              @keyup.enter="send" />
            <v-btn color="primary" icon="mdi-send" :loading="sending" @click="send" />
          </div>
        </v-col>
      </v-row>
    </v-card>
    <v-alert v-else-if="!loading" type="info" variant="tonal">{{ t('messages.noConversations') }}</v-alert>

    <!-- Dialog de signalement -->
    <v-dialog v-model="reportDialog" max-width="480">
      <v-card class="pa-4">
        <v-card-title class="text-h6">Signaler {{ otherParticipant(active)?.fullName }}</v-card-title>
        <v-card-text>
          <v-select v-model="reportReason" :items="reportReasons" label="Motif" class="mb-2" />
          <v-textarea v-model="reportDetails" label="Détails (facultatif)" rows="3" />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="reportDialog = false">Annuler</v-btn>
          <v-btn color="error" variant="flat" @click="sendReport">Envoyer le signalement</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>
    <v-snackbar v-model="reportSent" color="success" timeout="4000">
      Signalement envoyé. Notre équipe va l'examiner.
    </v-snackbar>
  </v-container>
</template>
