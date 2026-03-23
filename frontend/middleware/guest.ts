// middleware/guest.ts
// Redirect authenticated users away from guest-only pages (e.g. /login).
export default defineNuxtRouteMiddleware(() => {
  const auth = useAuthStore()
  auth.init()
  if (auth.isAuthenticated) {
    return navigateTo(auth.dashboardRoute())
  }
})
