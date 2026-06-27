<template>
  <div>
    <div class="grid grid-cols-7 mb-2">
      <div
        v-for="day in weekDays" :key="day"
        class="text-center text-xs font-medium py-1"
        style="color: var(--text-muted)"
      >{{ day }}</div>
    </div>

    <div class="grid grid-cols-7 gap-y-1">
      <div v-for="i in firstDayOfWeek" :key="`empty-${i}`"></div>

      <button
        v-for="day in daysInMonth"
        :key="day"
        @click="!isFuture(day) && $emit('select', dateStr(day))"
        @mouseenter="!isFuture(day) && $emit('hover', dateStr(day))"
        @mouseleave="$emit('hover', null)"
        :disabled="isFuture(day)"
        :class="dayClass(day)"
        class="relative h-8 w-full text-xs flex items-center justify-center transition-colors focus:outline-none focus:ring-2 focus:ring-black focus:z-10"
        :aria-label="`${day} de ${monthLabel}`"
        :aria-pressed="isStart(day) || isEnd(day)"
      >{{ day }}</button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  year:    Number,
  month:   Number,
  start:   String,
  end:     String,
  hovered: String,
})

defineEmits(['select', 'hover'])

const weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb']

const monthLabel = computed(() =>
  new Date(props.year, props.month, 1).toLocaleDateString('pt-BR', { month: 'long' })
)

const daysInMonth = computed(() =>
  new Date(props.year, props.month + 1, 0).getDate()
)

const firstDayOfWeek = computed(() =>
  new Date(props.year, props.month, 1).getDay()
)

function dateStr(day) {
  const m = String(props.month + 1).padStart(2, '0')
  const d = String(day).padStart(2, '0')
  return `${props.year}-${m}-${d}`
}

function isFuture(day) {
  return new Date(dateStr(day)) > new Date()
}

function isStart(day) { return props.start === dateStr(day) }
function isEnd(day)   { return props.end   === dateStr(day) }

function isInRange(day) {
  const d    = dateStr(day)
  const endD = props.end || props.hovered
  if (!props.start || !endD) return false
  const [a, b] = props.start < endD
    ? [props.start, endD]
    : [endD, props.start]
  return d > a && d < b
}

function dayClass(day) {
  const d       = dateStr(day)
  const start   = isStart(day)
  const end     = isEnd(day) || (props.hovered === d && !props.end)
  const inRange = isInRange(day)
  const future  = isFuture(day)

  return [
    future
      ? 'opacity-30 cursor-not-allowed'
      : 'cursor-pointer hover:opacity-80 active:opacity-60',
    start || end
      ? 'bg-black text-white rounded-full font-semibold'
      : inRange
        ? 'bg-zinc-200 dark:bg-gray-800 rounded-full text-black dark:text-white'
        : 'hover:bg-black rounded-full',
    'disabled:pointer-events-none',
  ]
}
</script>