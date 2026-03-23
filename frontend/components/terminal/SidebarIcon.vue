<template>
  <div
    class="relative flex flex-col items-center justify-center w-full py-5 cursor-pointer transition-all duration-200 group"
    :class="active ? 'text-orange-500' : 'text-gray-500 hover:text-orange-400'"
    @click="emit('click')"
  >
    <!-- Active indicator bar -->
    <div
      v-if="active"
      class="absolute left-0 top-0 bottom-0 w-1.5 bg-orange-500 rounded-r-full shadow-[2px_0_10px_rgba(249,115,22,0.4)]"
    />

    <div
      class="p-2 rounded-xl transition-all"
      :class="active ? 'bg-orange-500/10' : 'group-hover:bg-gray-800'"
    >
      <i :class="`bi ${icon} text-2xl`" />
    </div>

    <span class="text-[10px] font-black uppercase tracking-tighter mt-1">{{ label }}</span>
  </div>
</template>

<script setup lang="ts">
defineProps<{ icon: string; label: string; active?: boolean }>()
const emit = defineEmits<{ click: [] }>()
</script>
