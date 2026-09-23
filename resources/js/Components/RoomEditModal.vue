<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
    <div class="bg-white rounded-3xl border border-pink-100 shadow-glam-lg w-full max-w-md overflow-hidden flex flex-col">
      <!-- Modal Header -->
      <div class="px-6 py-5 border-b border-pink-100 flex items-center justify-between bg-gradient-to-r from-pink-50/50 to-white">
        <div>
          <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
            Manage Room {{ room.room_number }}
          </h3>
          <p class="text-xs text-pink-600 font-semibold">{{ room.block?.name }} &bull; Floor {{ room.floor_number }}</p>
        </div>
        <button
          @click="$emit('close')"
          class="w-8 h-8 rounded-full bg-pink-50 hover:bg-pink-100 text-pink-700 font-bold flex items-center justify-center transition-colors"
        >
          &times;
        </button>
      </div>

      <!-- Form Body -->
      <form @submit.prevent="submitRoomUpdate" class="p-6 space-y-4 text-xs">
        <!-- Status Picker -->
        <div>
          <label class="block font-semibold text-slate-700 mb-1.5">Room Occupancy State *</label>
          <div class="grid grid-cols-3 gap-2">
            <button
              type="button"
              @click="form.status = 'vacant'"
              :class="[
                form.status === 'vacant'
                  ? 'bg-emerald-50 border-emerald-400 text-emerald-800 font-bold ring-2 ring-emerald-200'
                  : 'bg-white border-slate-200 text-slate-600',
                'p-2.5 rounded-xl border text-center transition-all'
              ]"
            >
              Vacant
            </button>
            <button
              type="button"
              @click="form.status = 'occupied'"
              :class="[
                form.status === 'occupied'
                  ? 'bg-pink-50 border-pink-500 text-pink-700 font-bold ring-2 ring-pink-200'
                  : 'bg-white border-slate-200 text-slate-600',
                'p-2.5 rounded-xl border text-center transition-all'
              ]"
            >
              Occupied
            </button>
            <button
              type="button"
              @click="form.status = 'maintenance'"
              :class="[
                form.status === 'maintenance'
                  ? 'bg-amber-50 border-amber-400 text-amber-800 font-bold ring-2 ring-amber-200'
                  : 'bg-white border-slate-200 text-slate-600',
                'p-2.5 rounded-xl border text-center transition-all'
              ]"
            >
              Maintenance
            </button>
          </div>
        </div>

        <!-- Effective Price & Custom Override -->
        <div class="bg-pink-50/40 p-3.5 rounded-2xl border border-pink-100 space-y-2">
          <div class="flex justify-between items-center">
            <span class="text-slate-500">Block Default Base Rate:</span>
            <span class="font-mono font-bold text-slate-900">{{ formatUGX(defaultBaseRate) }}</span>
          </div>
          <div>
            <label class="block font-semibold text-slate-700 mb-1">Custom Price Override (UGX)</label>
            <input
              v-model.number="form.custom_price"
              type="number"
              step="any"
              min="0"
              placeholder="Leave empty to use base rate"
              class="w-full px-3 py-2 rounded-xl border border-pink-200 bg-white font-mono focus:border-pink-400 outline-none"
            />
            <p class="text-[10px] text-slate-400 mt-1">If set, overrides default monthly rate for this specific unit.</p>
          </div>
        </div>

        <!-- Occupant Preview if Occupied -->
        <div v-if="room.active_tenant" class="p-3 bg-white border border-pink-100 rounded-2xl flex items-center justify-between">
          <div class="flex items-center space-x-2.5">
            <img :src="room.active_tenant.avatar_url" class="w-8 h-8 rounded-full border border-pink-200 object-cover" />
            <div>
              <div class="font-bold text-slate-800 text-[11px]">{{ room.active_tenant.full_name }}</div>
              <div class="text-[10px] text-slate-400">Due: {{ formatDate(room.active_tenant.rent_due_date) }}</div>
            </div>
          </div>
          <span class="text-[10px] font-bold text-pink-700 bg-pink-50 px-2 py-0.5 rounded-md">Active</span>
        </div>

        <!-- Room Notes -->
        <div>
          <label class="block font-semibold text-slate-700 mb-1">Room Maintenance Notes</label>
          <textarea
            v-model="form.notes"
            rows="3"
            placeholder="e.g. Needs bathroom seal replacement or balcony touch-up..."
            class="w-full px-3 py-2 rounded-xl border border-pink-100 focus:border-pink-300 outline-none"
          ></textarea>
        </div>

        <!-- Action Strip -->
        <div class="pt-3 border-t border-pink-100 flex items-center justify-end space-x-2">
          <button
            type="button"
            @click="$emit('close')"
            class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-semibold hover:bg-slate-50"
          >
            Cancel
          </button>
          <button
            type="submit"
            :disabled="submitting"
            class="px-5 py-2 rounded-xl bg-pink-600 hover:bg-pink-700 text-white font-bold shadow-glam transition-colors disabled:opacity-50"
          >
            {{ submitting ? 'Saving...' : 'Update Room' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  show: Boolean,
  room: Object,
});

const emit = defineEmits(['close']);

const submitting = ref(false);
const form = ref({
  status: 'vacant',
  custom_price: null,
  notes: '',
});

const defaultBaseRate = computed(() => {
  if (props.room?.base_price && props.room.base_price > 0) return props.room.base_price;
  if (props.room?.block?.default_monthly_price) return props.room.block.default_monthly_price;
  const blockName = (props.room?.block?.name || '').toLowerCase();
  const blockCode = (props.room?.block?.code || '').toUpperCase();
  if (blockName.includes('b') || blockName.includes('2') || blockCode === 'B' || blockCode === 'B2') return 1000000;
  return 420000;
});

watch(
  () => props.room,
  (newVal) => {
    if (newVal) {
      form.value = {
        status: newVal.status,
        custom_price: newVal.custom_price,
        notes: newVal.notes || '',
      };
    }
  },
  { immediate: true }
);

function formatUGX(val) {
  if (!val) return '0 UGX';
  return Number(val).toLocaleString() + ' UGX';
}

function formatDate(dateVal) {
  if (!dateVal) return 'N/A';
  const d = new Date(dateVal);
  if (isNaN(d.getTime())) return String(dateVal);
  return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function submitRoomUpdate() {
  submitting.value = true;
  router.put(`/rooms/${props.room.id}`, form.value, {
    onSuccess: () => {
      emit('close');
    },
    onFinish: () => {
      submitting.value = false;
    }
  });
}
</script>
