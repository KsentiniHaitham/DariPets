import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth.js'

const routes = [
  { path: '/', name: 'home', component: () => import('@/views/HomeView.vue') },
  { path: '/recherche', name: 'search', component: () => import('@/views/SearchView.vue') },
  { path: '/gardien/:id', name: 'sitter', component: () => import('@/views/SitterView.vue'), props: true },
  { path: '/connexion', name: 'login', component: () => import('@/views/LoginView.vue') },
  { path: '/login', redirect: '/connexion' },
  { path: '/inscription', name: 'register', component: () => import('@/views/RegisterView.vue') },
  { path: '/comment-ca-marche', name: 'how-it-works', component: () => import('@/views/HowItWorksView.vue') },
  { path: '/devenir-gardien', name: 'become-sitter', component: () => import('@/views/BecomeSitterView.vue') },
  {
    path: '/favoris',
    name: 'favorites',
    component: () => import('@/views/FavoritesView.vue'),
    meta: { requiresAuth: true, roles: ['owner'] },
  },
  {
    path: '/reserver/:sitterId',
    name: 'booking-create',
    component: () => import('@/views/BookingCreateView.vue'),
    props: true,
    meta: { requiresAuth: true },
  },
  { path: '/espace', name: 'dashboard', component: () => import('@/views/DashboardView.vue'), meta: { requiresAuth: true } },
  { path: '/messages', name: 'messages', component: () => import('@/views/MessagesView.vue'), meta: { requiresAuth: true } },

  // --- Propriétaire ---
  {
    path: '/mes-animaux',
    name: 'owner-pets',
    component: () => import('@/views/OwnerPetsView.vue'),
    meta: { requiresAuth: true, roles: ['owner'] },
  },

  // --- Gardien ---
  {
    path: '/mon-profil',
    name: 'sitter-profile',
    component: () => import('@/views/SitterProfileEditView.vue'),
    meta: { requiresAuth: true, roles: ['sitter'] },
  },
  {
    path: '/mes-revenus',
    name: 'sitter-earnings',
    component: () => import('@/views/SitterEarningsView.vue'),
    meta: { requiresAuth: true, roles: ['sitter'] },
  },

  // --- Admin ---
  {
    path: '/admin',
    name: 'admin',
    component: () => import('@/views/AdminView.vue'),
    meta: { requiresAuth: true, roles: ['admin'] },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach((to) => {
  const auth = useAuthStore()
  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }
  // Garde par rôle : redirige vers l'espace approprié si rôle non autorisé
  if (to.meta.roles && !to.meta.roles.includes(auth.role)) {
    return auth.isAdmin ? { name: 'admin' } : { name: 'dashboard' }
  }
})

export default router
