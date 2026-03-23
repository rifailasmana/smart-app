<template>
  <div class="w-[420px] bg-white border-l border-gray-100 flex flex-col p-8 shadow-[-10px_0_30px_rgba(0,0,0,0.02)]">
    <div class="flex items-center gap-3 mb-8">
      <i class="bi bi-cart-fill text-2xl text-orange-500" />
      <h2 class="text-2xl font-black text-gray-900 tracking-tight">Pesanan</h2>
    </div>

    <!-- Cart items -->
    <div class="flex-1 overflow-y-auto pr-1 space-y-4">
      <div v-if="!cart.length" class="h-full flex flex-col items-center justify-center opacity-30">
        <i class="bi bi-cart-x text-6xl mb-4" />
        <p class="font-bold text-sm uppercase tracking-widest">Belum ada produk</p>
        <p class="text-[10px] mt-1">Ketuk produk untuk menambahkan</p>
      </div>

      <div
        v-for="item in cart"
        :key="item.id"
        class="bg-gray-50 rounded-2xl p-4 flex gap-4"
      >
        <img :src="item.image_url || fallbackImg" class="w-16 h-16 rounded-xl object-cover" />
        <div class="flex-1">
          <h5 class="font-black text-gray-900 text-sm leading-tight">{{ item.name }}</h5>
          <p class="text-orange-500 font-bold text-xs mt-1">{{ formatRp(item.price) }}</p>
          <div class="flex items-center gap-3 mt-3">
            <button
              class="w-8 h-8 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-gray-900 hover:bg-red-50 hover:text-red-500 transition-all"
              @click="emit('update-qty', item.id, -1)"
            >
              <i class="bi bi-dash" />
            </button>
            <span class="font-black text-gray-900 w-6 text-center">{{ item.qty }}</span>
            <button
              class="w-8 h-8 rounded-lg bg-white border border-gray-200 flex items-center justify-center text-gray-900 hover:bg-green-50 hover:text-green-500 transition-all"
              @click="emit('update-qty', item.id, 1)"
            >
              <i class="bi bi-plus" />
            </button>
          </div>
        </div>
        <div class="text-right font-black text-gray-900 text-sm">
          {{ formatRp(item.price * item.qty) }}
        </div>
      </div>
    </div>

    <!-- Summary + action -->
    <div class="mt-8 pt-8 border-t-2 border-dashed border-gray-100 space-y-4">
      <div class="flex justify-between items-center text-gray-400 font-bold">
        <span class="text-[10px] uppercase tracking-widest">Subtotal</span>
        <span>{{ formatRp(subtotal) }}</span>
      </div>
      <div class="flex justify-between items-center">
        <span class="text-lg font-black text-gray-900 uppercase tracking-tighter">Total Bill</span>
        <span class="text-3xl font-black text-orange-500 tracking-tighter">{{ formatRp(subtotal) }}</span>
      </div>
      <button
        :disabled="!cart.length"
        class="w-full py-5 bg-gradient-to-r from-orange-500 to-yellow-400 text-white rounded-[2rem] font-black text-xl shadow-xl shadow-orange-500/30 transition-all active:scale-95 disabled:opacity-30 disabled:shadow-none mt-4"
        @click="emit('submit')"
      >
        Pesan Sekarang <i class="bi bi-check-circle ml-2" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
interface CartItem {
  id: number; name: string; price: number; image_url?: string; qty: number
}

const props = defineProps<{ cart: CartItem[] }>()
const emit = defineEmits<{
  'update-qty': [id: number, delta: number]
  submit: []
}>()

const fallbackImg = 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?q=80&w=100&auto=format&fit=crop'

const subtotal = computed(() => props.cart.reduce((s, i) => s + i.price * i.qty, 0))

function formatRp(val: number) {
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(val)
}
</script>
