<template>
  <AppLayout>
    <div class="space-y-6 pb-12">
      <!-- Title & Action -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-900 tracking-tight">Payments Ledger &amp; PDF Receipt Engine</h2>
          <p class="text-xs text-slate-500 mt-1">Real-time ledger entries, vector-sharp PDF generation, and dispatch tracking</p>
        </div>
        <button
          @click="showBillingModal = true"
          class="px-4 py-2.5 bg-gradient-to-r from-pink-500 via-rose-500 to-pink-600 hover:from-pink-600 hover:to-rose-600 text-white text-xs font-bold rounded-xl shadow-glam transition-all active:scale-95 flex items-center space-x-1.5"
        >
          <Receipt class="w-4 h-4" />
          <span>Intake Payment &amp; Issue Receipt</span>
        </button>
      </div>

      <!-- Filters Strip -->
      <div class="bg-white/90 p-4 rounded-2xl border border-pink-100 shadow-glam grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
        <!-- Search -->
        <div class="relative">
          <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
          <input
            v-model="localFilters.search"
            @input="applyFilters"
            type="text"
            placeholder="Search receipt serial or tenant..."
            class="w-full pl-9 pr-3 py-2 rounded-xl border border-pink-100 focus:border-pink-300 outline-none"
          />
        </div>

        <!-- Payment Method -->
        <div>
          <select
            v-model="localFilters.method"
            @change="applyFilters"
            class="w-full px-3 py-2 rounded-xl border border-pink-100 focus:border-pink-300 outline-none bg-white font-medium"
          >
            <option value="">All Payment Modes</option>
            <option value="mobile_money">Mobile Money (MTN / Airtel)</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="cash">Direct Cash</option>
          </select>
        </div>

        <!-- Start Date -->
        <div>
          <input
            v-model="localFilters.start_date"
            @change="applyFilters"
            type="date"
            placeholder="Start Date"
            class="w-full px-3 py-2 rounded-xl border border-pink-100 focus:border-pink-300 outline-none"
          />
        </div>

        <!-- End Date -->
        <div>
          <input
            v-model="localFilters.end_date"
            @change="applyFilters"
            type="date"
            placeholder="End Date"
            class="w-full px-3 py-2 rounded-xl border border-pink-100 focus:border-pink-300 outline-none"
          />
        </div>
      </div>

      <!-- Payments Table -->
      <div class="bg-white/95 rounded-2xl border border-pink-100 shadow-glam overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-pink-50/60 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-pink-100">
              <tr>
                <th class="px-6 py-3.5">Serial No</th>
                <th class="px-4 py-3.5">Payment Date</th>
                <th class="px-4 py-3.5">Resident</th>
                <th class="px-4 py-3.5">Unit Allocation</th>
                <th class="px-4 py-3.5">Payment Method</th>
                <th class="px-4 py-3.5 text-right">Amount (UGX)</th>
                <th class="px-6 py-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-pink-50">
              <tr
                v-for="p in payments.data"
                :key="p.id"
                class="hover:bg-pink-50/20 transition-colors"
              >
                <!-- Serial No -->
                <td class="px-6 py-3.5 whitespace-nowrap">
                  <span class="font-mono font-bold text-slate-900 bg-pink-50 px-2.5 py-1 rounded-lg border border-pink-100 text-xs">
                    {{ p.receipt_serial_no }}
                  </span>
                </td>

                <!-- Date -->
                <td class="px-4 py-3.5 whitespace-nowrap text-slate-700 font-medium">
                  {{ formatDate(p.payment_date) }}
                </td>

                <!-- Tenant -->
                <td class="px-4 py-3.5 whitespace-nowrap font-bold text-slate-900">
                  {{ p.tenant?.full_name || 'Archived Tenant' }}
                </td>

                <!-- Room -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold bg-white border border-pink-200 text-pink-700">
                    {{ p.room ? p.room.block.code + ' - Rm ' + p.room.room_number : 'Assigned' }}
                  </span>
                </td>

                <!-- Method -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <span
                    :class="[
                      p.payment_method === 'mobile_money'
                        ? 'bg-amber-50 text-amber-800 border-amber-200'
                        : p.payment_method === 'bank_transfer'
                        ? 'bg-blue-50 text-blue-800 border-blue-200'
                        : 'bg-emerald-50 text-emerald-800 border-emerald-200',
                      'px-2.5 py-1 rounded-full text-[10px] font-bold border uppercase tracking-wider'
                    ]"
                  >
                    {{ formatMethod(p.payment_method) }}
                  </span>
                </td>

                <!-- Amount -->
                <td class="px-4 py-3.5 whitespace-nowrap text-right font-mono font-bold text-slate-900">
                  {{ formatUGX(p.amount_received) }}
                </td>

                <!-- Actions -->
                <td class="px-6 py-3.5 whitespace-nowrap text-right space-x-2">
                  <a
                    :href="`/payments/${p.id}/receipt`"
                    target="_blank"
                    class="inline-flex items-center px-2.5 py-1 bg-white hover:bg-pink-50 border border-pink-200 text-pink-700 rounded-lg text-xs font-semibold shadow-xs transition-colors space-x-1"
                    title="View & Download Official Vector PDF"
                  >
                    <FileText class="w-3.5 h-3.5" />
                    <span>PDF Receipt</span>
                  </a>

                  <button
                    @click="resendReceipt(p)"
                    class="inline-flex items-center px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 text-slate-600 rounded-lg text-xs font-semibold shadow-xs transition-colors space-x-1"
                    title="Re-dispatch to Email, WhatsApp, and SMS"
                  >
                    <Send class="w-3 h-3 text-pink-500" />
                    <span>Dispatch</span>
                  </button>
                </td>
              </tr>

              <tr v-if="payments.data.length === 0">
                <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                  No payment transactions found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination Controls -->
        <div v-if="payments.links && payments.links.length > 3" class="px-6 py-3.5 border-t border-pink-100 flex items-center justify-between">
          <div class="text-[11px] text-slate-500">
            Showing {{ payments.from }} to {{ payments.to }} of {{ payments.total }} entries
          </div>
          <div class="flex items-center space-x-1">
            <Link
              v-for="(link, i) in payments.links"
              :key="i"
              :href="link.url || '#'"
              :class="[
                link.active ? 'bg-pink-600 text-white font-bold' : 'bg-white text-slate-600 hover:bg-pink-50',
                !link.url ? 'opacity-40 cursor-not-allowed' : '',
                'px-2.5 py-1 rounded-lg border border-pink-100 text-xs transition-colors'
              ]"
              v-html="link.label"
            />
          </div>
        </div>
      </div>

      <!-- Payment Intake Modal -->
      <BillingModal
        :show="showBillingModal"
        :active-tenants="activeTenants"
        @close="showBillingModal = false"
      />
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BillingModal from '@/Components/BillingModal.vue';
import {
  Receipt,
  Search,
  FileText,
  Send,
} from 'lucide-vue-next';

const props = defineProps({
  payments: Object,
  activeTenants: Array,
  filters: Object,
});

const showBillingModal = ref(false);

const localFilters = ref({
  search: props.filters?.search || '',
  method: props.filters?.method || '',
  start_date: props.filters?.start_date || '',
  end_date: props.filters?.end_date || '',
});

function applyFilters() {
  router.get('/payments', localFilters.value, { preserveState: true, replace: true });
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatUGX(val) {
  if (!val) return '0 UGX';
  return Number(val).toLocaleString() + ' UGX';
}

function formatMethod(method) {
  return method ? method.replace('_', ' ') : 'Cash';
}

function resendReceipt(payment) {
  router.post(`/payments/${payment.id}/resend`);
}
</script>
