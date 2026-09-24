import axios from 'axios'

export const http = axios.create({
  baseURL: import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000',
  timeout: 15000,
  withCredentials: true,
  withXSRFToken: true,
  headers: { Accept: 'application/json' },
})

export function installInterceptors(onUnauthorized) {
  return http.interceptors.response.use(
    response => response,
    error => {
      if (error.response?.status === 401 && !error.config?.skipAuthRedirect) {
        onUnauthorized()
      }
      return Promise.reject(error)
    }
  )
}

export function errorText(error) {
  const status = error.response?.status
  if (status === 401) return 'Sesi berakhir. Silakan login lagi.'
  if (status === 403) return 'Anda tidak berhak mengakses tiket ini.'
  if (status === 404) return 'Data tidak ditemukan.'
  if (status === 419) return 'CSRF/session tidak cocok. Muat ulang lalu login kembali bila perlu.'
  if (status === 422) return 'Periksa isian formulir atau parameter permintaan.'
  if (status === 429) return 'Terlalu banyak permintaan. Tunggu sesuai Retry-After sebelum mencoba lagi.'
  if (status >= 500) return 'Server bermasalah. Coba lagi nanti.'
  if (error.code === 'ECONNABORTED') return 'Permintaan melewati batas waktu.'
  return 'Tidak dapat menghubungi API. Periksa jaringan, server, dan CORS.'
}

export const isCanceled = axios.isCancel
