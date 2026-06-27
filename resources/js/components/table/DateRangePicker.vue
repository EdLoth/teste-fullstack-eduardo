<template>
  <div class="relative" ref="pickerRef">
    <button
      @click="open = !open"
      class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm transition-opacity hover:opacity-80 active:opacity-60 cursor-pointer focus:outline-none focus:ring-2 focus:ring-black"
      style="background-color: var(--bg-input); border: 1px solid var(--border); color: var(--text-main)"
      aria-label="Selecionar período"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" style="color: var(--text-muted)">
        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      <span class="text-sm" style="color: var(--text-muted)">{{ label }}</span>
    </button>

    <div
      v-if="open"
      class="absolute top-full mt-2 z-50 rounded-xl shadow-xl p-4 w-[620px] right-0"
      style="background-color: var(--bg-card); border: 1px solid var(--border)"
    >
      <div class="flex gap-6">
        <div class="flex-1">
          <div class="flex items-center justify-between mb-4">
            <button
              @click="prevMonth"
              class="w-7 h-7 rounded-lg flex items-center justify-center hover:opacity-70 active:opacity-50 cursor-pointer transition-opacity focus:outline-none focus:ring-2 focus:ring-black"
              style="background-color: var(--bg-input); color: var(--text-main)"
              aria-label="Mês anterior"
            >‹</button>
            <span class="text-sm font-semibold" style="color: var(--text-main)">{{ monthName(currentMonth) }}</span>
            <div class="w-7"></div>
          </div>
          <CalendarGrid
            :year="currentMonth.year"
            :month="currentMonth.month"
            :start="start"
            :end="end"
            :hovered="hovered"
            @select="selectDate"
            @hover="hovered = $event"
          />
        </div>

        <div class="flex-1">
          <div class="flex items-center justify-between mb-4">
            <div class="w-7"></div>
            <span class="text-sm font-semibold" style="color: var(--text-main)">{{ monthName(nextMonth) }}</span>
            <button
              @click="nextMonthFn"
              class="w-7 h-7 rounded-lg flex items-center justify-center hover:opacity-70 active:opacity-50 cursor-pointer transition-opacity focus:outline-none focus:ring-2 focus:ring-black"
              style="background-color: var(--bg-input); color: var(--text-main)"
              aria-label="Próximo mês"
            >›</button>
          </div>
          <CalendarGrid
            :year="nextMonth.year"
            :month="nextMonth.month"
            :start="start"
            :end="end"
            :hovered="hovered"
            @select="selectDate"
            @hover="hovered = $event"
          />
        </div>
      </div>

      <div class="flex items-center justify-between mt-4 pt-4" style="border-top: 1px solid var(--border)">
        <button
          @click="clear"
          class="text-sm hover:opacity-70 active:opacity-50 transition-opacity cursor-pointer focus:outline-none focus:ring-2 focus:ring-black rounded px-2 py-1"
          style="color: var(--text-muted)"
        >Limpar</button>
        <button
          @click="apply"
          :disabled="!start"
          class="bg-black text-white text-sm px-4 py-2 rounded-lg hover:opacity-80 active:opacity-60 disabled:opacity-40 transition-opacity cursor-pointer focus:outline-none focus:ring-2 focus:ring-black"
        >Aplicar</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import CalendarGrid from './CalendarGrid.vue'

const emit = defineEmits(['update:modelValue'])
const props = defineProps({
  modelValue: { type: Object, default: () => ({ from: '', to: '' }) }
})

const open      = ref(false)
const start     = ref(props.modelValue.from || null)
const end       = ref(props.modelValue.to   || null)
const hovered   = ref(null)
const pickerRef = ref(null)

const today = new Date()
const currentMonth = ref({ year: today.getFullYear(), month: today.getMonth() })

const nextMonth = computed(() => {
  const m = currentMonth.value.month + 1
  return m > 11
    ? { year: currentMonth.value.year + 1, month: 0 }
    : { year: currentMonth.value.year, month: m }
})

const label = computed(() => {
  if (!start.value && !end.value) return 'Selecionar período'
  if (start.value && !end.value)  return formatLabel(start.value)
  return `${formatLabel(start.value)} → ${formatLabel(end.value)}`
})

function formatLabel(d) {
  if (!d) return ''
  const [y, m, day] = d.split('-')
  return `${day}/${m}/${y}`
}

function monthName({ year, month }) {
  return new Date(year, month, 1).toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' })
}

function prevMonth() {
  const m = currentMonth.value.month - 1
  currentMonth.value = m < 0
    ? { year: currentMonth.value.year - 1, month: 11 }
    : { year: currentMonth.value.year, month: m }
}

function nextMonthFn() {
  const m = currentMonth.value.month + 1
  currentMonth.value = m > 11
    ? { year: currentMonth.value.year + 1, month: 0 }
    : { year: currentMonth.value.year, month: m }
}

function selectDate(date) {
  if (!start.value || (start.value && end.value)) {
    start.value = date
    end.value   = null
  } else {
    if (date < start.value) {
      end.value   = start.value
      start.value = date
    } else {
      end.value = date
    }
  }
}

function apply() {
  emit('update:modelValue', { from: start.value, to: end.value || start.value })
  open.value = false
}

function clear() {
  start.value   = null
  end.value     = null
  hovered.value = null
  emit('update:modelValue', { from: '', to: '' })
  open.value = false
}

function handleClickOutside(e) {
  if (pickerRef.value && !pickerRef.value.contains(e.target)) {
    open.value = false
  }
}

onMounted(()  => document.addEventListener('mousedown', handleClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside))
</script>