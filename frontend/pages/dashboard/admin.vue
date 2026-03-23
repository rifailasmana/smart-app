<template>
  <div>
    <div class="mb-8">
      <h1 class="text-3xl font-black text-gray-900 tracking-tight">Admin Dashboard</h1>
      <p class="text-gray-500 font-semibold mt-1">Manajemen warung & pengguna</p>
    </div>

    <!-- KPI -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
      <div v-for="kpi in kpis" :key="kpi.label" class="bg-white rounded-[1.5rem] p-6 border border-gray-100">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl mb-4" :class="kpi.iconBg">
          <i :class="kpi.icon" />
        </div>
        <div class="text-2xl font-black text-gray-900 mb-1">{{ kpi.value }}</div>
        <div class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ kpi.label }}</div>
      </div>
    </div>

    <!-- Users table -->
    <div class="bg-white rounded-[1.5rem] p-8 border border-gray-100 mb-6">
      <div class="flex items-center justify-between mb-6">
        <h3 class="font-black text-gray-900">Pengguna Sistem</h3>
        <div class="flex gap-3">
          <input v-model="search" type="text" placeholder="Cari pengguna…"
            class="px-4 py-2 border border-gray-200 rounded-xl text-sm w-48 focus:outline-none focus:border-orange-400"
          />
        </div>
      </div>
      <div v-if="loading" class="space-y-3">
        <div v-for="n in 5" :key="n" class="h-12 bg-gray-100 rounded-xl animate-pulse" />
      </div>
      <table v-else class="w-full text-sm">
        <thead>
          <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">
            <th class="text-left pb-4">Nama</th>
            <th class="text-left pb-4">Username</th>
            <th class="text-left pb-4">Role</th>
            <th class="text-left pb-4">Warung</th>
            <th class="text-right pb-4">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="u in filteredUsers" :key="u.id" class="hover:bg-gray-50">
            <td class="py-3 font-bold text-gray-900">{{ u.name }}</td>
            <td class="py-3 text-gray-500 font-mono text-xs">{{ u.username }}</td>
            <td class="py-3">
              <span class="px-2 py-1 rounded-full text-[10px] font-black bg-[#062e22]/10 text-[#062e22]">{{ u.role }}</span>
            </td>
            <td class="py-3 text-gray-500">{{ u.warung ?? '–' }}</td>
            <td class="py-3 text-right">
              <span class="px-2 py-1 rounded-full text-[10px] font-black bg-green-100 text-green-700">Aktif</span>
            </td>
          </tr>
          <tr v-if="!filteredUsers.length">
            <td colspan="5" class="py-8 text-center text-gray-400 font-semibold">Tidak ada pengguna</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Warungs -->
    <div class="bg-white rounded-[1.5rem] p-8 border border-gray-100">
      <h3 class="font-black text-gray-900 mb-6">Daftar Warung</h3>
      <div v-if="loading" class="grid grid-cols-3 gap-4">
        <div v-for="n in 3" :key="n" class="h-24 bg-gray-100 rounded-2xl animate-pulse" />
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <div v-for="w in warungs" :key="w.id" class="bg-gray-50 rounded-2xl p-5 border border-gray-100">
          <div class="flex items-start justify-between mb-2">
            <h4 class="font-black text-gray-900">{{ w.name }}</h4>
            <span class="text-xs font-bold text-gray-400">#{{ w.id }}</span>
          </div>
          <p class="text-sm text-gray-500 mb-3">{{ w.address ?? '–' }}</p>
          <div class="flex gap-2">
            <span class="text-[10px] font-black bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">{{ w.user_count }} pengguna</span>
            <span class="text-[10px] font-black bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full">{{ w.table_count }} meja</span>
          </div>
        </div>
        <div v-if="!warungs.length" class="col-span-3 text-center text-gray-400 py-8 font-semibold">
          Belum ada warung terdaftar
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'dashboard', middleware: 'auth' })
useHead({ title: 'Admin Dashboard – Majar Signature' })

const api     = useApi()
const loading = ref(true)
const search  = ref('')

interface AdminData {
  total_users: number
  total_warungs: number
  total_tables: number
  total_menu_items: number
  users: { id: number; name: string; username: string; role: string; warung?: string }[]
  warungs: { id: number; name: string; address?: string; user_count: number; table_count: number }[]
}

const data   = ref<AdminData | null>(null)
const warungs = computed(() => data.value?.warungs ?? [])

const kpis = computed(() => [
  { label: 'Total Pengguna',  value: data.value?.total_users ?? 0,      icon: 'bi bi-people-fill',  iconBg: 'bg-blue-100 text-blue-600' },
  { label: 'Total Warung',    value: data.value?.total_warungs ?? 0,    icon: 'bi bi-shop',         iconBg: 'bg-green-100 text-green-600' },
  { label: 'Total Meja',     value: data.value?.total_tables ?? 0,     icon: 'bi bi-table',        iconBg: 'bg-orange-100 text-orange-600' },
  { label: 'Total Menu',     value: data.value?.total_menu_items ?? 0, icon: 'bi bi-menu-button',  iconBg: 'bg-purple-100 text-purple-600' },
])

const filteredUsers = computed(() => {
  const q = search.value.toLowerCase()
  return (data.value?.users ?? []).filter(u =>
    u.name.toLowerCase().includes(q) || u.username.toLowerCase().includes(q)
  )
})

onMounted(async () => {
  try { data.value = await api.get<AdminData>('/api/dashboard/admin') }
  catch { data.value = { total_users: 0, total_warungs: 0, total_tables: 0, total_menu_items: 0, users: [], warungs: [] } }
  finally { loading.value = false }
})
</script>
