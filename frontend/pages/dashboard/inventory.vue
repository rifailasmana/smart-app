<template>
  <div>
    <div class="mb-8">
      <h1 class="text-3xl font-black text-gray-900 tracking-tight">Inventory Dashboard</h1>
      <p class="text-gray-500 font-semibold mt-1">Stok bahan baku & pengadaan</p>
    </div>

    <!-- Alerts bar -->
    <div v-if="lowStock.length" class="mb-6 p-5 bg-red-50 border border-red-200 rounded-2xl flex items-start gap-4">
      <i class="bi bi-exclamation-triangle-fill text-red-500 text-xl mt-0.5" />
      <div>
        <h4 class="font-black text-red-700 mb-1">Stok Menipis ({{ lowStock.length }} item)</h4>
        <p class="text-sm text-red-600">{{ lowStock.map(i => i.name).join(', ') }}</p>
      </div>
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

    <!-- Ingredients table -->
    <div class="bg-white rounded-[1.5rem] p-8 border border-gray-100">
      <div class="flex items-center justify-between mb-6">
        <h3 class="font-black text-gray-900">Stok Bahan Baku</h3>
        <input
          v-model="search" type="text" placeholder="Cari bahan…"
          class="px-4 py-2 border border-gray-200 rounded-xl text-sm w-56 focus:outline-none focus:border-orange-400"
        />
      </div>

      <div v-if="loading" class="space-y-3">
        <div v-for="n in 8" :key="n" class="h-12 bg-gray-100 rounded-xl animate-pulse" />
      </div>
      <table v-else class="w-full text-sm">
        <thead>
          <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">
            <th class="text-left pb-4">Nama Bahan</th>
            <th class="text-left pb-4">Satuan</th>
            <th class="text-right pb-4">Stock</th>
            <th class="text-right pb-4">Min. Stock</th>
            <th class="text-right pb-4">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <tr v-for="item in filteredItems" :key="item.id" class="hover:bg-gray-50">
            <td class="py-3 font-bold text-gray-900">{{ item.name }}</td>
            <td class="py-3 text-gray-500">{{ item.unit }}</td>
            <td class="py-3 text-right font-bold" :class="item.stock <= item.min_stock ? 'text-red-600' : 'text-gray-900'">{{ item.stock }}</td>
            <td class="py-3 text-right text-gray-400">{{ item.min_stock }}</td>
            <td class="py-3 text-right">
              <span class="px-2 py-1 rounded-full text-[10px] font-black"
                :class="item.stock <= 0 ? 'bg-red-100 text-red-700' : item.stock <= item.min_stock ? 'bg-yellow-100 text-yellow-700' : 'bg-green-100 text-green-700'"
              >{{ item.stock <= 0 ? 'Habis' : item.stock <= item.min_stock ? 'Menipis' : 'Aman' }}</span>
            </td>
          </tr>
          <tr v-if="!filteredItems.length">
            <td colspan="5" class="py-8 text-center text-gray-400 font-semibold">Tidak ada data</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'dashboard', middleware: 'auth' })
useHead({ title: 'Inventory Dashboard – Majar Signature' })

const api     = useApi()
const loading = ref(true)
const search  = ref('')

interface InventoryData {
  total_items: number
  low_stock_count: number
  out_of_stock_count: number
  pending_restock: number
  ingredients: { id: number; name: string; unit: string; stock: number; min_stock: number }[]
}

const data = ref<InventoryData | null>(null)

const kpis = computed(() => [
  { label: 'Total Item',      value: data.value?.total_items ?? 0,       icon: 'bi bi-boxes',              iconBg: 'bg-blue-100 text-blue-600' },
  { label: 'Stok Menipis',   value: data.value?.low_stock_count ?? 0,   icon: 'bi bi-exclamation-circle', iconBg: 'bg-yellow-100 text-yellow-600' },
  { label: 'Habis',          value: data.value?.out_of_stock_count ?? 0, icon: 'bi bi-x-circle',           iconBg: 'bg-red-100 text-red-600' },
  { label: 'Restock Pending',value: data.value?.pending_restock ?? 0,   icon: 'bi bi-truck',              iconBg: 'bg-green-100 text-green-600' },
])

const lowStock = computed(() => (data.value?.ingredients ?? []).filter(i => i.stock <= i.min_stock))

const filteredItems = computed(() => {
  const q = search.value.toLowerCase()
  return (data.value?.ingredients ?? []).filter(i => i.name.toLowerCase().includes(q))
})

onMounted(async () => {
  try { data.value = await api.get<InventoryData>('/api/dashboard/inventory') }
  catch { data.value = { total_items: 0, low_stock_count: 0, out_of_stock_count: 0, pending_restock: 0, ingredients: [] } }
  finally { loading.value = false }
})
</script>
