<template>
  <AppLayout>
    <div class="space-y-6 pb-12">
      <!-- Title & Export Buttons -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-900 tracking-tight">Executive Financial Reporting &amp; Ledger Exports</h2>
          <p class="text-xs text-slate-500 mt-1">Generate certified statements, analyze cash flow, and export financial dossiers</p>
        </div>
        <div class="flex items-center space-x-3">
          <a
            :href="excelExportUrl"
            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition-colors flex items-center space-x-1.5"
          >
            <Download class="w-4 h-4" />
            <span>Export Excel (.csv)</span>
          </a>
          <a
            :href="pdfExportUrl"
            target="_blank"
            class="px-4 py-2.5 bg-gradient-to-r from-pink-500 via-rose-500 to-pink-600 hover:from-pink-600 hover:to-rose-600 text-white text-xs font-bold rounded-xl shadow-glam transition-all active:scale-95 flex items-center space-x-1.5"
          >
            <FileSpreadsheet class="w-4 h-4" />
            <span>Formal Ledger (.pdf)</span>
          </a>
        </div>
      </div>

      <!-- Date Range Filter Pill Buttons -->
      <div class="bg-white/90 p-4 rounded-2xl border border-pink-100 shadow-glam space-y-3">
        <div class="flex flex-wrap items-center gap-2">
          <button
            v-for="r in rangeOptions"
            :key="r.id"
            @click="selectRange(r.id)"
            :class="[
              range === r.id
                ? 'bg-pink-600 text-white font-bold shadow-xs'
                : 'bg-pink-50/60 text-slate-600 hover:bg-pink-100/60 font-medium',
              'px-3.5 py-1.5 rounded-xl text-xs transition-colors'
            ]"
          >
            {{ r.label }}
          </button>
        </div>

        <!-- Custom Date Range Inputs (if custom is selected) -->
        <div v-if="range === 'custom'" class="pt-3 border-t border-pink-100 grid grid-cols-1 sm:grid-cols-3 gap-3 items-end text-xs">
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Custom Start Date</label>
            <input
              v-model="customStart"
              type="date"
              class="w-full px-3 py-2 rounded-xl border border-pink-200 outline-none"
            />
          </div>
          <div>
            <label class="block font-semibold text-slate-600 mb-1">Custom End Date</label>
            <input
              v-model="customEnd"
              type="date"
              class="w-full px-3 py-2 rounded-xl border border-pink-200 outline-none"
            />
          </div>
          <div>
            <button
              @click="applyCustomRange"
              class="w-full py-2 bg-pink-600 hover:bg-pink-700 text-white font-bold rounded-xl shadow-xs"
            >
              Filter Custom Window
            </button>
          </div>
        </div>
      </div>

      <!-- 4 Financial KPI Summaries for Selected Window -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white/90 p-5 rounded-2xl border border-pink-100 shadow-glam">
          <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Invoiced</span>
          <div class="text-xl font-black text-slate-900 font-mono mt-2">
            {{ formatUGX(stats.total_invoiced) }}
          </div>
          <span class="text-[11px] text-slate-500 mt-1 block">Accumulated billing records</span>
        </div>

        <div class="bg-white/90 p-5 rounded-2xl border border-pink-100 shadow-glam">
          <span class="text-xs font-bold text-pink-600 uppercase tracking-wider block">Total Collected</span>
          <div class="text-xl font-black text-pink-700 font-mono mt-2">
            {{ formatUGX(stats.total_collected) }}
          </div>
          <span class="text-[11px] text-emerald-600 font-semibold mt-1 block">Deposits confirmed</span>
        </div>

        <div class="bg-white/90 p-5 rounded-2xl border border-pink-100 shadow-glam">
          <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Taxes &amp; Levies</span>
          <div class="text-xl font-black text-slate-700 font-mono mt-2">
            {{ formatUGX(stats.total_tax) }}
          </div>
          <span class="text-[11px] text-slate-500 mt-1 block">Statutory contributions</span>
        </div>

        <div class="bg-white/90 p-5 rounded-2xl border border-pink-100 shadow-glam">
          <span class="text-xs font-bold text-rose-500 uppercase tracking-wider block">Outstanding Deficit</span>
          <div class="text-xl font-black text-rose-600 font-mono mt-2">
            {{ formatUGX(stats.outstanding_deficit) }}
          </div>
          <span class="text-[11px] text-rose-500 mt-1 block">Uncollected rent variance</span>
        </div>
      </div>

      <!-- Transactions Ledger for Window -->
      <div class="bg-white/95 rounded-2xl border border-pink-100 shadow-glam overflow-hidden">
        <div class="p-5 border-b border-pink-100/80 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-slate-900 tracking-tight">Window Transactions Detail</h3>
            <p class="text-[11px] text-slate-500 mt-0.5">Showing {{ payments.length }} transactions for: {{ dateRangeLabel }}</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-pink-50/60 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-pink-100">
              <tr>
                <th class="px-6 py-3.5">Serial No</th>
                <th class="px-4 py-3.5">Date</th>
                <th class="px-4 py-3.5">Resident Name</th>
                <th class="px-4 py-3.5">Unit</th>
                <th class="px-4 py-3.5">Method</th>
                <th class="px-4 py-3.5 text-right">Tax (UGX)</th>
                <th class="px-6 py-3.5 text-right">Amount (UGX)</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-pink-50">
              <tr
                v-for="p in payments"
                :key="p.id"
                class="hover:bg-pink-50/20 transition-colors"
              >
                <td class="px-6 py-3.5 whitespace-nowrap font-mono font-bold text-slate-900">
                  {{ p.receipt_serial_no }}
                </td>
                <td class="px-4 py-3.5 whitespace-nowrap text-slate-700">
                  {{ formatDate(p.payment_date) }}
                </td>
                <td class="px-4 py-3.5 whitespace-nowrap font-semibold text-slate-900">
                  {{ p.tenant?.full_name || 'Archived Tenant' }}
                </td>
                <td class="px-4 py-3.5 whitespace-nowrap">
                  {{ p.room ? p.room.block.code + ' - ' + p.room.room_number : 'N/A' }}
                </td>
                <td class="px-4 py-3.5 whitespace-nowrap uppercase font-bold text-[10px]">
                  {{ p.payment_method?.replace('_', ' ') }}
                </td>
                <td class="px-4 py-3.5 whitespace-nowrap text-right font-mono text-slate-500">
                  {{ formatUGX(p.tax_amount) }}
                </td>
                <td class="px-6 py-3.5 whitespace-nowrap text-right font-mono font-bold text-pink-700">
                  {{ formatUGX(p.amount_received) }}
                </td>
              </tr>

              <tr v-if="payments.length === 0">
                <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                  No transaction records found for this period.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  Download,
  FileSpreadsheet,
} from 'lucide-vue-next';

