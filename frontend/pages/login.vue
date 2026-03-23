<template>
  <div class="min-h-screen flex items-center justify-center" style="background: linear-gradient(135deg, #062e22 0%, #0a4a35 100%)">
    <div class="w-full max-w-md px-5">
      <!-- Card -->
      <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
        <!-- Header -->
        <div class="px-10 py-10 text-center" style="background: linear-gradient(135deg, #FF8C00, #FFC107)">
          <h1 class="text-3xl font-black text-black uppercase tracking-tight mb-1">MAJAR SIGNATURE</h1>
          <p class="text-black/70 font-semibold">Operating System Login</p>
        </div>

        <!-- Body -->
        <div class="px-10 py-8">
          <!-- Error -->
          <div v-if="error" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-2xl text-red-700 text-sm font-semibold">
            {{ error }}
          </div>

          <form @submit.prevent="handleLogin" class="space-y-5">
            <!-- Username -->
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Username</label>
              <div class="flex rounded-2xl overflow-hidden border-2 border-gray-100 focus-within:border-orange-400 transition-colors">
                <span class="bg-gray-50 px-4 flex items-center text-gray-400">
                  <i class="bi bi-person-fill" />
                </span>
                <input
                  v-model="form.username"
                  type="text"
                  placeholder="Masukkan username"
                  required
                  autofocus
                  class="flex-1 px-4 py-3 outline-none bg-gray-50 text-gray-900 font-medium"
                />
              </div>
            </div>

            <!-- Password -->
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-2">Password</label>
              <div class="flex rounded-2xl overflow-hidden border-2 border-gray-100 focus-within:border-orange-400 transition-colors">
                <span class="bg-gray-50 px-4 flex items-center text-gray-400">
                  <i class="bi bi-lock-fill" />
                </span>
                <input
                  v-model="form.password"
                  type="password"
                  placeholder="••••••••"
                  required
                  class="flex-1 px-4 py-3 outline-none bg-gray-50 text-gray-900 font-medium"
                />
              </div>
            </div>

            <button
              type="submit"
              :disabled="loading"
              class="w-full py-4 rounded-2xl font-black text-black uppercase tracking-widest text-sm transition-all hover:-translate-y-0.5 hover:shadow-lg active:scale-95 disabled:opacity-50"
              style="background: linear-gradient(135deg, #FF8C00, #FFC107)"
            >
              <span v-if="loading">Masuk…</span>
              <span v-else>Sign In <i class="bi bi-arrow-right ms-2" /></span>
            </button>
          </form>

          <!-- Demo accounts -->
          <div class="mt-8 p-5 bg-gray-50 rounded-2xl border border-dashed border-gray-200">
            <h6 class="text-xs font-black uppercase tracking-widest text-gray-600 mb-4">
              <i class="bi bi-info-circle text-orange-500 mr-2" />Test Accounts
            </h6>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-gray-500">
              <div>Owner: <code class="text-gray-800 font-bold">owner</code></div>
              <div>Manager: <code class="text-gray-800 font-bold">manager</code></div>
              <div>HRD: <code class="text-gray-800 font-bold">hrd</code></div>
              <div>Inventory: <code class="text-gray-800 font-bold">inventory</code></div>
              <div>Kasir: <code class="text-gray-800 font-bold">kasir</code></div>
              <div>Waiter: <code class="text-gray-800 font-bold">waiter</code></div>
              <div>Kitchen: <code class="text-gray-800 font-bold">kitchen</code></div>
              <div>Admin: <code class="text-gray-800 font-bold">admin</code></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'default', middleware: 'guest' })

useHead({ title: 'Login – Majar Signature' })

const auth   = useAuthStore()
const router = useRouter()

const form    = reactive({ username: '', password: '' })
const loading = ref(false)
const error   = ref('')

async function handleLogin() {
  error.value   = ''
  loading.value = true
  try {
    const user = await auth.login(form.username, form.password)
    router.push(auth.dashboardRoute())
  } catch (e: unknown) {
    const err = e as { data?: { message?: string } }
    error.value = err?.data?.message ?? 'Login gagal, periksa username dan password.'
  } finally {
    loading.value = false
  }
}
</script>
