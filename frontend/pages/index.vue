<template>
  <!-- Instant redirect to /login - no UI needed -->
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'guest' })
navigateTo('/login')
</script>
