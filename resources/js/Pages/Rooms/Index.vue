<template>
  <AppLayout>
    <div class="space-y-6 pb-12">
      <!-- Title & Summary Strip -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-900 tracking-tight">Rooms &amp; Units</h2>
          <p class="text-xs text-slate-500 mt-0.5">Floor layout for Block A &amp; Block B</p>
        </div>

        <!-- Legend Pills -->
        <div class="flex items-center space-x-3 text-xs bg-white px-4 py-2 rounded-2xl border border-pink-100 shadow-xs">
          <div class="flex items-center space-x-1.5">
            <span class="w-3 h-3 rounded-md bg-white border-2 border-dashed border-pink-300"></span>
            <span class="text-slate-600 font-medium">Vacant ({{ stats.vacant }})</span>
          </div>
          <div class="flex items-center space-x-1.5">
            <span class="w-3 h-3 rounded-md bg-pink-600"></span>
            <span class="text-slate-600 font-medium">Occupied ({{ stats.occupied }})</span>
          </div>
          <div class="flex items-center space-x-1.5">
            <span class="w-3 h-3 rounded-md bg-slate-300"></span>
            <span class="text-slate-600 font-medium">Maintenance ({{ stats.maintenance }})</span>
          </div>
        </div>
      </div>

      <!-- Block Tabs (Block 1 vs Block 2) -->
      <div class="flex items-center space-x-3">
        <button
          v-for="block in blocks"
          :key="block.id"
          @click="selectedBlockId = block.id"
          :class="[
            selectedBlockId === block.id
              ? 'bg-gradient-to-r from-pink-500 to-rose-600 text-white font-bold shadow-glam'
              : 'bg-white border border-pink-100 text-slate-600 hover:bg-pink-50/50 font-medium',
            'px-5 py-2.5 rounded-2xl text-xs transition-all duration-200'
          ]"
        >
          <span>{{ block.name }}</span>
          <span class="ml-2 text-[10px] opacity-80 font-mono">({{ formatUGX(block.default_monthly_price) }}/mo)</span>
        </button>
      </div>

      <!-- Building Grid: Floor by Floor (Floor 6 down to Floor 1) -->
      <div class="space-y-6">
        <div
          v-for="floor in [6, 5, 4, 3, 2, 1]"
          :key="floor"
          class="bg-white/80 backdrop-blur-md rounded-2xl border border-pink-100 p-5 shadow-glam"
        >
          <!-- Floor Header -->
          <div class="flex items-center justify-between pb-3 mb-4 border-b border-pink-100/60">
            <div class="flex items-center space-x-2">
              <span class="w-2 h-2 rounded-full bg-pink-500"></span>
              <h3 class="text-xs font-extrabold text-slate-800 uppercase tracking-wider">
                Floor {{ floor }} &bull; {{ currentBlock?.name }}
              </h3>
            </div>
            <span class="text-[11px] text-slate-400 font-mono">
              {{ getRoomsForFloor(floor).length }} Units
            </span>
          </div>

          <!-- Room Cards Grid -->
          <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3.5">
            <div
              v-for="room in getRoomsForFloor(floor)"
              :key="room.id"
              @click="openRoomModal(room)"
              :class="[
                room.status === 'vacant'
                  ? 'bg-white border-2 border-dashed border-pink-300 hover:border-pink-500 hover:shadow-glam'
                  : room.status === 'occupied'
                  ? 'bg-gradient-to-br from-pink-50 to-white border border-pink-200 hover:border-pink-400 shadow-xs'
                  : 'bg-slate-50 border border-slate-200 opacity-70 hover:opacity-100',
                'p-3.5 rounded-2xl cursor-pointer transition-all duration-200 flex flex-col justify-between min-h-[110px] group'
              ]"
            >
              <!-- Card Top: Room Number & Status Pill -->
              <div class="flex items-center justify-between">
                <span class="font-extrabold text-sm text-slate-900 group-hover:text-pink-600 transition-colors">
                  Rm {{ room.room_number }}
                </span>
                <span
                  v-if="room.status === 'occupied'"
                  class="w-2 h-2 rounded-full bg-pink-600 ring-2 ring-pink-100"
                  title="Occupied"
                ></span>
                <span
                  v-else-if="room.status === 'vacant'"
                  class="w-2 h-2 rounded-full bg-emerald-400 ring-2 ring-emerald-100"
                  title="Vacant"
                ></span>
                <span
                  v-else
                  class="w-2 h-2 rounded-full bg-slate-400"
                  title="Maintenance"
                ></span>
              </div>

              <!-- Card Middle: Occupant preview if occupied -->
              <div class="my-1.5">
                <div v-if="room.status === 'occupied' && room.active_tenant" class="truncate">
                  <div class="text-[11px] font-bold text-slate-800 truncate">
                    {{ room.active_tenant.full_name }}
                  </div>
                  <div class="text-[10px] text-pink-600 font-mono">
                    Due: {{ formatDate(room.active_tenant.rent_due_date) }}
                  </div>
                </div>
                <div v-else-if="room.status === 'vacant'" class="text-[11px] text-emerald-600 font-semibold">
                  Ready to Book
                </div>
                <div v-else class="text-[11px] text-slate-500 font-medium">
                  Under Repair
                </div>
              </div>

              <!-- Card Bottom: Effective Price -->
              <div class="pt-1.5 border-t border-pink-100/50 flex items-center justify-between text-[10px]">
                <span class="font-mono font-bold text-slate-600">
                  {{ formatUGX(room.effective_price) }}
                </span>
                <span v-if="room.custom_price" class="text-pink-600 font-bold" title="Custom Price Override">
                  *Overridden
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Room Edit / Override Modal -->
      <RoomEditModal
        :show="showModal"
        :room="selectedRoom"
        @close="showModal = false"
      />
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import RoomEditModal from '@/Components/RoomEditModal.vue';

const props = defineProps({
  blocks: Array,
  stats: Object,
});

const selectedBlockId = ref(props.blocks?.[0]?.id ?? 1);
const selectedRoom = ref(null);
const showModal = ref(false);

const currentBlock = computed(() => {
  return props.blocks?.find(b => b.id === selectedBlockId.value);
});

function getRoomsForFloor(floor) {
  if (!currentBlock.value) return [];
  return currentBlock.value.rooms?.filter(r => r.floor_number === floor) || [];
}

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

function openRoomModal(room) {
  selectedRoom.value = {
    ...room,
    block: currentBlock.value,
  };
  showModal.value = true;
}
</script>
