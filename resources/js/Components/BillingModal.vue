<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
    <!-- Main Modal Container -->
    <div class="bg-white rounded-3xl border border-pink-100 shadow-glam-lg w-full max-w-lg overflow-hidden flex flex-col max-h-[92vh]">
      
      <!-- STAGE A: FORM INTAKE (Before Submission) -->
      <template v-if="!issuedReceipt">
        <!-- Header -->
        <div class="px-6 py-5 border-b border-pink-100 flex items-center justify-between bg-gradient-to-r from-pink-50/50 to-white">
          <div>
            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Record Rent Payment</h3>
            <p class="text-xs text-pink-600 font-semibold">Strict block pricing, tax, and automated dispatch</p>
          </div>
          <button
            @click="$emit('close')"
            class="w-8 h-8 rounded-full bg-pink-50 hover:bg-pink-100 text-pink-700 font-bold flex items-center justify-center transition-colors"
          >
            &times;
          </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submitPayment" novalidate class="p-6 overflow-y-auto space-y-4 text-xs">
          <!-- 1. Select Resident -->
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Select Resident *</label>
            <select
              v-model="form.tenant_id"
              @change="handleTenantSelect"
              :class="[
                errors.tenant_id ? 'border-red-400 bg-red-50/20 ring-1 ring-red-300' : 'border-pink-100 focus:border-pink-300',
                'w-full px-3.5 py-2.5 rounded-xl border outline-none bg-white font-medium'
              ]"
            >
              <option value="" disabled>-- Choose resident --</option>
              <option
                v-for="t in activeTenants"
                :key="t.id"
                :value="t.id"
              >
                {{ t.full_name }} &bull; {{ t.room ? t.room.block?.name + ' - Rm ' + t.room.room_number : 'No Room' }}
              </option>
            </select>
            <p v-if="errors.tenant_id" class="mt-1 text-[11px] font-semibold text-rose-600">{{ errors.tenant_id }}</p>
          </div>

          <!-- Resident Quick Snapshot (Block A / B rate, next due date, previous balance) -->
          <div v-if="selectedTenant" class="p-3.5 bg-pink-50/50 border border-pink-100 rounded-2xl flex items-center justify-between">
            <div>
              <div class="font-bold text-slate-900 text-xs">{{ selectedTenant.full_name }}</div>
              <div class="text-[11px] text-pink-600 font-medium">
                {{ selectedTenant.room ? selectedTenant.room.block?.name + ' &bull; Room ' + selectedTenant.room.room_number : 'Unit' }}
              </div>
              <div class="text-[10px] text-slate-500 mt-0.5">
                Current Due Date: <strong>{{ formatDueDate(selectedTenant.rent_due_date) }}</strong>
              </div>
            </div>
            <div class="text-right">
              <span class="text-[10px] uppercase font-bold text-slate-400 block">Default Rent</span>
              <span class="font-mono font-extrabold text-pink-700 text-sm">
                {{ formatUGX(selectedTenantBaseRate) }}
              </span>
            </div>
          </div>

          <!-- Previous Arrears Banner & Carryover Toggle -->
          <div
            v-if="selectedTenant && previousArrearsBalance > 0"
            class="p-3 bg-amber-50/80 border border-amber-200 rounded-2xl space-y-1.5"
          >
            <div class="flex items-center justify-between">
              <span class="font-bold text-amber-900 text-xs flex items-center space-x-1.5">
                <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                <span>Previous Unsettled Arrears</span>
              </span>
              <span class="font-mono font-bold text-amber-900">{{ formatUGX(previousArrearsBalance) }}</span>
            </div>
            <div class="flex items-center space-x-2 pt-1 text-[11px] text-amber-800">
              <input
                id="includeArrears"
                v-model="form.include_previous_balance"
                type="checkbox"
                class="rounded text-pink-600 focus:ring-pink-300 w-3.5 h-3.5"
              />
              <label for="includeArrears" class="cursor-pointer">
                Include previous balance in this invoice total
              </label>
            </div>
          </div>

          <!-- Quick Preset Buttons -->
          <div v-if="selectedTenant" class="space-y-1.5">
            <span class="text-[11px] font-semibold text-slate-600 block">Payment Presets:</span>
            <div class="grid grid-cols-3 gap-2">
              <button
                type="button"
                @click="setPaymentPreset('full')"
                :class="[
                  paymentType === 'full'
                    ? 'bg-pink-600 text-white font-bold'
                    : 'bg-pink-50 text-pink-700 border border-pink-200 hover:bg-pink-100',
                  'py-2 px-2 rounded-xl text-center text-[11px] transition-all'
                ]"
              >
                Full Invoice
              </button>
              <button
                type="button"
                @click="setPaymentPreset('half')"
                :class="[
                  paymentType === 'half'
                    ? 'bg-pink-600 text-white font-bold'
                    : 'bg-pink-50 text-pink-700 border border-pink-200 hover:bg-pink-100',
                  'py-2 px-2 rounded-xl text-center text-[11px] transition-all'
                ]"
              >
                50% Partial
              </button>
              <button
                type="button"
                @click="setPaymentPreset('custom')"
                :class="[
                  paymentType === 'custom'
                    ? 'bg-pink-600 text-white font-bold'
                    : 'bg-pink-50 text-pink-700 border border-pink-200 hover:bg-pink-100',
                  'py-2 px-2 rounded-xl text-center text-[11px] transition-all'
                ]"
              >
                Custom / Advance
              </button>
            </div>
          </div>

          <!-- Base Rent, Optional Tax, and Amount Paid -->
          <div class="space-y-3">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label for="base_rent_charged" class="block font-semibold text-slate-700 mb-1">
                  Total Rent Charged (UGX) *
                </label>
                <input
                  id="base_rent_charged"
                  v-model.number="form.base_rent_charged"
                  required
                  type="number"
                  min="0"
                  step="any"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-pink-200 focus:border-pink-400 font-mono text-slate-800 outline-none"
                />
              </div>
              <div>
                <label for="tax_amount" class="block font-semibold text-slate-700 mb-1">
                  Tax / Surcharge (UGX) <span class="text-slate-400 font-normal">(Optional)</span>
                </label>
                <input
                  id="tax_amount"
                  v-model.number="form.tax_amount"
                  type="number"
                  min="0"
                  step="any"
                  placeholder="0"
                  class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 font-mono text-slate-800 outline-none"
                />
              </div>
            </div>

            <!-- Invoice Breakdown Display -->
            <div class="p-3.5 bg-gradient-to-r from-pink-50/70 to-rose-50/50 rounded-2xl border border-pink-200/80 space-y-2">
              <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center justify-between">
                <span>Invoice Breakdown</span>
                <span class="text-pink-600 font-semibold">{{ selectedTenant?.room ? selectedTenant.room.block?.name + ' - Room ' + selectedTenant.room.room_number : '' }}</span>
              </div>
              <div class="space-y-1.5 text-xs">
                <div class="flex justify-between items-center text-slate-600">
                  <span>Base Rent:</span>
                  <span class="font-mono font-bold text-slate-900">{{ formatUGX(form.base_rent_charged) }}</span>
                </div>
                <div v-if="form.tax_amount > 0" class="flex justify-between items-center text-slate-600">
                  <span>Tax Added (Optional):</span>
                  <span class="font-mono font-bold text-pink-700">+ {{ formatUGX(form.tax_amount) }}</span>
                </div>
                <div v-if="form.include_previous_balance && previousArrearsBalance > 0" class="flex justify-between items-center text-amber-800">
                  <span>Previous Arrears:</span>
                  <span class="font-mono font-bold text-amber-900">+ {{ formatUGX(previousArrearsBalance) }}</span>
                </div>
                <div class="pt-1.5 border-t border-pink-200/60 flex justify-between items-center">
                  <span class="font-extrabold text-slate-800">Total Invoice Due:</span>
                  <span class="font-mono font-black text-pink-700 text-sm">{{ formatUGX(totalInvoiceDue) }}</span>
                </div>
              </div>
            </div>

            <!-- Amount Paying Now Input -->
            <div class="p-3.5 bg-white rounded-2xl border border-pink-200 shadow-xs">
              <label for="amount_paid" class="block font-bold text-pink-700 text-xs mb-1.5">Amount Paying Now (UGX) *</label>
              <input
                id="amount_paid"
                v-model.number="form.amount_received"
                type="number"
                min="0"
                step="any"
                placeholder="Enter amount paid"
                :class="[
                  errors.amount_received
                    ? 'border-red-400 bg-red-50/20 ring-1 ring-red-300'
                    : 'border-pink-300 focus:border-pink-500',
                  'w-full px-3.5 py-2.5 rounded-xl border font-mono font-extrabold text-slate-900 outline-none text-base bg-white transition-all shadow-inner'
                ]"
                @input="delete errors.amount_received"
              />
              <p v-if="errors.amount_received" class="mt-1.5 text-[11px] font-semibold text-rose-600 flex items-center space-x-1">
                <span>&bull;</span>
                <span>{{ errors.amount_received }}</span>
              </p>
            </div>
          </div>

          <!-- Real-Time Reactive Balance & Payment Status Badge -->
          <div
            :class="[
              liveRemainingBalance > 0
                ? 'bg-rose-50/90 border-rose-200 text-rose-900'
                : liveCreditAmount > 0
                ? 'bg-indigo-50/90 border-indigo-200 text-indigo-900'
                : 'bg-emerald-50/90 border-emerald-200 text-emerald-900',
              'p-3.5 rounded-2xl border flex items-center justify-between transition-colors'
            ]"
          >
            <div>
              <div class="flex items-center space-x-2">
                <span
                  v-if="liveRemainingBalance > 0"
                  class="px-2.5 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-rose-200/80 text-rose-800 border border-rose-300 flex items-center space-x-1"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-pulse mr-1"></span>
                  <span>Partial Payment - Balance Due: {{ formatUGX(liveRemainingBalance) }}</span>
                </span>
                <span
                  v-else-if="liveCreditAmount > 0"
                  class="px-2.5 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-indigo-200/80 text-indigo-800 border border-indigo-300"
                >
                  Overpaid (Credit: {{ formatUGX(liveCreditAmount) }})
                </span>
                <span
                  v-else
                  class="px-2.5 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-emerald-200/80 text-emerald-800 border border-emerald-300 flex items-center space-x-1"
                >
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 mr-1"></span>
                  <span>Full Settlement (0 UGX Balance)</span>
                </span>
              </div>
              <div class="text-[10px] opacity-80 mt-1">
                {{ liveRemainingBalance > 0 ? 'Remaining deficit carries over to tenant record as active debt.' : 'Full invoice settled. No active balance.' }}
              </div>
            </div>
            <div class="text-right">
              <span class="text-[10px] uppercase font-bold opacity-75 block">
                {{ liveRemainingBalance > 0 ? 'Balance Remaining' : liveCreditAmount > 0 ? 'Excess Credit' : 'Balance Settled' }}
              </span>
              <span class="text-sm font-black font-mono">
                {{ formatUGX(liveRemainingBalance > 0 ? liveRemainingBalance : liveCreditAmount) }}
              </span>
            </div>
          </div>

          <!-- Payment Date & Method -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Payment Date (Date Paid) *</label>
              <input
                v-model="form.payment_date"
                type="date"
                :class="[
                  errors.payment_date
                    ? 'border-red-400 bg-red-50/20 ring-1 ring-red-300'
                    : 'border-pink-100 focus:border-pink-300',
                  'w-full px-3.5 py-2 rounded-xl border outline-none font-medium text-slate-800 transition-all'
                ]"
                @input="delete errors.payment_date"
              />
              <p v-if="errors.payment_date" class="mt-1 text-[11px] font-semibold text-rose-600">
                {{ errors.payment_date }}
              </p>
            </div>

            <div>
              <label class="block font-semibold text-slate-700 mb-1">Payment Mode *</label>
              <select
                v-model="form.payment_method"
                :class="[
                  errors.payment_method
                    ? 'border-red-400 bg-red-50/20 ring-1 ring-red-300'
                    : 'border-pink-100 focus:border-pink-300',
                  'w-full px-3.5 py-2 rounded-xl border outline-none bg-white font-medium text-slate-800 transition-all'
                ]"
                @change="delete errors.payment_method"
              >
                <option value="mobile_money">Mobile Money (MTN / Airtel)</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cash">Direct Cash</option>
              </select>
              <p v-if="errors.payment_method" class="mt-1 text-[11px] font-semibold text-rose-600">
                {{ errors.payment_method }}
              </p>
            </div>
          </div>

          <!-- Dual Receipt & Notification Delivery Checkboxes -->
          <div class="p-3.5 bg-white border border-pink-100 rounded-2xl space-y-2.5">
            <span class="font-bold text-slate-800 block text-xs">Receipt Delivery &amp; Notifications</span>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
              <!-- Send Receipt via Email -->
              <label class="flex items-start space-x-2.5 text-xs text-slate-700 cursor-pointer p-2 rounded-xl hover:bg-pink-50/50 transition-colors border border-transparent hover:border-pink-100">
                <input
                  type="checkbox"
                  value="email"
                  v-model="form.dispatch_channels"
                  :disabled="downloadOnly"
                  class="rounded text-pink-600 focus:ring-pink-300 w-4 h-4 mt-0.5"
                />
                <div class="min-w-0">
                  <span class="font-semibold block text-slate-800">Send Receipt via Email</span>
                  <span class="text-[10px] text-slate-500 truncate block">{{ selectedTenant?.email || 'Registered Tenant Email' }}</span>
                </div>
              </label>

              <!-- Send Receipt via WhatsApp -->
              <label class="flex items-start space-x-2.5 text-xs text-slate-700 cursor-pointer p-2 rounded-xl hover:bg-pink-50/50 transition-colors border border-transparent hover:border-pink-100">
                <input
                  type="checkbox"
                  value="whatsapp"
                  v-model="form.dispatch_channels"
                  :disabled="downloadOnly"
                  class="rounded text-pink-600 focus:ring-pink-300 w-4 h-4 mt-0.5"
                />
                <div class="min-w-0">
                  <span class="font-semibold block text-slate-800">Send Receipt via WhatsApp</span>
                  <span class="text-[10px] text-slate-500 font-mono block">{{ selectedTenant?.whatsapp_number || 'Registered WhatsApp Phone' }}</span>
                </div>
              </label>
            </div>

            <!-- Download Only Checkbox -->
            <div class="pt-1.5 border-t border-pink-50 flex items-center space-x-2 text-[11px] text-slate-500">
              <input
                id="downloadOnly"
                type="checkbox"
                v-model="downloadOnly"
                @change="handleDownloadOnlyChange"
                class="rounded text-pink-600 focus:ring-pink-300 w-3.5 h-3.5"
              />
              <label for="downloadOnly" class="cursor-pointer">
                Download / Print PDF only (do not send online dispatches)
              </label>
            </div>
          </div>

          <!-- Advance Next Due Date Option -->
          <div class="flex items-center space-x-2 pt-1">
            <input
              id="advanceDue"
              v-model="form.advance_due_date"
              type="checkbox"
              class="rounded text-pink-600 focus:ring-pink-300 w-4 h-4"
            />
            <label for="advanceDue" class="font-medium text-slate-700 cursor-pointer">
              Advance next lease due date by +30 days
            </label>
          </div>

          <!-- Reference Note -->
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Transaction Ref / Note</label>
            <input
              v-model="form.notes"
              type="text"
              placeholder="e.g. MTN MoMo Ref # or Advance Payment"
              class="w-full px-3.5 py-2 rounded-xl border border-pink-100 focus:border-pink-300 outline-none"
            />
          </div>

          <!-- Action Buttons -->
          <div class="pt-3 border-t border-pink-100 flex items-center justify-end space-x-2">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50 transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="submitting"
              class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-pink-500 via-rose-500 to-pink-600 hover:from-pink-600 hover:to-rose-600 text-white font-bold shadow-glam hover:shadow-glam-lg transition-all active:scale-95 disabled:opacity-50 flex items-center space-x-2"
            >
              <Loader2 v-if="submitting" class="w-4 h-4 animate-spin shrink-0" />
              <span>{{ submitting ? 'Processing Payment...' : 'Confirm & Process Payment' }}</span>
            </button>
          </div>
        </form>
      </template>

      <!-- STAGE B: RECEIPT READY MODAL (Immediately after recording) -->
      <template v-else>
        <div class="p-8 text-center space-y-5">
          <!-- Big Checkmark Ring -->
          <div class="w-16 h-16 rounded-full bg-pink-50 border-2 border-pink-300 text-pink-600 flex items-center justify-center mx-auto text-2xl font-bold shadow-glam">
            &check;
          </div>

          <div>
            <h3 class="text-lg font-black text-slate-900 tracking-tight">Payment Recorded &amp; Receipt Issued!</h3>
            <p class="text-xs text-slate-500 mt-1">Vector PDF generated and automated dispatches initiated</p>
          </div>

          <!-- Receipt Details Card -->
          <div class="bg-pink-50/50 border border-pink-200/80 rounded-2xl p-4 text-left space-y-2 text-xs">
            <div class="flex justify-between">
              <span class="text-slate-500">Receipt Serial:</span>
              <span class="font-mono font-bold text-slate-900">{{ issuedReceipt.receipt_serial_no }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Resident:</span>
              <span class="font-bold text-slate-800">{{ issuedReceipt.tenant?.full_name || selectedTenant?.full_name }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Total Invoice Due:</span>
              <span class="font-mono font-bold text-slate-800">{{ formatUGX(issuedReceipt.total_due) }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Amount Paid:</span>
              <span class="font-mono font-bold text-pink-700 text-sm">{{ formatUGX(issuedReceipt.amount_received) }}</span>
            </div>
            <div class="flex justify-between pt-1 border-t border-pink-100">
              <span class="font-bold text-slate-700">Remaining Balance:</span>
              <span
                :class="[
                  issuedReceipt.balance_remaining > 0 ? 'text-rose-600' : 'text-emerald-600',
                  'font-mono font-bold text-sm'
                ]"
              >
                {{ formatUGX(issuedReceipt.balance_remaining) }}
                <span class="text-[10px] font-normal">({{ issuedReceipt.payment_status?.toUpperCase() || (issuedReceipt.balance_remaining > 0 ? 'PARTIAL' : 'PAID') }})</span>
              </span>
            </div>
          </div>

          <!-- Primary Actions (Open / Print PDF) -->
          <div class="space-y-2 pt-2">
            <a
              :href="`/payments/${issuedReceipt.id}/receipt`"
              target="_blank"
              class="block w-full py-3 bg-gradient-to-r from-pink-500 via-rose-500 to-pink-600 hover:from-pink-600 hover:to-rose-600 text-white font-bold text-xs rounded-xl shadow-glam transition-all active:scale-95 text-center"
            >
              Open &amp; Print Official PDF Receipt &rarr;
            </a>

            <button
              type="button"
              @click="closeAndReset"
              class="block w-full py-2.5 bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 font-semibold text-xs rounded-xl transition-colors"
            >
              Done / Close
            </button>
          </div>
        </div>
      </template>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';

const props = defineProps({
  show: Boolean,
  activeTenants: Array,
  preselectedTenantId: [Number, String],
});

const emit = defineEmits(['close']);

const submitting = ref(false);
const errors = ref({});
const issuedReceipt = ref(null);
const paymentType = ref('full');
const downloadOnly = ref(false);

const form = ref({
  tenant_id: '',
  amount_received: 420000,
  base_rent_charged: 420000,
  tax_amount: 0,
  include_previous_balance: true,
  payment_method: 'mobile_money',
  payment_date: new Date().toISOString().split('T')[0],
  advance_due_date: true,
  notes: '',
  dispatch_channels: ['whatsapp', 'email'],
});

const selectedTenant = computed(() => {
  return props.activeTenants?.find(t => t.id === form.value.tenant_id);
});

// Strict Block pricing defaults: Block A = 420k, Block B = 1M
const selectedTenantBaseRate = computed(() => {
  const tenant = selectedTenant.value;
  if (!tenant || !tenant.room) return 420000;
  if (tenant.room.custom_price && Number(tenant.room.custom_price) > 0) {
    return Number(tenant.room.custom_price);
  }
  if (tenant.room.effective_price && Number(tenant.room.effective_price) > 0) {
    return Number(tenant.room.effective_price);
  }
  const blockName = (tenant.room.block?.name || '').toLowerCase();
  const blockCode = (tenant.room.block?.code || '').toUpperCase();
  if (blockName.includes('b') || blockName.includes('2') || blockCode === 'B' || blockCode === 'B2') {
    return 1000000;
  }
  return 420000;
});

const previousArrearsBalance = computed(() => {
  const tenant = selectedTenant.value;
  if (!tenant) return 0;
  return Math.max(0, Number(tenant.current_balance ?? tenant.outstanding_balance ?? 0));
});

const totalInvoiceDue = computed(() => {
  const rent = Number(form.value.base_rent_charged || 0);
  const tax = Number(form.value.tax_amount || 0);
  const arrears = form.value.include_previous_balance ? previousArrearsBalance.value : 0;
  return rent + tax + arrears;
});

const liveRemainingBalance = computed(() => {
  const total = totalInvoiceDue.value;
  const paid = Number(form.value.amount_received || 0);
  return Math.max(0, total - paid);
});

const liveCreditAmount = computed(() => {
  const total = totalInvoiceDue.value;
  const paid = Number(form.value.amount_received || 0);
  return paid > total ? paid - total : 0;
});

watch(
  () => props.preselectedTenantId,
  (newVal) => {
    if (newVal) {
      form.value.tenant_id = newVal;
      handleTenantSelect();
    }
  },
  { immediate: true }
);

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      issuedReceipt.value = null;
      if (props.preselectedTenantId) {
        form.value.tenant_id = props.preselectedTenantId;
        handleTenantSelect();
      }
    }
  }
);

