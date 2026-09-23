<template>
  <div class="flex flex-col h-screen w-screen overflow-hidden bg-[#FFF5F8] text-slate-700 font-sans selection:bg-pink-500 selection:text-white">
    <!-- Sonner Toast Container -->
    <Toaster position="top-right" richColors />

    <!-- 1. Custom Frameless Desktop Titlebar (Native Desktop UX) -->
    <header class="app-drag-region h-10 w-full bg-white/70 backdrop-blur-md border-b border-pink-100 flex items-center justify-between px-4 select-none z-50 shrink-0">
      <!-- Window Controls (Mac/Desktop style) -->
      <div class="flex items-center space-x-2 app-no-drag">
        <button @click="minimizeWindow" title="Minimize" class="w-3 h-3 rounded-full bg-amber-400 hover:bg-amber-500 transition-colors focus:outline-none"></button>
        <button @click="maximizeWindow" title="Maximize" class="w-3 h-3 rounded-full bg-emerald-400 hover:bg-emerald-500 transition-colors focus:outline-none"></button>
        <button @click="closeWindow" title="Close" class="w-3 h-3 rounded-full bg-rose-400 hover:bg-rose-500 transition-colors focus:outline-none"></button>
        <span class="ml-3 text-xs font-semibold tracking-wide text-slate-500 flex items-center space-x-1.5">
          <span class="w-2 h-2 rounded-full bg-pink-500 animate-pulse"></span>
          <span>Nafisa Hostel Management Desktop</span>
        </span>
      </div>

      <!-- Live Clock & Date -->
      <div class="text-xs font-medium text-slate-500 flex items-center space-x-3">
        <span class="font-mono text-pink-600 bg-pink-50 px-2 py-0.5 rounded border border-pink-100">{{ currentTime }}</span>
        <span>{{ currentDate }}</span>
      </div>

      <!-- System Health Pills (SQLite + Last Backup) -->
      <div class="flex items-center space-x-2 text-[11px] app-no-drag">
        <div class="flex items-center space-x-1.5 bg-emerald-50 border border-emerald-200/60 text-emerald-700 px-2 py-0.5 rounded-full font-medium" title="Local SQLite Database Engine Active">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-ping"></span>
          <span>SQLite 3 (Local)</span>
        </div>
        <div class="flex items-center space-x-1.5 bg-pink-50 border border-pink-200/60 text-pink-700 px-2 py-0.5 rounded-full font-medium" :title="'Last Gmail Cloud Backup: ' + $page.props.system?.lastBackup">
          <Cloud class="w-3 h-3 text-pink-500" />
          <span>Backup: {{ $page.props.system?.lastBackup ?? 'Active' }}</span>
        </div>
      </div>
    </header>

    <!-- 2. Main Desktop Workstation Body -->
    <div class="flex flex-1 overflow-hidden relative">
      <!-- Sidebar Navigation Rail -->
      <aside class="w-64 bg-white/85 backdrop-blur-xl border-r border-pink-100/80 flex flex-col justify-between shrink-0 shadow-glam z-30 transition-all duration-300">
        <div>
          <!-- Hostel Identity Header -->
          <div class="p-5 border-b border-pink-100/60 flex items-center space-x-3">
            <div class="relative">
              <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-pink-500 via-rose-400 to-pink-300 flex items-center justify-center text-white font-bold text-lg shadow-glam-glow">
                NH
              </div>
              <span class="absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 bg-emerald-500 border-2 border-white rounded-full"></span>
            </div>
            <div>
              <h1 class="text-sm font-extrabold text-slate-900 tracking-tight">NAFISA HOSTEL</h1>
              <p class="text-[11px] font-medium text-pink-600 tracking-wide uppercase">Luxury Management</p>
            </div>
          </div>

          <!-- Navigation Links -->
          <nav class="p-3 space-y-1.5">
            <Link
              v-for="item in navItems"
              :key="item.name"
              :href="item.href"
              :class="[
                isUrl(item.href)
                  ? 'bg-pink-50 text-pink-700 font-semibold shadow-sm'
                  : 'text-slate-600 hover:text-slate-900 hover:bg-pink-50/50 font-medium',
                'group flex items-center px-3 py-2.5 text-xs rounded-xl transition-all duration-200 relative'
              ]"
            >
              <!-- Left Pink Active Indicator Pill -->
              <span
                v-if="isUrl(item.href)"
                class="absolute left-0 top-2 bottom-2 w-1.5 rounded-r-full bg-pink-600"
              ></span>

              <component
                :is="item.icon"
                :class="[
                  isUrl(item.href) ? 'text-pink-600' : 'text-slate-400 group-hover:text-pink-500',
                  'mr-3 h-4 w-4 shrink-0 transition-colors'
                ]"
              />
              <span class="flex-1">{{ item.name }}</span>
              <span
                v-if="item.badge"
                class="ml-auto text-[10px] font-bold px-2 py-0.5 rounded-full bg-pink-100 text-pink-700"
              >
                {{ item.badge }}
              </span>
            </Link>
          </nav>
        </div>

        <!-- Sidebar Bottom Footer: Quick Info & Backup Shortcut -->
        <div class="p-4 border-t border-pink-100/60 bg-gradient-to-b from-transparent to-pink-50/50">
          <div class="bg-white/90 border border-pink-100 rounded-xl p-3 shadow-xs">
            <div class="flex items-center justify-between text-xs mb-1">
              <span class="text-slate-500 font-medium">Cloud Vault</span>
              <span class="text-emerald-600 font-bold text-[10px] flex items-center">
                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-1"></span> Secured
              </span>
            </div>
            <p class="text-[11px] text-slate-500 leading-snug">Weekly encrypted backup to Gmail scheduled.</p>
            <Link
              href="/settings"
              class="mt-2 block text-center w-full py-1.5 px-2 bg-pink-50 hover:bg-pink-100 text-pink-700 text-[11px] font-semibold rounded-lg transition-colors border border-pink-200"
            >
              System Controls &rarr;
            </Link>
          </div>
        </div>
      </aside>

      <!-- Main Workspace Container -->
      <div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-[#FFF5F8]">
        <!-- Global Header Toolbar -->
        <header class="h-16 bg-white/70 backdrop-blur-md border-b border-pink-100/80 px-6 flex items-center justify-between z-20 shrink-0">
          <!-- Universal Live Filter / Search -->
          <div class="flex items-center flex-1 max-w-md">
            <div class="relative w-full">
              <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input
                v-model="globalSearch"
                @keyup.enter="handleGlobalSearch"
                type="text"
                placeholder="Search tenant name, room number, or phone..."
                class="w-full pl-10 pr-4 py-2 bg-pink-50/40 hover:bg-pink-50/70 focus:bg-white text-xs text-slate-800 placeholder-slate-400 rounded-xl border border-pink-100 focus:border-pink-300 focus:outline-none focus:ring-2 focus:ring-pink-200/50 transition-all"
              />
            </div>
          </div>

          <!-- Right Actions Strip -->
          <div class="flex items-center space-x-3">
            <!-- Manual Backup Button in Top Navigation Header -->
            <BackupButton :show-timestamp="false" size="sm" />

            <!-- Collect Rent Global Shortcut Modal Button -->
            <button
              @click="openCollectRentModal"
              class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-pink-500 via-rose-500 to-pink-600 hover:from-pink-600 hover:to-rose-600 text-white text-xs font-semibold rounded-xl shadow-glam transition-all duration-200 hover:shadow-glam-lg active:scale-95 space-x-2"
            >
              <Receipt class="w-4 h-4" />
              <span>Collect Rent</span>
            </button>

            <!-- Expiring Leases Alert Bell -->
            <div class="relative">
              <Link
                href="/tenants?due_status=due_soon"
                class="relative p-2.5 bg-white border border-pink-100 text-slate-600 hover:text-pink-600 hover:border-pink-200 rounded-xl shadow-xs transition-colors flex items-center justify-center"
                title="View Imminent Due Leases"
              >
                <Bell class="w-4 h-4" />
                <span class="absolute -top-1 -right-1 w-2.5 h-2.5 bg-pink-500 rounded-full ring-2 ring-white animate-pulse"></span>
              </Link>
            </div>

            <!-- User / Admin Profile Thumbnail -->
            <div class="flex items-center space-x-2.5 pl-2 border-l border-pink-100">
              <div class="w-8 h-8 rounded-xl bg-pink-100 border border-pink-200 text-pink-700 font-bold text-xs flex items-center justify-center">
                AD
              </div>
              <div class="hidden sm:block text-left leading-tight">
                <div class="text-xs font-bold text-slate-800">Admin Desk</div>
                <div class="text-[10px] text-slate-400">Hostel Supervisor</div>
              </div>
            </div>
          </div>
        </header>

        <!-- Flash Toast Notification -->
        <transition
          enter-active-class="transform ease-out duration-300 transition"
          enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
          enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
          leave-active-class="transition ease-in duration-200"
          leave-from-class="opacity-100"
          leave-to-class="opacity-0"
        >
          <div
            v-if="flashMessage"
            class="fixed bottom-5 right-6 z-50 max-w-md bg-white border border-pink-200 rounded-2xl shadow-glam-lg p-4 flex items-start space-x-3"
          >
            <div class="w-8 h-8 rounded-full bg-pink-50 border border-pink-200 flex items-center justify-center shrink-0 text-pink-600 font-bold">
              &check;
            </div>
            <div class="flex-1 text-xs">
              <div class="font-bold text-slate-900">Success Notification</div>
              <div class="text-slate-600 mt-0.5">{{ flashMessage }}</div>
            </div>
            <button @click="flashMessage = null" class="text-slate-400 hover:text-slate-600 text-sm font-bold">&times;</button>
          </div>
        </transition>

        <!-- Dynamic Main Page View -->
        <main class="flex-1 overflow-y-auto px-8 py-6">
          <slot />
        </main>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { Toaster, toast } from 'vue-sonner';
import 'vue-sonner/style.css';
import BackupButton from '@/Components/BackupButton.vue';
import {
  LayoutDashboard,
  Users,
  Building2,
  Receipt,
  FileBarChart,
  Settings,
  Search,
  Bell,
  Cloud,
} from 'lucide-vue-next';

const page = usePage();
const flashMessage = ref(null);
const globalSearch = ref('');

// Watch for global Inertia flash notifications and trigger Sonner toasts
watch(
  () => page.props.flash,
  (newFlash) => {
    if (!newFlash) return;

    if (newFlash.type === 'error' || newFlash.error) {
      const title = newFlash.title || 'Error';
      const desc = newFlash.description || newFlash.message || newFlash.error;
      toast.error(title, {
        description: desc,
        duration: 5000,
      });
    } else if (newFlash.type === 'success' || newFlash.success || newFlash.title || newFlash.description) {
      const title = newFlash.title || 'Success';
      const desc = newFlash.description || newFlash.message || newFlash.success;
      if (desc) {
        toast.success(title, {
          description: desc,
          duration: 5000,
        });
      }
    }
  },
  { deep: true, immediate: true }
);

// Navigation items
const navItems = [
  { name: 'Dashboard', href: '/', icon: LayoutDashboard },
  { name: 'Tenants Directory', href: '/tenants', icon: Users },
  { name: 'Room Inventory', href: '/rooms', icon: Building2 },
  { name: 'Payments & Billing', href: '/payments', icon: Receipt },
  { name: 'Financial Reports', href: '/reports', icon: FileBarChart },
  { name: 'System & Backup', href: '/settings', icon: Settings },
];

function isUrl(href) {
  const currentUrl = page.url;
  if (href === '/') {
    return currentUrl === '/' || currentUrl === '/dashboard';
  }
  return currentUrl.startsWith(href);
}

// Clock & Date state
const currentTime = ref('');
const currentDate = ref('');

function updateTime() {
  const now = new Date();
  currentTime.value = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  currentDate.value = now.toLocaleDateString([], { weekday: 'short', day: 'numeric', month: 'short', year: 'numeric' });
}

let timer = null;
onMounted(() => {
  updateTime();
  timer = setInterval(updateTime, 1000);
});

onUnmounted(() => {
  if (timer) clearInterval(timer);
});

// Window controls (with safe fallback if NativePHP bridge is not active)
function minimizeWindow() {
  if (window.nativephp?.window) {
    window.nativephp.window.minimize();
  }
}

function maximizeWindow() {
  if (window.nativephp?.window) {
    window.nativephp.window.maximize();
  }
}

function closeWindow() {
  if (window.nativephp?.window) {
    window.nativephp.window.close();
  }
}

function handleGlobalSearch() {
  if (globalSearch.value.trim()) {
    router.get('/tenants', { search: globalSearch.value.trim() }, { preserveState: true });
  }
}

function openCollectRentModal() {
  router.get('/payments', {}, { preserveState: true });
}
</script>
