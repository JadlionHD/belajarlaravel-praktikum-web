import { ref, onScopeDispose } from 'vue'
import { errorText, isCanceled } from '../api/client'

export function useRead(loader) {
  const data = ref(null), state = ref('idle'), message = ref('')
  let controller, sequence = 0
  async function run(...args) {
    const own = ++sequence
    controller?.abort()
    controller = new AbortController()
    state.value = 'loading'
    message.value = ''
    data.value = null
    try {
      const result = await loader(...args, controller.signal)
      if (own !== sequence) return
      data.value = result
      const rows = Array.isArray(result) ? result : result?.data
      state.value = Array.isArray(rows) && rows.length === 0 ? 'empty' : 'success'
    } catch (e) {
      if (own !== sequence || isCanceled(e)) return
      state.value = 'error'
      message.value = errorText(e)
    }
  }
  onScopeDispose(() => {
    sequence++
    controller?.abort()
  })
  return { data, state, message, run }
}
