<template>
	<div class="w-full h-full flex overflow-hidden">

		<!-- ── Fixed Sidebar ──────────────────────────────────────────── -->
		<div class="w-24 bg-gray-900 flex flex-col border-r border-gray-800">
			<div class="p-6 border-b border-gray-800">
				<div
					class="w-full aspect-square rounded-2xl bg-gradient-to-br from-orange-500 to-yellow-400 flex items-center justify-center shadow-lg shadow-orange-500/30">
					<span class="font-black text-2xl text-white">S</span>
				</div>
			</div>
			<div class="flex-1 py-4">
				<TerminalSidebarIcon icon="bi-cash-stack" label="Kasir"
					:active="view === 'ORDER_TYPE' || view === 'TABLE_SELECT' || view === 'MENU'"
					@click="view = 'ORDER_TYPE'" />
				<TerminalSidebarIcon icon="bi-list-check" label="Status" :active="view === 'ORDER_STATUS'"
					@click="view = 'ORDER_STATUS'" />
				<TerminalSidebarIcon icon="bi-clock-history" label="History" :active="view === 'ORDER_HISTORY'"
					@click="view = 'ORDER_HISTORY'" />
			</div>
			<div class="py-4 border-t border-gray-800">
				<TerminalSidebarIcon icon="bi-gear-fill" label="Setting" />
			</div>
		</div>

		<!-- ── Content Area ───────────────────────────────────────────── -->
		<div class="flex-1 h-full overflow-hidden">

			<!-- Order type selection -->
			<div v-if="view === 'ORDER_TYPE'"
				class="w-full h-full flex flex-col items-center justify-center bg-brand-content animate-zoom-in">
				<h1 class="text-5xl font-black text-gray-900 tracking-tighter mb-2">Selamat Datang!</h1>
				<p class="text-xl text-gray-400 font-medium mb-16">Pilih jenis pesanan Anda untuk memulai</p>
				<div class="flex gap-10 w-full max-w-4xl px-6">
					<TerminalOrderTypeCard icon="bi-shop" title="Dine In" subtitle="Makan di tempat" cta="Pilih Meja"
						color="orange" @click="selectOrderType('DINE_IN')" />
					<TerminalOrderTypeCard icon="bi-bag-heart-fill" title="Take Away" subtitle="Dibawa pulang"
						cta="Langsung Pesan" color="green" @click="selectOrderType('TAKE_AWAY')" />
				</div>
			</div>

			<!-- Table selection -->
			<div v-else-if="view === 'TABLE_SELECT'" class="w-full h-full flex bg-brand-content animate-slide-right">
				<!-- Left: guest count selector -->
				<div class="w-80 bg-white border-r border-gray-100 p-8 flex flex-col">
					<button class="flex items-center gap-2 text-gray-400 hover:text-gray-900 font-bold text-sm mb-10"
						@click="view = 'ORDER_TYPE'">
						<i class="bi bi-arrow-left" /> Kembali
					</button>

					<div class="flex items-center gap-3 mb-6">
						<div
							class="w-8 h-8 rounded-full bg-orange-500 text-white flex items-center justify-center font-black text-xs">
							2</div>
						<h2 class="text-2xl font-black text-gray-900 tracking-tight">Pilih Meja</h2>
					</div>

					<!-- Guest stepper -->
					<label class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mb-3 block">Jumlah
						Tamu</label>
					<div
						class="flex items-center justify-between bg-gray-50 rounded-3xl p-4 border border-gray-100 mb-6">
						<button
							class="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center hover:bg-orange-500 hover:text-white transition-all"
							@click="guestCount = Math.max(1, guestCount - 1)">
							<i class="bi bi-dash-lg text-xl" />
						</button>
						<span class="text-5xl font-black text-gray-900 w-20 text-center tracking-tighter">{{ guestCount
						}}</span>
						<button
							class="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center hover:bg-orange-500 hover:text-white transition-all"
							@click="guestCount++">
							<i class="bi bi-plus-lg text-xl" />
						</button>
					</div>

					<div class="grid grid-cols-4 gap-2 mb-8">
						<button v-for="n in [1, 2, 3, 4, 5, 6, 8, 10]" :key="n"
							class="py-3 rounded-xl font-black transition-all"
							:class="guestCount === n ? 'bg-orange-500 text-white shadow-lg' : 'bg-gray-100 text-gray-400 hover:bg-gray-200'"
							@click="guestCount = n">{{ n }}</button>
					</div>

					<!-- Join table toggle -->
					<button
						class="w-full rounded-2xl px-4 py-3 text-sm font-black uppercase tracking-widest transition-all mb-4"
						:class="joinMode ? 'bg-[#062e22] text-white shadow-lg' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'"
						@click="toggleJoinMode">
						<i class="bi bi-diagram-3-fill mr-2" />
						<span v-if="joinMode">Mode Gabung Meja Aktif</span>
						<span v-else>Aktifkan Gabung Meja</span>
					</button>

					<div v-if="selectedTables.length"
						class="mb-6 p-4 rounded-2xl bg-orange-50 border border-orange-100">
						<div class="text-[10px] font-black text-orange-600 uppercase tracking-widest mb-2">Meja Dipilih
						</div>
						<div class="flex flex-wrap gap-2 mb-2">
							<span v-for="t in selectedTables" :key="t.id"
								class="px-3 py-1 rounded-full bg-white border border-orange-200 text-orange-700 text-xs font-bold">
								{{ t.name }}
							</span>
						</div>
						<div class="text-xs font-bold"
							:class="hasEnoughCapacity ? 'text-green-600' : 'text-orange-600'">
							Kapasitas total {{ selectedCapacity }} / {{ guestCount }} tamu
						</div>
					</div>

					<!-- Legend -->
					<div class="mt-auto space-y-3 pt-6 border-t border-gray-50">
						<div
							class="flex items-center gap-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
							<div class="w-3 h-3 rounded-full bg-green-500"></div> Tersedia
						</div>
						<div
							class="flex items-center gap-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
							<div class="w-3 h-3 rounded-full bg-gray-300"></div> Terisi
						</div>
						<div
							class="flex items-center gap-3 text-[10px] font-bold text-gray-400 uppercase tracking-widest">
							<div class="w-3 h-3 rounded-full bg-orange-500"></div> Reservasi
						</div>
					</div>
				</div>

				<!-- Right: table grid -->
				<div class="flex-1 p-10 flex flex-col overflow-hidden">
					<div class="flex justify-between items-center mb-8">
						<h3 class="text-xl font-black text-gray-900 tracking-tight">
							{{ joinMode ? 'Pilih meja untuk digabung' : 'Tersedia untuk ' + guestCount + '+ tamu' }}
						</h3>
						<div
							class="bg-white px-4 py-2 rounded-full border border-gray-100 text-[10px] font-black text-gray-500 uppercase tracking-widest">
							{{ filteredTables.length }}
							tersedia
						</div>
					</div>
					<div
						class="flex-1 overflow-y-auto pr-4 grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 content-start">
						<TerminalTableCard v-for="t in filteredTables" :key="t.id" :table="t"
							:active="selectedTables.some(s => s.id === t.id)" :guest-count="guestCount"
							:allow-any-available="joinMode" @select="toggleTableSelection" />
						<div v-if="filteredTables.length === 0"
							class="col-span-full text-center py-16 text-gray-400 font-semibold">
							Tidak ada meja yang memenuhi kapasitas {{ guestCount }} tamu
						</div>
					</div>
					<div class="mt-8 flex justify-end">
						<button :disabled="selectedTables.length === 0 || !hasEnoughCapacity"
							class="px-12 py-5 bg-gradient-to-r from-orange-500 to-yellow-400 text-white rounded-[2rem] font-black text-lg shadow-xl disabled:opacity-30 transition-all active:scale-95"
							@click="view = 'MENU'">
							Lanjut ke Menu <i class="bi bi-arrow-right ml-2" />
						</button>
					</div>
				</div>
			</div>

			<!-- Menu + Cart -->
			<TerminalMenuView v-else-if="view === 'MENU'" :menu-items="menuItems" :categories="categories"
				:order-type="orderType" :selected-table="selectedTable" :selected-tables="selectedTables"
				:guest-count="guestCount" @back="handleMenuBack" @submitted="view = 'ORDER_STATUS'" />

			<!-- Order status -->
			<TerminalOrderStatusView v-else-if="view === 'ORDER_STATUS'" role="kasir" @back="view = 'ORDER_TYPE'" />

			<!-- Order history -->
			<TerminalOrderHistoryView v-else-if="view === 'ORDER_HISTORY'" @back="view = 'ORDER_TYPE'" />
		</div>
	</div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'terminal', middleware: 'auth' })
