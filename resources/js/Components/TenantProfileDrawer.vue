<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-hidden bg-slate-900/30 backdrop-blur-xs flex justify-end">
    <div class="w-full max-w-xl bg-white h-full shadow-2xl border-l border-pink-100 flex flex-col justify-between overflow-hidden animate-in slide-in-from-right duration-300">
      <!-- Drawer Header -->
      <div class="px-6 py-5 border-b border-pink-100 flex items-center justify-between bg-gradient-to-r from-pink-50/60 to-white">
        <div class="flex items-center space-x-3">
          <img :src="tenant.avatar_url" class="w-12 h-12 rounded-2xl border-2 border-pink-300 object-cover shadow-glam" />
          <div>
            <h3 class="text-base font-extrabold text-slate-900">{{ tenant.full_name }}</h3>
            <p class="text-xs text-pink-600 font-medium capitalize">{{ tenant.occupation }} &bull; {{ tenant.age }} yrs</p>
          </div>
        </div>
        <button
          @click="$emit('close')"
          class="w-8 h-8 rounded-full bg-pink-50 hover:bg-pink-100 text-pink-700 font-bold flex items-center justify-center transition-colors"
        >
          &times;
        </button>
      </div>

      <!-- Drawer Content -->
      <div class="flex-1 overflow-y-auto p-6 space-y-6 text-xs">
        <!-- 1. Accommodation Status Pill -->
        <div class="bg-pink-50/60 border border-pink-200/80 rounded-2xl p-4 flex items-center justify-between">
          <div>
            <span class="text-[10px] font-bold text-pink-700 uppercase tracking-wider block">Assigned Unit</span>
            <span class="text-sm font-black text-slate-900">
              {{ tenant.room ? tenant.room.block.name + ' - Room ' + tenant.room.room_number : 'Unassigned' }}
            </span>
            <span class="text-[11px] text-slate-500 block">
              Floor {{ tenant.room?.floor_number ?? 'N/A' }} &bull; Monthly: {{ formatUGX(tenant.room?.effective_price) }}
            </span>
          </div>
          <div class="text-right">
            <span
              v-if="tenant.remaining_due_days < 0"
              class="px-3 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-700 border border-rose-200"
            >
              Overdue ({{ Math.abs(tenant.remaining_due_days) }}d)
            </span>
            <span
              v-else-if="tenant.remaining_due_days <= 3"
              class="px-3 py-1 rounded-full text-[11px] font-bold bg-pink-100 text-pink-700 border border-pink-300 pink-pulse-badge"
            >
              Due in {{ tenant.remaining_due_days }}d
            </span>
            <span
              v-else
              class="px-3 py-1 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800"
            >
              Paid / In {{ tenant.remaining_due_days }}d
            </span>
            <span class="text-[10px] text-slate-400 block mt-1">Due: {{ tenant.rent_due_date }}</span>
          </div>
        </div>

        <!-- 2. Contact Details -->
        <div>
          <h4 class="font-bold text-slate-900 uppercase tracking-wider text-[11px] mb-2 text-pink-700">Contact &amp; Personal Info</h4>
          <div class="grid grid-cols-2 gap-3 bg-white p-3.5 rounded-2xl border border-pink-100">
            <div>
              <span class="text-[10px] text-slate-400 block uppercase">WhatsApp Phone</span>
              <span class="font-bold text-slate-800 font-mono">{{ tenant.whatsapp_number }}</span>
            </div>
            <div>
              <span class="text-[10px] text-slate-400 block uppercase">Email Address</span>
              <span class="font-medium text-slate-800 truncate block">{{ tenant.email }}</span>
            </div>
            <div>
              <span class="text-[10px] text-slate-400 block uppercase">Check-In Date</span>
              <span class="font-medium text-slate-800">{{ tenant.check_in_date }}</span>
            </div>
            <div>
              <span class="text-[10px] text-slate-400 block uppercase">Status</span>
              <span class="font-bold text-emerald-600 capitalize">{{ tenant.status }}</span>
            </div>
          </div>
        </div>

        <!-- 3. Emergency Contact Details -->
        <div>
          <h4 class="font-bold text-slate-900 uppercase tracking-wider text-[11px] mb-2 text-pink-700">Emergency Contact</h4>
          <div class="bg-white p-3.5 rounded-2xl border border-pink-100 space-y-1.5">
            <div class="flex justify-between">
              <span class="text-slate-500">Contact Name:</span>
              <span class="font-bold text-slate-800">{{ tenant.emergency_name }} ({{ tenant.emergency_relationship }})</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500">Phone Number:</span>
              <span class="font-bold text-pink-700 font-mono">{{ tenant.emergency_phone }}</span>
            </div>
          </div>
        </div>

        <!-- 4. ID Document Card -->
        <div>
          <h4 class="font-bold text-slate-900 uppercase tracking-wider text-[11px] mb-2 text-pink-700">Official ID Verification</h4>
          <div v-if="tenant.id_document_url" class="p-3 bg-pink-50/30 rounded-2xl border border-pink-100 flex items-center justify-between">
            <span class="font-medium text-slate-700">National ID / Passport File</span>
            <a
              :href="tenant.id_document_url"
              target="_blank"
              class="px-3 py-1 bg-white border border-pink-200 text-pink-700 rounded-lg text-xs font-bold hover:bg-pink-50 shadow-xs"
            >
              View Document &rarr;
            </a>
          </div>
          <div v-else class="p-3 bg-slate-50 rounded-2xl border border-slate-100 text-slate-400 italic">
            No identity document uploaded.
          </div>
        </div>

        <!-- 5. Payment History Ledger -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <h4 class="font-bold text-slate-900 uppercase tracking-wider text-[11px] text-pink-700">Payment History</h4>
            <span class="text-[10px] text-slate-400">{{ tenant.payments?.length || 0 }} Payments</span>
          </div>
          <div class="space-y-2 max-h-48 overflow-y-auto">
            <div
              v-for="p in tenant.payments"
              :key="p.id"
              class="p-2.5 bg-white border border-pink-100 rounded-xl flex items-center justify-between shadow-xs"
            >
              <div>
                <span class="font-mono font-bold text-slate-800 text-[11px] block">{{ p.receipt_serial_no }}</span>
                <span class="text-[10px] text-slate-400">{{ p.payment_date }} &bull; {{ p.payment_method }}</span>
              </div>
              <div class="text-right">
                <span class="font-mono font-bold text-pink-700 block">{{ formatUGX(p.amount_received) }}</span>
                <a
                  :href="`/payments/${p.id}/receipt`"
                  target="_blank"
                  class="text-[10px] text-pink-600 hover:underline"
                >
                  Download PDF
                </a>
              </div>
            </div>
            <div v-if="!tenant.payments || tenant.payments.length === 0" class="text-slate-400 italic text-center py-3">
              No recorded payments yet.
            </div>
          </div>
        </div>
      </div>

      <!-- Drawer Footer Actions -->
      <div class="p-4 border-t border-pink-100 bg-pink-50/30 flex items-center justify-between">
        <a
          :href="`/tenants/${tenant.id}/dossier`"
          target="_blank"
          class="px-3 py-2 bg-white border border-pink-200 text-pink-700 text-xs font-semibold rounded-xl hover:bg-pink-50 transition-colors shadow-xs"
        >
          Export Dossier PDF
        </a>

        <div class="flex items-center space-x-2">
          <button
            @click="$emit('edit', tenant)"
            class="px-3.5 py-2 bg-white border border-slate-200 text-slate-700 text-xs font-semibold rounded-xl hover:bg-slate-50 transition-colors"
          >
            Edit Profile
          </button>
          <button
            v-if="tenant.status === 'active'"
            @click="checkoutTenant"
            class="px-3.5 py-2 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors"
          >
            Checkout Lease
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
  show: Boolean,
  tenant: Object,
});

const emit = defineEmits(['close', 'edit']);

function formatUGX(val) {
  if (!val) return '0 UGX';
  return Number(val).toLocaleString() + ' UGX';
}

function checkoutTenant() {
  if (confirm(`Are you sure you want to check out ${props.tenant.full_name}? The room will become vacant and the record will be moved to the 1-Year Retention Archive.`)) {
    router.delete(`/tenants/${props.tenant.id}`, {
      onSuccess: () => {
        emit('close');
      }
    });
  }
}
</script>
