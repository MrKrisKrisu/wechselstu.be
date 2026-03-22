<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue';
import CashMultipleIcon from 'vue-material-design-icons/CashMultiple.vue';
import ClipboardTextIcon from 'vue-material-design-icons/ClipboardText.vue';
import CurrencyEurIcon from 'vue-material-design-icons/CurrencyEur.vue';
import { useFinanceChannel } from '@/composables/useEcho';
import axios from '@/lib/axios';
import { useTicketStore } from '@/stores/tickets';
import type { Ticket } from '@/types';

const ticketStore = useTicketStore();

let leaveChannel: (() => void) | null = null;

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/finance/tickets');
        ticketStore.setTickets(data.tickets ?? data);
    } catch (err) {
        console.error('Failed to fetch tickets', err);
    }

    leaveChannel = useFinanceChannel(
        (ticket: Ticket) => ticketStore.addTicket(ticket),
        (ticket: Ticket) => ticketStore.updateTicket(ticket),
    );
});

onUnmounted(() => {
    leaveChannel?.();
});

async function acceptTicket(id: string) {
    try {
        const { data } = await axios.patch(`/api/finance/tickets/${id}/accept`);
        ticketStore.updateTicket(data.ticket ?? data);
    } catch (err) {
        console.error('Failed to accept ticket', err);
    }
}

async function completeTicket(id: string) {
    try {
        const { data } = await axios.patch(
            `/api/finance/tickets/${id}/complete`,
        );
        ticketStore.updateTicket(data.ticket ?? data);
    } catch (err) {
        console.error('Failed to complete ticket', err);
    }
}

const typeIcons: Record<string, typeof CashMultipleIcon> = {
    cash_full: CashMultipleIcon,
    change_request: CurrencyEurIcon,
    other: ClipboardTextIcon,
};

function typeLabel(ticket: Ticket): string {
    return ticket.type_label ?? ticket.type;
}

function relativeTime(dateStr: string): string {
    const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);

    if (diff < 60) {
        return `vor ${diff} Sek`;
    }

    if (diff < 3600) {
        return `vor ${Math.floor(diff / 60)} Min`;
    }

    if (diff < 86400) {
        return `vor ${Math.floor(diff / 3600)} Std`;
    }

    return `vor ${Math.floor(diff / 86400)} Tagen`;
}

function formatCents(cents: number): string {
    return (cents / 100).toFixed(2).replace('.', ',') + ' €';
}
</script>

