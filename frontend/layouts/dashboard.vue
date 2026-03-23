<template>
  <div class="flex min-h-screen">

    <!-- ── Sidebar ──────────────────────────────────────────────── -->
    <aside
      class="w-64 bg-[#062e22] text-gray-200 flex flex-col fixed h-screen z-50 shadow-xl transition-transform duration-300"
      :class="{ '-translate-x-full': !sidebarOpen, 'translate-x-0': sidebarOpen }"
    >
      <!-- Brand -->
      <div class="px-6 py-7 border-b border-white/10 text-center">
        <span class="font-black text-xl tracking-wide">
          <span class="text-orange-400">MAJAR</span>
          <span class="text-white"> SIGNATURE</span>
        </span>
      </div>

      <!-- Role-aware nav items -->
      <nav class="flex-1 py-4 px-3 overflow-y-auto">
        <template v-for="item in sidebarNav" :key="item.to">
          <NuxtLink
            :to="item.to"
            class="flex items-center gap-3 px-4 py-3 rounded-xl mb-1 font-semibold text-sm transition-all"
            :class="route.path.startsWith(item.to) ? 'bg-white/15 text-white' : 'text-gray-400 hover:bg-white/8 hover:text-white'"
          >
            <i :class="item.icon + ' w-5 text-center text-base'" />
            {{ item.label }}
          </NuxtLink>
        </template>
      </nav>

      <!-- Logout -->
      <div class="px-3 pb-6 border-t border-white/10 pt-4">
        <button
          class="flex items-center gap-3 w-full px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 transition font-semibold"
          @click="handleLogout"
        >
          <i class="fas fa-sign-out-alt w-6 text-center"></i>
          <span>Logout</span>
        </button>
      </div>
    </aside>

    <!-- Backdrop (mobile) -->
    <div
      v-if="sidebarOpen && isMobile"
      class="fixed inset-0 bg-black/40 z-40 lg:hidden"
      @click="sidebarOpen = false"
    />

    <!-- ── Main ──────────────────────────────────────────────────── -->
    <div
      class="flex flex-col flex-1 min-w-0"
      :class="sidebarOpen ? 'lg:ml-64' : 'ml-0'"
    >
      <!-- Sticky Header -->
      <header class="bg-[#062e22] text-white px-8 py-4 flex justify-between items-center sticky top-0 z-40 shadow-md">
        <div class="flex items-center gap-4">
          <!-- Hamburger (mobile) -->
          <button class="lg:hidden text-white mr-2" @click="sidebarOpen = !sidebarOpen">
            <i class="fas fa-bars text-xl"></i>
          </button>

          <!-- Logo icon -->
          <div
            class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-lg"
            style="background: linear-gradient(135deg, #FF8C00, #FFC107); color: #000"
          >
            MS
          </div>

          <div>
            <div class="font-bold text-base leading-tight">
              <slot name="header-title">Dashboard</slot>
            </div>
            <div class="text-xs text-white/70">
              <slot name="header-subtitle">Majar Signature Operating System</slot>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-5">
          <AppClock class="text-white/80 text-sm" />

          <!-- User chip -->
          <div class="flex items-center gap-3 bg-white/10 px-4 py-2 rounded-full border border-white/20">
            <div
              class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-sm text-black"
              style="background: linear-gradient(135deg, #FF8C00, #FFC107)"
            >
              {{ auth.user?.name?.charAt(0)?.toUpperCase() }}
            </div>
            <div>
              <div class="text-sm font-semibold text-white leading-tight">{{ auth.user?.name }}</div>
              <div class="text-[10px] text-orange-300 font-bold uppercase tracking-widest">{{ auth.user?.role }}</div>
            </div>
          </div>

          <button
            class="text-sm font-semibold text-white/70 hover:text-white border border-white/20 px-4 py-2 rounded-full transition"
            @click="handleLogout"
          >
            Logout
          </button>
        </div>
      </header>

      <!-- Page content -->
      <main class="flex-1 p-8 bg-[#e5d3bf]">
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
const auth  = useAuthStore()
const router = useRouter()
const route  = useRoute()

const sidebarOpen = ref(true)
const isMobile    = ref(false)

const navByRole: Record<string, { to: string; icon: string; label: string }[]> = {
  owner: [
    { to: '/dashboard/owner',     icon: 'bi bi-speedometer2',   label: 'Overview' },
    { to: '/dashboard/manager',   icon: 'bi bi-graph-up-arrow', label: 'Operasional' },
    { to: '/dashboard/inventory', icon: 'bi bi-boxes',          label: 'Inventory' },
    { to: '/dashboard/hrd',       icon: 'bi bi-people-fill',    label: 'HRD' },
  ],
  manager: [
    { to: '/dashboard/manager', icon: 'bi bi-speedometer2', label: 'Overview' },
  ],
  hrd: [
    { to: '/dashboard/hrd', icon: 'bi bi-people-fill', label: 'HRD' },
  ],
  inventory: [
    { to: '/dashboard/inventory', icon: 'bi bi-boxes', label: 'Inventory' },
  ],
  admin: [
    { to: '/dashboard/admin',     icon: 'bi bi-gear-fill',      label: 'Admin' },
    { to: '/dashboard/owner',     icon: 'bi bi-speedometer2',   label: 'Overview' },
    { to: '/dashboard/manager',   icon: 'bi bi-graph-up-arrow', label: 'Operasional' },
    { to: '/dashboard/inventory', icon: 'bi bi-boxes',          label: 'Inventory' },
    { to: '/dashboard/hrd',       icon: 'bi bi-people-fill',    label: 'HRD' },
  ],
}

const sidebarNav = computed(() => navByRole[auth.user?.role ?? ''] ?? [])

onMounted(() => {
  isMobile.value    = window.innerWidth < 1024
  sidebarOpen.value = !isMobile.value
  window.addEventListener('resize', () => {
    isMobile.value = window.innerWidth < 1024
  })
})

async function handleLogout() {
  await auth.logout()
  router.push('/login')
}
</script>
