import { defineStore } from 'pinia'
import api from '@/services/api.js'

// Compteur de messages non lus, affiché en badge sur l'icône messages de la navbar
export const useUnreadStore = defineStore('unread', {
  state: () => ({
    count: 0,
    timer: null,
  }),
  actions: {
    async fetch() {
      try {
        const { data } = await api.get('/messages/unread-count')
        this.count = data.count
      } catch {
        // silencieux : on retentera au prochain tick
      }
    },
    startPolling() {
      if (this.timer) return
      this.fetch()
      this.timer = setInterval(() => this.fetch(), 30000)
    },
    stopPolling() {
      if (this.timer) {
        clearInterval(this.timer)
        this.timer = null
      }
      this.count = 0
    },
  },
})
