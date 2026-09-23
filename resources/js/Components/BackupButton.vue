<template>
  <div :class="containerClass">
    <button
      @click="triggerBackup"
      :disabled="isBackingUp"
      :class="buttonClass"
      title="Instant Cloud Backup of SQLite Database & Media to Gmail"
    >
      <Loader2 v-if="isBackingUp" class="w-4 h-4 text-pink-600 animate-spin shrink-0" />
      <CloudUpload v-else class="w-4 h-4 text-pink-600 shrink-0" />
      <span>{{ isBackingUp ? 'Generating & Dispatching Backup...' : 'Backup to Gmail Now' }}</span>
    </button>

    <div
      v-if="showTimestamp"
      :class="badgeClass"
      :title="'Last successful backup timestamp: ' + currentTimestamp"
    >
      <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 shrink-0"></span>
      <span>Last Backup: {{ currentTimestamp }}</span>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { CloudUpload, Loader2 } from 'lucide-vue-next';
import { toast } from 'vue-sonner';

const props = defineProps({
  showTimestamp: {
    type: Boolean,
    default: true,
  },
  timestampPosition: {
    type: String,
    default: 'beside', // 'beside' or 'beneath'
  },
  size: {
    type: String,
    default: 'md', // 'sm' or 'md'
  },
});

const page = usePage();
const isBackingUp = ref(false);
const justBackedUp = ref(false);

const currentTimestamp = computed(() => {
  if (justBackedUp.value) {
    return 'Just Now';
  }
  return page.props.system?.lastBackup || 'Active';
});

const containerClass = computed(() => {
  if (props.timestampPosition === 'beneath') {
    return 'flex flex-col items-start gap-1.5';
  }
  return 'inline-flex items-center gap-2.5';
});

const buttonClass = computed(() => {
  const base = [
    'inline-flex items-center space-x-2 font-semibold transition-all duration-200 rounded-xl',
    'bg-white hover:bg-pink-50/70 border border-pink-200 hover:border-pink-300',
    'text-pink-600 hover:text-pink-700 shadow-sm hover:shadow-pink-100',
    'active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed disabled:active:scale-100',
  ];

  if (props.size === 'sm') {
    base.push('px-3 py-1.5 text-[11px]');
  } else {
    base.push('px-3.5 py-2 text-xs');
  }

  return base.join(' ');
});

const badgeClass = computed(() => [
  'inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-medium',
  'bg-pink-50/80 border border-pink-200/60 text-pink-700 whitespace-nowrap'
].join(' '));

function triggerBackup() {
  if (isBackingUp.value) return;

  isBackingUp.value = true;

  router.post('/backup/trigger', {}, {
    preserveScroll: true,
    onSuccess: () => {
      justBackedUp.value = true;
    },
    onFinish: () => {
      isBackingUp.value = false;
    },
  });
}
</script>
