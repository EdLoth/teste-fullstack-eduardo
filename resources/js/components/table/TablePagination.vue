<template>
  <div
    class="px-4 py-3 flex items-center justify-between text-sm"
    style="border-top: 1px solid var(--border)"
  >
    <span style="color: var(--text-muted)">
      {{ meta.total ?? 0 }} pedidos no total
    </span>

    <div class="flex items-center gap-1">
      <!-- Primeira página -->
      <button
        @click="$emit('changePage', 1)"
        :disabled="meta.current_page <= 1"
        class="w-8 h-8 rounded-lg flex items-center justify-center text-xs disabled:opacity-30 disabled:cursor-not-allowed hover:opacity-70 active:opacity-50 transition-opacity cursor-pointer focus:outline-none focus:ring-2 focus:ring-black"
        style="background-color: var(--bg-input); border: 1px solid var(--border); color: var(--text-main)"
        aria-label="Primeira página"
      >«</button>

      <!-- Anterior -->
      <button
        @click="$emit('changePage', meta.current_page - 1)"
        :disabled="meta.current_page <= 1"
        class="w-8 h-8 rounded-lg flex items-center justify-center text-xs disabled:opacity-30 disabled:cursor-not-allowed hover:opacity-70 active:opacity-50 transition-opacity cursor-pointer focus:outline-none focus:ring-2 focus:ring-black"
        style="background-color: var(--bg-input); border: 1px solid var(--border); color: var(--text-main)"
        aria-label="Página anterior"
      >‹</button>

      <!-- Páginas -->
      <div class="flex items-center gap-1">
        <button
          v-for="page in visiblePages"
          :key="page"
          @click="page !== '...' && $emit('changePage', page)"
          :disabled="page === '...'"
          :class="[
            'w-8 h-8 rounded-lg flex items-center justify-center text-xs transition-colors focus:outline-none focus:ring-2 focus:ring-black',
            page === meta.current_page
              ? 'bg-black text-white'
              : 'hover:opacity-70'
          ]"
          :style="page !== meta.current_page ? `background-color: var(--bg-input); border: 1px solid var(--border); color: var(--text-main)` : ''"
          :aria-label="page === '...' ? 'Mais páginas' : `Ir para página ${page}`"
          :aria-current="page === meta.current_page ? 'page' : undefined"
        >{{ page }}</button>
      </div>

      <!-- Próxima -->
      <button
        @click="$emit('changePage', meta.current_page + 1)"
        :disabled="meta.current_page >= meta.last_page"
        class="w-8 h-8 rounded-lg flex items-center justify-center text-xs disabled:opacity-30 disabled:cursor-not-allowed hover:opacity-70 active:opacity-50 transition-opacity cursor-pointer focus:outline-none focus:ring-2 focus:ring-black"
        style="background-color: var(--bg-input); border: 1px solid var(--border); color: var(--text-main)"
        aria-label="Próxima página"
      >›</button>

      <!-- Última -->
      <button
        @click="$emit('changePage', meta.last_page)"
        :disabled="meta.current_page >= meta.last_page"
        class="w-8 h-8 rounded-lg flex items-center justify-center text-xs disabled:opacity-30 disabled:cursor-not-allowed hover:opacity-70 active:opacity-50 transition-opacity cursor-pointer focus:outline-none focus:ring-2 focus:ring-black"
        style="background-color: var(--bg-input); border: 1px solid var(--border); color: var(--text-main)"
        aria-label="Última página"
      >»</button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  meta: Object,
})

defineEmits(['changePage'])

const visiblePages = computed(() => {
  const total   = props.meta.last_page ?? 1
  const current = props.meta.current_page ?? 1
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1)

  const pages = []
  if (current <= 4) {
    pages.push(1, 2, 3, 4, 5, '...', total)
  } else if (current >= total - 3) {
    pages.push(1, '...', total - 4, total - 3, total - 2, total - 1, total)
  } else {
    pages.push(1, '...', current - 1, current, current + 1, '...', total)
  }
  return pages
})
</script>