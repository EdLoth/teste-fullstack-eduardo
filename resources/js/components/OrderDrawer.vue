<template>
  <Teleport to="body">
    <Transition name="overlay">
      <div
        v-if="visible"
        class="fixed inset-0 z-50 flex justify-end"
      >
        <!-- Overlay -->
        <div
          class="absolute inset-0"
          style="background-color: rgba(0,0,0,0.25)"
          @click="close"
        ></div>

        <!-- Painel -->
        <Transition name="drawer">
          <div
            v-if="visible"
            class="relative w-full max-w-md h-full overflow-y-auto p-6 shadow-2xl flex flex-col gap-6"
            style="background-color: var(--bg-card); border-left: 1px solid var(--border)"
          >
            <!-- Header -->
            <div class="flex items-center justify-between">
              <div>
                <p class="text-xs font-medium uppercase tracking-wider mb-1" style="color: var(--text-muted)">Pedido</p>
                <h2 class="text-xl font-semibold" style="color: var(--text-main)">#{{ order.id }}</h2>
              </div>
              <button
                @click="close"
                class="w-9 h-9 rounded-lg flex items-center justify-center hover:opacity-70 transition-opacity focus:outline-none focus:ring-2 focus:ring-black"
                style="background-color: var(--bg-input); border: 1px solid var(--border); color: var(--text-main)"
                aria-label="Fechar painel"
              >✕</button>
            </div>

            <!-- Detalhes -->
            <div class="rounded-xl p-4 grid grid-cols-2 gap-4" style="background-color: var(--bg-input); border: 1px solid var(--border)">
              <div>
                <p class="text-xs font-medium uppercase tracking-wider mb-1" style="color: var(--text-muted)">Afiliado</p>
                <p class="text-sm font-medium" style="color: var(--text-main)">{{ order.affiliate?.name ?? '—' }}</p>
              </div>
              <div>
                <p class="text-xs font-medium uppercase tracking-wider mb-1" style="color: var(--text-muted)">Status</p>
                <span :class="statusClass(currentStatus)" class="px-2.5 py-1 rounded-full text-xs font-medium">
                  {{ statusLabel(currentStatus) }}
                </span>
              </div>
              <div>
                <p class="text-xs font-medium uppercase tracking-wider mb-1" style="color: var(--text-muted)">Total</p>
                <p class="text-sm font-semibold" style="color: var(--text-main)">{{ formatCurrency(order.total) }}</p>
              </div>
              <div>
                <p class="text-xs font-medium uppercase tracking-wider mb-1" style="color: var(--text-muted)">Data</p>
                <p class="text-sm" style="color: var(--text-main)">{{ formatDate(order.created_at) }}</p>
              </div>
            </div>

            <!-- Itens -->
            <div>
              <p class="text-xs font-semibold uppercase tracking-wider mb-3" style="color: var(--text-muted)">Itens do pedido</p>
              <div class="space-y-2">
                <div v-if="loading" v-for="i in 3" :key="i" class="h-12 rounded-lg animate-pulse" style="background-color: var(--bg-input)"></div>
                <div
                  v-else
                  v-for="item in orderDetails?.items" :key="item.id"
                  class="rounded-lg p-3 flex justify-between items-center text-sm"
                  style="background-color: var(--bg-input); border: 1px solid var(--border)"
                >
                  <span class="font-medium truncate pr-4" style="color: var(--text-main)">{{ item.product?.title ?? 'Produto' }}</span>
                  <span class="text-xs whitespace-nowrap" style="color: var(--text-muted)">{{ item.quantity }}x {{ formatCurrency(item.price) }}</span>
                </div>
              </div>
            </div>

            <!-- Timeline -->
            <div>
              <p class="text-xs font-semibold uppercase tracking-wider mb-3" style="color: var(--text-muted)">Histórico</p>
              <div class="relative pl-5 border-l-2 space-y-4" style="border-color: var(--border)">
                <div v-for="log in orderDetails?.status_logs" :key="log.id" class="relative">
                  <div class="absolute -left-[1.35rem] top-1 w-3 h-3 rounded-full border-2 bg-black" style="border-color: var(--bg-card)"></div>
                  <p class="text-xs mb-0.5" style="color: var(--text-muted)">{{ formatDate(log.created_at) }}</p>
                  <p class="text-sm font-medium" style="color: var(--text-main)">
                    <span v-if="log.previous_status" style="color: var(--text-muted)">{{ statusLabel(log.previous_status) }} → </span>
                    {{ statusLabel(log.new_status) }}
                  </p>
                  <p v-if="log.reason" class="text-xs mt-0.5" style="color: var(--text-muted)">{{ log.reason }}</p>
                </div>
              </div>
            </div>

            <!-- Atualizar status -->
            <div class="rounded-xl p-4" style="background-color: var(--bg-input); border: 1px solid var(--border)">
              <p class="text-xs font-semibold uppercase tracking-wider mb-3" style="color: var(--text-muted)">Atualizar status</p>
              <div v-if="validTransitions.length === 0" class="text-sm" style="color: var(--text-muted)">
                Nenhuma transição disponível para este status.
              </div>
              <div v-else class="flex gap-2">
                <select
                  v-model="newStatus"
                  class="flex-1 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black"
                  style="background-color: var(--bg-card); border: 1px solid var(--border); color: var(--text-main)"
                  aria-label="Selecionar novo status"
                >
                  <option value="">Selecione...</option>
                  <option v-for="t in validTransitions" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
                <button
                  @click="updateStatus"
                  :disabled="!newStatus || updating"
                  class="bg-black text-white text-sm px-4 py-2 rounded-lg hover:opacity-80 disabled:opacity-40 transition-opacity focus:outline-none focus:ring-2 focus:ring-black"
                  aria-label="Confirmar mudança de status"
                >{{ updating ? '...' : 'Confirmar' }}</button>
              </div>
              <p v-if="error" class="text-red-500 text-xs mt-2">⚠️ {{ error }}</p>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'

