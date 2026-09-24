import { defineStore } from 'pinia'
import { http } from '../api/client'

export const useAuthStore = defineStore('auth', {
  state: () => ({ user: null, ready: false }),
  getters: { authenticated: state => state.user !== null },
  actions: {
    clear() {
      this.user = null
      this.ready = true
    },
    async restore() {
      if (this.ready) return
      try {
        const r = await http.get('/api/v1/me', { skipAuthRedirect: true })
        this.user = r.data.data
        this.ready = true
      } catch (e) {
        if (e.response?.status === 401) {
          this.clear()
        } else {
          throw e // jaringan gagal bukan bukti pengguna logout
        }
      }
    },
    async login(credentials) {
      await http.get('/sanctum/csrf-cookie', { skipAuthRedirect: true })
      const r = await http.post('/login', credentials, { skipAuthRedirect: true })
      this.user = r.data.data
      this.ready = true
    },
    async logout() {
      try {
        await http.post('/logout', {}, { skipAuthRedirect: true })
      } catch (e) {
        if (e.response?.status !== 401) throw e
      }
      this.clear() // jangan mengklaim logout sukses jika server tidak terjangkau
    },
  },
})
