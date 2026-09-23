<template>
  <div v-if="show" class="fixed inset-0 z-50 overflow-hidden bg-slate-900/30 backdrop-blur-xs flex justify-end">
    <div class="w-full max-w-2xl bg-white h-full shadow-2xl border-l border-pink-100 flex flex-col justify-between overflow-hidden animate-in slide-in-from-right duration-300">
      
      <!-- Drawer Header -->
      <div class="px-6 py-5 border-b border-pink-100 flex items-center justify-between bg-gradient-to-r from-pink-50/60 to-white">
        <div class="flex items-center space-x-3.5">
          <img :src="tenant.avatar_url" class="w-13 h-13 rounded-2xl border-2 border-pink-300 object-cover shadow-glam" />
          <div>
            <div class="flex items-center space-x-2">
              <h3 class="text-base font-extrabold text-slate-900">{{ tenant.full_name }}</h3>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-pink-100 text-pink-700 border border-pink-200">
                {{ tenant.occupation }}
              </span>
            </div>
            <p class="text-xs text-slate-500 font-medium mt-0.5">
              {{ tenant.age }} years old &bull; Resident ID: NFH-T-{{ tenant.id }}
            </p>
          </div>
        </div>
        <button
          @click="$emit('close')"
          class="w-8 h-8 rounded-full bg-pink-50 hover:bg-pink-100 text-pink-700 font-bold flex items-center justify-center transition-colors"
        >
          &times;
        </button>
      </div>

      <!-- Drawer Body Content -->
      <div class="flex-1 overflow-y-auto p-6 space-y-6 text-xs">
        
        <!-- 1. Current Tenancy & Room Status -->
        <div class="bg-white rounded-2xl border border-pink-100 shadow-sm p-4 space-y-3">
          <div class="flex items-center justify-between pb-2.5 border-b border-pink-50">
            <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px]">Current Tenancy Allocation</span>
            
            <!-- Outstanding Balance Badge -->
            <div>
              <span
                v-if="tenant.outstanding_balance > 0"
                class="px-3 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-700 border border-rose-200 font-mono inline-flex items-center space-x-1.5"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-rose-600 animate-ping"></span>
                <span>Arrears: {{ formatUGX(tenant.outstanding_balance) }}</span>
              </span>
              <span
                v-else
                class="px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 font-mono inline-flex items-center space-x-1.5"
              >
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Balance: 0 UGX (Settled)</span>
              </span>
            </div>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
            <div class="bg-pink-50/50 p-2.5 rounded-xl border border-pink-100/50">
              <span class="text-[10px] uppercase font-bold text-slate-400 block">Assigned Unit</span>
              <span class="text-xs font-black text-pink-700 font-mono">
                {{ tenant.room ? tenant.room.block.name + ' - Rm ' + tenant.room.room_number : 'Unassigned' }}
              </span>
            </div>
            <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
              <span class="text-[10px] uppercase font-bold text-slate-400 block">Floor</span>
              <span class="text-xs font-bold text-slate-800">
                Floor {{ tenant.room?.floor_number ?? 'N/A' }}
              </span>
            </div>
            <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
              <span class="text-[10px] uppercase font-bold text-slate-400 block">Check-In Date</span>
              <span class="text-xs font-semibold text-slate-800 font-mono">
                {{ formatDate(tenant.check_in_date) }}
              </span>
            </div>
            <div class="bg-pink-50/40 p-2.5 rounded-xl border border-pink-100/50">
              <span class="text-[10px] uppercase font-bold text-slate-400 block">Next Rent Due</span>
              <span class="text-xs font-bold text-pink-700 font-mono">
                {{ formatDate(tenant.rent_due_date) }}
              </span>
            </div>
          </div>
        </div>

        <!-- 2. Contact & Emergency Particulars (with Quick Copy buttons) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <!-- Direct Tenant Contact -->
          <div class="bg-white p-4 rounded-2xl border border-pink-100 shadow-sm space-y-2">
            <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px] block">Resident Contact</span>
            
            <!-- WhatsApp Phone -->
            <div class="flex items-center justify-between p-2 bg-pink-50/30 rounded-xl border border-pink-100/60">
              <div>
                <span class="text-[10px] text-slate-400 block uppercase">WhatsApp Phone</span>
                <span class="font-mono font-bold text-slate-900">{{ tenant.whatsapp_number }}</span>
              </div>
              <button
                type="button"
                @click="copyText(tenant.whatsapp_number, 'phone')"
                class="px-2 py-1 bg-white hover:bg-pink-50 border border-pink-200 text-pink-700 rounded-lg text-[10px] font-bold shadow-xs transition-colors flex items-center space-x-1"
                title="Copy phone to clipboard"
              >
                <Check v-if="copiedField === 'phone'" class="w-3 h-3 text-emerald-600" />
                <Copy v-else class="w-3 h-3" />
                <span>{{ copiedField === 'phone' ? 'Copied' : 'Copy' }}</span>
              </button>
            </div>

            <!-- Email Address -->
            <div class="flex items-center justify-between p-2 bg-pink-50/30 rounded-xl border border-pink-100/60">
              <div class="truncate mr-2">
                <span class="text-[10px] text-slate-400 block uppercase">Email</span>
                <span class="font-semibold text-slate-900 truncate block">{{ tenant.email }}</span>
              </div>
              <button
                type="button"
                @click="copyText(tenant.email, 'email')"
                class="px-2 py-1 bg-white hover:bg-pink-50 border border-pink-200 text-pink-700 rounded-lg text-[10px] font-bold shadow-xs transition-colors shrink-0 flex items-center space-x-1"
                title="Copy email to clipboard"
              >
                <Check v-if="copiedField === 'email'" class="w-3 h-3 text-emerald-600" />
                <Copy v-else class="w-3 h-3" />
                <span>{{ copiedField === 'email' ? 'Copied' : 'Copy' }}</span>
              </button>
            </div>
          </div>

          <!-- Emergency Contact -->
          <div class="bg-white p-4 rounded-2xl border border-pink-100 shadow-sm space-y-2">
            <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px] block">Emergency Contact</span>
            
            <div class="p-2 bg-pink-50/30 rounded-xl border border-pink-100/60 space-y-1">
              <div class="flex items-center justify-between">
                <div>
                  <span class="text-[10px] text-slate-400 block uppercase">Kin Name &amp; Role</span>
                  <span class="font-bold text-slate-900">{{ tenant.emergency_name }} ({{ tenant.emergency_relationship }})</span>
                </div>
                <button
                  type="button"
                  @click="copyText(tenant.emergency_name, 'kin_name')"
                  class="px-2 py-1 bg-white hover:bg-pink-50 border border-pink-200 text-pink-700 rounded-lg text-[10px] font-bold shadow-xs transition-colors flex items-center space-x-1"
                >
                  <Check v-if="copiedField === 'kin_name'" class="w-3 h-3 text-emerald-600" />
                  <Copy v-else class="w-3 h-3" />
                  <span>{{ copiedField === 'kin_name' ? 'Copied' : 'Copy' }}</span>
                </button>
              </div>
              <div class="flex items-center justify-between pt-1 border-t border-pink-100/40">
                <span class="font-mono font-bold text-pink-700">{{ tenant.emergency_phone }}</span>
                <button
                  type="button"
                  @click="copyText(tenant.emergency_phone, 'kin_phone')"
                  class="px-2 py-1 bg-white hover:bg-pink-50 border border-pink-200 text-pink-700 rounded-lg text-[10px] font-bold shadow-xs transition-colors flex items-center space-x-1"
                >
                  <Check v-if="copiedField === 'kin_phone'" class="w-3 h-3 text-emerald-600" />
                  <Copy v-else class="w-3 h-3" />
                  <span>{{ copiedField === 'kin_phone' ? 'Copied' : 'Copy' }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- 3. Identification Document Preview & Click-to-Enlarge -->
        <div class="bg-white p-4 rounded-2xl border border-pink-100 shadow-sm space-y-2">
          <div class="flex items-center justify-between">
            <span class="font-bold text-slate-800 uppercase tracking-wider text-[11px]">Identification Verification Document</span>
            <span v-if="tenant.id_document_url" class="text-[10px] text-emerald-600 font-bold flex items-center">
              <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1"></span> Verified Document
            </span>
          </div>

          <div v-if="tenant.id_document_url" class="p-3 bg-pink-50/20 rounded-xl border border-pink-100 flex items-center justify-between">
            <div class="flex items-center space-x-3">
              <div
                @click="showIdModal = true"
                class="w-14 h-10 rounded-lg overflow-hidden border border-pink-200 cursor-pointer hover:opacity-80 transition-opacity bg-slate-100 relative group"
                title="Click to Enlarge"
              >
                <img :src="tenant.id_document_url" class="w-full h-full object-cover" />
                <div class="absolute inset-0 bg-slate-900/30 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                  <Maximize2 class="w-3.5 h-3.5 text-white" />
                </div>
              </div>
              <div>
                <span class="font-bold text-slate-800 block text-xs">National ID / Passport Scan</span>
                <span class="text-[10px] text-slate-400">Click preview to view high-resolution scan</span>
              </div>
            </div>
            
            <button
              type="button"
              @click="showIdModal = true"
              class="px-3 py-1.5 bg-white hover:bg-pink-50 border border-pink-200 text-pink-700 rounded-xl text-xs font-bold shadow-xs transition-colors flex items-center space-x-1.5"
            >
              <ExternalLink class="w-3.5 h-3.5" />
              <span>Enlarge ID</span>
            </button>
          </div>
          <div v-else class="p-3 bg-slate-50 rounded-xl border border-slate-100 text-slate-400 italic text-center">
            No identity document uploaded for this resident.
          </div>
        </div>

        <!-- 4. Monthly Receipts & Payment Ledger Table -->
        <div class="bg-white rounded-2xl border border-pink-100 shadow-sm overflow-hidden">
          <div class="p-4 border-b border-pink-100/80 flex items-center justify-between bg-pink-50/30">
            <div>
              <span class="font-bold text-slate-900 uppercase tracking-wider text-[11px] block">
                Monthly Receipts &amp; Ledger History
              </span>
              <span class="text-[10px] text-slate-400">
                Complete chronological billing transactions ({{ tenant.payments?.length || 0 }} Records)
              </span>
            </div>
            <a
              :href="`/tenants/${tenant.id}/dossier`"
              target="_blank"
              class="px-2.5 py-1 bg-white hover:bg-pink-50 border border-pink-200 text-pink-700 text-xs font-bold rounded-lg shadow-xs transition-colors flex items-center space-x-1"
            >
              <FileText class="w-3.5 h-3.5" />
              <span>Dossier PDF</span>
            </a>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="bg-pink-50/50 text-slate-500 uppercase font-bold text-[9px] tracking-wider border-b border-pink-100">
                <tr>
                  <th class="px-3.5 py-2.5">Receipt No</th>
                  <th class="px-3 py-2.5">Date Paid</th>
                  <th class="px-3 py-2.5">Mode</th>
                  <th class="px-3 py-2.5 text-right">Rent</th>
                  <th class="px-3 py-2.5 text-right">Tax</th>
                  <th class="px-3 py-2.5 text-right">Paid</th>
                  <th class="px-3 py-2.5 text-right">Balance</th>
                  <th class="px-3.5 py-2.5 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-pink-50 text-[11px]">
                <tr
                  v-for="p in tenant.payments"
                  :key="p.id"
                  class="hover:bg-pink-50/20 transition-colors"
                >
                  <!-- Serial No -->
                  <td class="px-3.5 py-2.5 whitespace-nowrap font-mono font-bold text-slate-900">
                    {{ p.receipt_serial_no }}
                  </td>

                  <!-- Date Paid -->
                  <td class="px-3 py-2.5 whitespace-nowrap text-slate-700">
                    {{ formatDate(p.payment_date) }}
                  </td>

                  <!-- Payment Mode -->
                  <td class="px-3 py-2.5 whitespace-nowrap">
                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold border uppercase tracking-wider bg-pink-50/50 text-pink-700 border-pink-100">
                      {{ formatMethod(p.payment_method) }}
                    </span>
                  </td>

                  <!-- Rent Charged -->
                  <td class="px-3 py-2.5 whitespace-nowrap text-right font-mono text-slate-700">
                    {{ formatUGX(p.base_rent_charged) }}
                  </td>

                  <!-- Tax -->
                  <td class="px-3 py-2.5 whitespace-nowrap text-right font-mono text-slate-400">
                    {{ formatUGX(p.tax_amount) }}
                  </td>

                  <!-- Amount Paid -->
                  <td class="px-3 py-2.5 whitespace-nowrap text-right font-mono font-bold text-pink-700">
                    {{ formatUGX(p.amount_received) }}
                  </td>

                  <!-- Balance Remaining -->
                  <td class="px-3 py-2.5 whitespace-nowrap text-right font-mono">
                    <span v-if="p.balance_remaining > 0" class="text-rose-600 font-bold">
                      {{ formatUGX(p.balance_remaining) }}
                    </span>
                    <span v-else class="text-emerald-600 font-semibold text-[10px]">
                      Cleared
                    </span>
                  </td>

                  <!-- Actions: Download, Re-send WhatsApp, Re-send Email -->
                  <td class="px-3.5 py-2.5 whitespace-nowrap text-right space-x-1">
                    <!-- Download PDF -->
                    <a
                      :href="`/payments/${p.id}/receipt`"
                      target="_blank"
                      class="p-1.5 bg-white hover:bg-pink-50 border border-pink-200 text-pink-700 rounded-lg inline-flex items-center shadow-xs transition-colors"
                      title="Download Vector PDF"
                    >
                      <FileText class="w-3 h-3" />
                    </a>

                    <!-- Re-send WhatsApp -->
                    <button
                      type="button"
                      @click="resendChannel(p.id, 'whatsapp')"
                      class="p-1.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 rounded-lg inline-flex items-center transition-colors"
                      title="Re-send via WhatsApp"
                    >
                      <Smartphone class="w-3 h-3" />
                    </button>

                    <!-- Re-send Email -->
                    <button
                      type="button"
                      @click="resendChannel(p.id, 'email')"
                      class="p-1.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-blue-700 rounded-lg inline-flex items-center transition-colors"
                      title="Re-send via Email"
                    >
                      <Mail class="w-3 h-3" />
                    </button>
                  </td>
                </tr>

                <tr v-if="!tenant.payments || tenant.payments.length === 0">
                  <td colspan="8" class="px-6 py-8 text-center text-slate-400 italic">
                    No payment receipts issued for this tenant yet.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Drawer Footer Actions -->
      <div class="p-4 border-t border-pink-100 bg-pink-50/30 flex items-center justify-between">
        <a
          :href="`/tenants/${tenant.id}/dossier`"
          target="_blank"
          class="px-3 py-2 bg-white border border-pink-200 text-pink-700 text-xs font-semibold rounded-xl hover:bg-pink-50 transition-colors shadow-xs flex items-center space-x-1.5"
        >
          <FileText class="w-3.5 h-3.5" />
          <span>Export Full Dossier PDF</span>
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

    <!-- Click-to-Enlarge ID Document Lightbox Modal -->
    <div
      v-if="showIdModal && tenant.id_document_url"
      class="fixed inset-0 z-60 bg-slate-900/80 backdrop-blur-sm flex items-center justify-center p-4 animate-in fade-in duration-200"
      @click.self="showIdModal = false"
    >
      <div class="bg-white rounded-3xl overflow-hidden max-w-3xl w-full shadow-2xl border border-pink-200">
        <div class="px-6 py-4 border-b border-pink-100 flex items-center justify-between bg-pink-50/60">
          <div>
            <h4 class="font-bold text-slate-900 text-sm">Official ID / Passport Scan</h4>
            <p class="text-xs text-slate-500">{{ tenant.full_name }} &bull; {{ tenant.whatsapp_number }}</p>
          </div>
          <button
            @click="showIdModal = false"
            class="w-8 h-8 rounded-full bg-white hover:bg-pink-100 text-pink-700 font-bold flex items-center justify-center transition-colors"
          >
            &times;
          </button>
        </div>
        <div class="p-6 flex items-center justify-center bg-slate-950 max-h-[75vh] overflow-auto">
          <img :src="tenant.id_document_url" class="max-w-full max-h-[70vh] rounded-xl object-contain shadow-2xl" />
        </div>
        <div class="px-6 py-3 border-t border-pink-100 flex items-center justify-between bg-white text-xs">
          <a
            :href="tenant.id_document_url"
            target="_blank"
            download
            class="text-pink-600 font-bold hover:underline flex items-center space-x-1"
          >
            <ExternalLink class="w-3.5 h-3.5" />
            <span>Open Raw Document in New Window</span>
          </a>
          <button
            @click="showIdModal = false"
            class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl transition-colors"
          >
            Close Viewer
          </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import {
  Copy,
  Check,
  ExternalLink,
  FileText,
  Smartphone,
  Mail,
  Maximize2
} from 'lucide-vue-next';

const props = defineProps({
  show: Boolean,
  tenant: Object,
});

const emit = defineEmits(['close', 'edit']);

const showIdModal = ref(false);
const copiedField = ref(null);

function formatUGX(val) {
  if (!val && val !== 0) return '0 UGX';
  return Number(val).toLocaleString() + ' UGX';
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatMethod(method) {
  if (!method) return 'Cash';
  return method.replace('_', ' ');
}

function copyText(text, fieldName) {
  if (!text) return;
  navigator.clipboard.writeText(text);
  copiedField.value = fieldName;
  setTimeout(() => {
    if (copiedField.value === fieldName) copiedField.value = null;
  }, 2000);
}

function resendChannel(paymentId, channel) {
  router.post(`/payments/${paymentId}/resend/${channel}`, {}, {
    preserveScroll: true,
  });
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
