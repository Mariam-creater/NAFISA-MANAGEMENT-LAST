<template>
  <AppLayout>
    <div class="space-y-6 pb-12">
      <!-- Header Strip with Tabs & Action -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-900 tracking-tight">Tenant Records &amp; Lifecycle Archive</h2>
          <p class="text-xs text-slate-500 mt-1">Manage active residents, room allocations, and 1-year retention archives</p>
        </div>
        <div class="flex items-center space-x-3">
          <button
            @click="openRegisterModal"
            class="px-4 py-2.5 bg-gradient-to-r from-pink-500 via-rose-500 to-pink-600 hover:from-pink-600 hover:to-rose-600 text-white text-xs font-bold rounded-xl shadow-glam transition-all duration-200 active:scale-95 flex items-center space-x-1.5"
          >
            <UserPlus class="w-4 h-4" />
            <span>Register New Resident</span>
          </button>
        </div>
      </div>

      <!-- Navigation Tabs (Active vs 1-Year Retention Archive) -->
      <div class="flex items-center space-x-2 border-b border-pink-100 pb-1">
        <button
          @click="activeTab = 'active'"
          :class="[
            activeTab === 'active'
              ? 'text-pink-700 border-b-2 border-pink-600 font-bold bg-pink-50/60'
              : 'text-slate-500 hover:text-slate-800 font-medium',
            'px-4 py-2 text-xs rounded-t-xl transition-all flex items-center space-x-2'
          ]"
        >
          <Users class="w-4 h-4" />
          <span>Active Residents ({{ activeTenants.length }})</span>
        </button>

        <button
          @click="activeTab = 'archived'"
          :class="[
            activeTab === 'archived'
              ? 'text-pink-700 border-b-2 border-pink-600 font-bold bg-pink-50/60'
              : 'text-slate-500 hover:text-slate-800 font-medium',
            'px-4 py-2 text-xs rounded-t-xl transition-all flex items-center space-x-2'
          ]"
        >
          <Archive class="w-4 h-4" />
          <span>1-Year Retention Archive ({{ archivedTenants.length }})</span>
        </button>
      </div>

      <!-- TAB 1: ACTIVE RESIDENTS DIRECTORY -->
      <div v-if="activeTab === 'active'" class="space-y-4">
        <!-- Comprehensive Filter Bar -->
        <div class="bg-white/90 p-4 rounded-2xl border border-pink-100 shadow-glam grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-xs">
          <!-- Search -->
          <div class="lg:col-span-2 relative">
            <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
            <input
              v-model="localFilters.search"
              @input="applyFilters"
              type="text"
              placeholder="Search by name, phone, or email..."
              class="w-full pl-9 pr-3 py-2 rounded-xl border border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50 outline-none"
            />
          </div>

          <!-- Filter by Block -->
          <div>
            <select
              v-model="localFilters.block_id"
              @change="applyFilters"
              class="w-full px-3 py-2 rounded-xl border border-pink-100 focus:border-pink-300 outline-none bg-white font-medium"
            >
              <option value="">All Blocks</option>
              <option v-for="b in blocks" :key="b.id" :value="b.id">{{ b.name }}</option>
            </select>
          </div>

          <!-- Filter by Floor -->
          <div>
            <select
              v-model="localFilters.floor_number"
              @change="applyFilters"
              class="w-full px-3 py-2 rounded-xl border border-pink-100 focus:border-pink-300 outline-none bg-white font-medium"
            >
              <option value="">All Floors</option>
              <option v-for="f in 6" :key="f" :value="f">Floor {{ f }}</option>
            </select>
          </div>

          <!-- Filter by Due Status -->
          <div>
            <select
              v-model="localFilters.due_status"
              @change="applyFilters"
              class="w-full px-3 py-2 rounded-xl border border-pink-100 focus:border-pink-300 outline-none bg-white font-medium text-pink-700"
            >
              <option value="">All Due Statuses</option>
              <option value="overdue">Overdue Only</option>
              <option value="due_soon">Due in &le; 3 Days</option>
              <option value="upcoming">Paid / Upcoming</option>
            </select>
          </div>
        </div>

        <!-- High-Density Scannable Table -->
        <div class="bg-white/95 rounded-2xl border border-pink-100 shadow-glam overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="bg-pink-50/60 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-pink-100">
                <tr>
                  <th class="px-6 py-3.5">Resident</th>
                  <th class="px-4 py-3.5">Assigned Unit</th>
                  <th class="px-4 py-3.5">Occupation</th>
                  <th class="px-4 py-3.5">Rent Due Date</th>
                  <th class="px-4 py-3.5">Status &amp; Countdown</th>
                  <th class="px-6 py-3.5 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-pink-50">
                <tr
                  v-for="t in activeTenants"
                  :key="t.id"
                  class="hover:bg-pink-50/20 transition-colors"
                >
                  <!-- Resident Avatar & Contact -->
                  <td class="px-6 py-3.5 whitespace-nowrap">
                    <div class="flex items-center space-x-3">
                      <img :src="t.avatar_url" class="w-9 h-9 rounded-xl border border-pink-200 object-cover" />
                      <div>
                        <button
                          @click="openProfileDrawer(t)"
                          class="font-bold text-slate-900 hover:text-pink-600 transition-colors text-left"
                        >
                          {{ t.full_name }}
                        </button>
                        <div class="text-[10px] text-slate-400 font-mono">{{ t.whatsapp_number }}</div>
                      </div>
                    </div>
                  </td>

                  <!-- Room Unit Pill -->
                  <td class="px-4 py-3.5 whitespace-nowrap">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-white border border-pink-200 text-pink-700 shadow-xs">
                      {{ t.room ? t.room.block.name + ' - Rm ' + t.room.room_number : 'Unassigned' }}
                    </span>
                  </td>

                  <!-- Occupation -->
                  <td class="px-4 py-3.5 whitespace-nowrap capitalize font-medium text-slate-700">
                    {{ t.occupation }}
                  </td>

                  <!-- Rent Due Date -->
                  <td class="px-4 py-3.5 whitespace-nowrap font-medium text-slate-700">
                    {{ formatDate(t.rent_due_date) }}
                  </td>

                  <!-- Countdown & Due Status -->
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
                      class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800"
                    >
                      In {{ t.remaining_due_days }} days
                    </span>
                  </td>

                  <!-- Actions Dropdown / Buttons -->
                  <td class="px-6 py-3.5 whitespace-nowrap text-right space-x-2">
                    <button
                      @click="openProfileDrawer(t)"
                      class="px-2.5 py-1 bg-white hover:bg-pink-50 border border-pink-200 text-pink-700 rounded-lg font-semibold text-xs transition-colors shadow-xs"
                      title="View Details"
                    >
                      Profile
                    </button>
                    <button
                      @click="openEditModal(t)"
                      class="px-2.5 py-1 bg-white hover:bg-slate-50 border border-slate-200 text-slate-600 rounded-lg font-semibold text-xs transition-colors"
                      title="Edit Resident"
                    >
                      Edit
                    </button>
                    <button
                      @click="checkoutTenant(t)"
                      class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 rounded-lg font-bold text-xs transition-colors"
                      title="Checkout & Move to Archive"
                    >
                      Checkout
                    </button>
                  </td>
                </tr>

                <tr v-if="activeTenants.length === 0">
                  <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                    No active tenants match the selected filters.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 2: 1-YEAR RETENTION ARCHIVE -->
      <div v-else-if="activeTab === 'archived'" class="space-y-4">
        <div class="bg-amber-50/70 border border-amber-200/80 rounded-2xl p-4 flex items-start space-x-3 text-xs text-amber-900">
          <ShieldAlert class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
          <div>
            <span class="font-bold">1-Year Data Retention Compliance Policy:</span>
            Checked-out tenant records and lifetime financial ledgers are retained securely for 365 days before permanent automated pruning. You can restore a resident anytime or export their certified historical dossier.
          </div>
        </div>

        <div class="bg-white/95 rounded-2xl border border-pink-100 shadow-glam overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
              <thead class="bg-pink-50/60 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-pink-100">
                <tr>
                  <th class="px-6 py-3.5">Archived Resident</th>
                  <th class="px-4 py-3.5">Occupation</th>
                  <th class="px-4 py-3.5">Checkout Date</th>
                  <th class="px-4 py-3.5">Retention Countdown</th>
                  <th class="px-4 py-3.5">Recorded Payments</th>
                  <th class="px-6 py-3.5 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-pink-50">
                <tr
                  v-for="t in archivedTenants"
                  :key="t.id"
                  class="hover:bg-pink-50/20 transition-colors"
                >
                  <td class="px-6 py-3.5 whitespace-nowrap">
                    <div class="flex items-center space-x-3">
                      <img :src="t.avatar_url" class="w-9 h-9 rounded-xl border border-pink-200 object-cover grayscale opacity-80" />
                      <div>
                        <div class="font-bold text-slate-800">{{ t.full_name }}</div>
                        <div class="text-[10px] text-slate-400 font-mono">{{ t.whatsapp_number }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3.5 whitespace-nowrap capitalize text-slate-600">
                    {{ t.occupation }}
                  </td>
                  <td class="px-4 py-3.5 whitespace-nowrap text-slate-600">
                    {{ formatDate(t.deleted_at) }}
                  </td>
                  <td class="px-4 py-3.5 whitespace-nowrap">
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-pink-50 text-pink-700 border border-pink-200 font-mono">
                      Expires in {{ t.retention_days_left }} days
                    </span>
                  </td>
                  <td class="px-4 py-3.5 whitespace-nowrap font-mono font-bold text-slate-700">
                    {{ t.payments?.length || 0 }} receipts
                  </td>
                  <td class="px-6 py-3.5 whitespace-nowrap text-right space-x-2">
                    <a
                      :href="`/tenants/${t.id}/dossier`"
                      target="_blank"
                      class="px-2.5 py-1 bg-white hover:bg-pink-50 border border-pink-200 text-pink-700 rounded-lg font-semibold text-xs transition-colors shadow-xs inline-block"
                    >
                      Dossier PDF
                    </a>
                    <button
                      @click="restoreTenant(t)"
                      class="px-2.5 py-1 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-700 rounded-lg font-bold text-xs transition-colors"
                    >
                      Restore
                    </button>
                    <button
                      @click="permanentDeleteTenant(t)"
                      class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 border border-rose-200 text-rose-700 rounded-lg font-bold text-xs transition-colors"
                    >
                      Prune
                    </button>
                  </td>
                </tr>

                <tr v-if="archivedTenants.length === 0">
                  <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                    The 1-year retention archive is currently empty.
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Modals and Slide-Over Drawer -->
      <TenantFormModal
        :show="showFormModal"
        :tenant="selectedTenantForEdit"
        :vacant-rooms="vacantRooms"
        @close="showFormModal = false"
      />

      <TenantProfileDrawer
        :show="showProfileDrawer"
        :tenant="selectedTenantForProfile"
        @close="showProfileDrawer = false"
        @edit="handleEditFromDrawer"
      />
    </div>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import TenantFormModal from '@/Components/TenantFormModal.vue';
import TenantProfileDrawer from '@/Components/TenantProfileDrawer.vue';
import {
  Users,
  Archive,
  UserPlus,
  Search,
  ShieldAlert,
} from 'lucide-vue-next';

const props = defineProps({
  activeTenants: Array,
  archivedTenants: Array,
  vacantRooms: Array,
  blocks: Array,
  filters: Object,
});

const activeTab = ref('active');
const showFormModal = ref(false);
const showProfileDrawer = ref(false);
const selectedTenantForEdit = ref(null);
const selectedTenantForProfile = ref({});

const localFilters = ref({
  search: props.filters?.search || '',
  block_id: props.filters?.block_id || '',
  floor_number: props.filters?.floor_number || '',
  occupation: props.filters?.occupation || '',
  due_status: props.filters?.due_status || '',
});

function applyFilters() {
  router.get('/tenants', localFilters.value, { preserveState: true, replace: true });
}

function formatDate(dateStr) {
  if (!dateStr) return 'N/A';
  const d = new Date(dateStr);
  return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
}

function openRegisterModal() {
  selectedTenantForEdit.value = null;
  showFormModal.value = true;
}

function openEditModal(tenant) {
  selectedTenantForEdit.value = tenant;
  showFormModal.value = true;
}

function openProfileDrawer(tenant) {
  selectedTenantForProfile.value = tenant;
  showProfileDrawer.value = true;
}

function handleEditFromDrawer(tenant) {
  showProfileDrawer.value = false;
  openEditModal(tenant);
}

function checkoutTenant(tenant) {
  if (confirm(`Check out ${tenant.full_name}? The unit will become vacant and record will be preserved in 1-Year Archive.`)) {
    router.delete(`/tenants/${tenant.id}`);
  }
}

function restoreTenant(tenant) {
  if (confirm(`Restore archived record for ${tenant.full_name}?`)) {
    router.post(`/tenants/${tenant.id}/restore`);
  }
}

function permanentDeleteTenant(tenant) {
  if (confirm(`PERMANENT PRUNE: Are you sure you want to permanently delete ${tenant.full_name}? This cannot be undone.`)) {
    router.delete(`/tenants/${tenant.id}/force`);
  }
}
</script>
