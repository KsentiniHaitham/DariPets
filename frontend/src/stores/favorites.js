import { defineStore } from 'pinia'
import api from '@/services/api.js'
import { useAuthStore } from '@/stores/auth.js'

export const useFavoritesStore = defineStore('favorites', {
  state: () => ({
    items: [],       // [{ id, profile: {...} }]
    loaded: false,
  }),
  getters: {
    profileIds: (s) => new Set(s.items.map((f) => f.profile?.id)),
    isFavorite() {
      return (profileId) => this.profileIds.has(profileId)
    },
  },
  actions: {
    async load() {
      const auth = useAuthStore()
      if (!auth.isAuthenticated) return
      const { data } = await api.get('/favorites', { params: { owner: auth.user.id } })
      this.items = data
      this.loaded = true
    },
    async toggle(profile) {
      const existing = this.items.find((f) => f.profile?.id === profile.id)
      if (existing) {
        await api.delete(`/favorites/${existing.id}`)
        this.items = this.items.filter((f) => f.id !== existing.id)
        return false
      }
      const { data } = await api.post('/favorites', { profile: `/api/pet_sitter_profiles/${profile.id}` })
      this.items.push(data)
      return true
    },
  },
})
