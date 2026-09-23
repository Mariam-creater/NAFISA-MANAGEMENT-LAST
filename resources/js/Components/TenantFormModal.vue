<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
    <div class="bg-white rounded-3xl border border-pink-100 shadow-glam-lg w-full max-w-2xl overflow-hidden max-h-[90vh] flex flex-col">
      <!-- Modal Header -->
      <div class="px-6 py-5 border-b border-pink-100 flex items-center justify-between bg-gradient-to-r from-pink-50/50 to-white">
        <div>
          <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
            {{ isEditing ? 'Edit Tenant Profile' : 'Register New Resident' }}
          </h3>
          <p class="text-xs text-slate-500">Nafisa Hostel Tenancy Onboarding</p>
        </div>
        <button
          @click="$emit('close')"
          class="w-8 h-8 rounded-full bg-pink-50 hover:bg-pink-100 text-pink-700 font-bold flex items-center justify-center transition-colors"
        >
          &times;
        </button>
      </div>

      <!-- Form Body -->
      <form @submit.prevent="submitForm" class="p-6 overflow-y-auto space-y-5 text-xs">
        <!-- 1. Personal Details -->
        <div>
          <h4 class="font-bold text-pink-700 uppercase tracking-wider text-[11px] mb-3">1. Personal Particulars</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Full Name *</label>
              <input
                v-model="form.full_name"
                required
                type="text"
                placeholder="e.g. Sarah Nakato Nabukalu"
                class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50 outline-none transition-all"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Age *</label>
              <input
                v-model.number="form.age"
                required
                type="number"
                min="16"
                max="100"
                placeholder="e.g. 22"
                class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50 outline-none transition-all"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Email Address *</label>
              <input
                v-model="form.email"
                required
                type="email"
                placeholder="sarah@example.com"
                class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50 outline-none transition-all"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">WhatsApp / Phone Number *</label>
              <input
                v-model="form.whatsapp_number"
                required
                type="text"
                placeholder="+256 700 123 456"
                class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50 outline-none transition-all"
              />
            </div>
            <div class="sm:col-span-2">
              <label class="block font-semibold text-slate-700 mb-1">Occupation Status *</label>
              <select
                v-model="form.occupation"
                required
                class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50 outline-none bg-white transition-all"
              >
                <option value="student">Student</option>
                <option value="employed">Formally Employed</option>
                <option value="self_employed">Self Employed / Entrepreneur</option>
                <option value="unemployed">Unemployed</option>
              </select>
            </div>
          </div>
        </div>

        <!-- 2. Room Allocation & Lease Timing -->
        <div class="pt-2 border-t border-pink-100">
          <h4 class="font-bold text-pink-700 uppercase tracking-wider text-[11px] mb-3">2. Room Allocation &amp; Lease Dates</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
              <label class="block font-semibold text-slate-700 mb-1">Select Available Unit *</label>
              <select
                v-model="form.room_id"
                required
                class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50 outline-none bg-white transition-all font-mono"
              >
                <option value="" disabled>-- Select a vacant unit --</option>
                <option
                  v-for="room in vacantRooms"
                  :key="room.id"
                  :value="room.id"
                >
                  {{ room.block.name }} &bull; Floor {{ room.floor_number }} &bull; Room {{ room.room_number }} ({{ Number(room.effective_price).toLocaleString() }} UGX/mo)
                </option>
              </select>
              <p v-if="vacantRooms.length === 0" class="text-[11px] text-rose-500 mt-1">No vacant rooms currently available!</p>
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Check-In Date *</label>
              <input
                v-model="form.check_in_date"
                @change="handleCheckInChange"
                required
                type="date"
                class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50 outline-none transition-all"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">
                Next Rent Due Date (Auto +30 Days)
              </label>
              <input
                v-model="form.rent_due_date"
                type="date"
                class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50 outline-none transition-all"
              />
            </div>
          </div>
        </div>

        <!-- 3. Emergency Contact -->
        <div class="pt-2 border-t border-pink-100">
          <h4 class="font-bold text-pink-700 uppercase tracking-wider text-[11px] mb-3">3. Emergency Contact</h4>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Contact Name *</label>
              <input
                v-model="form.emergency_name"
                required
                type="text"
                placeholder="Parent / Guardian Name"
                class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50 outline-none transition-all"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Phone Number *</label>
              <input
                v-model="form.emergency_phone"
                required
                type="text"
                placeholder="+256 782 ..."
                class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50 outline-none transition-all"
              />
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Relationship *</label>
              <input
                v-model="form.emergency_relationship"
                required
                type="text"
                placeholder="e.g. Mother, Father, Uncle"
                class="w-full px-3.5 py-2.5 rounded-xl border border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50 outline-none transition-all"
              />
            </div>
          </div>
        </div>

        <!-- 4. Media Uploaders (Avatar & ID Document Preview) -->
        <div class="pt-2 border-t border-pink-100">
          <h4 class="font-bold text-pink-700 uppercase tracking-wider text-[11px] mb-3">4. Document &amp; Photo Verification</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Avatar Upload -->
            <div class="p-3 bg-pink-50/40 rounded-2xl border border-pink-100 text-center">
              <label class="block font-semibold text-slate-700 mb-2">Tenant Portrait / Avatar</label>
              <div class="flex flex-col items-center justify-center">
                <img
                  v-if="avatarPreview"
                  :src="avatarPreview"
                  class="w-16 h-16 rounded-full object-cover border-2 border-pink-300 mb-2 shadow-xs"
                />
                <div v-else class="w-16 h-16 rounded-full bg-pink-100 border border-pink-200 flex items-center justify-center text-pink-500 mb-2 font-bold">
                  Photo
                </div>
                <input
                  type="file"
                  accept="image/*"
                  @change="handleAvatarChange"
                  class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-pink-100 file:text-pink-700 hover:file:bg-pink-200 cursor-pointer"
                />
              </div>
            </div>

            <!-- ID Document Upload -->
            <div class="p-3 bg-pink-50/40 rounded-2xl border border-pink-100 text-center">
              <label class="block font-semibold text-slate-700 mb-2">National ID / Passport Scan</label>
              <div class="flex flex-col items-center justify-center">
                <div v-if="idDocumentPreview" class="text-xs font-semibold text-pink-700 mb-2 flex items-center space-x-1">
                  <span>&check; Selected:</span>
                  <span class="truncate max-w-[120px]">{{ idDocumentName }}</span>
                </div>
                <div v-else class="w-16 h-16 rounded-xl bg-pink-100 border border-pink-200 flex items-center justify-center text-pink-500 mb-2 font-bold text-[10px]">
                  ID Doc
                </div>
                <input
                  type="file"
                  accept="image/*,application/pdf"
                  @change="handleIdDocChange"
                  class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-pink-100 file:text-pink-700 hover:file:bg-pink-200 cursor-pointer"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Button Strip -->
        <div class="pt-4 border-t border-pink-100 flex items-center justify-end space-x-3">
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
            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-pink-500 via-rose-500 to-pink-600 text-white font-bold shadow-glam hover:shadow-glam-lg transition-all active:scale-95 disabled:opacity-50"
          >
            {{ submitting ? 'Processing...' : (isEditing ? 'Save Changes' : 'Confirm Registration') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
  show: Boolean,
  tenant: Object,
  vacantRooms: Array,
});

