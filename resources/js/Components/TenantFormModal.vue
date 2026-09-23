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
          type="button"
          @click="$emit('close')"
          class="w-8 h-8 rounded-full bg-pink-50 hover:bg-pink-100 text-pink-700 font-bold flex items-center justify-center transition-colors"
        >
          &times;
        </button>
      </div>

      <!-- Form Body -->
      <form @submit.prevent="submitTenant" class="p-6 overflow-y-auto space-y-5 text-xs">
        <!-- 1. Personal Details -->
        <div>
          <h4 class="font-bold text-pink-700 uppercase tracking-wider text-[11px] mb-3">1. Personal Particulars</h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Full Name *</label>
              <input
                v-model="form.full_name"
                type="text"
                placeholder="e.g. Sarah Nakato Nabukalu"
                :class="[
                  form.errors.full_name ? 'border-red-400 bg-red-50/20 ring-1 ring-red-300' : 'border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50',
                  'w-full px-3.5 py-2.5 rounded-xl border outline-none transition-all'
                ]"
              />
              <p v-if="form.errors.full_name" class="mt-1 text-[11px] font-semibold text-rose-600">{{ form.errors.full_name }}</p>
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Age *</label>
              <input
                v-model.number="form.age"
                type="number"
                min="16"
                max="100"
                placeholder="e.g. 22"
                :class="[
                  form.errors.age ? 'border-red-400 bg-red-50/20 ring-1 ring-red-300' : 'border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50',
                  'w-full px-3.5 py-2.5 rounded-xl border outline-none transition-all'
                ]"
              />
              <p v-if="form.errors.age" class="mt-1 text-[11px] font-semibold text-rose-600">{{ form.errors.age }}</p>
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Email Address *</label>
              <input
                v-model="form.email"
                type="email"
                placeholder="sarah@example.com"
                :class="[
                  form.errors.email ? 'border-red-400 bg-red-50/20 ring-1 ring-red-300' : 'border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50',
                  'w-full px-3.5 py-2.5 rounded-xl border outline-none transition-all'
                ]"
              />
              <p v-if="form.errors.email" class="mt-1 text-[11px] font-semibold text-rose-600">{{ form.errors.email }}</p>
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">WhatsApp / Phone Number *</label>
              <input
                v-model="form.whatsapp_number"
                type="text"
                placeholder="+256 700 123 456"
                :class="[
                  form.errors.whatsapp_number ? 'border-red-400 bg-red-50/20 ring-1 ring-red-300' : 'border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50',
                  'w-full px-3.5 py-2.5 rounded-xl border outline-none transition-all'
                ]"
              />
              <p v-if="form.errors.whatsapp_number" class="mt-1 text-[11px] font-semibold text-rose-600">{{ form.errors.whatsapp_number }}</p>
            </div>
            <div class="sm:col-span-2">
              <label class="block font-semibold text-slate-700 mb-1">Occupation Status *</label>
              <select
                v-model="form.occupation"
                :class="[
                  form.errors.occupation ? 'border-red-400 bg-red-50/20 ring-1 ring-red-300' : 'border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50',
                  'w-full px-3.5 py-2.5 rounded-xl border outline-none bg-white transition-all'
                ]"
              >
                <option value="student">Student</option>
                <option value="employed">Formally Employed</option>
                <option value="self_employed">Self Employed / Entrepreneur</option>
                <option value="unemployed">Unemployed</option>
              </select>
              <p v-if="form.errors.occupation" class="mt-1 text-[11px] font-semibold text-rose-600">{{ form.errors.occupation }}</p>
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
                :class="[
                  form.errors.room_id ? 'border-red-400 bg-red-50/20 ring-1 ring-red-300' : 'border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50',
                  'w-full px-3.5 py-2.5 rounded-xl border outline-none bg-white transition-all font-mono'
                ]"
              >
                <option value="" disabled>-- Select a vacant unit --</option>
                <option
                  v-if="isEditing && props.tenant?.room && !vacantRooms?.some(r => r.id === props.tenant.room.id)"
                  :value="props.tenant.room.id"
                >
                  {{ props.tenant.room.block?.name || 'Block' }} &bull; Floor {{ props.tenant.room.floor_number }} &bull; Room {{ props.tenant.room.room_number }} (Currently Assigned)
                </option>
                <option
                  v-for="room in vacantRooms"
                  :key="room.id"
                  :value="room.id"
                >
                  {{ room.block?.name || 'Block' }} &bull; Floor {{ room.floor_number }} &bull; Room {{ room.room_number }} ({{ Number(room.effective_price || 0).toLocaleString() }} UGX/mo)
                </option>
              </select>
              <p v-if="form.errors.room_id" class="mt-1 text-[11px] font-semibold text-rose-600">{{ form.errors.room_id }}</p>
              <p v-else-if="!isEditing && (!vacantRooms || vacantRooms.length === 0)" class="text-[11px] text-rose-500 mt-1">No vacant rooms currently available!</p>
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Check-In Date *</label>
              <input
                v-model="form.check_in_date"
                @change="handleCheckInChange"
                type="date"
                :class="[
                  form.errors.check_in_date ? 'border-red-400 bg-red-50/20 ring-1 ring-red-300' : 'border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50',
                  'w-full px-3.5 py-2.5 rounded-xl border outline-none transition-all'
                ]"
              />
              <p v-if="form.errors.check_in_date" class="mt-1 text-[11px] font-semibold text-rose-600">{{ form.errors.check_in_date }}</p>
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">
                Next Rent Due Date (Auto +30 Days) *
              </label>
              <input
                v-model="form.rent_due_date"
                type="date"
                :class="[
                  form.errors.rent_due_date ? 'border-red-400 bg-red-50/20 ring-1 ring-red-300' : 'border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50',
                  'w-full px-3.5 py-2.5 rounded-xl border outline-none transition-all'
                ]"
              />
              <p v-if="form.errors.rent_due_date" class="mt-1 text-[11px] font-semibold text-rose-600">{{ form.errors.rent_due_date }}</p>
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
                type="text"
                placeholder="Parent / Guardian Name"
                :class="[
                  form.errors.emergency_name ? 'border-red-400 bg-red-50/20 ring-1 ring-red-300' : 'border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50',
                  'w-full px-3.5 py-2.5 rounded-xl border outline-none transition-all'
                ]"
              />
              <p v-if="form.errors.emergency_name" class="mt-1 text-[11px] font-semibold text-rose-600">{{ form.errors.emergency_name }}</p>
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Phone Number *</label>
              <input
                v-model="form.emergency_phone"
                type="text"
                placeholder="+256 782 ..."
                :class="[
                  form.errors.emergency_phone ? 'border-red-400 bg-red-50/20 ring-1 ring-red-300' : 'border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50',
                  'w-full px-3.5 py-2.5 rounded-xl border outline-none transition-all'
                ]"
              />
              <p v-if="form.errors.emergency_phone" class="mt-1 text-[11px] font-semibold text-rose-600">{{ form.errors.emergency_phone }}</p>
            </div>
            <div>
              <label class="block font-semibold text-slate-700 mb-1">Relationship *</label>
              <input
                v-model="form.emergency_relationship"
                type="text"
                placeholder="e.g. Mother, Father, Uncle"
                :class="[
                  form.errors.emergency_relationship ? 'border-red-400 bg-red-50/20 ring-1 ring-red-300' : 'border-pink-100 focus:border-pink-300 focus:ring-2 focus:ring-pink-200/50',
                  'w-full px-3.5 py-2.5 rounded-xl border outline-none transition-all'
                ]"
              />
              <p v-if="form.errors.emergency_relationship" class="mt-1 text-[11px] font-semibold text-rose-600">{{ form.errors.emergency_relationship }}</p>
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
                  ref="avatarInputRef"
                  type="file"
                  accept="image/*"
                  @change="handleAvatarChange"
                  class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-pink-100 file:text-pink-700 hover:file:bg-pink-200 cursor-pointer"
                />
                <p v-if="form.errors.avatar" class="mt-1 text-[11px] font-semibold text-rose-600">{{ form.errors.avatar }}</p>
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
                  ref="idDocInputRef"
                  type="file"
                  accept="image/*,application/pdf"
                  @change="handleIdDocChange"
                  class="text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-[11px] file:font-semibold file:bg-pink-100 file:text-pink-700 hover:file:bg-pink-200 cursor-pointer"
                />
                <p v-if="form.errors.id_document" class="mt-1 text-[11px] font-semibold text-rose-600">{{ form.errors.id_document }}</p>
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
            :disabled="form.processing"
            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-pink-500 via-rose-500 to-pink-600 text-white font-bold shadow-glam hover:shadow-glam-lg transition-all active:scale-95 disabled:opacity-50 flex items-center space-x-2"
          >
            <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin shrink-0" />
            <span>{{ form.processing ? (isEditing ? 'Saving Changes...' : 'Registering Resident...') : (isEditing ? 'Save Changes' : 'Submit / Register Tenant') }}</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Loader2 } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

