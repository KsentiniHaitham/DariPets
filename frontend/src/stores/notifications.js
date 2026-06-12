import { defineStore } from 'pinia'
import api from '@/services/api.js'

// Centre de notifications (cloche navbar) : réservations, paiements, avis,
// signalements, KYC… selon le rôle de l'utilisateur.
export const useNotificationsStore = defineStore('notifications', {
  state: () => ({
    items: [],
    unread: 0,
    timer: null,
    // Titre de la dernière notification arrivée pendant la session (pour le snackbar)
    lastIncoming: null,
  }),
  actions: {
    async fetch() {
      try {
        const { data } = await api.get('/notifications')
        if (data.unread > this.unread && data.items.length) {
          const newest = data.items.find((n) => !n.isRead)
          if (newest) this.lastIncoming = { ...newest, at: Date.now() }
        }
        this.items = data.items
        this.unread = data.unread
      } catch {
        // silencieux : on retentera au prochain tick
      }
    },
    async markRead(notification) {
      if (notification.isRead) return
      notification.isRead = true
      this.unread = Math.max(0, this.unread - 1)
      try {
        await api.post(`/notifications/${notification.id}/read`, {})
      } catch {
        // non bloquant
      }
    },
    async markAllRead() {
      this.items.forEach((n) => (n.isRead = true))
      this.unread = 0
      try {
        await api.post('/notifications/read-all', {})
      } catch {
        // non bloquant
      }
    },
    startPolling() {
      if (this.timer) return
      this.fetch()
      this.timer = setInterval(() => this.fetch(), 15000)
    },
    stopPolling() {
      if (this.timer) {
        clearInterval(this.timer)
        this.timer = null
      }
      this.items = []
      this.unread = 0
      this.lastIncoming = null
    },
  },
})