useHead({ title: 'Kasir Terminal – Majar Signature' })

const api = useApi()

type View = 'ORDER_TYPE' | 'TABLE_SELECT' | 'MENU' | 'ORDER_STATUS' | 'ORDER_HISTORY'

const view = ref<View>('ORDER_TYPE')
const orderType = ref<'DINE_IN' | 'TAKE_AWAY'>('DINE_IN')
const guestCount = ref(2)
const joinMode = ref(false)

interface Table { id: number; name: string; capacity: number; status: 'available' | 'occupied' | 'reserved' }
interface MenuItem { id: number; name: string; category: string; price: number; image_url?: string }

const tables = ref<Table[]>([])
const menuItems = ref<MenuItem[]>([])
const categories = ref<string[]>([])
const selectedTables = ref<Table[]>([])

const selectedTable = computed(() => selectedTables.value[0] ?? null)
const selectedCapacity = computed(() => selectedTables.value.reduce((sum, t) => sum + t.capacity, 0))
const hasEnoughCapacity = computed(() =>
	orderType.value === 'TAKE_AWAY' || selectedCapacity.value >= guestCount.value
)
const filteredTables = computed(() =>
	tables.value.filter(t => t.status === 'available' && t.capacity >= guestCount.value)
)

watch(guestCount, () => {
	const allowedIds = new Set(filteredTables.value.map(t => t.id))
	selectedTables.value = selectedTables.value.filter(t => allowedIds.has(t.id))
})

