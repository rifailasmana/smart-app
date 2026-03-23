<template>
  <div class="w-full h-full flex flex-col p-8 bg-brand-content animate-fade-in overflow-hidden">
    <div class="flex items-center gap-4 mb-8">
      <button class="text-gray-400 hover:text-gray-900" @click="emit('back')">
        <i class="bi bi-arrow-left text-2xl" />
      </button>
      <h1 class="text-3xl font-black text-gray-900 tracking-tighter">Status Pesanan</h1>
      <button class="ml-auto text-xs font-bold text-gray-400 hover:text-gray-700" @click="fetchOrders">
        <i class="bi bi-arrow-clockwise mr-1" />Muat Ulang
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex-1 flex items-center justify-center opacity-30">
      <i class="bi bi-hourglass-split text-5xl animate-spin" />
    </div>

    <!-- Empty -->
    <div v-else-if="!orders.length" class="flex-1 flex flex-col items-center justify-center opacity-20">
      <i class="bi bi-inbox text-6xl" />
      <p class="font-bold uppercase tracking-widest mt-4">Tidak ada pesanan aktif</p>
    </div>

    <!-- Order cards -->
    <div v-else class="flex-1 overflow-y-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 content-start pr-2">
      <div
        v-for="order in orders"
        :key="order.id"
        class="bg-white rounded-[2rem] p-6 shadow-sm border border-gray-100 flex flex-col"
      >
        <div class="flex justify-between items-start mb-4">
          <div>
            <h4 class="font-black text-gray-900">Meja {{ order.table?.name ?? 'TA' }}</h4>
            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">#{{ order.code }}</p>
          </div>
          <span
            class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
            :class="stageBadge(order.stage)"
          >
            {{ order.stage.replace(/_/g, ' ') }}
          </span>
        </div>

        <div class="flex-1 space-y-2 mb-6">
          <div v-for="(item, idx) in order.items" :key="idx" class="text-sm">
            <span class="text-gray-600 font-medium">{{ item.qty }}x {{ item.menu_name }}</span>
          </div>
        </div>

        <div class="pt-4 border-t border-gray-50 mt-auto space-y-3">
          <div class="flex justify-between items-center">
            <span class="text-xs font-bold text-gray-400 uppercase">Total</span>
            <span class="font-black text-gray-900">{{ formatRp(order.total) }}</span>
          </div>

          <!-- Kasir actions -->
          <button
            v-if="role === 'kasir' && order.stage === 'WAITING_CASHIER'"
            class="w-full py-3 bg-orange-500 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg active:scale-95 transition-all"
            @click="handleApprove(order.id)"
          >
            Approve
          </button>
          <button
            v-if="role === 'kasir' && order.stage === 'SERVED'"
            class="w-full py-3 bg-green-500 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg active:scale-95 transition-all"
            @click="handleFinalize(order)"
          >
            Payment
          </button>

          <!-- Waiter: submit to cashier -->
          <button
            v-if="role === 'waiter' && order.stage === 'DRAFT'"
            class="w-full py-3 bg-orange-500 text-white rounded-xl font-black text-xs uppercase tracking-widest shadow-lg active:scale-95 transition-all"
            @click="handleSubmitToCashier(order.id)"
          >
            Kirim ke Kasir
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface OrderItem { qty: number; menu_name: string }
interface Order {
  id: number; code: string; stage: string; total: number
  table: { name: string } | null
  items: OrderItem[]
}

const props = defineProps<{ role: 'kasir' | 'waiter' | 'kitchen' }>()
const emit = defineEmits<{ back: [] }>()

const api = useApi()
const orders = ref<Order[]>([])
const loading = ref(true)

async function fetchOrders() {
  try {
    const data = await api.get<Order[]>('/api/terminal/orders', { role: props.role })
    orders.value = data
  } catch { /* silent */ } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchOrders()
  const timer = setInterval(fetchOrders, 5000)
  onUnmounted(() => clearInterval(timer))
})

async function handleApprove(id: number) {
  if (!confirm('Approve pesanan ini?')) return
  await api.post(`/api/terminal/orders/${id}/approve`)
  fetchOrders()
}

async function handleFinalize(order: Order) {
  const amount = prompt(`Total: ${formatRp(order.total)}\nMasukkan jumlah bayar:`, String(order.total))
  if (!amount) return
  await api.post(`/api/terminal/orders/${order.id}/finalize-payment`, {
    payment_method: 'cash', amount_paid: Number(amount),
  })
  fetchOrders()
}

async function handleSubmitToCashier(id: number) {
  await api.post(`/api/terminal/orders/${id}/submit-to-cashier`)
  fetchOrders()
}

const BADGE: Record<string, string> = {
  WAITING_CASHIER:  'bg-yellow-100 text-yellow-600',
  READY_FOR_KITCHEN:'bg-orange-100 text-orange-600',
  COOKING:          'bg-blue-100 text-blue-600',
  READY:            'bg-green-100 text-green-600',
  SERVED:           'bg-purple-100 text-purple-600',
  DRAFT:            'bg-gray-100 text-gray-500',
}
function stageBadge(stage: string) { return BADGE[stage] ?? 'bg-gray-100 text-gray-600' }
function formatRp(val: number) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(val) }
</script>
