<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import CashMultipleIcon from 'vue-material-design-icons/CashMultiple.vue';
import ClipboardTextIcon from 'vue-material-design-icons/ClipboardText.vue';
import CurrencyEurIcon from 'vue-material-design-icons/CurrencyEur.vue';
import { useRoute } from 'vue-router';
import { useMonitorChannel } from '@/composables/useEcho';
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
let leaveChannel: (() => void) | null = null;

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

function timeAgo(dateStr: string): string {
    const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);

    if (diff < 60) {
        return `vor ${diff}s`;
    }

    if (diff < 3600) {
        return `vor ${Math.floor(diff / 60)}min`;
    }

    return `vor ${Math.floor(diff / 3600)}h`;
}

onMounted(async () => {
    try {
        const res = await axios.get(`/api/monitor`, { params: { token } });
        const list: Ticket[] = res.data.tickets ?? [];
        store.setTickets(list);
    } catch {
        error.value =
            'Monitor konnte nicht geladen werden. Bitte Token überprüfen.';
    } finally {
        loading.value = false;
    }

    leaveChannel = useMonitorChannel(
        (ticket: Ticket) => store.addTicket(ticket),
        (ticket: Ticket) => store.updateTicket(ticket),
    );
});

onUnmounted(() => {
    leaveChannel?.();
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
                <div class="text-center">
                    <p class="mb-4 text-5xl">✅</p>
                    <p class="text-xl font-semibold text-slate-400">
                        Keine offenen Tickets
                    </p>
                    <p class="mt-2 text-sm tracking-wider text-slate-600">
                        Alle Kassen sind versorgt.
                    </p>
                </div>
            </div>

            <div
                v-else
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
            >
                <div
                    v-for="ticket in store.openTickets"
                    :key="ticket.id"
                    class="overflow-hidden rounded-xl border-2 border-red-500 bg-slate-800 shadow-lg shadow-red-900/30"
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
                                {{ ticket.station.name }}
                            </p>
                            <p class="text-sm text-slate-400">
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
                    v-for="ticket in store.acceptedTickets"
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
                                {{ ticket.station.name }}
                            </p>
                            <p class="text-sm text-slate-400">
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
                            <span class="text-xs text-amber-500">👤</span>
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
                    v-for="ticket in store.doneTickets"
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
                                {{ ticket.station.name }}
                            </p>
                            <p class="text-sm text-slate-400">
                                {{ ticket.station.location }}
                            </p>
                        </div>

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
            </div>
        </div>
    </MonitorLayout>
</template>
