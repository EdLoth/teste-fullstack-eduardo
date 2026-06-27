<template>
  <div
    class="p-4 flex flex-col sm:flex-row sm:items-center gap-3"
    style="border-bottom: 1px solid var(--border)"
  >
    <!-- Título + busca (esquerda) -->
    <div class="flex items-center gap-3 flex-1">
      <div>
        <h2 class="text-base font-semibold" style="color: var(--text-main)">Pedidos</h2>
        <p class="text-xs" style="color: var(--text-muted)">
          {{ total }} pedidos encontrados
        </p>
      </div>
      <div class="relative ml-2">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none"
          fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"
          style="color: var(--text-muted)"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 105 11a6 6 0 0012 0z" />
        </svg>
        <input
          :value="search"
          @input="$emit('update:search', $event.target.value)"
          type="text"
          placeholder="Buscar afiliado..."
          class="pl-9 pr-3 py-2 text-sm rounded-lg focus:outline-none focus:ring-2 focus:ring-black w-52 cursor-text"
          style="background-color: var(--bg-input); border: 1px solid var(--border); color: var(--text-main)"
          aria-label="Buscar por afiliado"
        />
      </div>
    </div>

    <!-- Filtros (direita) -->
    <div class="flex items-center gap-2 flex-wrap">
      <TableFilters
        :status="status"
        :dateRange="dateRange"
        @update:status="$emit('update:status', $event)"
        @update:dateRange="$emit('update:dateRange', $event)"
      />

      <button
        v-if="selectedCount > 0"
        @click="$emit('bulkCancel')"
        class="bg-black text-white text-sm px-4 py-2 rounded-lg hover:opacity-80 active:opacity-60 transition-opacity cursor-pointer focus:outline-none focus:ring-2 focus:ring-black"
        :aria-label="`Cancelar ${selectedCount} pedidos selecionados`"
      >
        Cancelar ({{ selectedCount }})
      </button>
    </div>
  </div>
</template>

<script setup>
import TableFilters from './TableFilters.vue'

defineProps({
  search:        String,
  status:        String,
  dateRange:     Object,
  total:         Number,
  selectedCount: Number,
})

defineEmits(['update:search', 'update:status', 'update:dateRange', 'bulkCancel'])
</script>