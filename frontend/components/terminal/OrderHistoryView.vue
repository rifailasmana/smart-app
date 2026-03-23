<template>
  <div class="w-full h-full flex flex-col p-8 bg-brand-content animate-fade-in overflow-hidden">
    <div class="flex items-center gap-4 mb-8">
      <button class="text-gray-400 hover:text-gray-900" @click="emit('back')">
        <i class="bi bi-arrow-left text-2xl" />
      </button>
      <h1 class="text-3xl font-black text-gray-900 tracking-tighter">History Hari Ini</h1>
    </div>

    <div class="flex-1 overflow-y-auto bg-white rounded-[2rem] border border-gray-100 shadow-sm">
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b border-gray-50">
            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Waktu</th>
            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Kode</th>
            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Meja</th>
            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Total</th>
            <th class="p-6 text-[10px] font-black text-gray-400 uppercase tracking-widest">Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="5" class="p-10 text-center text-gray-300 text-sm">Memuat…</td>
          </tr>
          <tr
            v-for="order in history"
            :key="order.id"
            class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors"
          >
            <td class="p-6 text-sm font-bold text-gray-600">{{ timeOf(order.created_at) }}</td>
            <td class="p-6 text-sm font-black text-gray-900">{{ order.code }}</td>
            <td class="p-6 text-sm font-bold text-gray-600">{{ order.table?.name ?? 'TA' }}</td>
            <td class="p-6 text-sm font-black text-orange-500">{{ formatRp(order.total) }}</td>
            <td class="p-6">
              <span
                class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                :class="order.stage === 'DONE' ? 'bg-green-100 text-green-600' : 'bg-gray-100 text-gray-400'"
              >
                {{ order.stage }}
              </span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Order {
  id: number; code: string; stage: string; total: number; created_at: string
  table: { name: string } | null
}

const emit = defineEmits<{ back: [] }>()
const api = useApi()
const history = ref<Order[]>([])
const loading = ref(true)

onMounted(async () => {
  try {
    history.value = await api.get<Order[]>('/api/terminal/orders/history')
  } finally {
    loading.value = false
  }
})

function timeOf(ts: string) {
  return new Date(ts).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}
function formatRp(val: number) {
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(val)
}
</script>
