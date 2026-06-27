<template>
  <div
    class="rounded-xl overflow-hidden"
    style="background-color: var(--bg-card); border: 1px solid var(--border)"
  >
    <TableHeader
      v-model:search="filters.search"
      v-model:status="filters.status"
      :dateRange="dateRange"
      :total="meta.total ?? 0"
      :selectedCount="selectedIds.length"
      @update:dateRange="updateDateRange"
      @bulkCancel="bulkCancel"
    />

    <TableBody
      :orders="orders"
      :loading="loading"
      :selectedIds="selectedIds"
      :allSelected="allSelected"
      :sortBy="sortBy"
      :sortDir="sortDir"
      @sort="sort"
      @toggleAll="toggleAll"
      @toggleOne="toggleOne"
      @openDrawer="openDrawer"
    />

    <TablePagination
      :meta="meta"
      @changePage="fetchOrders"
    />
  </div>

  <OrderDrawer
    v-if="drawerOrder"
    :order="drawerOrder"
    @close="drawerOrder = null"
    @updated="onStatusUpdated"
  />
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import TableHeader     from './table/TableHeader.vue'
import TableBody       from './table/TableBody.vue'
import TablePagination from './table/TablePagination.vue'
import OrderDrawer     from './OrderDrawer.vue'

const props = defineProps({
  dateRange: { type: Object, default: () => ({ from: '', to: '' }) }
})

const emit = defineEmits(['update:dateRange', 'statusUpdated'])

const orders      = ref([])
const meta        = ref({ current_page: 1, last_page: 1, total: 0 })
const loading     = ref(true)
const selectedIds = ref([])
const drawerOrder = ref(null)
const sortBy      = ref('created_at')
const sortDir     = ref('desc')

const filters = ref({ search: '', status: '' })

let debounceTimer = null

function updateDateRange(val) {
  emit('update:dateRange', val)
}

function onStatusUpdated() {
  fetchOrders()
  emit('statusUpdated')
}

watch([filters, () => props.dateRange], () => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => fetchOrders(1), 400)
}, { deep: true })

async function fetchOrders(page = 1) {
  loading.value = true
  const params  = new URLSearchParams()
  params.set('page', page)
  params.set('sort_by', sortBy.value)
  params.set('sort_dir', sortDir.value)
  if (filters.value.status)  params.set('status',    filters.value.status)
  if (filters.value.search)  params.set('search',    filters.value.search)
  if (props.dateRange?.from) params.set('date_from', props.dateRange.from)
  if (props.dateRange?.to)   params.set('date_to',   props.dateRange.to)

  window.history.replaceState({}, '', `?${params.toString()}`)

  const res  = await fetch(`/api/orders?${params.toString()}`)
  const json = await res.json()
  orders.value  = json.data
  meta.value    = json.meta
  loading.value = false
}

function sort(field) {
  if (sortBy.value === field) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value  = field
    sortDir.value = 'desc'
  }
  fetchOrders()
}

const allSelected = computed(() =>
  orders.value.length > 0 && selectedIds.value.length === orders.value.length
)

function toggleAll(e) {
  selectedIds.value = e.target.checked ? orders.value.map(o => o.id) : []
}

function toggleOne(id) {
  const idx = selectedIds.value.indexOf(id)
  if (idx === -1) selectedIds.value.push(id)
  else selectedIds.value.splice(idx, 1)
}

async function bulkCancel() {
  if (!confirm(`Cancelar ${selectedIds.value.length} pedidos?`)) return
  await Promise.all(
    selectedIds.value.map(id =>
      fetch(`/api/orders/${id}/status`, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body:    JSON.stringify({ status: 'cancelled' }),
      })
    )
  )
  selectedIds.value = []
  onStatusUpdated()
}

function openDrawer(order) { drawerOrder.value = order }

onMounted(() => {
  const params = new URLSearchParams(window.location.search)
  if (params.get('status')) filters.value.status = params.get('status')
  if (params.get('search')) filters.value.search = params.get('search')
  fetchOrders(params.get('page') ?? 1)
})
</script>