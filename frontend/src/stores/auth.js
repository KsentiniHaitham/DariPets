import { defineStore } from 'pinia'
import api from '@/services/api.js'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('token') || null,
    user: JSON.parse(localStorage.getItem('user') || 'null'),
  }),
  getters: {
    isAuthenticated: (s) => !!s.token,
    isSitter: (s) => s.user?.type === 'sitter',
    isOwner: (s) => s.user?.type === 'owner' && !(s.user?.roles || []).includes('ROLE_ADMIN'),
    isAdmin: (s) => (s.user?.roles || []).includes('ROLE_ADMIN'),
    // Rôle effectif pour piloter la navigation : admin > sitter > owner
    role: (s) => {
      if ((s.user?.roles || []).includes('ROLE_ADMIN')) return 'admin'
      return s.user?.type === 'sitter' ? 'sitter' : 'owner'
    },
    fullName: (s) => (s.user ? `${s.user.firstName} ${s.user.lastName}` : ''),
    homeRoute() {
      if (this.isAdmin) return { name: 'admin' }
      return { name: 'dashboard' }
    },
  },
  actions: {
    async login(email, password) {
      const { data } = await api.post('/login_check', { email, password })
      this.token = data.token
      localStorage.setItem('token', data.token)
      await this.fetchMe(email)
    },
    async register(payload) {
      await api.post('/register', payload)
      await this.login(payload.email, payload.plainPassword)
    },
    async fetchMe(email) {
      // Récupère le profil courant via filtre e-mail
      const { data } = await api.get('/users', { params: { email } })
      const me = Array.isArray(data) ? data[0] : (data['hydra:member']?.[0] ?? data.member?.[0])
      if (me) {
        this.user = me
        localStorage.setItem('user', JSON.stringify(me))
      }
    },
    logout() {
      this.token = null
      this.user = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    },
  },
})
