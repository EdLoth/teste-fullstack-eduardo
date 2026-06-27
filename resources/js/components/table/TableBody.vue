<template>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead>
        <tr style="border-bottom: 1px solid var(--border)">
          <th class="p-4 w-8">
            <input
              type="checkbox"
              @change="$emit('toggleAll', $event)"
              :checked="allSelected"
              class="rounded accent-black"
              aria-label="Selecionar todos os pedidos"
            />
          </th>
          <th
            v-for="col in columns" :key="col.field"
            class="p-4 text-left text-xs font-semibold uppercase tracking-wider cursor-pointer select-none hover:opacity-70 active:opacity-50 transition-opacity"
            style="color: var(--text-muted)"
            @click="$emit('sort', col.field)"
            :aria-label="`Ordenar por ${col.label}`"
          >
            {{ col.label }}
            <span class="ml-1 text-xs">{{ sortIcon(col.field) }}</span>
          </th>
          <th class="p-4 text-left text-xs font-semibold uppercase tracking-wider" style="color: var(--text-muted)">
            Ações
          </th>
        </tr>
      </thead>
      <tbody>

        <!-- Skeleton -->
        <template v-if="loading">
          <tr v-for="i in 5" :key="`sk-${i}`" style="border-bottom: 1px solid var(--border)">
            <td class="p-4">
              <div class="h-4 w-4 rounded animate-pulse" style="background-color: var(--border)"></div>
            </td>
            <td v-for="col in columns" :key="col.field" class="p-4">
              <div class="h-4 rounded animate-pulse" :style="`background-color: var(--border); width: ${col.skeletonW ?? '80px'}`"></div>
            </td>
            <td class="p-4">
              <div class="h-4 w-12 rounded animate-pulse" style="background-color: var(--border)"></div>
            </td>
          </tr>
        </template>

        <!-- Vazio -->
        <tr v-else-if="orders.length === 0">
          <td :colspan="columns.length + 2" class="p-12 text-center text-sm" style="color: var(--text-muted)">
            <div class="flex flex-col items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>Nenhum pedido encontrado para os filtros aplicados.</span>
            </div>
          </td>
        </tr>

        <!-- Dados -->
        <tr
          v-else
          v-for="order in orders"
          :key="order.id"
          class="transition-colors"
          style="border-bottom: 1px solid var(--border)"
          :style="selectedIds.includes(order.id) ? `background-color: var(--bg-input)` : ''"
        >
          <td class="p-4">
            <input
              type="checkbox"
              :value="order.id"
              :checked="selectedIds.includes(order.id)"
              @change="$emit('toggleOne', order.id)"
              class="rounded accent-black"
              :aria-label="`Selecionar pedido ${order.id}`"
            />
          </td>
          <td class="p-4 font-medium text-xs" style="color: var(--text-muted)">#{{ order.id }}</td>
          <td class="p-4 font-medium" style="color: var(--text-main)">{{ order.affiliate?.name ?? '—' }}</td>
          <td class="p-4 font-semibold" style="color: var(--text-main)">{{ formatCurrency(order.total) }}</td>
          <td class="p-4">
            <span :class="statusClass(order.status)" class="px-2.5 py-1 rounded-full text-xs font-medium">
              {{ statusLabel(order.status) }}
            </span>
          </td>
          <td class="p-4 text-sm" style="color: var(--text-muted)">{{ formatDate(order.created_at) }}</td>
          <td class="p-4">
            <button
              @click="$emit('openDrawer', order)"
             class="text-xs font-medium underline underline-offset-2 hover:opacity-70 active:opacity-50 transition-opacity cursor-pointer focus:outline-none focus:ring-2 focus:ring-black rounded"
              style="color: var(--text-main)"
              :aria-label="`Ver detalhes do pedido ${order.id}`"
            >
              Detalhes
            </button>
          </td>
        </tr>

      </tbody>
    </table>
  </div>

  <!-- Mobile cards -->
  <div class="sm:hidden divide-y" style="border-color: var(--border)">
    <template v-if="loading">
      <div v-for="i in 4" :key="`msk-${i}`" class="p-4 animate-pulse">
        <div class="h-4 rounded w-40 mb-2" style="background-color: var(--border)"></div>
        <div class="h-3 rounded w-24" style="background-color: var(--border)"></div>
      </div>
    </template>
    <template v-else>
      <div v-for="order in orders" :key="`m-${order.id}`" class="p-4">
        <div class="flex justify-between items-start mb-2">
          <div>
            <p class="font-semibold text-sm" style="color: var(--text-main)">{{ order.affiliate?.name ?? '—' }}</p>
            <p class="text-xs mt-0.5" style="color: var(--text-muted)">#{{ order.id }} · {{ formatDate(order.created_at) }}</p>
          </div>
          <span :class="statusClass(order.status)" class="px-2.5 py-1 rounded-full text-xs font-medium">
            {{ statusLabel(order.status) }}
          </span>
        </div>
        <div class="flex justify-between items-center mt-3">
          <span class="font-semibold text-sm" style="color: var(--text-main)">{{ formatCurrency(order.total) }}</span>
          <button
            @click="$emit('openDrawer', order)"
            class="text-xs font-medium underline underline-offset-2 hover:opacity-70 active:opacity-50 transition-opacity cursor-pointer"
            style="color: var(--text-main)"
            :aria-label="`Ver detalhes do pedido ${order.id}`"
          >
            Detalhes →
          </button>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
const props = defineProps({
  orders:     Array,
  loading:    Boolean,
  selectedIds: Array,
  allSelected: Boolean,
  sortBy:     String,
  sortDir:    String,
})

defineEmits(['sort', 'toggleAll', 'toggleOne', 'openDrawer'])

const columns = [
  { field: 'id',           label: 'ID',       skeletonW: '40px'  },
  { field: 'affiliate_id', label: 'Afiliado', skeletonW: '100px' },
  { field: 'total',        label: 'Valor',    skeletonW: '80px'  },
  { field: 'status',       label: 'Status',   skeletonW: '70px'  },
  { field: 'created_at',   label: 'Data',     skeletonW: '90px'  },
]

function sortIcon(field) {
  if (props.sortBy !== field) return '↕'
  return props.sortDir === 'asc' ? '↑' : '↓'
}

function statusClass(status) {
  return {
    pending:   'bg-amber-100 text-amber-700',
    approved:  'bg-green-100 text-green-700',
    cancelled: 'bg-red-100 text-red-700',
    refunded:  'bg-zinc-100 text-zinc-600',
  }[status] ?? 'bg-zinc-100 text-zinc-600'
}

function statusLabel(status) {
  return { pending: 'Pendente', approved: 'Aprovado', cancelled: 'Cancelado', refunded: 'Reembolsado' }[status] ?? status
}

function formatCurrency(value) {
  if (!value) return 'R$ 0,00'
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
}

function formatDate(date) {
  if (!date) return '—'
  return new Date(date).toLocaleDateString('pt-BR')
}
</script>