const props = defineProps({
  show: Boolean,
  tenant: Object,
  vacantRooms: Array,
});

const emit = defineEmits(['close']);

const isEditing = ref(false);

const form = useForm({
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
const avatarInputRef = ref(null);
const idDocInputRef = ref(null);

// Auto calculate rent due date (+30 days)
function handleCheckInChange() {
  if (form.check_in_date) {
    const d = new Date(form.check_in_date);
    d.setDate(d.getDate() + 30);
    form.rent_due_date = d.toISOString().split('T')[0];
  }
}

watch(
  [() => props.tenant, () => props.show],
  ([newVal, isShown]) => {
    form.clearErrors();
    if (!isShown) return;

    if (newVal) {
      isEditing.value = true;
      form.full_name = newVal.full_name || '';
      form.age = newVal.age || 22;
      form.email = newVal.email || '';
      form.whatsapp_number = newVal.whatsapp_number || '';
      form.occupation = newVal.occupation || 'student';
      form.emergency_name = newVal.emergency_name || '';
      form.emergency_phone = newVal.emergency_phone || '';
      form.emergency_relationship = newVal.emergency_relationship || '';
      form.room_id = newVal.room_id ?? '';
      form.check_in_date = newVal.check_in_date ? new Date(newVal.check_in_date).toISOString().split('T')[0] : '';
      form.rent_due_date = newVal.rent_due_date ? new Date(newVal.rent_due_date).toISOString().split('T')[0] : '';
      form.status = newVal.status ?? 'active';
      form.avatar = null;
      form.id_document = null;

      avatarPreview.value = newVal.avatar_url;
      idDocumentPreview.value = newVal.id_document_url;
      idDocumentName.value = newVal.id_document_path ? 'Current ID Attached' : '';
    } else {
      isEditing.value = false;
      const today = new Date().toISOString().split('T')[0];
      const nextDue = new Date();
      nextDue.setDate(nextDue.getDate() + 30);

      form.reset();
      form.full_name = '';
      form.age = 22;
      form.email = '';
      form.whatsapp_number = '';
      form.occupation = 'student';
      form.emergency_name = '';
      form.emergency_phone = '';
      form.emergency_relationship = '';
      form.room_id = props.vacantRooms?.[0]?.id ?? '';
      form.check_in_date = today;
      form.rent_due_date = nextDue.toISOString().split('T')[0];
      form.status = 'active';
      form.avatar = null;
      form.id_document = null;

      avatarPreview.value = null;
      idDocumentPreview.value = null;
      idDocumentName.value = '';
      if (avatarInputRef.value) avatarInputRef.value.value = '';
      if (idDocInputRef.value) idDocInputRef.value.value = '';
    }
  },
  { immediate: true }
);

function handleAvatarChange(e) {
  const file = e.target.files?.[0];
  if (file) {
    form.avatar = file;
    avatarPreview.value = URL.createObjectURL(file);
  }
}

function handleIdDocChange(e) {
  const file = e.target.files?.[0];
  if (file) {
    form.id_document = file;
    idDocumentPreview.value = true;
    idDocumentName.value = file.name;
  }
}

const submitTenant = () => {
  if (isEditing.value) {
    const updateUrl = typeof route === 'function' ? route('tenants.update', props.tenant.id) : `/tenants/${props.tenant.id}`;
    form.transform((data) => ({
      ...data,
      _method: 'PUT',
    })).post(updateUrl, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        emit('close');
        toast.success('Resident Profile Updated', {
          description: `${form.full_name || 'Resident profile'} has been updated.`
        });
      },
      onError: () => {
        toast.error('Update Failed', {
          description: 'Please complete all required fields correctly.'
        });
      }
    });
  } else {
    const storeUrl = typeof route === 'function' ? route('tenants.store') : '/tenants';
    const residentName = form.full_name || 'Resident';

    form.post(storeUrl, {
      forceFormData: true, // Crucial for uploading files/images
      preserveScroll: true,
      onStart: () => {
        // Handled by form.processing
      },
      onSuccess: () => {
        // 1. Close modal
        emit('close');

        // 2. Clear/reset form
        form.reset();
        avatarPreview.value = null;
        idDocumentPreview.value = null;
        idDocumentName.value = '';
        if (avatarInputRef.value) avatarInputRef.value.value = '';
        if (idDocInputRef.value) idDocInputRef.value.value = '';

        // 3. Trigger English Toast Notification
        toast.success('Resident Registered Successfully', {
          description: `${residentName} has been saved to the directory.`
        });
      },
      onError: () => {
        toast.error('Registration Failed', {
          description: 'Please complete all required fields correctly.'
        });
      }
    });
  }
};
</script>
