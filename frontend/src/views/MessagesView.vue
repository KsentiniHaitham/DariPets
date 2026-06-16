<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
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
// Horloge réactive (rafraîchie à chaque tick) pour les libellés « en ligne / vu il y a… »
const now = ref(Date.now())

const otherParticipant = (c) => c.participants?.find((p) => p.id !== auth.user.id) || c.participants?.[0]
const mine = (m) => m.sender?.id === auth.user.id

function initials(u) {
  if (!u) return '?'
  return ((u.firstName?.[0] || '') + (u.lastName?.[0] || '')).toUpperCase() || '?'
}

function scrollToBottom() {
  nextTick(() => {
    if (messagesBox.value) messagesBox.value.scrollTop = messagesBox.value.scrollHeight
  })
}

// --- Présence en ligne ---
function isOnline(u) {
  if (!u?.lastSeenAt) return false
  return now.value - new Date(u.lastSeenAt).getTime() < 120000 // < 2 min
}
function presenceText(u) {
  if (isOnline(u)) return 'En ligne'
  if (!u?.lastSeenAt) return 'Hors ligne'
  const mins = Math.floor((now.value - new Date(u.lastSeenAt).getTime()) / 60000)
  if (mins < 60) return `Vu il y a ${mins} min`
  const h = Math.floor(mins / 60)
  if (h < 24) return `Vu il y a ${h} h`
  return `Vu il y a ${Math.floor(h / 24)} j`
}

// --- Horodatage & séparateurs de date ---
function fmtTime(iso) {
  return new Date(iso).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}
function dateLabel(iso) {
  const d = new Date(iso)
  const today = new Date()
  const yest = new Date()
  yest.setDate(today.getDate() - 1)
  if (d.toDateString() === today.toDateString()) return "Aujourd'hui"
  if (d.toDateString() === yest.toDateString()) return 'Hier'
  return d.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long' })
}
function showDaySep(list, i) {
  if (i === 0) return true
  return new Date(list[i].sentAt).toDateString() !== new Date(list[i - 1].sentAt).toDateString()
}

// --- Accusés de réception (Envoyé / Distribué / Vu), façon Messenger ---
function receiptOf(m) {
  if (m.readAt) return { icon: 'mdi-check-all', color: 'info', text: 'Vu ' + fmtTime(m.readAt) }
  if (m.deliveredAt) return { icon: 'mdi-check-all', color: 'grey', text: 'Distribué' }
  return { icon: 'mdi-check', color: 'grey', text: 'Envoyé' }
}
const lastMine = computed(() => (active.value?.messages || []).filter(mine).at(-1))

// Nombre de messages non lus (reçus) d'une conversation, pour le badge de la liste
function unreadCount(c) {
  return (c.messages || []).filter((m) => !m.isRead && m.sender?.id !== auth.user.id).length
}

// Marque la conversation comme lue côté serveur et rafraîchit le badge navbar
async function markRead(c) {
  if (!c) return
  try {
    await api.post(`/conversations/${c.id}/read`, {})
    ;(c.messages || []).forEach((m) => {
      if (m.sender?.id !== auth.user.id) m.isRead = true
    })
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
        const ts = new Date(m.sentAt).getTime()
        if (ts > bestTime) {
          bestTime = ts
          best = c
        }
      }
    }
  }
  return best
}

