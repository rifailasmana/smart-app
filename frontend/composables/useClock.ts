/**
 * Reactive live clock composable.
 * Returns a ref that updates every second.
 */
export function useClock(format: 'time' | 'datetime' = 'time') {
  const display = ref('')

  function tick() {
    const now = new Date()
    if (format === 'time') {
      display.value = now.toLocaleTimeString('id-ID', { hour12: false })
    } else {
      display.value = now.toLocaleString('id-ID', {
        hour12: false,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
      })
    }
  }

  let timer: ReturnType<typeof setInterval>
  onMounted(() => {
    tick()
    timer = setInterval(tick, 1000)
  })
  onUnmounted(() => clearInterval(timer))

  return display
}