const props = defineProps({ order: Object })
const emit  = defineEmits(['close', 'updated'])

const visible      = ref(false)
const orderDetails = ref(null)
const newStatus    = ref('')
const updating     = ref(false)
const error        = ref('')
const loading      = ref(true)
const currentStatus = ref(props.order.status)

const TRANSITIONS = {
  pending:  [
    { value: 'approved',  label: '✅ Aprovar'    },
    { value: 'cancelled', label: '❌ Cancelar'   },
  ],
  approved: [
    { value: 'refunded',  label: '↩️ Reembolsar' },
  ],
}

const validTransitions = computed(() => TRANSITIONS[currentStatus.value] ?? [])

function close() {
  visible.value = false
  setTimeout(() => emit('close'), 300)
}

async function fetchDetails() {
  loading.value = true
  const res  = await fetch(`/api/orders/${props.order.id}`)
  const json = await res.json()
  orderDetails.value  = json.data
  currentStatus.value = json.data?.status ?? props.order.status
  loading.value = false
}

async function updateStatus() {
  if (!newStatus.value) return
  updating.value = true
  error.value    = ''
  const res  = await fetch(`/api/orders/${props.order.id}/status`, {
    method:  'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body:    JSON.stringify({ status: newStatus.value }),
  })
  const json = await res.json()
  if (!res.ok) {
    error.value = json.errors?.[0] ?? 'Erro ao atualizar status.'
  } else {
    emit('updated')
    close()
  }
  updating.value = false
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

function handleEsc(e) {
  if (e.key === 'Escape') close()
}

onMounted(() => {
  fetchDetails()
  setTimeout(() => { visible.value = true }, 10)
  document.addEventListener('keydown', handleEsc)
})

onUnmounted(() => {
  document.removeEventListener('keydown', handleEsc)
})
</script>

<style scoped>
/* Overlay */
.overlay-enter-active, .overlay-leave-active { transition: opacity 0.25s ease; }
.overlay-enter-from, .overlay-leave-to       { opacity: 0; }

/* Drawer desliza da direita */
.drawer-enter-active { transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.drawer-leave-active { transition: transform 0.25s ease-in; }
.drawer-enter-from   { transform: translateX(100%); }
.drawer-leave-to     { transform: translateX(100%); }
</style>