async function load() {
  const { data } = await api.get('/conversations')
  conversations.value = [...data].sort((a, b) => lastActivity(b) - lastActivity(a))
  if (data.length && !active.value) {
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

// Rafraîchit la conversation active : nouveaux messages ET mise à jour des
// accusés (distribué/vu) sur les messages déjà affichés.
async function refreshActive() {
  now.value = Date.now()
  if (!active.value || sending.value) return
  try {
    const { data } = await api.get(`/conversations/${active.value.id}`)
    const incoming = data.messages || []
    const current = active.value.messages || []
    const hasNew = incoming.length > current.length
    // Met à jour les statuts (readAt/deliveredAt) même sans nouveau message
    active.value.messages = incoming
    if (hasNew) {
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
  pollTimer = setInterval(refreshActive, 8000)
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
                <v-badge dot color="success" location="bottom end" offset-x="4" offset-y="4" bordered
                  :model-value="isOnline(otherParticipant(c))">
                  <v-avatar color="primary">
                    <v-img v-if="otherParticipant(c)?.avatar" :src="otherParticipant(c).avatar" cover />
                    <span v-else>{{ initials(otherParticipant(c)) }}</span>
                  </v-avatar>
                </v-badge>
              </template>
              <v-list-item-title :class="unreadCount(c) ? 'font-weight-bold' : ''">
                {{ otherParticipant(c)?.fullName }}
              </v-list-item-title>
              <v-list-item-subtitle :class="unreadCount(c) ? 'font-weight-medium text-high-emphasis' : ''">
                {{ c.messages?.at(-1)?.body }}
              </v-list-item-subtitle>
              <template #append>
                <div class="d-flex flex-column align-end ga-1">
                  <span class="text-caption text-grey" v-if="c.messages?.length">
                    {{ fmtTime(c.messages.at(-1).sentAt) }}
                  </span>
                  <v-badge v-if="unreadCount(c)" :content="unreadCount(c)" color="error" inline />
                </div>
              </template>
            </v-list-item>
          </v-list>
        </v-col>

        <v-col cols="12" md="8" class="d-flex flex-column">
          <div v-if="active" class="d-flex align-center px-4 py-2 border-b">
            <v-badge dot color="success" location="bottom end" offset-x="3" offset-y="3" bordered
              :model-value="isOnline(otherParticipant(active))">
              <v-avatar color="primary" size="40" class="me-3">
                <v-img v-if="otherParticipant(active)?.avatar" :src="otherParticipant(active).avatar" cover />
                <span v-else>{{ initials(otherParticipant(active)) }}</span>
              </v-avatar>
            </v-badge>
            <div>
              <div class="font-weight-medium">{{ otherParticipant(active)?.fullName }}</div>
              <div class="text-caption" :class="isOnline(otherParticipant(active)) ? 'text-success' : 'text-grey'">
                {{ presenceText(otherParticipant(active)) }}
              </div>
            </div>
            <v-spacer />
            <v-btn size="small" variant="text" color="error" prepend-icon="mdi-flag" @click="reportDialog = true">
              Signaler
            </v-btn>
          </div>

          <div ref="messagesBox" class="flex-grow-1 pa-4" style="overflow-y:auto; max-height: 420px">
            <template v-for="(m, i) in active?.messages" :key="m.id">
              <!-- Séparateur de date -->
              <div v-if="showDaySep(active.messages, i)" class="text-center my-3">
                <v-chip size="x-small" variant="tonal" color="grey">{{ dateLabel(m.sentAt) }}</v-chip>
              </div>

              <div class="d-flex mb-1 align-end" :class="mine(m) ? 'justify-end' : 'justify-start'">
                <!-- Avatar de l'interlocuteur sur les messages reçus -->
                <v-avatar v-if="!mine(m)" size="28" color="primary" class="me-2">
                  <v-img v-if="otherParticipant(active)?.avatar" :src="otherParticipant(active).avatar" cover />
                  <span v-else class="text-caption">{{ initials(otherParticipant(active)) }}</span>
                </v-avatar>

                <div style="max-width: 72%">
                  <v-sheet rounded="lg" class="pa-2 px-3" :color="mine(m) ? 'primary' : 'grey-lighten-3'"
                    :class="mine(m) ? 'text-white' : ''" style="white-space: pre-wrap; word-break: break-word">
                    {{ m.body }}
                  </v-sheet>
                  <div class="text-caption text-grey d-flex align-center ga-1 mt-1"
                    :class="mine(m) ? 'justify-end' : ''">
                    <span>{{ fmtTime(m.sentAt) }}</span>
                    <v-icon v-if="mine(m)" size="14" :color="receiptOf(m).color">{{ receiptOf(m).icon }}</v-icon>
                  </div>
                </div>
              </div>

              <!-- Accusé « Vu » sous le dernier message envoyé et lu -->
              <div v-if="mine(m) && m.id === lastMine?.id && m.readAt"
                class="text-caption text-info text-right pe-1 mb-2">
                Vu {{ fmtTime(m.readAt) }}
              </div>
            </template>
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
