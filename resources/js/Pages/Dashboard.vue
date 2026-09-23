<template>
  <AppLayout>
    <div class="space-y-7 pb-10">
      <!-- Page Title & Quick Metric Subheading -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-900 tracking-tight">Executive Financial &amp; Occupancy Dashboard</h2>
          <p class="text-xs text-slate-500 mt-1">Real-time localized metrics for Block 1 &amp; Block 2 operations</p>
        </div>
        <div class="flex items-center space-x-3">
          <Link
            href="/payments"
            class="px-3.5 py-2 bg-white hover:bg-pink-50 border border-pink-200 text-pink-700 text-xs font-semibold rounded-xl shadow-xs transition-colors flex items-center space-x-1.5"
          >
            <Receipt class="w-3.5 h-3.5" />
            <span>Open Cashier</span>
          </Link>
          <Link
            href="/tenants"
            class="px-3.5 py-2 bg-pink-600 hover:bg-pink-700 text-white text-xs font-semibold rounded-xl shadow-glam transition-all flex items-center space-x-1.5"
          >
            <UserPlus class="w-3.5 h-3.5" />
            <span>Register Tenant</span>
          </Link>
        </div>
      </div>

      <!-- 1. Top KPI Strip (4 Glassmorphic Cards) -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Card 1: Revenue Collected This Month -->
        <div class="bg-white/90 backdrop-blur-md rounded-2xl p-5 border border-pink-100 shadow-glam hover:border-pink-300 transition-all duration-300 relative overflow-hidden group">
          <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-pink-100/50 rounded-full blur-2xl group-hover:scale-125 transition-transform"></div>
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Collected Revenue</span>
            <span class="p-2 bg-pink-50 text-pink-600 rounded-xl border border-pink-100">
              <Banknote class="w-4 h-4" />
            </span>
          </div>
          <div class="mt-4">
            <div class="text-2xl font-extrabold text-slate-900 font-mono tracking-tight">
              {{ formatUGX(kpis.collectedThisMonth) }}
            </div>
            <div class="text-[11px] text-emerald-600 font-semibold mt-1 flex items-center">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
              Current Calendar Month
            </div>
          </div>
        </div>

        <!-- Card 2: Expected Monthly Rent -->
        <div class="bg-white/90 backdrop-blur-md rounded-2xl p-5 border border-pink-100 shadow-glam hover:border-pink-300 transition-all duration-300 relative overflow-hidden group">
          <div class="absolute -right-6 -bottom-6 w-24 h-24 bg-rose-100/40 rounded-full blur-2xl group-hover:scale-125 transition-transform"></div>
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Expected Monthly</span>
            <span class="p-2 bg-rose-50 text-rose-600 rounded-xl border border-rose-100">
              <Coins class="w-4 h-4" />
            </span>
          </div>
          <div class="mt-4">
            <div class="text-2xl font-extrabold text-slate-900 font-mono tracking-tight">
              {{ formatUGX(kpis.expectedMonthlyRent) }}
            </div>
            <div class="text-[11px] text-slate-500 mt-1">
              Active occupied units yield
            </div>
          </div>
        </div>

        <!-- Card 3: Collection Progress Rate -->
        <div class="bg-white/90 backdrop-blur-md rounded-2xl p-5 border border-pink-100 shadow-glam hover:border-pink-300 transition-all duration-300 relative overflow-hidden group">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Collection Rate</span>
            <span class="p-2 bg-pink-50 text-pink-600 rounded-xl border border-pink-100">
              <TrendingUp class="w-4 h-4" />
            </span>
          </div>
          <div class="mt-4">
            <div class="flex items-baseline justify-between">
              <span class="text-2xl font-black text-pink-700 font-mono">{{ kpis.collectionRate }}%</span>
              <span class="text-[11px] font-semibold text-rose-600 font-mono">
                Deficit: {{ formatUGX(kpis.deficit) }}
              </span>
            </div>
            <!-- Progress Bar -->
            <div class="w-full bg-pink-100/60 rounded-full h-2 mt-2.5 overflow-hidden">
              <div
                class="bg-gradient-to-r from-pink-500 to-rose-600 h-full rounded-full transition-all duration-1000"
                :style="{ width: Math.min(kpis.collectionRate, 100) + '%' }"
              ></div>
            </div>
          </div>
        </div>

        <!-- Card 4: Occupancy Snapshot -->
        <div class="bg-white/90 backdrop-blur-md rounded-2xl p-5 border border-pink-100 shadow-glam hover:border-pink-300 transition-all duration-300">
          <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Occupancy Snapshot</span>
            <span class="p-2 bg-purple-50 text-purple-600 rounded-xl border border-purple-100">
              <Building class="w-4 h-4" />
            </span>
          </div>
          <div class="grid grid-cols-3 gap-2 mt-3.5 pt-1 text-center border-t border-pink-50">
            <div class="bg-pink-50/50 p-2 rounded-xl border border-pink-100/50">
              <div class="text-base font-extrabold text-pink-700 font-mono">{{ kpis.occupiedCount }}</div>
              <div class="text-[10px] font-medium text-slate-500 uppercase">Occupied</div>
            </div>
            <div class="bg-emerald-50/50 p-2 rounded-xl border border-emerald-100/50">
              <div class="text-base font-extrabold text-emerald-700 font-mono">{{ kpis.vacantCount }}</div>
              <div class="text-[10px] font-medium text-slate-500 uppercase">Vacant</div>
            </div>
            <div class="bg-slate-50 p-2 rounded-xl border border-slate-200/50">
              <div class="text-base font-extrabold text-slate-700 font-mono">{{ kpis.totalRooms }}</div>
              <div class="text-[10px] font-medium text-slate-500 uppercase">Total</div>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Visualizations Strip (2 Charts) -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- 12-Month Financial Comparison (Grouped Bar Chart - 2 Cols) -->
        <div class="lg:col-span-2 bg-white/95 rounded-2xl p-6 border border-pink-100 shadow-glam">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-sm font-bold text-slate-900 tracking-tight">12-Month Financial Performance Comparison</h3>
              <p class="text-[11px] text-slate-500">Expected Lease Revenue vs. Actual Rent Collected</p>
            </div>
            <div class="flex items-center space-x-3 text-xs">
              <div class="flex items-center space-x-1.5">
                <span class="w-3 h-3 rounded-md bg-pink-300"></span>
                <span class="text-slate-600 font-medium">Expected</span>
              </div>
              <div class="flex items-center space-x-1.5">
                <span class="w-3 h-3 rounded-md bg-pink-600"></span>
                <span class="text-slate-600 font-medium">Collected</span>
              </div>
            </div>
          </div>
          <div class="h-64 relative">
            <canvas ref="barChartCanvas"></canvas>
          </div>
        </div>

        <!-- Occupancy & Block Share (Donut Chart - 1 Col) -->
        <div class="bg-white/95 rounded-2xl p-6 border border-pink-100 shadow-glam flex flex-col justify-between">
          <div>
            <div class="flex items-center justify-between mb-2">
              <h3 class="text-sm font-bold text-slate-900 tracking-tight">Building Occupancy Share</h3>
              <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-pink-50 text-pink-700 border border-pink-100">Live</span>
            </div>
            <p class="text-[11px] text-slate-500 mb-4">Unit ratio &amp; revenue contribution</p>
            <div class="h-48 relative flex items-center justify-center">
              <canvas ref="donutChartCanvas"></canvas>
            </div>
          </div>
          <div class="mt-4 pt-3 border-t border-pink-100/60 grid grid-cols-2 gap-3 text-xs">
            <div class="bg-pink-50/40 p-2.5 rounded-xl border border-pink-100/50">
              <span class="text-[10px] font-bold text-slate-400 block uppercase">Block 1 Revenue</span>
              <span class="text-xs font-bold text-pink-700 font-mono">{{ formatUGX(donutChart.blockShare.block1) }}</span>
            </div>
            <div class="bg-rose-50/40 p-2.5 rounded-xl border border-rose-100/50">
              <span class="text-[10px] font-bold text-slate-400 block uppercase">Block 2 Revenue</span>
              <span class="text-xs font-bold text-rose-700 font-mono">{{ formatUGX(donutChart.blockShare.block2) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. Imminent Due Date Watchlist (<= 7 Days) -->
      <div class="bg-white/95 rounded-2xl border border-pink-100 shadow-glam overflow-hidden">
        <div class="p-5 border-b border-pink-100/80 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-slate-900 tracking-tight flex items-center space-x-2">
              <span>Imminent Rent Due Watchlist</span>
              <span class="text-[10px] px-2 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-200 font-bold">
                Next 7 Days ({{ imminentTenants.length }})
              </span>
            </h3>
            <p class="text-[11px] text-slate-500 mt-0.5">Automated 3-day reminder triggers &amp; instant manual dispatch</p>
          </div>
          <Link
            href="/tenants"
            class="text-xs font-semibold text-pink-600 hover:text-pink-700 transition-colors"
          >
            View All Tenants &rarr;
          </Link>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-pink-50/60 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-pink-100">
              <tr>
                <th class="px-6 py-3.5">Tenant Name</th>
                <th class="px-4 py-3.5">Assigned Unit</th>
                <th class="px-4 py-3.5">Rent Due Date</th>
                <th class="px-4 py-3.5">Monthly UGX</th>
                <th class="px-4 py-3.5">Countdown</th>
                <th class="px-6 py-3.5 text-right">Quick Alert</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-pink-50">
              <tr
                v-for="t in imminentTenants"
                :key="t.id"
                :class="[
                  t.remaining_due_days <= 3 ? 'bg-pink-50/30' : 'hover:bg-pink-50/20',
                  'transition-colors'
                ]"
              >
                <!-- Tenant Avatar & Name -->
                <td class="px-6 py-3.5 whitespace-nowrap">
                  <div class="flex items-center space-x-3">
                    <img :src="t.avatar_url" class="w-8 h-8 rounded-full border border-pink-200 object-cover" />
                    <div>
                      <div class="font-bold text-slate-900">{{ t.full_name }}</div>
                      <div class="text-[10px] text-slate-400">{{ t.whatsapp_number }}</div>
                    </div>
                  </div>
                </td>

                <!-- Room Pill -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-pink-200 text-pink-700 shadow-xs">
                    {{ t.room ? t.room.block.name + ' - Rm ' + t.room.room_number : 'Unassigned' }}
                  </span>
                </td>

                <!-- Due Date -->
                <td class="px-4 py-3.5 whitespace-nowrap font-medium text-slate-700">
                  {{ formatDate(t.rent_due_date) }}
                </td>

                <!-- Monthly Rate -->
                <td class="px-4 py-3.5 whitespace-nowrap font-mono font-bold text-slate-900">
                  {{ formatUGX(t.room ? t.room.effective_price : 0) }}
                </td>

                <!-- Countdown & Soft Pink Pulse Warning -->
                <td class="px-4 py-3.5 whitespace-nowrap">
                  <span
                    v-if="t.remaining_due_days < 0"
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-100 text-rose-700 border border-rose-200"
                  >
                    Overdue by {{ Math.abs(t.remaining_due_days) }}d
                  </span>
                  <span
                    v-else-if="t.remaining_due_days <= 3"
                    class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-pink-100 text-pink-700 border border-pink-300 pink-pulse-badge inline-flex items-center space-x-1"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-pink-600 animate-ping"></span>
                    <span>Due in {{ t.remaining_due_days }}d</span>
                  </span>
                  <span
                    v-else
                    class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-slate-100 text-slate-600"
                  >
                    In {{ t.remaining_due_days }} days
                  </span>
                </td>

                <!-- Quick Alert Action -->
                <td class="px-6 py-3.5 whitespace-nowrap text-right">
                  <button
                    @click="triggerQuickReminder(t)"
                    :disabled="sendingReminderId === t.id"
                    class="inline-flex items-center px-3 py-1.5 bg-white hover:bg-pink-50 border border-pink-200 text-pink-700 text-xs font-semibold rounded-lg shadow-xs transition-colors space-x-1.5 disabled:opacity-50"
                  >
                    <Send class="w-3 h-3 text-pink-600" />
                    <span>{{ sendingReminderId === t.id ? 'Sending...' : 'Send Alert' }}</span>
                  </button>
                </td>
              </tr>

              <tr v-if="imminentTenants.length === 0">
                <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                  No leases due within the next 7 days. All accounts in good standing!
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
import { ref, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Chart, registerables } from 'chart.js';
import {
  Banknote,
  Coins,
  TrendingUp,
  Building,
  Receipt,
  UserPlus,
  Send,
} from 'lucide-vue-next';

Chart.register(...registerables);

const props = defineProps({
  kpis: Object,
  donutChart: Object,
  monthlyComparison: Array,
  imminentTenants: Array,
});

const barChartCanvas = ref(null);
const donutChartCanvas = ref(null);
const sendingReminderId = ref(null);

function formatUGX(val) {
  if (val === null || val === undefined) return '0 UGX';
  return Number(val).toLocaleString() + ' UGX';
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function triggerQuickReminder(tenant) {
  sendingReminderId.value = tenant.id;
  router.post(`/tenants/${tenant.id}/remind`, {}, {
    preserveScroll: true,
    onFinish: () => {
      sendingReminderId.value = null;
    }
  });
}

onMounted(() => {
  // 1. Grouped Bar Chart (12 Months)
  if (barChartCanvas.value) {
    const months = props.monthlyComparison.map(m => m.month);
    const expected = props.monthlyComparison.map(m => m.expected);
    const collected = props.monthlyComparison.map(m => m.collected);

    new Chart(barChartCanvas.value, {
      type: 'bar',
      data: {
        labels: months,
        datasets: [
          {
            label: 'Expected Rent',
            data: expected,
            backgroundColor: 'rgba(244, 114, 182, 0.45)', // soft pink
            borderRadius: 6,
            barPercentage: 0.7,
            categoryPercentage: 0.8,
          },
          {
            label: 'Collected Rent',
            data: collected,
            backgroundColor: '#DB2777', // Deep magenta action
            borderRadius: 6,
            barPercentage: 0.7,
            categoryPercentage: 0.8,
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#0F172A',
            padding: 10,
            cornerRadius: 8,
            callbacks: {
              label: (ctx) => `${ctx.dataset.label}: ${Number(ctx.raw).toLocaleString()} UGX`
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { font: { size: 10, family: 'Inter' }, color: '#64748B' }
          },
          y: {
            grid: { color: 'rgba(244, 114, 182, 0.1)' },
            ticks: {
              font: { size: 10, family: 'JetBrains Mono' },
              color: '#64748B',
              callback: (val) => (val / 1000000) + 'M'
            }
          }
        }
      }
    });
  }

  // 2. Donut Chart (Occupancy)
  if (donutChartCanvas.value) {
    const occ = props.donutChart.occupancy;
    new Chart(donutChartCanvas.value, {
      type: 'doughnut',
      data: {
        labels: ['Occupied', 'Vacant', 'Maintenance'],
        datasets: [{
          data: [occ.occupied, occ.vacant, occ.maintenance],
          backgroundColor: [
            '#DB2777', // occupied (deep magenta)
            '#FBCFE8', // vacant (soft baby pink)
            '#94A3B8', // maintenance (slate)
          ],
          borderWidth: 2,
          borderColor: '#FFFFFF',
          hoverOffset: 4,
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '72%',
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 10, font: { size: 10, family: 'Inter' }, color: '#475569' }
          },
          tooltip: {
            backgroundColor: '#0F172A',
            padding: 8,
            cornerRadius: 8,
          }
        }
      }
    });
  }
});
</script>
