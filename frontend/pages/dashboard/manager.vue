<template>
  <div>
    <div class="mb-8">
      <h1 class="text-3xl font-black text-gray-900 tracking-tight">Manager Dashboard</h1>
      <p class="text-gray-500 font-semibold mt-1">Operasional & performa harian</p>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
      <div v-for="kpi in kpis" :key="kpi.label" class="bg-white rounded-[1.5rem] p-6 border border-gray-100">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl mb-4" :class="kpi.iconBg">
          <i :class="kpi.icon" />
        </div>
        <div class="text-2xl font-black text-gray-900 mb-1">{{ kpi.value }}</div>
        <div class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ kpi.label }}</div>
      </div>
    </div>

    <!-- Today's shift & orders  -->
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
      <div class="bg-white rounded-[1.5rem] p-8 border border-gray-100">
        <h3 class="font-black text-gray-900 mb-6">Shift Aktif Hari Ini</h3>
        <div v-if="loading" class="space-y-3">
          <div v-for="n in 3" :key="n" class="h-14 bg-gray-100 rounded-xl animate-pulse" />
        </div>
        <div v-else-if="!shifts.length" class="text-center text-gray-400 py-8 font-semibold">Tidak ada shift hari ini</div>
        <div v-else class="space-y-3">
          <div v-for="s in shifts" :key="s.id" class="flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3">
            <div>
              <div class="font-bold text-gray-800">{{ s.name }}</div>
              <div class="text-xs text-gray-400">{{ s.time }}</div>
            </div>
            <span class="text-xs font-black px-2 py-1 rounded-full bg-green-100 text-green-700">{{ s.role }}</span>
          </div>
        </div>
      </div>

      <div class="bg-white rounded-[1.5rem] p-8 border border-gray-100">
        <h3 class="font-black text-gray-900 mb-6">Order Hari Ini</h3>
        <div class="grid grid-cols-3 gap-4">
          <div v-for="st in orderStats" :key="st.label" class="text-center rounded-2xl p-4" :class="st.bg">
            <div class="text-3xl font-black mb-1" :class="st.text">{{ st.value }}</div>
            <div class="text-[10px] font-black uppercase tracking-widest" :class="st.text">{{ st.label }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'dashboard', middleware: 'auth' })
useHead({ title: 'Manager Dashboard – Majar Signature' })

const api     = useApi()
const loading = ref(true)

interface ManagerData {
  revenue_today: number
  order_count: number
  table_turns: number
  avg_service_minutes: number
  shifts: { id: number; name: string; time: string; role: string }[]
  order_status: { pending: number; serving: number; done: number }
}

const data = ref<ManagerData | null>(null)

const kpis = computed(() => [
  { label: 'Pendapatan Hari Ini', value: 'Rp ' + (data.value?.revenue_today ?? 0).toLocaleString('id-ID'), icon: 'bi bi-cash-coin',    iconBg: 'bg-green-100 text-green-600' },
  { label: 'Total Order',         value: data.value?.order_count ?? 0,                                      icon: 'bi bi-receipt',      iconBg: 'bg-orange-100 text-orange-600' },
  { label: 'Table Turns',         value: data.value?.table_turns ?? 0,                                      icon: 'bi bi-table',        iconBg: 'bg-blue-100 text-blue-600' },
  { label: 'Avg Service (mnt)',   value: data.value?.avg_service_minutes ?? 0,                              icon: 'bi bi-stopwatch',    iconBg: 'bg-purple-100 text-purple-600' },
])

const shifts = computed(() => data.value?.shifts ?? [])

const orderStats = computed(() => {
  const s = data.value?.order_status ?? { pending: 0, serving: 0, done: 0 }
  return [
    { label: 'Pending',  value: s.pending, bg: 'bg-yellow-50', text: 'text-yellow-600' },
    { label: 'Serving',  value: s.serving, bg: 'bg-blue-50',   text: 'text-blue-600' },
    { label: 'Selesai',  value: s.done,    bg: 'bg-green-50',  text: 'text-green-600' },
  ]
})

onMounted(async () => {
  try { data.value = await api.get<ManagerData>('/api/dashboard/manager') }
  catch { data.value = { revenue_today: 0, order_count: 0, table_turns: 0, avg_service_minutes: 0, shifts: [], order_status: { pending: 0, serving: 0, done: 0 } } }
  finally { loading.value = false }
})
</script>
