<template>
  <!-- Role-based redirect — no UI -->
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'auth' })

const auth = useAuthStore()
const router = useRouter()

onMounted(() => {
  router.replace(auth.dashboardRoute())
})
</script>