function handleTenantSelect() {
  const rate = selectedTenantBaseRate.value;
  form.value.base_rent_charged = rate;
  form.value.tax_amount = 0;
  form.value.include_previous_balance = true;
  paymentType.value = 'full';
  form.value.amount_received = totalInvoiceDue.value;
}

watch(totalInvoiceDue, (newTotal) => {
  if (paymentType.value === 'full') {
    form.value.amount_received = newTotal;
  } else if (paymentType.value === 'half') {
    form.value.amount_received = Math.round(newTotal / 2);
  }
});

function handleDownloadOnlyChange() {
  if (downloadOnly.value) {
    form.value.dispatch_channels = [];
  } else {
    form.value.dispatch_channels = ['whatsapp', 'email'];
  }
}

function setPaymentPreset(type) {
  paymentType.value = type;
  const total = totalInvoiceDue.value;

  if (type === 'full') {
    form.value.amount_received = total;
    form.value.advance_due_date = true;
  } else if (type === 'half') {
    form.value.amount_received = Math.round(total / 2);
    form.value.advance_due_date = false;
  } else if (type === 'custom') {
    // Keep user's custom typed amount
  }
}

function formatDueDate(dateVal) {
  if (!dateVal) return 'N/A';
  const d = new Date(dateVal);
  if (isNaN(d.getTime())) return String(dateVal);
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function formatUGX(val) {
  if (!val && val !== 0) return '0 UGX';
  return Number(val).toLocaleString() + ' UGX';
}

function submitPayment() {
  errors.value = {};

  if (!form.value.tenant_id) {
    errors.value.tenant_id = 'Please select a resident.';
  }
  if (
    form.value.amount_received === null ||
    form.value.amount_received === undefined ||
    form.value.amount_received === '' ||
    Number(form.value.amount_received) < 0
  ) {
    errors.value.amount_received = 'Amount paid is required.';
  }
  if (!form.value.payment_date) {
    errors.value.payment_date = 'Payment date is required.';
  }
  if (!form.value.payment_method) {
    errors.value.payment_method = 'Please select a payment mode.';
  }

  if (Object.keys(errors.value).length > 0) {
    return;
  }

  submitting.value = true;
  router.post('/payments', form.value, {
    onSuccess: (page) => {
      const newPay = page.props.flash?.newPayment;
      if (newPay) {
        issuedReceipt.value = newPay;
        window.open(`/payments/${newPay.id}/receipt`, '_blank');
      } else {
        emit('close');
      }
    },
    onError: (errs) => {
      errors.value = errs || {};
    },
    onFinish: () => {
      submitting.value = false;
    }
  });
}

function closeAndReset() {
  issuedReceipt.value = null;
  emit('close');
}
</script>
