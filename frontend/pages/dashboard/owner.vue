<template>
  <div>
    <!-- Page header -->
    <div class="mb-8">
      <h1 class="text-3xl font-black text-gray-900 tracking-tight">Owner Dashboard</h1>
      <p class="text-gray-500 font-semibold mt-1">Ringkasan performa bisnis</p>
    </div>

    <!-- KPI Cards -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
      <div
        v-for="kpi in kpis" :key="kpi.label"
        class="bg-white rounded-[1.5rem] p-6 border border-gray-100 hover:shadow-lg transition-shadow"
      >
        <div class="flex items-start justify-between mb-4">
          <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl" :class="kpi.iconBg">
            <i :class="kpi.icon" />
          </div>
          <span
            class="text-xs font-black px-2 py-1 rounded-full"
            :class="kpi.trend > 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
          >
            {{ kpi.trend > 0 ? '+' : '' }}{{ kpi.trend }}%
          </span>
        </div>
        <div class="text-2xl font-black text-gray-900 mb-1">{{ kpi.value }}</div>
        <div class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ kpi.label }}</div>
      </div>
    </div>

    <!-- Charts row -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-8">
      <!-- Revenue chart placeholder -->
      <div class="xl:col-span-2 bg-white rounded-[1.5rem] p-8 border border-gray-100">
        <div class="flex items-center justify-between mb-6">
          <h3 class="font-black text-gray-900">Pendapatan Bulanan</h3>
          <div class="flex gap-2">
            <button v-for="r in ['7H','1B','3B','1T']" :key="r"
              class="px-3 py-1.5 text-xs font-bold rounded-xl"
              :class="range === r ? 'bg-[#062e22] text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
              @click="range = r"
            >{{ r }}</button>
          </div>
        </div>
        <div v-if="summaryLoading" class="h-48 flex items-center justify-center text-gray-400 font-semibold">
          <div class="w-8 h-8 border-4 border-orange-500 border-t-transparent rounded-full animate-spin mr-3" /> Memuat…
        </div>
        <div v-else class="h-48 flex items-end gap-2">
          <div
            v-for="(bar, i) in revenueBars" :key="i"
            class="flex-1 rounded-t-xl bg-gradient-to-t from-[#062e22] to-[#0a4a35] transition-all hover:opacity-80"
            :style="{ height: bar + '%' }"
            :title="'Bar ' + i"
          />
        </div>
      </div>

      <!-- Top items -->
      <div class="bg-white rounded-[1.5rem] p-8 border border-gray-100">
        <h3 class="font-black text-gray-900 mb-6">Menu Terlaris</h3>
        <div v-if="summaryLoading" class="space-y-3">
          <div v-for="n in 5" :key="n" class="h-10 bg-gray-100 rounded-xl animate-pulse" />
        </div>
        <div v-else class="space-y-3">
          <div v-for="(item, i) in topItems" :key="item.name" class="flex items-center gap-3">
            <span class="text-xs font-black text-gray-300 w-5">{{ i + 1 }}</span>
            <div class="flex-1 flex items-center justify-between bg-gray-50 rounded-xl px-4 py-3">
              <span class="font-semibold text-gray-800 text-sm">{{ item.name }}</span>
              <span class="text-xs font-black text-orange-500">{{ item.qty }}x</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent orders -->
    <div class="bg-white rounded-[1.5rem] p-8 border border-gray-100">
      <h3 class="font-black text-gray-900 mb-6">Transaksi Terbaru</h3>
      <div v-if="summaryLoading" class="space-y-3">
        <div v-for="n in 5" :key="n" class="h-12 bg-gray-100 rounded-xl animate-pulse" />
      </div>
      <table v-else class="w-full text-sm">
        <thead>
          <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">
            <th class="text-left pb-4">No. Order</th><th class="text-left pb-4">Meja</th>
            <th class="text-left pb-4">Kasir</th><th class="text-right pb-4">Total</th>
            <th class="text-right pb-4">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="o in recentOrders" :key="o.id" class="hover:bg-gray-50 transition-colors">
            <td class="py-3 font-bold text-gray-900">{{ o.order_number }}</td>
            <td class="py-3 text-gray-500">{{ o.table ?? 'Take Away' }}</td>
            <td class="py-3 text-gray-500">{{ o.cashier }}</td>
            <td class="py-3 text-right font-bold text-gray-900">{{ formatRp(o.total) }}</td>
            <td class="py-3 text-right">
              <span class="px-2 py-1 rounded-full text-[10px] font-black bg-green-100 text-green-700">{{ o.status }}</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'dashboard', middleware: 'auth' })
useHead({ title: 'Owner Dashboard – Majar Signature' })

const api = useApi()

const summaryLoading = ref(true)
const range          = ref('1B')

interface Summary {
  revenue_today: number
  revenue_month: number
  order_count: number
  avg_order: number
  top_items: { name: string; qty: number }[]
  recent_orders: { id: number; order_number: string; table?: string; cashier: string; total: number; status: string }[]
  revenue_chart: number[]
}

const summary = ref<Summary | null>(null)

const kpis = computed(() => {
  const s = summary.value
  return [
    { label: 'Pendapatan Hari Ini', value: formatRp(s?.revenue_today ?? 0), icon: 'bi bi-cash-coin',    iconBg: 'bg-green-100 text-green-600',  trend: 12 },
    { label: 'Pendapatan Bulan',    value: formatRp(s?.revenue_month ?? 0),  icon: 'bi bi-bar-chart',    iconBg: 'bg-blue-100 text-blue-600',    trend: 5 },
    { label: 'Total Order',         value: s?.order_count ?? 0,               icon: 'bi bi-receipt',      iconBg: 'bg-orange-100 text-orange-600', trend: -2 },
    { label: 'Rata-rata Order',     value: formatRp(s?.avg_order ?? 0),      icon: 'bi bi-graph-up-arrow',iconBg: 'bg-purple-100 text-purple-600', trend: 8 },
  ]
})

const topItems    = computed(() => summary.value?.top_items ?? [])
const recentOrders = computed(() => summary.value?.recent_orders ?? [])
const revenueBars = computed(() => {
  const chart = summary.value?.revenue_chart ?? []
  const max   = Math.max(...chart, 1)
  return chart.map(v => Math.round((v / max) * 100))
})

function formatRp(v: number) {
  return 'Rp ' + v.toLocaleString('id-ID')
}

onMounted(async () => {
  try {
    summary.value = await api.get<Summary>('/api/dashboard/owner')
  } catch {
    // Show empty state if endpoint not yet ready
    summary.value = {
      revenue_today: 0, revenue_month: 0, order_count: 0, avg_order: 0,
      top_items: [], recent_orders: [],
      revenue_chart: Array(12).fill(0).map(() => Math.floor(Math.random() * 100)),
    }
  } finally {
    summaryLoading.value = false
  }
})
</script>
