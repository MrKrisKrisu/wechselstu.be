<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from 'vue';
import AccountIcon from 'vue-material-design-icons/Account.vue';
import CashMultipleIcon from 'vue-material-design-icons/CashMultiple.vue';
import CheckCircleIcon from 'vue-material-design-icons/CheckCircle.vue';
import ClipboardTextIcon from 'vue-material-design-icons/ClipboardText.vue';
import CurrencyEurIcon from 'vue-material-design-icons/CurrencyEur.vue';
import { useRoute } from 'vue-router';
import MonitorLayout from '@/layouts/MonitorLayout.vue';
import axios from '@/lib/axios';
import { useTicketStore } from '@/stores/tickets';
import type { Ticket } from '@/types';
import { DENOMINATIONS } from '@/types';

const route = useRoute();
const token = route.query.token as string;
const store = useTicketStore();

const loading = ref(true);
const error = ref<string | null>(null);
let pollTimer: ReturnType<typeof setInterval> | null = null;

const knownTicketIds = new Set<string>();
let initialized = false;

function playRandomSound() {
    const n = Math.floor(Math.random() * 3) + 1;
    const audio = new Audio(`/audio/${n}.mp3`);
    audio.play().catch(() => {});
}

const typeIcons: Record<string, typeof CashMultipleIcon> = {
    cash_full: CashMultipleIcon,
    change_request: CurrencyEurIcon,
    other: ClipboardTextIcon,
};

function denominationLabel(cents: number): string {
    return (
        DENOMINATIONS.find((d) => d.cents === cents)?.label ??
        `${cents / 100} €`
    );
}

function relevantDenominations(ticket: Ticket) {
    return ticket.denominations.filter((d) => d.quantity > 0);
}

const now = ref(Date.now());
setInterval(() => {
    now.value = Date.now();
}, 10_000);

function isFuture(ticket: Ticket): boolean {
    return (
        ticket.status !== 'done' &&
        new Date(ticket.created_at).getTime() > now.value
    );
}

const recentDoneTickets = computed(() =>
    store.doneTickets.filter((t) => {
        const doneAt = t.done_at ? new Date(t.done_at).getTime() : null;

        return doneAt !== null && doneAt > now.value - 10 * 60 * 1000;
    }),
);

function timeAgo(dateStr: string): string {
    const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);

    if (diff < 0) {
        const abs = Math.abs(diff);

        if (abs < 60) {
            return `in ${abs}s`;
        }

        if (abs < 3600) {
            return `in ${Math.floor(abs / 60)}min`;
        }

        return `in ${Math.floor(abs / 3600)}h`;
    }

    if (diff < 60) {
        return `vor ${diff}s`;
    }

    if (diff < 3600) {
        return `vor ${Math.floor(diff / 60)}min`;
    }

    return `vor ${Math.floor(diff / 3600)}h`;
}

async function fetchTickets() {
    try {
        const res = await axios.get(`/api/monitor`, { params: { token } });
        const tickets: Ticket[] = res.data.tickets ?? [];

        if (initialized && tickets.some((t) => !knownTicketIds.has(t.id))) {
            playRandomSound();
        }

        knownTicketIds.clear();
        tickets.forEach((t) => knownTicketIds.add(t.id));
        initialized = true;

        store.setTickets(tickets);
    } catch {
        error.value =
            'Monitor konnte nicht geladen werden. Bitte Token überprüfen.';
    }
}

onMounted(async () => {
    await fetchTickets();
    loading.value = false;
    pollTimer = setInterval(fetchTickets, 500);
});

onUnmounted(() => {
    if (pollTimer !== null) {
        clearInterval(pollTimer);
    }
});
</script>

