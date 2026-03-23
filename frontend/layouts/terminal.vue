<template>
  <!-- Full-screen terminal shell: dark header + content area -->
  <div class="flex flex-col h-screen overflow-hidden bg-gray-900 text-white">

    <!-- ── Terminal Header ─────────────────────────────────────── -->
    <header class="h-16 bg-[#062e22] border-b border-gray-800 flex items-center justify-between px-6 flex-shrink-0 shadow-md">
      <!-- Left: role badge + date -->
      <div class="flex items-center gap-4">
        <div class="w-10 h-10 bg-gradient-to-br from-orange-500 to-yellow-400 rounded-lg flex items-center justify-center">
          <i class="bi bi-person-circle text-2xl text-white"></i>
        </div>
        <div>
          <h1 class="text-lg font-black tracking-tight text-white leading-tight">{{ roleLabel }}</h1>
          <p class="text-xs text-gray-400">{{ dateLabel }}</p>
        </div>
      </div>

      <!-- Center: slot for extra controls injected by each terminal page -->
      <div class="flex items-center gap-6">
        <slot name="header-extra" />
      </div>

      <!-- Right: warung name + logout -->
      <div class="flex items-center gap-6">
        <div class="flex items-center gap-2 text-white">
          <i class="bi bi-wifi text-lg"></i>
          <span class="text-sm font-semibold">{{ auth.user?.warung?.name ?? 'SmartOrder' }}</span>
        </div>
        <AppClock />
        <button
          class="text-gray-400 hover:text-white transition-colors"
          title="Logout"
          @click="handleLogout"
        >
          <i class="bi bi-box-arrow-right text-xl"></i>
        </button>
      </div>
    </header>

    <!-- ── Content ─────────────────────────────────────────────── -->
    <main class="flex-1 flex overflow-hidden bg-brand-content">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
const auth = useAuthStore()
const router = useRouter()

const props = defineProps<{ roleLabel?: string }>()

const dateLabel = computed(() =>
  new Date().toLocaleDateString('id-ID', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
    year: 'numeric',
  })
)

const roleLabel = computed(() => props.roleLabel ?? auth.user?.role?.toUpperCase() ?? 'Terminal')

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>
