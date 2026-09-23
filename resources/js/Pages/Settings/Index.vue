<template>
  <AppLayout>
    <div class="space-y-6 pb-12">
      <!-- Title -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-black text-slate-900 tracking-tight">System Controls &amp; Gmail Cloud Vault</h2>
          <p class="text-xs text-slate-500 mt-1">Local-first SQLite engine management &amp; automated weekly cloud backups</p>
        </div>
        <BackupButton :show-timestamp="true" timestamp-position="beside" />
      </div>

      <!-- System Health Diagnostics Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
        <div class="bg-white/90 p-5 rounded-2xl border border-pink-100 shadow-glam">
          <div class="flex items-center space-x-2 text-emerald-600 font-bold mb-2">
            <Database class="w-4 h-4" />
            <span class="uppercase tracking-wider text-[10px]">Database Engine</span>
          </div>
          <div class="text-base font-extrabold text-slate-900">SQLite 3 (Local-First)</div>
          <div class="text-slate-500 mt-1">Integrity Status: <strong class="text-emerald-600">{{ system.integrity_status }}</strong></div>
          <div class="text-slate-400 text-[10px] mt-0.5">Database Size: {{ system.sqlite_size }}</div>
        </div>

        <div class="bg-white/90 p-5 rounded-2xl border border-pink-100 shadow-glam">
          <div class="flex items-center space-x-2 text-pink-600 font-bold mb-2">
            <Mail class="w-4 h-4" />
            <span class="uppercase tracking-wider text-[10px]">Gmail Cloud Vault</span>
          </div>
          <div class="text-base font-extrabold text-slate-900">Automated Weekly ZIP</div>
          <div class="text-slate-500 mt-1">Schedule: <strong>Every Friday @ 21:00</strong></div>
          <div class="text-slate-400 text-[10px] mt-0.5">Target: {{ system.backup_recipient || 'Default Admin Email' }}</div>
        </div>

        <div class="bg-white/90 p-5 rounded-2xl border border-pink-100 shadow-glam">
          <div class="flex items-center space-x-2 text-purple-600 font-bold mb-2">
            <Cpu class="w-4 h-4" />
            <span class="uppercase tracking-wider text-[10px]">Desktop Runtime</span>
          </div>
          <div class="text-base font-extrabold text-slate-900">NativePHP Electron Bridge</div>
          <div class="text-slate-500 mt-1">PHP Version: <strong>{{ system.php_version }}</strong></div>
          <div class="text-slate-400 text-[10px] mt-0.5">Laravel Version: {{ system.laravel_version }}</div>
        </div>
      </div>

      <!-- Backup Archives History Table -->
      <div class="bg-white/95 rounded-2xl border border-pink-100 shadow-glam overflow-hidden">
        <div class="p-5 border-b border-pink-100/80 flex items-center justify-between">
          <div>
            <h3 class="text-sm font-bold text-slate-900 tracking-tight">Cloud ZIP Archives Storage</h3>
            <p class="text-[11px] text-slate-500 mt-0.5">Retains up to 5 rolling verified backups locally in addition to Gmail dispatches</p>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-600">
            <thead class="bg-pink-50/60 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-pink-100">
              <tr>
                <th class="px-6 py-3.5">Archive Filename</th>
                <th class="px-4 py-3.5">Creation Timestamp</th>
                <th class="px-4 py-3.5">Compressed Size</th>
                <th class="px-4 py-3.5">Content Scope</th>
                <th class="px-6 py-3.5 text-right">Download Archive</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-pink-50">
              <tr
                v-for="b in backups"
                :key="b.filename"
                class="hover:bg-pink-50/20 transition-colors"
              >
                <td class="px-6 py-3.5 whitespace-nowrap font-mono font-bold text-slate-900">
                  {{ b.filename }}
                </td>
                <td class="px-4 py-3.5 whitespace-nowrap text-slate-700">
                  {{ b.created_at }}
                </td>
                <td class="px-4 py-3.5 whitespace-nowrap font-mono font-bold text-pink-700">
                  {{ b.formatted_size }}
                </td>
                <td class="px-4 py-3.5 whitespace-nowrap text-slate-500">
                  SQLite DB + Media Storage (Avatars, IDs, Receipts)
                </td>
                <td class="px-6 py-3.5 whitespace-nowrap text-right">
                  <a
                    :href="b.download_url"
                    class="inline-flex items-center px-3 py-1.5 bg-white hover:bg-pink-50 border border-pink-200 text-pink-700 rounded-lg text-xs font-semibold shadow-xs transition-colors space-x-1"
                  >
                    <Download class="w-3.5 h-3.5" />
                    <span>Download ZIP</span>
                  </a>
                </td>
              </tr>

              <tr v-if="backups.length === 0">
                <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                  No backup archives generated yet. Click "Trigger Cloud Backup Now" above.
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
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BackupButton from '@/Components/BackupButton.vue';
import {
  Database,
  Mail,
  Cpu,
  Download,
} from 'lucide-vue-next';

const props = defineProps({
  backups: Array,
  system: Object,
});

const backingUp = ref(false);

function triggerBackupNow() {
  backingUp.value = true;
  router.post('/backup/trigger', {}, {
    onFinish: () => {
      backingUp.value = false;
    }
  });
}
</script>
