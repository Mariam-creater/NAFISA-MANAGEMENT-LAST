<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
    <div class="bg-white rounded-3xl border border-pink-100 shadow-glam-lg w-full max-w-xl overflow-hidden max-h-[90vh] flex flex-col">
      <!-- Modal Header -->
      <div class="px-6 py-5 border-b border-pink-100 flex items-center justify-between bg-gradient-to-r from-pink-50/50 to-white">
        <div>
          <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Rent Collection &amp; Receipt Generator</h3>
          <p class="text-xs text-pink-600 font-semibold">Immediate PDF creation &amp; multi-channel dispatch</p>
        </div>
        <button
          @click="$emit('close')"
          class="w-8 h-8 rounded-full bg-pink-50 hover:bg-pink-100 text-pink-700 font-bold flex items-center justify-center transition-colors"
        >
          &times;
        </button>
      </div>

      <!-- Form Body -->
      <form @submit.prevent="submitPayment" class="p-6 overflow-y-auto space-y-4 text-xs">
        <!-- 1. Tenant Picker -->
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Select Active Resident *</label>
          <select
            v-model="form.tenant_id"
            @change="handleTenantSelect"
            required
            class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 outline-none bg-white font-medium"
          >
            <option value="" disabled>-- Choose resident to bill --</option>
            <option
              v-for="t in activeTenants"
              :key="t.id"
              :value="t.id"
            >
              {{ t.full_name }} &bull; {{ t.room ? t.room.block.name + ' - Rm ' + t.room.room_number : 'No Room' }}
            </option>
          </select>
        </div>

        <!-- Tenant Details Snapshot (if selected) -->
        <div v-if="selectedTenant" class="p-4 bg-pink-50/50 border border-pink-200/70 rounded-2xl flex items-center justify-between">
          <div>
            <div class="font-bold text-slate-900 text-xs">{{ selectedTenant.full_name }}</div>
            <div class="text-[11px] text-slate-500">
              {{ selectedTenant.room ? selectedTenant.room.block.name + ' &bull; Room ' + selectedTenant.room.room_number : 'No Room' }}
            </div>
            <div class="text-[10px] text-pink-600 font-medium mt-0.5">
              Next Due Date: <strong>{{ selectedTenant.rent_due_date }}</strong>
            </div>
          </div>
          <div class="text-right">
            <span class="text-[10px] uppercase font-bold text-slate-400 block">Unit Base Rate</span>
            <span class="font-mono font-bold text-pink-700 text-sm">
              {{ formatUGX(selectedTenant.room?.effective_price) }}
            </span>
          </div>
        </div>

        <!-- 2. Amounts Breakdown -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Rent Amount Received (UGX) *</label>
            <input
              v-model.number="form.amount_received"
              required
              type="number"
              min="1000"
              step="5000"
              class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 font-mono font-bold text-slate-900 outline-none"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Base Rent Charged (UGX) *</label>
            <input
              v-model.number="form.base_rent_charged"
              required
              type="number"
              min="1000"
              step="5000"
              class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 font-mono text-slate-700 outline-none"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Optional Tax / Service Levy (UGX)</label>
            <input
              v-model.number="form.tax_amount"
              type="number"
              min="0"
              step="1000"
              class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 font-mono text-slate-700 outline-none"
            />
          </div>

          <div>
            <label class="block font-semibold text-slate-700 mb-1">Payment Date *</label>
            <input
              v-model="form.payment_date"
              required
              type="date"
              class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 outline-none"
            />
          </div>
        </div>

        <!-- 3. Payment Method -->
        <div>
          <label class="block font-semibold text-slate-700 mb-1.5">Payment Method *</label>
          <div class="grid grid-cols-3 gap-2">
            <button
              type="button"
              @click="form.payment_method = 'mobile_money'"
              :class="[
                form.payment_method === 'mobile_money'
                  ? 'bg-pink-50 border-pink-500 text-pink-700 font-bold ring-2 ring-pink-200'
                  : 'bg-white border-slate-200 text-slate-600',
                'p-2.5 rounded-xl border text-center transition-all'
              ]"
            >
              Mobile Money (MTN/Airtel)
            </button>
            <button
              type="button"
              @click="form.payment_method = 'bank_transfer'"
              :class="[
                form.payment_method === 'bank_transfer'
                  ? 'bg-pink-50 border-pink-500 text-pink-700 font-bold ring-2 ring-pink-200'
                  : 'bg-white border-slate-200 text-slate-600',
                'p-2.5 rounded-xl border text-center transition-all'
              ]"
            >
              Bank Transfer
            </button>
            <button
              type="button"
              @click="form.payment_method = 'cash'"
              :class="[
                form.payment_method === 'cash'
                  ? 'bg-pink-50 border-pink-500 text-pink-700 font-bold ring-2 ring-pink-200'
                  : 'bg-white border-slate-200 text-slate-600',
                'p-2.5 rounded-xl border text-center transition-all'
              ]"
            >
              Direct Cash
            </button>
          </div>
        </div>

        <!-- Advance Due Date Option -->
        <div class="flex items-center space-x-2 pt-1">
          <input
            id="advanceDue"
            v-model="form.advance_due_date"
            type="checkbox"
            class="rounded text-pink-600 focus:ring-pink-300 w-4 h-4"
          />
          <label for="advanceDue" class="font-medium text-slate-700 cursor-pointer">
            Automatically advance resident's next rent due date by +30 days
          </label>
        </div>

        <!-- Automated Multi-Channel Dispatch -->
        <div class="bg-pink-50/30 p-3.5 rounded-2xl border border-pink-100">
          <label class="block font-semibold text-slate-800 mb-2">Automated Instant Dispatch Hub</label>
          <div class="grid grid-cols-3 gap-2">
            <label class="flex items-center space-x-2 cursor-pointer">
              <input type="checkbox" value="email" v-model="form.dispatch_channels" class="rounded text-pink-600 focus:ring-pink-300" />
              <span class="text-[11px] text-slate-700">Email (PDF attached)</span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
              <input type="checkbox" value="whatsapp" v-model="form.dispatch_channels" class="rounded text-pink-600 focus:ring-pink-300" />
              <span class="text-[11px] text-slate-700">WhatsApp Alert</span>
            </label>
            <label class="flex items-center space-x-2 cursor-pointer">
              <input type="checkbox" value="sms" v-model="form.dispatch_channels" class="rounded text-pink-600 focus:ring-pink-300" />
              <span class="text-[11px] text-slate-700">SMS Gateway</span>
            </label>
          </div>
        </div>

        <!-- Notes -->
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Receipt Notes / Transaction Reference</label>
          <input
            v-model="form.notes"
            type="text"
            placeholder="e.g. MTN MoMo Ref #98721345"
            class="w-full px-3.5 py-2 rounded-xl border border-pink-100 focus:border-pink-300 outline-none"
          />
        </div>

        <!-- Submit Strip -->
        <div class="pt-3 border-t border-pink-100 flex items-center justify-end space-x-2">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-pink-500 via-rose-500 to-pink-600 hover:from-pink-600 hover:to-rose-600 text-white font-bold shadow-glam hover:shadow-glam-lg transition-all active:scale-95 disabled:opacity-50"
          >
            {{ submitting ? 'Generating...' : 'Record Payment & Issue PDF Receipt' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  show: Boolean,
  activeTenants: Array,
  preselectedTenantId: [Number, String],
});

const emit = defineEmits(['close']);

const submitting = ref(false);
const form = ref({
  tenant_id: '',
  amount_received: 420000,
  base_rent_charged: 420000,
  tax_amount: 0,
  payment_method: 'mobile_money',
  payment_date: new Date().toISOString().split('T')[0],
  advance_due_date: true,
  notes: '',
  dispatch_channels: ['email', 'whatsapp', 'sms'],
});

const selectedTenant = computed(() => {
  return props.activeTenants?.find(t => t.id === form.value.tenant_id);
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

function handleTenantSelect() {
  const tenant = selectedTenant.value;
  if (tenant && tenant.room) {
    const price = tenant.room.effective_price;
    form.value.amount_received = price;
    form.value.base_rent_charged = price;
  }
}

function formatUGX(val) {
  if (!val) return '0 UGX';
  return Number(val).toLocaleString() + ' UGX';
}

function submitPayment() {
  submitting.value = true;
  router.post('/payments', form.value, {
    onSuccess: () => {
      emit('close');
    },
    onFinish: () => {
      submitting.value = false;
    }
  });
}
</script>
