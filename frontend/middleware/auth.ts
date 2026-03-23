// middleware/auth.ts
// Redirect to /login if the user is not authenticated.
export default defineNuxtRouteMiddleware(() => {
  const auth = useAuthStore()
  auth.init()
  if (!auth.isAuthenticated) {
    return navigateTo('/login')
  }
})
