<template>
  <div class="w-full h-full flex overflow-hidden">

    <!-- Sidebar -->
    <div class="w-24 bg-gray-900 flex flex-col border-r border-gray-800">
      <div class="p-6 border-b border-gray-800">
        <div class="w-full aspect-square rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-400 flex items-center justify-center shadow-lg">
          <span class="font-black text-2xl text-white">W</span>
        </div>
      </div>
      <div class="flex-1 py-4">
        <TerminalSidebarIcon icon="bi-ui-checks-grid" label="Pesanan" :active="view === 'ORDER_TYPE' || view === 'TABLE_SELECT' || view === 'MENU'" @click="view = 'ORDER_TYPE'" />
        <TerminalSidebarIcon icon="bi-list-check"     label="Status"  :active="view === 'ORDER_STATUS'"  @click="view = 'ORDER_STATUS'" />
        <TerminalSidebarIcon icon="bi-clock-history"  label="History" :active="view === 'ORDER_HISTORY'" @click="view = 'ORDER_HISTORY'" />
      </div>
    </div>

    <!-- Content -->
    <div class="flex-1 h-full overflow-hidden">

      <!-- Order type -->
      <div v-if="view === 'ORDER_TYPE'" class="w-full h-full flex flex-col items-center justify-center bg-brand-content animate-zoom-in">
        <h1 class="text-5xl font-black text-gray-900 tracking-tighter mb-2">Buat Pesanan</h1>
        <p class="text-xl text-gray-400 font-medium mb-16">Pilih jenis pesanan</p>
        <div class="flex gap-10 w-full max-w-4xl px-6">
          <TerminalOrderTypeCard icon="bi-shop"           title="Dine In"   subtitle="Makan di tempat" cta="Pilih Meja"      color="blue"   @click="selectOrderType('DINE_IN')" />
          <TerminalOrderTypeCard icon="bi-bag-heart-fill" title="Take Away" subtitle="Dibawa pulang"   cta="Langsung Pesan" color="green"  @click="selectOrderType('TAKE_AWAY')" />
        </div>
      </div>

      <!-- Table selection -->
      <div v-else-if="view === 'TABLE_SELECT'" class="w-full h-full flex bg-brand-content animate-slide-right">
        <div class="w-80 bg-white border-r border-gray-100 p-8 flex flex-col">
          <button class="flex items-center gap-2 text-gray-400 hover:text-gray-900 font-bold text-sm mb-10" @click="view = 'ORDER_TYPE'">
            <i class="bi bi-arrow-left" /> Kembali
          </button>
          <div class="flex items-center gap-3 mb-6">
            <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center font-black text-xs">2</div>
            <h2 class="text-2xl font-black text-gray-900 tracking-tight">Pilih Meja</h2>
          </div>
          <label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 block">Jumlah Tamu</label>
          <div class="flex items-center justify-between bg-gray-50 rounded-3xl p-4 border border-gray-100 mb-6">
            <button class="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center hover:bg-blue-500 hover:text-white transition-all" @click="guestCount = Math.max(1, guestCount - 1)">
              <i class="bi bi-dash-lg text-xl" />
            </button>
            <span class="text-5xl font-black text-gray-900 w-20 text-center tracking-tighter">{{ guestCount }}</span>
            <button class="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center hover:bg-blue-500 hover:text-white transition-all" @click="guestCount++">
              <i class="bi bi-plus-lg text-xl" />
            </button>
          </div>
          <div class="grid grid-cols-4 gap-2 mb-8">
            <button v-for="n in [1,2,3,4,5,6,8,10]" :key="n"
              class="py-3 rounded-xl font-black transition-all"
              :class="guestCount === n ? 'bg-blue-500 text-white shadow-lg' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'"
              @click="guestCount = n"
            >{{ n }}</button>
          </div>
        </div>
        <div class="flex-1 p-10 flex flex-col overflow-hidden">
          <div class="flex-1 overflow-y-auto pr-4 grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 content-start">
            <TerminalTableCard
              v-for="t in tables" :key="t.id"
              :table="t" :active="selectedTable?.id === t.id" :guest-count="guestCount"
              @select="selectedTable = $event"
            />
          </div>
          <div class="mt-8 flex justify-end">
            <button
              :disabled="!selectedTable"
              class="px-12 py-5 bg-gradient-to-r from-blue-500 to-cyan-400 text-white rounded-[2rem] font-black text-lg shadow-xl disabled:opacity-30 transition-all active:scale-95"
              @click="view = 'MENU'"
            >
              Lanjut ke Menu <i class="bi bi-arrow-right ml-2" />
            </button>
          </div>
        </div>
      </div>

      <!-- Menu -->
      <TerminalMenuView
        v-else-if="view === 'MENU'"
        :menu-items="menuItems"
        :categories="categories"
        :order-type="orderType"
        :selected-table="selectedTable"
        :guest-count="guestCount"
        @back="handleMenuBack"
        @submitted="view = 'ORDER_STATUS'"
      />

      <!-- Order status (waiter mode: submit to cashier actions) -->
      <TerminalOrderStatusView v-else-if="view === 'ORDER_STATUS'" role="waiter" @back="view = 'ORDER_TYPE'" />

      <!-- History -->
      <TerminalOrderHistoryView v-else-if="view === 'ORDER_HISTORY'" @back="view = 'ORDER_TYPE'" />
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'terminal', middleware: 'auth' })
useHead({ title: 'Waiter Terminal – Majar Signature' })

const api = useApi()

type View = 'ORDER_TYPE' | 'TABLE_SELECT' | 'MENU' | 'ORDER_STATUS' | 'ORDER_HISTORY'

const view          = ref<View>('ORDER_TYPE')
const orderType     = ref<'DINE_IN' | 'TAKE_AWAY'>('DINE_IN')
const guestCount    = ref(2)
const selectedTable = ref<{ id: number; name: string } | null>(null)

interface Table    { id: number; name: string; capacity: number; status: 'available'|'occupied'|'reserved' }
interface MenuItem { id: number; name: string; category: string; price: number; image_url?: string }

const tables     = ref<Table[]>([])
const menuItems  = ref<MenuItem[]>([])
const categories = ref<string[]>([])

onMounted(async () => {
  const [t, m] = await Promise.all([
    api.get<Table[]>('/api/terminal/tables'),
    api.get<{ items: MenuItem[]; categories: string[] }>('/api/terminal/menu-items'),
  ])
  tables.value     = t
  menuItems.value  = m.items
  categories.value = m.categories
})

function selectOrderType(type: 'DINE_IN' | 'TAKE_AWAY') {
  orderType.value = type
  view.value      = type === 'DINE_IN' ? 'TABLE_SELECT' : 'MENU'
  if (type === 'TAKE_AWAY') selectedTable.value = null
}

function handleMenuBack() {
  view.value = orderType.value === 'TAKE_AWAY' ? 'ORDER_TYPE' : 'TABLE_SELECT'
}

provide('terminalRoleLabel', 'WAITER')
</script>
