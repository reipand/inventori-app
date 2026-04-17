import { ref, onUnmounted } from 'vue'

export interface UseIdleLogoutOptions {
  timeoutMs?: number
  onIdle?: () => void
}

const DEFAULT_TIMEOUT_MS = 15 * 60 * 1000 // 15 minutes
const CHECK_INTERVAL_MS = 10 * 1000 // 10 seconds

export function useIdleLogout(options: UseIdleLogoutOptions = {}) {
  const { timeoutMs = DEFAULT_TIMEOUT_MS, onIdle } = options

  const lastActivity = ref<number>(Date.now())
  const isIdle = ref<boolean>(false)

  let intervalId: ReturnType<typeof setInterval> | null = null

  function reset(): void {
    lastActivity.value = Date.now()
    isIdle.value = false
  }

  function checkIdle(): void {
    if (Date.now() - lastActivity.value > timeoutMs) {
      if (!isIdle.value) {
        isIdle.value = true
        onIdle?.()
      }
    }
  }

  function handleActivity(): void {
    reset()
  }

  function handleVisibilityChange(): void {
    if (document.visibilityState === 'visible') {
      checkIdle()
    }
  }

  const events: Array<keyof DocumentEventMap> = ['mousemove', 'click', 'scroll', 'keydown']

  events.forEach((event) => {
    document.addEventListener(event, handleActivity)
  })

  document.addEventListener('visibilitychange', handleVisibilityChange)

  intervalId = setInterval(checkIdle, CHECK_INTERVAL_MS)

  function stop(): void {
    events.forEach((event) => {
      document.removeEventListener(event, handleActivity)
    })
    document.removeEventListener('visibilitychange', handleVisibilityChange)
    if (intervalId !== null) {
      clearInterval(intervalId)
      intervalId = null
    }
  }

  onUnmounted(() => {
    stop()
  })

  return {
    lastActivity,
    isIdle,
    reset,
    stop,
  }
}
