<template>
  <span class="font-mono tabular-nums text-sm">{{ clock }}</span>
</template>

<script setup lang="ts">
const clock = useClock('time')
</script>