const props = defineProps({
  payments: Array,
  stats: Object,
  range: String,
  dateRangeLabel: String,
  startDate: String,
  endDate: String,
});

const customStart = ref(props.startDate);
const customEnd = ref(props.endDate);

const rangeOptions = [
  { id: 'today', label: 'Today' },
  { id: 'this_month', label: 'This Month' },
  { id: 'last_6_months', label: 'Last 6 Months' },
  { id: 'ytd', label: 'Year-to-Date' },
  { id: 'custom', label: 'Custom Window...' },
];

function selectRange(rId) {
  if (rId !== 'custom') {
    router.get('/reports', { range: rId }, { preserveState: true });
  } else {
    router.get('/reports', { range: 'custom', start_date: customStart.value, end_date: customEnd.value }, { preserveState: true });
  }
}

function applyCustomRange() {
  router.get('/reports', {
    range: 'custom',
    start_date: customStart.value,
    end_date: customEnd.value,
  }, { preserveState: true });
}

const excelExportUrl = computed(() => {
  return `/reports/export/excel?range=${props.range}&start_date=${props.startDate}&end_date=${props.endDate}`;
});

const pdfExportUrl = computed(() => {
  return `/reports/export/pdf?range=${props.range}&start_date=${props.startDate}&end_date=${props.endDate}`;
});

function formatUGX(val) {
  if (!val) return '0 UGX';
  return Number(val).toLocaleString() + ' UGX';
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}
</script>