<template>
    <MonitorLayout>
        <div
            v-if="loading"
            class="flex min-h-screen items-center justify-center"
        >
            <div class="text-center">
                <div
                    class="inline-block h-12 w-12 animate-spin rounded-full border-4 border-slate-600 border-t-amber-400"
                ></div>
                <p
                    class="mt-4 text-sm tracking-widest text-slate-400 uppercase"
                >
                    Monitor wird geladen...
                </p>
            </div>
        </div>

        <div
            v-else-if="error"
            class="flex min-h-screen items-center justify-center"
        >
            <div
                class="max-w-md rounded-xl border border-red-700 bg-red-900/50 p-10 text-center"
            >
                <p class="text-xl font-bold text-red-300">Fehler</p>
                <p class="mt-2 text-red-400">{{ error }}</p>
            </div>
        </div>

        <div v-else class="flex min-h-screen flex-col p-6">
            <div
                class="mb-6 flex items-center justify-between border-b border-slate-700 pb-4"
            >
                <h1 class="text-2xl font-black tracking-tight text-white">
                    wechselstu.be
                </h1>
                <div class="flex items-center gap-6 text-right">
                    <div>
                        <p class="text-3xl font-black text-red-400">
                            {{ store.openTickets.length }}
                        </p>
                        <p
                            class="text-xs tracking-widest text-slate-500 uppercase"
                        >
                            Offen
                        </p>
                    </div>
                    <div>
                        <p class="text-3xl font-black text-amber-400">
                            {{ store.acceptedTickets.length }}
                        </p>
                        <p
                            class="text-xs tracking-widest text-slate-500 uppercase"
                        >
                            In Bearbeitung
                        </p>
                    </div>
                    <div>
                        <p class="text-3xl font-black text-green-400">
                            {{ store.doneTickets.length }}
                        </p>
                        <p
                            class="text-xs tracking-widest text-slate-500 uppercase"
                        >
                            Erledigt
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-if="store.visibleTickets.length === 0"
                class="flex flex-1 items-center justify-center"
            >
                <div class="flex flex-col items-center gap-4">
                    <CheckCircleIcon class="text-slate-300" :size="64" />
                    <div class="text-center">
                        <p class="text-xl font-semibold text-slate-400">
                            Keine offenen Tickets
                        </p>
                        <p class="mt-2 text-sm tracking-wider text-slate-600">
                            Alle Kassen sind versorgt.
                        </p>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    v-for="ticket in store.openTickets.filter(
                        (t) => !isFuture(t),
                    )"
                    :key="ticket.id"
                    class="ticket-open overflow-hidden rounded-xl border-2 border-red-500 bg-slate-800 shadow-lg shadow-red-900/30"
                >
                    <div
                        class="flex items-center justify-between border-b border-red-500/40 bg-red-500/20 px-4 py-3"
                    >
                        <div class="flex items-center gap-2">
                            <component
                                :is="
                                    typeIcons[ticket.type] ?? ClipboardTextIcon
                                "
                                :size="24"
                            />
                            <span
                                class="text-sm font-black tracking-wide text-red-300 uppercase"
                            >
                                {{ ticket.type_label }}
                            </span>
                        </div>
                        <span
                            class="rounded-full bg-red-500 px-2 py-0.5 text-xs font-bold tracking-wider text-white uppercase"
                        >
                            Offen
                        </span>
                    </div>

                    <div class="space-y-2 px-4 py-3">
                        <div>
                            <p
                                class="text-xl leading-tight font-black text-white"
                            >
                                {{ ticket.station?.name ?? 'Keine Kasse' }}
                            </p>
                            <p
                                v-if="ticket.station"
                                class="text-sm text-slate-400"
                            >
                                {{ ticket.station.location }}
                            </p>
                        </div>

                        <div
                            v-if="relevantDenominations(ticket).length > 0"
                            class="space-y-1"
                        >
                            <p
                                class="text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                            >
                                Stückelungen
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="denom in relevantDenominations(
                                        ticket,
                                    )"
                                    :key="denom.id"
                                    class="rounded-lg bg-slate-700 px-3 py-1 text-sm font-semibold text-slate-200"
                                >
                                    {{
                                        denominationLabel(
                                            denom.denomination_cents,
                                        )
                                    }}
                                    × {{ denom.quantity }}
                                </span>
                            </div>
                        </div>

                        <p
                            v-if="ticket.message"
                            class="rounded bg-slate-700/60 px-3 py-2 text-sm leading-snug text-slate-300"
                        >
                            {{ ticket.message }}
                        </p>

                        <p class="text-xs text-slate-600">
                            {{ timeAgo(ticket.created_at) }}
                        </p>
                    </div>
                </div>

                <div
                    v-for="ticket in store.acceptedTickets.filter(
                        (t) => !isFuture(t),
                    )"
                    :key="ticket.id"
                    class="overflow-hidden rounded-xl border-2 border-amber-500 bg-slate-800 shadow-lg shadow-amber-900/30"
                >
                    <div
                        class="flex items-center justify-between border-b border-amber-500/40 bg-amber-500/20 px-4 py-3"
                    >
                        <div class="flex items-center gap-2">
                            <component
                                :is="
                                    typeIcons[ticket.type] ?? ClipboardTextIcon
                                "
                                :size="24"
                            />
                            <span
                                class="text-sm font-black tracking-wide text-amber-300 uppercase"
                            >
                                {{ ticket.type_label }}
                            </span>
                        </div>
                        <span
                            class="rounded-full bg-amber-500 px-2 py-0.5 text-xs font-bold tracking-wider text-white uppercase"
                        >
                            In Bearbeitung
                        </span>
                    </div>

                    <div class="space-y-2 px-4 py-3">
                        <div>
                            <p
                                class="text-xl leading-tight font-black text-white"
                            >
                                {{ ticket.station?.name ?? 'Keine Kasse' }}
                            </p>
                            <p
                                v-if="ticket.station"
                                class="text-sm text-slate-400"
                            >
                                {{ ticket.station.location }}
                            </p>
                        </div>

                        <div
                            v-if="relevantDenominations(ticket).length > 0"
                            class="space-y-1"
                        >
                            <p
                                class="text-[10px] font-bold tracking-widest text-slate-500 uppercase"
                            >
                                Stückelungen
                            </p>
                            <div class="flex flex-wrap gap-2">
                                <span
                                    v-for="denom in relevantDenominations(
                                        ticket,
                                    )"
                                    :key="denom.id"
                                    class="rounded-lg bg-slate-700 px-3 py-1 text-sm font-semibold text-slate-200"
                                >
                                    {{
                                        denominationLabel(
                                            denom.denomination_cents,
                                        )
                                    }}
                                    × {{ denom.quantity }}
                                </span>
                            </div>
                        </div>

                        <p
                            v-if="ticket.message"
                            class="rounded bg-slate-700/60 px-3 py-2 text-sm leading-snug text-slate-300"
                        >
                            {{ ticket.message }}
                        </p>

                        <div
                            v-if="ticket.assigned_user"
                            class="flex items-center gap-2 pt-1"
                        >
                            <AccountIcon :size="16" class="text-amber-500" />
                            <span
                                class="text-sm font-semibold text-amber-300"
                                >{{ ticket.assigned_user.name }}</span
                            >
                        </div>

                        <p class="text-xs text-slate-600">
                            {{ timeAgo(ticket.created_at) }}
                        </p>
                    </div>
                </div>

                <div
                    v-for="ticket in recentDoneTickets.filter(
                        (t) => !isFuture(t),
                    )"
                    :key="ticket.id"
                    class="overflow-hidden rounded-xl border-2 border-green-700 bg-slate-800 opacity-70"
                >
                    <div
                        class="flex items-center justify-between border-b border-green-700/40 bg-green-900/30 px-4 py-3"
                    >
                        <div class="flex items-center gap-2">
                            <component
                                :is="
                                    typeIcons[ticket.type] ?? ClipboardTextIcon
                                "
                                :size="24"
                            />
                            <span
                                class="text-sm font-black tracking-wide text-green-400 uppercase"
                            >
                                {{ ticket.type_label }}
                            </span>
                        </div>
                        <span
                            class="rounded-full bg-green-700 px-2 py-0.5 text-xs font-bold tracking-wider text-white uppercase"
                        >
                            Erledigt
                        </span>
                    </div>

                    <div class="space-y-2 px-4 py-3">
                        <div>
                            <p
                                class="text-xl leading-tight font-black text-white"
                            >
                                {{ ticket.station?.name ?? 'Keine Kasse' }}
                            </p>
                            <p
                                v-if="ticket.station"
                                class="text-sm text-slate-400"
                            >
                                {{ ticket.station.location }}
                            </p>
                        </div>

                        <p
                            v-if="ticket.message"
                            class="rounded bg-slate-700/60 px-3 py-2 text-sm leading-snug text-slate-300"
                        >
                            {{ ticket.message }}
                        </p>

                        <div
                            v-if="ticket.assigned_user"
                            class="flex items-center gap-1.5 text-xs font-medium text-green-400"
                        >
                            <span
                                >Erledigt von
                                {{ ticket.assigned_user.name }}</span
                            >
                        </div>

                        <p class="text-xs text-slate-600">
                            {{ timeAgo(ticket.created_at) }}
                        </p>
                    </div>
                </div>

                <div
                    v-for="ticket in store.visibleTickets
                        .filter(isFuture)
                        .sort(
                            (a, b) =>
                                new Date(a.created_at).getTime() -
                                new Date(b.created_at).getTime(),
                        )"
                    :key="ticket.id"
                    class="overflow-hidden rounded-xl border-2 border-slate-600 bg-slate-800 opacity-60"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-600/40 bg-slate-700/30 px-4 py-3"
                    >
                        <div class="flex items-center gap-2">
                            <component
                                :is="
                                    typeIcons[ticket.type] ?? ClipboardTextIcon
                                "
                                :size="24"
                                class="text-slate-400"
                            />
                            <span
                                class="text-sm font-black tracking-wide text-slate-400 uppercase"
                            >
                                {{ ticket.type_label }}
                            </span>
                        </div>
                        <span
                            class="rounded-full bg-slate-600 px-2 py-0.5 text-xs font-bold tracking-wider text-slate-300 uppercase"
                        >
                            {{ timeAgo(ticket.created_at) }}
                        </span>
                    </div>

                    <div class="space-y-2 px-4 py-3">
                        <div>
                            <p
                                class="text-xl leading-tight font-black text-slate-300"
                            >
                                {{ ticket.station?.name ?? 'Keine Kasse' }}
                            </p>
                            <p
                                v-if="ticket.station"
                                class="text-sm text-slate-500"
                            >
                                {{ ticket.station.location }}
                            </p>
                        </div>

                        <p
                            v-if="ticket.message"
                            class="rounded bg-slate-700/60 px-3 py-2 text-sm leading-snug text-slate-400"
                        >
                            {{ ticket.message }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </MonitorLayout>
</template>

<style scoped>
.ticket-open {
    animation: subtle-shake 3s ease-in-out infinite;
}

@keyframes subtle-shake {
    0%,
    100% {
        transform: translateX(0);
    }
    80% {
        transform: translateX(0);
    }
    82% {
        transform: translateX(-4px);
    }
    84% {
        transform: translateX(4px);
    }
    86% {
        transform: translateX(-4px);
    }
    88% {
        transform: translateX(4px);
    }
    90% {
        transform: translateX(-3px);
    }
    92% {
        transform: translateX(3px);
    }
    94% {
        transform: translateX(-2px);
    }
    96% {
        transform: translateX(2px);
    }
    98% {
        transform: translateX(0);
    }
}
</style>
