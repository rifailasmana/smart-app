<template>
	<div class="relative p-6 rounded-[2rem] border-2 transition-all duration-300 flex flex-col justify-between aspect-video"
		:class="[
			active ? 'border-orange-500 bg-orange-50 shadow-lg' : statusClass.border + ' ' + statusClass.bg,
			clickable ? 'cursor-pointer hover:shadow-md' : 'opacity-60 cursor-not-allowed',
		]" @click="clickable && emit('select', table)">
		<div class="flex justify-between items-start">
			<div class="w-12 h-12 rounded-2xl flex items-center justify-center"
				:class="active ? 'bg-orange-500 text-white' : 'bg-white ' + statusClass.text">
				<i class="bi bi-grid-fill text-xl" />
			</div>
			<i v-if="active" class="bi bi-check-circle-fill text-orange-500 text-xl" />
		</div>

		<div>
			<h3 class="text-2xl font-black tracking-tight" :class="active ? 'text-orange-600' : 'text-gray-900'">
				{{ table.name }}
			</h3>
			<div class="flex items-center gap-2 mt-1">
				<i class="bi bi-people-fill text-xs text-gray-400" />
				<span class="text-[10px] font-bold text-gray-500 uppercase tracking-widest">Maks. {{ table.capacity
					}}</span>
			</div>
		</div>

		<div class="mt-4 inline-flex items-center gap-1.5 text-[10px] font-black uppercase tracking-widest"
			:class="statusClass.text">
			<div class="w-1.5 h-1.5 rounded-full" :class="active ? 'bg-orange-500' : statusClass.dot" />
			{{ statusClass.label }}
		</div>
	</div>
</template>

<script setup lang="ts">
interface Table {
	id: number
	name: string
	capacity: number
	status: 'available' | 'occupied' | 'reserved'
}

const props = defineProps<{
	table: Table
	active: boolean
	guestCount: number
	allowAnyAvailable?: boolean
}>()

const emit = defineEmits<{ select: [table: Table] }>()

const STATUS_CONFIG = {
	available: { bg: 'bg-green-50', border: 'border-green-100', text: 'text-green-600', dot: 'bg-green-600', label: 'Tersedia' },
	occupied: { bg: 'bg-gray-50', border: 'border-gray-200', text: 'text-gray-400', dot: 'bg-gray-400', label: 'Terisi' },
	reserved: { bg: 'bg-orange-50', border: 'border-orange-100', text: 'text-orange-500', dot: 'bg-orange-500', label: 'Reservasi' },
}

const statusClass = computed(() => STATUS_CONFIG[props.table.status] ?? STATUS_CONFIG.available)
const clickable = computed(() =>
	props.table.status === 'available' && (props.allowAnyAvailable || props.table.capacity >= props.guestCount)
)
</script>