onMounted(async () => {
	const [tablesData, menuData] = await Promise.all([
		api.get<Table[]>('/api/terminal/tables'),
		api.get<{ items: MenuItem[]; categories: string[] }>('/api/terminal/menu-items'),
	])
	tables.value = tablesData
	menuItems.value = menuData.items
	categories.value = menuData.categories
})

function selectOrderType(type: 'DINE_IN' | 'TAKE_AWAY') {
	orderType.value = type
	if (type === 'DINE_IN') {
		selectedTables.value = []
		view.value = 'TABLE_SELECT'
	} else {
		selectedTables.value = []
		view.value = 'MENU'
	}
}

function toggleJoinMode() {
	joinMode.value = !joinMode.value
	if (!joinMode.value && selectedTables.value.length > 1) {
		selectedTables.value = [selectedTables.value[0]]
	}
}

function toggleTableSelection(table: Table) {
	if (!joinMode.value) {
		selectedTables.value = [table]
		return
	}
	const exists = selectedTables.value.some(t => t.id === table.id)
	if (exists) {
		selectedTables.value = selectedTables.value.filter(t => t.id !== table.id)
	} else {
		selectedTables.value = [...selectedTables.value, table]
	}
}

function handleMenuBack() {
	if (orderType.value === 'TAKE_AWAY') view.value = 'ORDER_TYPE'
	else view.value = 'TABLE_SELECT'
}

// Provide roleLabel to the terminal layout
provide('terminalRoleLabel', 'KASIR')
</script>
