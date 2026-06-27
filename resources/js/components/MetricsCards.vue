<template>
  <div class="mb-8">

    <!-- Row 1 — métricas principais -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
      <template v-if="loading">
        <div v-for="i in 3" :key="`sk1-${i}`" class="rounded-xl p-5 animate-pulse"
          style="background-color: var(--bg-card); border: 1px solid var(--border)">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl flex-shrink-0" style="background-color: var(--border)"></div>
            <div class="flex-1">
              <div class="h-2.5 rounded w-20 mb-3" style="background-color: var(--border)"></div>
              <div class="h-6 rounded w-28" style="background-color: var(--border)"></div>
            </div>
          </div>
        </div>
      </template>
      <template v-else>
        <div v-for="card in primaryCards" :key="card.label"
          class="rounded-xl p-5 flex items-center gap-4 transition-shadow hover:shadow-sm"
          style="background-color: var(--bg-card); border: 1px solid var(--border)">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
            :style="`background-color: ${props.isDark ? card.iconBgDark : card.iconBgLight}`">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24"
              stroke="currentColor" stroke-width="1.8" :style="`color: ${card.iconColor}`" v-html="card.icon"></svg>
          </div>
          <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wider mb-1 truncate" style="color: var(--text-muted)">
              {{ card.label }}
            </p>
            <p class="text-2xl font-semibold truncate" :style="`color: ${card.valueColor}`">
              {{ card.value }}
            </p>
          </div>
        </div>
      </template>
    </div>

    <!-- Row 2 — métricas secundárias -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-2">
      <template v-if="loading">
        <div v-for="i in 3" :key="`sk2-${i}`" class="rounded-xl p-4 animate-pulse"
          style="background-color: var(--bg-card); border: 1px solid var(--border)">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg flex-shrink-0" style="background-color: var(--border)"></div>
            <div class="flex-1">
              <div class="h-2.5 rounded w-16 mb-2" style="background-color: var(--border)"></div>
              <div class="h-5 rounded w-24" style="background-color: var(--border)"></div>
            </div>
          </div>
        </div>
      </template>
      <template v-else>
        <div v-for="card in secondaryCards" :key="card.label"
          class="rounded-xl p-4 flex items-center gap-3 transition-shadow hover:shadow-sm"
          style="background-color: var(--bg-card); border: 1px solid var(--border)">
          <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0"
            :style="`background-color: ${props.isDark ? card.iconBgDark : card.iconBgLight}`">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24"
              stroke="currentColor" stroke-width="1.8" :style="`color: ${card.iconColor}`" v-html="card.icon"></svg>
          </div>
          <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wider mb-0.5 truncate" style="color: var(--text-muted)">
              {{ card.label }}
            </p>
            <p class="text-lg font-semibold truncate" :style="`color: ${card.valueColor}`">
              {{ card.value }}
            </p>
          </div>
        </div>
      </template>
    </div>

    <!-- Cache label -->
    <p class="text-xs text-right" style="color: var(--text-muted)">{{ cacheLabel }}</p>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'

const props = defineProps({
  isDark:         Boolean,
  dateRange:      { type: Object, default: () => ({ from: '', to: '' }) },
  refreshTrigger: { type: Number, default: 0 },
})

const metrics    = ref({})
const loading    = ref(true)
const updatedAt  = ref(null)
const cacheLabel = ref('')

// Row 1 — principais
const primaryCards = computed(() => [
  {
    label:       'Total de Pedidos',
    value:       metrics.value.total_orders ?? 0,
    icon:        `<path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2z"/><path stroke-linecap="round" stroke-linejoin="round" d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/>`,
    iconBgLight: '#dbeafe',
    iconBgDark:  '#1e3a5f',
    iconColor:   '#3b82f6',
    valueColor:  'var(--text-main)',
  },
  {
    label:       'Receita Prevista',
    value:       formatCurrency(metrics.value.pending_revenue),
    icon:        `<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>`,
    iconBgLight: '#eff6ff',
    iconBgDark:  '#1e3a5f',
    iconColor:   '#3b82f6',
    valueColor:  '#3b82f6',
  },
  {
    label:       'Receita Realizada',
    value:       formatCurrency(metrics.value.total_revenue),
    icon:        `<path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>`,
    iconBgLight: '#dcfce7',
    iconBgDark:  '#14532d',
    iconColor:   '#16a34a',
    valueColor:  '#16a34a',
  },
])

// Row 2 — secundárias
const secondaryCards = computed(() => [
  {
    label:       'Cancelados',
    value:       metrics.value.cancelled_count ?? 0,
    icon:        `<path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>`,
    iconBgLight: '#fee2e2',
    iconBgDark:  '#450a0a',
    iconColor:   '#dc2626',
    valueColor:  '#dc2626',
  },
  {
    label:       'Receita Reembolsada',
    value:       formatCurrency(metrics.value.refunded_revenue),
    icon:        `<path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>`,
    iconBgLight: '#fef3c7',
    iconBgDark:  '#451a03',
    iconColor:   '#d97706',
    valueColor:  '#d97706',
  },
  {
    label:       'Ticket Médio',
    value:       formatCurrency(metrics.value.average_ticket),
    icon:        `<path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>`,
    iconBgLight: '#f3f4f6',
    iconBgDark:  '#27272a',
    iconColor:   '#6b7280',
    valueColor:  'var(--text-main)',
  },
])

let interval      = null
let labelInterval = null

async function fetchMetrics() {
  try {
    const params = new URLSearchParams()
    if (props.dateRange?.from) params.set('date_from', props.dateRange.from)
    if (props.dateRange?.to)   params.set('date_to',   props.dateRange.to)

    const res  = await fetch(`/api/orders/metrics?${params.toString()}`)
    const json = await res.json()
    metrics.value   = json.data
    loading.value   = false
    updatedAt.value = new Date()
    updateCacheLabel()
  } catch {
    loading.value = false
  }
}

function updateCacheLabel() {
  if (!updatedAt.value) return
  const diff = Math.floor((new Date() - updatedAt.value) / 1000)
  cacheLabel.value = diff < 60
    ? `Atualizado há ${diff}s`
    : `Atualizado há ${Math.floor(diff / 60)} min`
}

function formatCurrency(value) {
  if (!value) return 'R$ 0,00'
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
}

watch(() => props.dateRange,      fetchMetrics, { deep: true })
watch(() => props.refreshTrigger, fetchMetrics)

onMounted(() => {
  fetchMetrics()
  interval      = setInterval(fetchMetrics, 60000)
  labelInterval = setInterval(updateCacheLabel, 1000)
})

onUnmounted(() => {
  clearInterval(interval)
  clearInterval(labelInterval)
})

defineExpose({ refresh: fetchMetrics })
</script>