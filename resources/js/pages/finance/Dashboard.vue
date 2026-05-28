<script lang="ts" setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import AccountArrowRightIcon from 'vue-material-design-icons/AccountArrowRight.vue';
import CashMultipleIcon from 'vue-material-design-icons/CashMultiple.vue';
import CheckCircleIcon from 'vue-material-design-icons/CheckCircle.vue';
import ClipboardTextIcon from 'vue-material-design-icons/ClipboardText.vue';
import CurrencyEurIcon from 'vue-material-design-icons/CurrencyEur.vue';
import PlusIcon from 'vue-material-design-icons/Plus.vue';
import PrinterIcon from 'vue-material-design-icons/Printer.vue';
import axios from '@/lib/axios';
import { useTicketStore } from '@/stores/tickets';
import type { Station, Ticket, TicketType } from '@/types';

const ticketStore = useTicketStore();

const now = ref(Date.now());
let timer: ReturnType<typeof setInterval> | null = null;
let pollTimer: ReturnType<typeof setInterval> | null = null;

const showCreateModal = ref(false);
const createStations = ref<Station[]>([]);
const createStationId = ref('');
const createType = ref<TicketType>('other');
const createMessage = ref('');
const createScheduledAt = ref('');
const createSubmitting = ref(false);
const createError = ref<string | null>(null);

const ticketTypes: { value: TicketType; label: string }[] = [
    { value: 'cash_full', label: 'Kasse voll' },
    { value: 'change_request', label: 'Wechselgeld' },
    { value: 'other', label: 'Sonstiges' },
];

async function openCreateModal() {
    showCreateModal.value = true;
    createError.value = null;
    createScheduledAt.value = scheduledAtNowMinus2Min();

    if (createStations.value.length === 0) {
        try {
            const { data } = await axios.get('/api/finance/stations');
            createStations.value = data.data ?? data;
        } catch {
            createError.value = 'Kassen konnten nicht geladen werden.';
        }
    }
}

const TZ = 'Europe/Berlin';

