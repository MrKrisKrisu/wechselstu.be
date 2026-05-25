<script setup lang="ts">
import { ref, watch } from 'vue';
import CloseIcon from 'vue-material-design-icons/Close.vue';
import DomainIcon from 'vue-material-design-icons/Domain.vue';
import HomeIcon from 'vue-material-design-icons/Home.vue';
import LogoutIcon from 'vue-material-design-icons/Logout.vue';
import MenuIcon from 'vue-material-design-icons/Menu.vue';
import MonitorIcon from 'vue-material-design-icons/Monitor.vue';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();

const sidebarOpen = ref(false);

const navItems = [
    { name: 'Dashboard', to: '/finance', icon: HomeIcon, exact: true },
    { name: 'Kassen', to: '/finance/stations', icon: DomainIcon, exact: false },
    {
        name: 'Monitor-Zugang',
        to: '/finance/dashboard-access',
        icon: MonitorIcon,
        exact: false,
    },
];

function isActive(item: { to: string; exact: boolean }): boolean {
    return item.exact ? route.path === item.to : route.path.startsWith(item.to);
}

watch(
    () => route.path,
    () => {
        sidebarOpen.value = false;
    },
);

async function handleLogout() {
    await auth.logout();
    router.push('/login');
}
</script>

<template>
    <div class="flex h-screen overflow-hidden bg-slate-50">
        <Transition name="fade">
            <div
                v-if="sidebarOpen"
                class="fixed inset-0 z-20 bg-black/50 lg:hidden"
                @click="sidebarOpen = false"
            />
        </Transition>

        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-30 flex w-64 flex-shrink-0 flex-col bg-slate-900 transition-transform duration-300 ease-in-out lg:static lg:translate-x-0"
        >
            <div
                class="flex h-16 items-center justify-between border-b border-slate-800 px-6"
            >
                <span class="text-lg font-semibold tracking-tight text-white"
                    >wechselstu.be</span
                >
                <button
                    class="text-slate-400 hover:text-white lg:hidden"
                    @click="sidebarOpen = false"
                >
                    <CloseIcon :size="20" />
                </button>
            </div>

            <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4">
                <RouterLink
                    v-for="item in navItems"
                    :key="item.to"
                    :to="item.to"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition-colors"
                    :class="
                        isActive(item)
                            ? 'bg-slate-700 text-white'
                            : 'text-slate-400 hover:bg-slate-800 hover:text-white'
                    "
                >
                    <component :is="item.icon" class="h-4 w-4 flex-shrink-0" />
                    {{ item.name }}
                </RouterLink>
            </nav>

            <div class="border-t border-slate-800 p-4">
                <div v-if="auth.user" class="mb-3">
                    <p class="truncate text-sm font-medium text-white">
                        {{ auth.user.name }}
                    </p>
                    <p class="truncate text-xs text-slate-400">
                        {{ auth.user.email }}
                    </p>
                </div>
                <button
                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-400 transition-colors hover:bg-slate-800 hover:text-white"
                    @click="handleLogout"
                >
                    <LogoutIcon :size="16" />
                    Abmelden
                </button>
            </div>
        </aside>

        <div class="flex flex-1 flex-col overflow-hidden">
            <header
                class="flex h-16 items-center gap-4 border-b border-slate-200 bg-white px-4 lg:hidden"
            >
                <button
                    class="rounded-lg p-2 text-slate-600 transition-colors hover:bg-slate-100"
                    @click="sidebarOpen = true"
                >
                    <MenuIcon :size="20" />
                </button>
                <span class="text-base font-semibold text-slate-900"
                    >wechselstu.be</span
                >
            </header>

            <main class="flex-1 overflow-y-auto">
                <RouterView />
            </main>
        </div>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
