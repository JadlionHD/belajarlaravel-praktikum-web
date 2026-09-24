import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import HelpdeskLayout from '../layouts/HelpdeskLayout.vue'
import LoginView from '../views/LoginView.vue'
import ListView from '../views/ListView.vue'
import DetailView from '../views/DetailView.vue'
import CreateView from '../views/CreateView.vue'
import SessionErrorView from '../views/SessionErrorView.vue'
import NotFoundView from '../views/NotFoundView.vue'

export function makeRouter(pinia) {
  const router = createRouter({
    history: createWebHistory(),
    routes: [
      { path: '/', redirect: '/tickets' },
      { path: '/login', name: 'login', component: LoginView },
      { path: '/session-error', name: 'session-error', component: SessionErrorView },
      {
        path: '/tickets',
        component: HelpdeskLayout,
        meta: { requiresAuth: true },
        children: [
          { path: '', name: 'tickets', component: ListView },
          { path: 'new', name: 'ticket-new', component: CreateView },
          { path: ':id(\\d+)', name: 'ticket-detail', component: DetailView },
        ],
      },
      { path: '/:pathMatch(.*)*', name: 'not-found', component: NotFoundView },
    ],
  })

  router.beforeEach(async to => {
    if (!to.meta.requiresAuth) return true
    const auth = useAuthStore(pinia)
    try {
      await auth.restore()
    } catch {
      return { name: 'session-error', query: { redirect: to.fullPath } }
    }
    if (!auth.authenticated) {
      return { name: 'login', query: { redirect: to.fullPath } }
    }
  })

  return router
}

export function safeTarget(value) {
  return typeof value === 'string' && /^\/tickets(?:\/|\?|$)/.test(value) ? value : '/tickets'
}