<template>
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h2
                        class="text-sm font-semibold tracking-wide text-red-600 uppercase"
                    >
                        Offen
                    </h2>
                    <span
                        class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700"
                    >
                        {{ ticketStore.openTickets.length }}
                    </span>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="ticket in ticketStore.openTickets"
                        :key="ticket.id"
                        class="rounded-xl border border-red-100 bg-white p-4 shadow-sm"
                    >
                        <div
                            class="mb-2 flex items-start justify-between gap-2"
                        >
                            <span class="text-sm font-medium text-slate-900">
                                <component
                                    :is="
                                        typeIcons[ticket.type] ??
                                        ClipboardTextIcon
                                    "
                                    :size="14"
                                    class="inline-block"
                                />
                                {{ typeLabel(ticket) }}
                            </span>
                            <span class="flex-shrink-0 text-xs text-slate-400">
                                {{ relativeTime(ticket.created_at) }}
                            </span>
                        </div>
                        <p class="text-sm font-medium text-slate-600">
                            {{ ticket.station.name }}
                        </p>
                        <p class="text-xs text-slate-400">
                            {{ ticket.station.location }}
                        </p>
                        <p
                            v-if="ticket.message"
                            class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700"
                        >
                            {{ ticket.message }}
                        </p>
                        <div
                            v-if="
                                ticket.type === 'change_request' &&
                                ticket.denominations.length > 0
                            "
                            class="mt-2 space-y-1"
                        >
                            <div
                                v-for="d in ticket.denominations"
                                :key="d.id"
                                class="flex justify-between text-xs text-slate-600"
                            >
                                <span>{{
                                    formatCents(d.denomination_cents)
                                }}</span>
                                <span class="font-medium"
                                    >× {{ d.quantity }}</span
                                >
                            </div>
                        </div>
                        <button
                            @click="acceptTicket(ticket.id)"
                            class="mt-3 w-full rounded-lg bg-red-600 px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-red-700"
                        >
                            Übernehmen
                        </button>
                    </div>
                    <p
                        v-if="ticketStore.openTickets.length === 0"
                        class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-400"
                    >
                        Keine offenen Tickets
                    </p>
                </div>
            </div>

            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h2
                        class="text-sm font-semibold tracking-wide text-amber-600 uppercase"
                    >
                        In Bearbeitung
                    </h2>
                    <span
                        class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700"
                    >
                        {{ ticketStore.acceptedTickets.length }}
                    </span>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="ticket in ticketStore.acceptedTickets"
                        :key="ticket.id"
                        class="rounded-xl border border-amber-100 bg-white p-4 shadow-sm"
                    >
                        <div
                            class="mb-2 flex items-start justify-between gap-2"
                        >
                            <span class="text-sm font-medium text-slate-900">
                                <component
                                    :is="
                                        typeIcons[ticket.type] ??
                                        ClipboardTextIcon
                                    "
                                    :size="14"
                                    class="inline-block"
                                />
                                {{ typeLabel(ticket) }}
                            </span>
                            <span class="flex-shrink-0 text-xs text-slate-400">
                                {{ relativeTime(ticket.created_at) }}
                            </span>
                        </div>
                        <p class="text-sm font-medium text-slate-600">
                            {{ ticket.station.name }}
                        </p>
                        <p class="text-xs text-slate-400">
                            {{ ticket.station.location }}
                        </p>
                        <p
                            v-if="ticket.message"
                            class="mt-2 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-700"
                        >
                            {{ ticket.message }}
                        </p>
                        <div
                            v-if="
                                ticket.type === 'change_request' &&
                                ticket.denominations.length > 0
                            "
                            class="mt-2 space-y-1"
                        >
                            <div
                                v-for="d in ticket.denominations"
                                :key="d.id"
                                class="flex justify-between text-xs text-slate-600"
                            >
                                <span>{{
                                    formatCents(d.denomination_cents)
                                }}</span>
                                <span class="font-medium"
                                    >× {{ d.quantity }}</span
                                >
                            </div>
                        </div>
                        <p
                            v-if="ticket.assigned_user"
                            class="mt-2 text-xs font-medium text-amber-700"
                        >
                            Übernommen von: {{ ticket.assigned_user.name }}
                        </p>
                        <button
                            @click="completeTicket(ticket.id)"
                            class="mt-3 w-full rounded-lg bg-amber-500 px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-amber-600"
                        >
                            Erledigt
                        </button>
                    </div>
                    <p
                        v-if="ticketStore.acceptedTickets.length === 0"
                        class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-400"
                    >
                        Keine Tickets in Bearbeitung
                    </p>
                </div>
            </div>

            <div>
                <div class="mb-3 flex items-center justify-between">
                    <h2
                        class="text-sm font-semibold tracking-wide text-green-600 uppercase"
                    >
                        Erledigt
                    </h2>
                    <span
                        class="rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700"
                    >
                        {{ ticketStore.doneTickets.length }}
                    </span>
                </div>
                <div class="space-y-3">
                    <div
                        v-for="ticket in ticketStore.doneTickets"
                        :key="ticket.id"
                        class="rounded-xl border border-green-100 bg-white p-4 opacity-80 shadow-sm"
                    >
                        <div
                            class="mb-2 flex items-start justify-between gap-2"
                        >
                            <span class="text-sm font-medium text-slate-900">
                                <component
                                    :is="
                                        typeIcons[ticket.type] ??
                                        ClipboardTextIcon
                                    "
                                    :size="14"
                                    class="inline-block"
                                />
                                {{ typeLabel(ticket) }}
                            </span>
                            <span class="flex-shrink-0 text-xs text-slate-400">
                                {{ relativeTime(ticket.created_at) }}
                            </span>
                        </div>
                        <p class="text-sm font-medium text-slate-600">
                            {{ ticket.station.name }}
                        </p>
                        <p class="text-xs text-slate-400">
                            {{ ticket.station.location }}
                        </p>
                        <p
                            v-if="ticket.assigned_user"
                            class="mt-2 text-xs font-medium text-green-700"
                        >
                            Erledigt von: {{ ticket.assigned_user.name }}
                        </p>
                    </div>
                    <p
                        v-if="ticketStore.doneTickets.length === 0"
                        class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-400"
                    >
                        Keine erledigten Tickets (letzte Stunde)
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