const emit = defineEmits(['close']);

const isEditing = ref(false);
const submitting = ref(false);

const form = ref({
  full_name: '',
  age: 22,
  email: '',
  whatsapp_number: '',
  occupation: 'student',
  emergency_name: '',
  emergency_phone: '',
  emergency_relationship: '',
  room_id: '',
  check_in_date: new Date().toISOString().split('T')[0],
  rent_due_date: '',
  avatar: null,
  id_document: null,
  status: 'active',
});

const avatarPreview = ref(null);
const idDocumentPreview = ref(null);
const idDocumentName = ref('');

// Auto calculate rent due date (+30 days)
function handleCheckInChange() {
  if (form.value.check_in_date) {
    const d = new Date(form.value.check_in_date);
    d.setDate(d.getDate() + 30);
    form.value.rent_due_date = d.toISOString().split('T')[0];
  }
}

watch(
  () => props.tenant,
  (newVal) => {
    if (newVal) {
      isEditing.value = true;
      form.value = {
        full_name: newVal.full_name,
        age: newVal.age,
        email: newVal.email,
        whatsapp_number: newVal.whatsapp_number,
        occupation: newVal.occupation,
        emergency_name: newVal.emergency_name,
        emergency_phone: newVal.emergency_phone,
        emergency_relationship: newVal.emergency_relationship,
        room_id: newVal.room_id ?? '',
        check_in_date: newVal.check_in_date,
        rent_due_date: newVal.rent_due_date,
        status: newVal.status ?? 'active',
        avatar: null,
        id_document: null,
      };
      avatarPreview.value = newVal.avatar_url;
      idDocumentPreview.value = newVal.id_document_url;
      idDocumentName.value = newVal.id_document_path ? 'Current ID Attached' : '';
    } else {
      isEditing.value = false;
      const today = new Date().toISOString().split('T')[0];
      const nextDue = new Date();
      nextDue.setDate(nextDue.getDate() + 30);

      form.value = {
        full_name: '',
        age: 22,
        email: '',
        whatsapp_number: '',
        occupation: 'student',
        emergency_name: '',
        emergency_phone: '',
        emergency_relationship: '',
        room_id: props.vacantRooms?.[0]?.id ?? '',
        check_in_date: today,
        rent_due_date: nextDue.toISOString().split('T')[0],
        avatar: null,
        id_document: null,
        status: 'active',
      };
      avatarPreview.value = null;
      idDocumentPreview.value = null;
      idDocumentName.value = '';
    }
  },
  { immediate: true }
);

function handleAvatarChange(e) {
  const file = e.target.files[0];
  if (file) {
    form.value.avatar = file;
    avatarPreview.value = URL.createObjectURL(file);
  }
}

function handleIdDocChange(e) {
  const file = e.target.files[0];
  if (file) {
    form.value.id_document = file;
    idDocumentPreview.value = true;
    idDocumentName.value = file.name;
  }
}

function submitForm() {
  submitting.value = true;

  const data = new FormData();
  Object.keys(form.value).forEach((key) => {
    if (form.value[key] !== null && form.value[key] !== undefined) {
      data.append(key, form.value[key]);
    }
  });

  if (isEditing.value) {
    data.append('_method', 'PUT');
    router.post(`/tenants/${props.tenant.id}`, data, {
      forceFormData: true,
      onSuccess: () => {
        emit('close');
      },
      onFinish: () => {
        submitting.value = false;
      },
    });
  } else {
    router.post('/tenants', data, {
      forceFormData: true,
      onSuccess: () => {
        emit('close');
      },
      onFinish: () => {
        submitting.value = false;
      },
    });
  }
}
</script>
