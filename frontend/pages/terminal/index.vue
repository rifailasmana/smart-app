<template>
  <div class="w-full h-full flex flex-col items-center justify-center bg-brand-content">
    <h1 class="text-4xl font-black text-gray-800 mb-4 tracking-tight">Terminal Access</h1>
    <p class="text-gray-500 font-semibold mb-12">Pilih terminal sesuai peran Anda</p>

    <div class="grid grid-cols-3 gap-8 px-10 max-w-3xl w-full">
      <NuxtLink
        v-for="item in terminalRoutes" :key="item.route"
        :to="item.route"
        class="flex flex-col items-center gap-5 p-8 bg-white rounded-3xl shadow-md hover:shadow-xl hover:-translate-y-1 transition-all group"
      >
        <div
          class="w-20 h-20 rounded-2xl flex items-center justify-center text-white text-3xl shadow-lg"
          :class="item.bg"
        >
          <i :class="item.icon" />
        </div>
        <div class="text-center">
          <h3 class="font-black text-gray-900 text-lg mb-1">{{ item.label }}</h3>
          <p class="text-xs text-gray-400 font-medium">{{ item.desc }}</p>
        </div>
      </NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'terminal', middleware: 'auth' })
useHead({ title: 'Terminal – Majar Signature' })

const terminalRoutes = [
  { route: '/terminal/kasir',   icon: 'bi bi-cash-stack',       label: 'Kasir',   desc: 'Point of Sale',     bg: 'bg-gradient-to-br from-orange-500 to-yellow-400' },
  { route: '/terminal/waiter',  icon: 'bi bi-ui-checks-grid',   label: 'Waiter',  desc: 'Pelayan lapangan',  bg: 'bg-gradient-to-br from-blue-500 to-cyan-400' },
  { route: '/terminal/kitchen', icon: 'bi bi-fire',              label: 'Kitchen', desc: 'Display dapur',     bg: 'bg-gradient-to-br from-red-500 to-orange-400' },
]

provide('terminalRoleLabel', 'TERMINAL')
</script>
