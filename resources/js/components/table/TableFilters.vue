<template>
  <div class="flex items-center gap-2 flex-wrap">
    <select
      :value="status"
      @change="$emit('update:status', $event.target.value)"
      class="rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black cursor-pointer hover:opacity-80 transition-opacity"
      style="background-color: var(--bg-input); border: 1px solid var(--border); color: var(--text-main)"
      aria-label="Filtrar por status"
    >
      <option value="">Todos os status</option>
      <option value="pending">Pendente</option>
      <option value="approved">Aprovado</option>
      <option value="cancelled">Cancelado</option>
      <option value="refunded">Reembolsado</option>
    </select>

    <DateRangePicker
      :modelValue="dateRange"
      @update:modelValue="$emit('update:dateRange', $event)"
    />

    <button
      v-if="hasFilters"
      @click="$emit('update:status', ''); $emit('update:dateRange', { from: '', to: '' })"
      class="text-sm px-3 py-2 rounded-lg hover:opacity-70 active:opacity-50 transition-opacity cursor-pointer focus:outline-none focus:ring-2 focus:ring-black"
      style="color: var(--text-muted); background-color: var(--bg-input); border: 1px solid var(--border)"
      aria-label="Limpar filtros"
    >
      Limpar
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import DateRangePicker from './DateRangePicker.vue'

const props = defineProps({
  status:    String,
  dateRange: Object,
})

defineEmits(['update:status', 'update:dateRange'])

const hasFilters = computed(() =>
  props.status || props.dateRange?.from || props.dateRange?.to
)
</script>