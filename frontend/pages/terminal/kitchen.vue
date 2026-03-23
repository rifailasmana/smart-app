<template>
  <div class="w-full h-full overflow-auto bg-brand-content p-6">
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h2 class="text-3xl font-black text-gray-900 tracking-tight">Kitchen Display</h2>
        <p class="text-sm text-gray-400 font-semibold mt-1">Antrian masak real-time</p>
      </div>
      <div class="flex items-center gap-4">
        <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Auto-refresh 5s</span>
        <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse" />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-32">
      <div class="text-center">
        <div class="w-12 h-12 border-4 border-orange-500 border-t-transparent rounded-full animate-spin mx-auto mb-4" />
        <p class="text-gray-500 font-semibold">Memuat data dapur…</p>
      </div>
    </div>

    <!-- Kanban columns -->
    <div v-else class="grid grid-cols-3 gap-6 h-full">
      <!-- Ready for Kitchen -->
      <div class="flex flex-col gap-4">
        <div class="flex items-center gap-3 px-1">
          <div class="w-3 h-3 rounded-full bg-yellow-400" />
          <h3 class="font-black text-gray-700 uppercase text-xs tracking-widest">Antrian Masak</h3>
          <span class="bg-yellow-100 text-yellow-700 font-black text-xs rounded-full px-2.5 py-0.5">{{ queued.length }}</span>
        </div>
        <div class="flex-1 overflow-y-auto space-y-4 pr-1">
          <TerminalKitchenTicket
            v-for="o in queued" :key="o.id" :order="o"
            @update-status="updateStatus"
          />
          <div v-if="!queued.length" class="text-center text-gray-300 py-10 font-semibold">Tidak ada antrian</div>
        </div>
      </div>

      <!-- Cooking -->
      <div class="flex flex-col gap-4">
        <div class="flex items-center gap-3 px-1">
          <div class="w-3 h-3 rounded-full bg-orange-500" />
          <h3 class="font-black text-gray-700 uppercase text-xs tracking-widest">Sedang Masak</h3>
          <span class="bg-orange-100 text-orange-700 font-black text-xs rounded-full px-2.5 py-0.5">{{ cooking.length }}</span>
        </div>
        <div class="flex-1 overflow-y-auto space-y-4 pr-1">
          <TerminalKitchenTicket
            v-for="o in cooking" :key="o.id" :order="o"
            @update-status="updateStatus"
          />
          <div v-if="!cooking.length" class="text-center text-gray-300 py-10 font-semibold">Sedang sepi…</div>
        </div>
      </div>

      <!-- Ready to Serve -->
      <div class="flex flex-col gap-4">
        <div class="flex items-center gap-3 px-1">
          <div class="w-3 h-3 rounded-full bg-green-500" />
          <h3 class="font-black text-gray-700 uppercase text-xs tracking-widest">Siap Disajikan</h3>
          <span class="bg-green-100 text-green-700 font-black text-xs rounded-full px-2.5 py-0.5">{{ ready.length }}</span>
        </div>
        <div class="flex-1 overflow-y-auto space-y-4 pr-1">
          <TerminalKitchenTicket
            v-for="o in ready" :key="o.id" :order="o"
            @update-status="updateStatus"
          />
          <div v-if="!ready.length" class="text-center text-gray-300 py-10 font-semibold">–</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'terminal', middleware: 'auth' })
useHead({ title: 'Kitchen Display – Majar Signature' })

const api = useApi()

interface Order {
  id: number
  order_number: string
  table?: { name: string }
  status: string
  created_at: string
  items: { name: string; qty: number; note?: string }[]
}

const orders  = ref<Order[]>([])
const loading = ref(true)

const queued  = computed(() => orders.value.filter(o => o.status === 'READY_FOR_KITCHEN'))
const cooking = computed(() => orders.value.filter(o => o.status === 'COOKING'))
const ready   = computed(() => orders.value.filter(o => o.status === 'READY'))

async function fetchOrders() {
  try {
    const data = await api.get<Order[]>('/api/terminal/orders?role=kitchen')
    orders.value = data
  } catch { /* silent */ } finally {
    loading.value = false
  }
}

async function updateStatus(id: number, status: string) {
  await api.post(`/api/terminal/orders/${id}/kitchen-status`, { status })
  await fetchOrders()
}

onMounted(fetchOrders)

const interval = setInterval(fetchOrders, 5000)
onUnmounted(() => clearInterval(interval))

provide('terminalRoleLabel', 'KITCHEN')
</script>
