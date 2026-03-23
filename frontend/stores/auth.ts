import { defineStore } from 'pinia'

interface Warung {
  id: number
  name: string
  slug: string
}

interface User {
  id: number
  name: string
  username: string
  role: string
  warung_id: number | null
  warung: Warung | null
}

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(null)
  const user  = ref<User | null>(null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)

  /** Hydrate from localStorage on client */
  function init() {
    if (import.meta.client) {
      token.value = localStorage.getItem('auth_token')
      const stored = localStorage.getItem('auth_user')
      if (stored) {
        try { user.value = JSON.parse(stored) } catch { /* ignore */ }
      }
    }
  }

  async function login(username: string, password: string) {
    const config = useRuntimeConfig()
    const data = await $fetch<{ token: string; user: User }>(`${config.public.apiBase}/api/auth/login`, {
      method: 'POST',
      body: { username, password },
    })
    token.value = data.token
    user.value  = data.user
    if (import.meta.client) {
      localStorage.setItem('auth_token', data.token)
      localStorage.setItem('auth_user', JSON.stringify(data.user))
    }
    return data.user
  }

  async function fetchUser() {
    if (!token.value) return
    const config = useRuntimeConfig()
    try {
      const data = await $fetch<User>(`${config.public.apiBase}/api/user`, {
        headers: { Authorization: `Bearer ${token.value}` },
      })
      user.value = data
      if (import.meta.client) {
        localStorage.setItem('auth_user', JSON.stringify(data))
      }
    } catch {
      // Token invalid – clear
      logout()
    }
  }

  async function logout() {
    const config = useRuntimeConfig()
    if (token.value) {
      try {
        await $fetch(`${config.public.apiBase}/api/auth/logout`, {
          method: 'POST',
          headers: { Authorization: `Bearer ${token.value}` },
        })
      } catch { /* best-effort */ }
    }
    token.value = null
    user.value  = null
    if (import.meta.client) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
    }
  }

  /** Role-based dashboard route */
  function dashboardRoute(): string {
    switch (user.value?.role) {
      case 'admin':     return '/dashboard/admin'
      case 'owner':     return '/dashboard/owner'
      case 'manager':   return '/dashboard/manager'
      case 'hrd':       return '/dashboard/hrd'
      case 'inventory': return '/dashboard/inventory'
      case 'kasir':     return '/terminal/kasir'
      case 'waiter':    return '/terminal/waiter'
      case 'dapur':
      case 'kitchen':   return '/terminal/kitchen'
      default:          return '/dashboard'
    }
  }

  return { token, user, isAuthenticated, init, login, fetchUser, logout, dashboardRoute }
})
