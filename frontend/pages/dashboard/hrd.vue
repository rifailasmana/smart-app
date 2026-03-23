<template>
  <div>
    <div class="mb-8">
      <h1 class="text-3xl font-black text-gray-900 tracking-tight">HRD Dashboard</h1>
      <p class="text-gray-500 font-semibold mt-1">Manajemen karyawan & penggajian</p>
    </div>

    <!-- KPI -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
      <div v-for="kpi in kpis" :key="kpi.label" class="bg-white rounded-[1.5rem] p-6 border border-gray-100">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center text-xl mb-4" :class="kpi.iconBg">
          <i :class="kpi.icon" />
        </div>
        <div class="text-2xl font-black text-gray-900 mb-1">{{ kpi.value }}</div>
        <div class="text-xs font-bold text-gray-400 uppercase tracking-widest">{{ kpi.label }}</div>
      </div>
    </div>

    <!-- Attendance today -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
      <div class="xl:col-span-2 bg-white rounded-[1.5rem] p-8 border border-gray-100">
        <h3 class="font-black text-gray-900 mb-6">Kehadiran Hari Ini</h3>
        <div v-if="loading" class="space-y-3">
          <div v-for="n in 5" :key="n" class="h-12 bg-gray-100 rounded-xl animate-pulse" />
        </div>
        <table v-else class="w-full text-sm">
          <thead>
            <tr class="text-[10px] font-black text-gray-400 uppercase tracking-widest border-b border-gray-50">
              <th class="text-left pb-4">Karyawan</th>
              <th class="text-left pb-4">Check-in</th>
              <th class="text-left pb-4">Check-out</th>
              <th class="text-right pb-4">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <tr v-for="a in attendance" :key="a.id" class="hover:bg-gray-50">
              <td class="py-3 font-bold text-gray-900">{{ a.name }}</td>
              <td class="py-3 text-gray-500">{{ a.check_in ?? '–' }}</td>
              <td class="py-3 text-gray-500">{{ a.check_out ?? '–' }}</td>
              <td class="py-3 text-right">
                <span class="px-2 py-1 rounded-full text-[10px] font-black"
                  :class="a.status === 'hadir' ? 'bg-green-100 text-green-700' : a.status === 'izin' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'"
                >{{ a.status }}</span>
              </td>
            </tr>
            <tr v-if="!attendance.length">
              <td colspan="4" class="py-8 text-center text-gray-400 font-semibold">Tidak ada data kehadiran</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Payroll summary -->
      <div class="bg-white rounded-[1.5rem] p-8 border border-gray-100">
        <h3 class="font-black text-gray-900 mb-6">Penggajian Bulan Ini</h3>
        <div class="space-y-4">
          <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
            <span class="text-sm font-semibold text-gray-600">Total Gaji Pokok</span>
            <span class="font-black text-gray-900">Rp {{ (data?.payroll_total ?? 0).toLocaleString('id-ID') }}</span>
          </div>
          <div class="flex items-center justify-between p-4 bg-green-50 rounded-xl">
            <span class="text-sm font-semibold text-gray-600">Total Bonus</span>
            <span class="font-black text-green-700">Rp {{ (data?.bonus_total ?? 0).toLocaleString('id-ID') }}</span>
          </div>
          <div class="flex items-center justify-between p-4 bg-orange-50 rounded-xl">
            <span class="text-sm font-semibold text-gray-600">Sudah Dibayar</span>
            <span class="font-black text-orange-700">{{ data?.paid_count ?? 0 }} / {{ data?.total_employees ?? 0 }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ layout: 'dashboard', middleware: 'auth' })
useHead({ title: 'HRD Dashboard – Majar Signature' })

const api     = useApi()
const loading = ref(true)

interface HrdData {
  total_employees: number
  present_today: number
  pending_leave: number
  paid_count: number
  payroll_total: number
  bonus_total: number
  attendance: { id: number; name: string; check_in?: string; check_out?: string; status: string }[]
}

const data = ref<HrdData | null>(null)

const kpis = computed(() => [
  { label: 'Total Karyawan', value: data.value?.total_employees ?? 0, icon: 'bi bi-people-fill',   iconBg: 'bg-blue-100 text-blue-600' },
  { label: 'Hadir Hari Ini', value: data.value?.present_today ?? 0,  icon: 'bi bi-check2-circle',  iconBg: 'bg-green-100 text-green-600' },
  { label: 'Izin Pending',   value: data.value?.pending_leave ?? 0,  icon: 'bi bi-hourglass-split', iconBg: 'bg-yellow-100 text-yellow-600' },
  { label: 'Sudah Dibayar', value: data.value?.paid_count ?? 0,      icon: 'bi bi-wallet-fill',    iconBg: 'bg-purple-100 text-purple-600' },
])

const attendance = computed(() => data.value?.attendance ?? [])

onMounted(async () => {
  try { data.value = await api.get<HrdData>('/api/dashboard/hrd') }
  catch { data.value = { total_employees: 0, present_today: 0, pending_leave: 0, paid_count: 0, payroll_total: 0, bonus_total: 0, attendance: [] } }
  finally { loading.value = false }
})
</script>
