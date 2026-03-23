<template>
  <div
    class="w-full max-w-[340px] aspect-square bg-white rounded-[2.5rem] p-10 flex flex-col items-center justify-center cursor-pointer transition-all duration-300 transform hover:-translate-y-3 hover:shadow-[0_20px_50px_rgba(0,0,0,0.1)] group border-2 border-transparent hover:border-orange-100"
    @click="emit('click')"
  >
    <div
      class="w-24 h-24 rounded-3xl flex items-center justify-center mb-8 transition-transform group-hover:scale-110"
      :class="color === 'orange' ? 'bg-orange-50 text-orange-500' : 'bg-green-50 text-green-500'"
    >
      <i :class="`bi ${icon} text-5xl`" />
    </div>
    <h2 class="text-3xl font-black text-gray-900 tracking-tight">{{ title }}</h2>
    <p class="text-gray-400 font-medium mt-2">{{ subtitle }}</p>
    <div
      class="mt-10 flex items-center gap-2 font-bold text-sm"
      :class="color === 'orange' ? 'text-orange-500' : 'text-green-500'"
    >
      {{ cta }} <i class="bi bi-arrow-right" />
    </div>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  icon: string
  title: string
  subtitle: string
  cta: string
  color?: 'orange' | 'green'
}>()

const emit = defineEmits<{ click: [] }>()
</script>
