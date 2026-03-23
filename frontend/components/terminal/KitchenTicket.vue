<template>
  <div
    class="bg-white rounded-[2.5rem] border-l-[12px] shadow-lg flex flex-col h-full transition-all hover:scale-[1.02] hover:shadow-2xl overflow-hidden"
    :class="config.border"
  >
    <!-- Header -->
    <div class="p-6 border-b border-gray-100 flex justify-between items-start">
      <div>
        <h3 class="text-3xl font-black text-gray-900 tracking-tighter">
          Meja {{ order.table?.name ?? 'TA' }}
        </h3>
        <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] mt-1">#{{ order.code }}</p>
      </div>
      <div class="text-right">
        <div class="px-3 py-1 rounded-full bg-gray-100 text-[10px] font-black uppercase tracking-widest text-gray-500 mb-2">
          {{ order.order_type }}
        </div>
        <div class="text-[10px] font-bold text-gray-400">{{ timeOf(order.created_at) }}</div>
      </div>
    </div>

    <!-- Items -->
    <div class="flex-1 overflow-y-auto p-6 space-y-4">
      <div v-for="(item, idx) in order.items" :key="idx" class="flex gap-4 items-start group">
        <div class="w-12 h-12 rounded-2xl bg-gray-50 border border-gray-100 flex items-center justify-center font-black text-2xl text-orange-500 shadow-sm group-hover:bg-orange-500 group-hover:text-white transition-all">
          {{ item.qty }}
        </div>
        <div class="flex-1 pt-1">
          <div class="font-black text-xl text-gray-900 leading-tight uppercase tracking-tight">
            {{ item.menu_name }}
          </div>
          <div v-if="item.note" class="mt-2 p-3 rounded-2xl bg-orange-50 border border-orange-100 text-orange-700 text-xs font-bold flex items-center gap-2">
            <i class="bi bi-info-circle-fill" /> {{ item.note }}
          </div>
        </div>
      </div>
    </div>

    <!-- Action -->
    <div class="p-6 bg-gray-50/50 border-t border-gray-100">
      <button
        class="w-full py-5 text-white font-black rounded-[1.5rem] transition-all active:scale-95 shadow-lg text-lg uppercase tracking-widest hover:opacity-90"
        :class="config.btnColor"
        @click="emit('update-status', order.id, nextStage)"
      >
        {{ config.btn }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface OrderItem { qty: number; menu_name: string; note?: string }
interface Order {
  id: number; code: string; stage: string; order_type: string; created_at: string
  table: { name: string } | null
  items: OrderItem[]
}

const props = defineProps<{ order: Order }>()
const emit = defineEmits<{ 'update-status': [id: number, newStage: string] }>()

const STAGE_CONFIG: Record<string, { border: string; btn: string; btnColor: string }> = {
  READY_FOR_KITCHEN: { border: 'border-orange-500', btn: 'Mulai Masak',  btnColor: 'bg-orange-500' },
  COOKING:           { border: 'border-blue-500',   btn: 'Tandai Siap',  btnColor: 'bg-blue-500' },
  READY:             { border: 'border-green-500',  btn: 'Selesai',      btnColor: 'bg-green-500' },
}

const config = computed(() => STAGE_CONFIG[props.order.stage] ?? STAGE_CONFIG.READY_FOR_KITCHEN)

const nextStage = computed(() => {
  const map: Record<string, string> = {
    READY_FOR_KITCHEN: 'COOKING',
    COOKING: 'READY',
    READY: 'DONE',
  }
  return map[props.order.stage] ?? 'DONE'
})

function timeOf(ts: string) {
  return new Date(ts).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' })
}
</script>
