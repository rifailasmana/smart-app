<template>
	<!-- Full-height menu + cart layout -->
	<div class="w-full h-full flex bg-brand-content animate-slide-right">

		<!-- ══ Menu area ══════════════════════════════════════════════ -->
		<div class="flex-1 flex flex-col overflow-hidden p-8">

			<!-- Breadcrumb / state chips -->
			<div class="flex items-center gap-6 mb-8 flex-wrap">
				<button class="flex items-center gap-2 text-gray-400 hover:text-gray-900 font-bold text-sm"
					@click="emit('back')">
					<i class="bi bi-arrow-left" /> Kembali
				</button>
				<div class="flex gap-2 flex-wrap">
					<span
						class="bg-green-100 text-green-600 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest">
						<i class="bi bi-check-circle-fill mr-1" />Tipe: {{ orderTypeLabel }}
					</span>
					<span v-if="selectedTable"
						class="bg-orange-100 text-orange-600 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest">
						<i class="bi bi-check-circle-fill mr-1" />Meja: {{ selectedTable.name }}
					</span>
					<span v-if="selectedTablesLabel"
						class="bg-orange-100 text-orange-600 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest">
						<i class="bi bi-diagram-3-fill mr-1" />Gabung: {{ selectedTablesLabel }}
					</span>
					<span
						class="bg-gray-200 text-gray-600 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest">
						<i class="bi bi-people-fill mr-1" />{{ guestCount }} Tamu
					</span>
				</div>
			</div>

			<!-- Category tabs -->
			<div class="flex gap-3 mb-8 overflow-x-auto pb-1 no-scrollbar">
				<button v-for="cat in ['Semua', ...categories]" :key="cat"
					class="px-8 py-3 rounded-2xl font-black text-sm transition-all whitespace-nowrap" :class="activeCategory === cat
						? 'bg-orange-500 text-white shadow-lg shadow-orange-500/20'
						: 'bg-white text-gray-400 hover:bg-gray-100'" @click="activeCategory = cat">
					{{ cat }}
				</button>
			</div>

			<!-- Menu grid -->
			<div class="flex-1 overflow-y-auto pr-2 grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 content-start">
				<div v-for="item in filteredMenu" :key="item.id"
					class="bg-white rounded-[2rem] p-5 flex flex-col cursor-pointer transition-all duration-300 hover:shadow-xl hover:-translate-y-2 group"
					@click="addToCart(item)">
					<div class="aspect-square rounded-2xl overflow-hidden mb-4 bg-gray-50">
						<img :src="item.image_url || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?q=80&w=200&h=200&auto=format&fit=crop'"
							class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" />
					</div>
					<h4 class="text-lg font-black text-gray-900 leading-tight mb-2">{{ item.name }}</h4>
					<div class="mt-auto flex justify-between items-center">
						<span class="text-orange-500 font-black text-xl">{{ formatRp(item.price) }}</span>
						<div
							class="w-10 h-10 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center group-hover:bg-orange-500 group-hover:text-white transition-all">
							<i class="bi bi-plus-lg" />
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- ══ Cart panel ══════════════════════════════════════════════ -->
		<TerminalCartPanel :cart="cart" :order-type="orderType" :selected-table="selectedTable"
			:guest-count="guestCount" @update-qty="updateQty" @submit="handleSubmit" />
	</div>
</template>

<script setup lang="ts">
interface MenuItem { id: number; name: string; category: string; price: number; image_url?: string }
interface Table { id: number; name: string }
interface CartItem extends MenuItem { qty: number }

const props = defineProps<{
	menuItems: MenuItem[]
	categories: string[]
	orderType: 'DINE_IN' | 'TAKE_AWAY'
	selectedTable: Table | null
	selectedTables?: Table[]
	guestCount: number
}>()

const emit = defineEmits<{
	back: []
	submitted: [orderId: number]
}>()

const api = useApi()
const auth = useAuthStore()

const activeCategory = ref('Semua')
const cart = ref<CartItem[]>([])
const orderTypeLabel = computed(() => (props.orderType === 'DINE_IN' ? 'Dine In' : 'Take Away'))

const selectedTablesLabel = computed(() => {
	const names = (props.selectedTables ?? []).map(t => t.name)
	if (names.length <= 1) return ''
	return names.join(' + ')
})

const filteredMenu = computed(() =>
	props.menuItems.filter(item =>
		(activeCategory.value === 'Semua' || item.category === activeCategory.value)
	)
)

function addToCart(item: MenuItem) {
	const existing = cart.value.find(i => i.id === item.id)
	if (existing) { existing.qty++ } else { cart.value.push({ ...item, qty: 1 }) }
}

function updateQty(id: number, delta: number) {
	const item = cart.value.find(i => i.id === id)
	if (!item) return
	item.qty = Math.max(0, item.qty + delta)
	if (item.qty === 0) cart.value = cart.value.filter(i => i.id !== id)
}

async function handleSubmit() {
	if (!cart.value.length) return
	try {
		const tableId = props.selectedTable?.id ?? props.selectedTables?.[0]?.id ?? null
		if (props.orderType === 'DINE_IN' && !tableId) {
			alert('Silakan pilih meja terlebih dahulu')
			return
		}

		const mergedIds = (props.selectedTables ?? [])
			.map(t => t.id)
			.filter(id => id !== tableId)

		const payload = {
			table_id: tableId,
			order_type: props.orderType,
			merged_table_ids: mergedIds.length ? JSON.stringify(mergedIds) : null,
			items: cart.value.map(i => ({ menu_item_id: i.id, qty: i.qty })),
		}
		const res = await api.post<{ id?: number; order?: { id?: number } }>('/api/terminal/orders', payload)
		const orderId = res?.order?.id ?? res?.id
		if (!orderId) throw new Error('ORDER_ID_NOT_FOUND')
		emit('submitted', orderId)
		cart.value = []
	} catch (e: unknown) {
		const err = e as { data?: { error?: string } }
		alert(err?.data?.error ?? 'Gagal membuat pesanan')
	}
}

function formatRp(val: number) {
	return 'Rp ' + new Intl.NumberFormat('id-ID').format(val)
}
</script>
