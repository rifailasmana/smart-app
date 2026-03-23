/**
 * Typed fetch wrapper that automatically injects the Bearer token
 * and uses the configured API base URL.
 */
export function useApi() {
  const auth   = useAuthStore()
  const config = useRuntimeConfig()
  const base   = config.public.apiBase as string

  function headers() {
    return auth.token ? { Authorization: `Bearer ${auth.token}` } : {}
  }

  async function get<T>(path: string, params?: Record<string, string>): Promise<T> {
    return $fetch<T>(`${base}${path}`, {
      method: 'GET',
      headers: headers(),
      params,
    })
  }

  async function post<T>(path: string, body?: unknown): Promise<T> {
    return $fetch<T>(`${base}${path}`, {
      method: 'POST',
      headers: headers(),
      body,
    })
  }

  async function put<T>(path: string, body?: unknown): Promise<T> {
    return $fetch<T>(`${base}${path}`, {
      method: 'PUT',
      headers: headers(),
      body,
    })
  }

  async function del<T>(path: string): Promise<T> {
    return $fetch<T>(`${base}${path}`, {
      method: 'DELETE',
      headers: headers(),
    })
  }

  return { get, post, put, del }
}