function scheduledAtNowMinus2Min(): string {
    const d = new Date(Date.now() - 2 * 60_000);
    const parts = new Intl.DateTimeFormat('de-DE', {
        timeZone: TZ,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).formatToParts(d);
    const get = (type: string) =>
        parts.find((p) => p.type === type)?.value ?? '00';

    return `${get('year')}-${get('month')}-${get('day')}T${get('hour')}:${get('minute')}`;
}

function closeCreateModal() {
    showCreateModal.value = false;
    createMessage.value = '';
    createScheduledAt.value = '';
    createError.value = null;
}

async function submitCreateTicket(accept: boolean) {
    createSubmitting.value = true;
    createError.value = null;

    try {
        const { data } = await axios.post('/api/finance/tickets', {
            station_id: createStationId.value || null,
            type: createType.value,
            message: createMessage.value || null,
            scheduled_at: createScheduledAt.value || null,
            accept,
        });
        now.value = Date.now();
        ticketStore.addTicket(data.ticket);
        closeCreateModal();
    } catch {
        createError.value = 'Ticket konnte nicht erstellt werden.';
    } finally {
        createSubmitting.value = false;
    }
}

async function fetchTickets() {
    try {
        const { data } = await axios.get('/api/finance/tickets');
        ticketStore.setTickets(data.tickets ?? data);
    } catch (err) {
        console.error('Failed to fetch tickets', err);
    }
}

onMounted(async () => {
    timer = setInterval(() => {
        now.value = Date.now();
    }, 5_000);

    await fetchTickets();
    pollTimer = setInterval(fetchTickets, 500);
});

onUnmounted(() => {
    if (timer !== null) {
        clearInterval(timer);
    }

    if (pollTimer !== null) {
        clearInterval(pollTimer);
    }
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

async function printTicket(id: string, printer: 'station' | 'office') {
    try {
        await axios.post(`/api/finance/tickets/${id}/print`, { printer });
    } catch (err) {
        console.error('Failed to print ticket', err);
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

function isFuture(ticket: Ticket): boolean {
    return new Date(ticket.created_at).getTime() > now.value;
}

function relativeTime(dateStr: string): string {
    const diff = Math.floor((now.value - new Date(dateStr).getTime()) / 1000);

    if (diff < 0) {
        const abs = Math.abs(diff);

        if (abs < 60) {
            return `in ${abs} Sek`;
        }

        if (abs < 3600) {
            return `in ${Math.floor(abs / 60)} Min`;
        }

        if (abs < 86400) {
            return `in ${Math.floor(abs / 3600)} Std`;
        }

        return `in ${Math.floor(abs / 86400)} Tagen`;
    }

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

function absoluteTime(dateStr: string): string {
    return new Date(dateStr).toLocaleString('de-DE', {
        timeZone: TZ,
        day: '2-digit',
        month: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
}

const sortedOpenTickets = computed(() => {
    const future = ticketStore.openTickets
        .filter(isFuture)
        .sort(
            (a, b) =>
                new Date(a.created_at).getTime() -
                new Date(b.created_at).getTime(),
        );
    const present = ticketStore.openTickets.filter((t) => !isFuture(t));

    return [...present, ...future];
});

function formatCents(cents: number): string {
    return (cents / 100).toFixed(2).replace('.', ',') + ' €';
}
</script>

<template>
    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-900">Dashboard</h1>
            <button
                class="flex items-center gap-1.5 rounded-lg bg-gpn-orange px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                @click="openCreateModal"
            >
                <PlusIcon :size="16" />
                Ticket erstellen
            </button>
        </div>

        <Teleport to="body">
            <div
                v-if="showCreateModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
                @click.self="closeCreateModal"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                    <h2 class="mb-4 text-lg font-semibold text-slate-900">
                        Ticket erstellen
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-500 uppercase"
                            >
                                Kasse
                            </label>
                            <select
                                v-model="createStationId"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-gpn-orange focus:outline-none"
                            >
                                <option value="">Keine Kasse</option>
                                <option
                                    v-for="s in createStations"
                                    :key="s.id"
                                    :value="s.id"
                                >
                                    {{ s.name }} – {{ s.location }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-500 uppercase"
                            >
                                Anliegen
                            </label>
                            <select
                                v-model="createType"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-gpn-orange focus:outline-none"
                            >
                                <option
                                    v-for="t in ticketTypes"
                                    :key="t.value"
                                    :value="t.value"
                                >
                                    {{ t.label }}
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-500 uppercase"
                            >
                                Hinweis (optional)
                            </label>
                            <input
                                v-model="createMessage"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-gpn-orange focus:outline-none"
                                placeholder="z.B. Münzen fehlen, ..."
                                type="text"
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-semibold text-slate-500 uppercase"
                            >
                                Zielzeit (optional)
                            </label>
                            <input
                                v-model="createScheduledAt"
                                class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-gpn-orange focus:outline-none"
                                type="datetime-local"
                            />
                        </div>
                        <p v-if="createError" class="text-xs text-red-600">
                            {{ createError }}
                        </p>
                    </div>
                    <div class="mt-5 flex justify-end gap-2">
                        <button
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                            @click="closeCreateModal"
                        >
                            Abbrechen
                        </button>
                        <button
                            :disabled="createSubmitting"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-50"
                            @click="submitCreateTicket(false)"
                        >
                            Erstellen
                        </button>
                        <button
                            :disabled="createSubmitting"
                            class="rounded-lg bg-gpn-orange px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-50"
                            @click="submitCreateTicket(true)"
                        >
                            {{
                                createSubmitting
                                    ? 'Wird erstellt...'
                                    : 'Erstellen & übernehmen'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

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
                        v-for="ticket in sortedOpenTickets"
                        :key="ticket.id"
                        :class="
                            isFuture(ticket)
                                ? 'rounded-xl border border-slate-200 bg-white p-4 opacity-60 shadow-sm'
                                : 'rounded-xl border border-red-100 bg-white p-4 shadow-sm'
                        "
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
                            <div class="flex-shrink-0 text-right">
                                <span
                                    :class="
                                        isFuture(ticket)
                                            ? 'text-xs text-slate-400'
                                            : 'text-xs text-slate-400'
                                    "
                                >
                                    {{ relativeTime(ticket.created_at) }}
                                </span>
                                <div
                                    v-if="isFuture(ticket)"
                                    class="text-xs text-slate-300"
                                >
                                    {{ absoluteTime(ticket.created_at) }}
                                </div>
                            </div>
                        </div>
                        <p class="text-sm font-medium text-slate-600">
                            {{ ticket.station?.name ?? 'Keine Kasse' }}
                        </p>
                        <p v-if="ticket.station" class="text-xs text-slate-400">
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
                            :class="
                                isFuture(ticket)
                                    ? 'mt-3 w-full rounded-lg bg-slate-400 px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-slate-500'
                                    : 'mt-3 w-full rounded-lg bg-red-600 px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-red-700'
                            "
                            @click="acceptTicket(ticket.id)"
                        >
                            <AccountArrowRightIcon
                                :size="14"
                                class="mr-1 inline-block"
                            />
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
                            {{ ticket.station?.name ?? 'Keine Kasse' }}
                        </p>
                        <p v-if="ticket.station" class="text-xs text-slate-400">
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
                            class="mt-3 w-full rounded-lg bg-amber-500 px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-amber-600"
                            @click="completeTicket(ticket.id)"
                        >
                            <CheckCircleIcon
                                :size="14"
                                class="mr-1 inline-block"
                            />
                            Erledigt
                        </button>
                        <div class="mt-1.5 flex gap-1.5">
                            <button
                                v-if="ticket.station?.printer_ip"
                                class="flex-1 rounded-md bg-slate-100 px-2 py-1.5 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-200"
                                @click="printTicket(ticket.id, 'station')"
                            >
                                <PrinterIcon
                                    :size="12"
                                    class="mr-0.5 inline-block"
                                />
                                Kasse
                            </button>
                            <button
                                class="flex-1 rounded-md bg-slate-100 px-2 py-1.5 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-200"
                                @click="printTicket(ticket.id, 'office')"
                            >
                                <PrinterIcon
                                    :size="12"
                                    class="mr-0.5 inline-block"
                                />
                                Büro
                            </button>
                        </div>
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
                            {{ ticket.station?.name ?? 'Keine Kasse' }}
                        </p>
                        <p v-if="ticket.station" class="text-xs text-slate-400">
                            {{ ticket.station.location }}
                        </p>
                        <p
                            v-if="ticket.assigned_user"
                            class="mt-2 text-xs font-medium text-green-700"
                        >
                            Erledigt von: {{ ticket.assigned_user.name }}
                        </p>
                        <div class="mt-2 flex gap-1.5">
                            <button
                                v-if="ticket.station?.printer_ip"
                                class="flex-1 rounded-md bg-slate-100 px-2 py-1.5 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-200"
                                @click="printTicket(ticket.id, 'station')"
                            >
                                <PrinterIcon
                                    :size="12"
                                    class="mr-0.5 inline-block"
                                />
                                Kasse
                            </button>
                            <button
                                class="flex-1 rounded-md bg-slate-100 px-2 py-1.5 text-xs font-semibold text-slate-700 transition-colors hover:bg-slate-200"
                                @click="printTicket(ticket.id, 'office')"
                            >
                                <PrinterIcon
                                    :size="12"
                                    class="mr-0.5 inline-block"
                                />
                                Büro
                            </button>
                        </div>
                    </div>
                    <p
                        v-if="ticketStore.doneTickets.length === 0"
                        class="rounded-xl border border-dashed border-slate-200 p-6 text-center text-sm text-slate-400"
                    >
                        Keine erledigten Tickets
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
