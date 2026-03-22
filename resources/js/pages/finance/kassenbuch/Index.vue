<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import AlertIcon from 'vue-material-design-icons/Alert.vue';
import BookCheckIcon from 'vue-material-design-icons/BookCheck.vue';
import CheckIcon from 'vue-material-design-icons/Check.vue';
import CloseIcon from 'vue-material-design-icons/Close.vue';
import DownloadIcon from 'vue-material-design-icons/Download.vue';
import PlusIcon from 'vue-material-design-icons/Plus.vue';
import UndoIcon from 'vue-material-design-icons/Undo.vue';
import axios from '@/lib/axios';

interface CashEntry {
    id: string;
    type: string;
    type_label: string;
    amount_cents: number;
    description: string | null;
    created_by: string;
    created_at: string;
    ticket_id: string | null;
    ticket_done_at: string | null;
    counterpart_station_id: string | null;
    counterpart_station_name: string | null;
    reversed_at: string | null;
    reversed_by_entry_id: string | null;
}

interface CashClosing {
    id: string;
    label: string;
    closing_date: string;
    locked_until: string;
    balance_cents: number;
    created_by: string;
    created_at: string;
}

interface StationBalance {
    id: string;
    name: string;
    location: string;
    balance_cents: number;
}

interface Suggestion {
    ticket_id: string;
    ticket_type: string;
    station_id: string;
    station_name: string;
    done_at: string | null;
    suggested_type: string;
    suggested_amount_cents: number | null;
}

const entries = ref<CashEntry[]>([]);
const closings = ref<CashClosing[]>([]);
const stations = ref<StationBalance[]>([]);
const suggestions = ref<Suggestion[]>([]);
const balanceCents = ref(0);
const lockedUntil = ref<string | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);

const showEntryForm = ref(false);
const showClosingForm = ref(false);

const entryForm = reactive({
    type: 'deposit',
    amount_cents_display: '',
    description: '',
    ticket_id: '',
    counterpart_station_id: '',
});

const closingForm = reactive({
    label: '',
    closing_date: new Date().toISOString().slice(0, 10),
    locked_until: new Date().toISOString().slice(0, 16),
});

const entryFormError = ref<string | null>(null);
const closingFormError = ref<string | null>(null);
const submitting = ref(false);

const entryTypes = [
    { value: 'opening', label: 'Anfangsbestand' },
    { value: 'deposit', label: 'Einzahlung' },
    { value: 'withdrawal', label: 'Auszahlung' },
    { value: 'cash_drawer_open', label: 'Kassenöffnung' },
    { value: 'cash_drawer_close', label: 'Kassenschließung' },
    { value: 'transfer_in', label: 'Abschöpfung' },
    { value: 'transfer_out', label: 'Wechselgeld ausgegeben' },
];

const creditTypes = new Set([
    'opening',
    'deposit',
    'transfer_in',
    'cash_drawer_close',
]);

const noCounterpartTypes = new Set(['opening', 'reversal']);

const showCounterpart = computed(() => !noCounterpartTypes.has(entryForm.type));

const entriesWithRunning = computed(() => {
    let running = 0;

    return entries.value.map((e) => {
        running += e.amount_cents;

        return { ...e, running };
    });
});

async function load() {
    loading.value = true;
    error.value = null;

    try {
        const { data } = await axios.get('/api/finance/cash-ledger');
        entries.value = data.entries;
        closings.value = data.closings;
        stations.value = data.stations;
        balanceCents.value = data.balance_cents;
        lockedUntil.value = data.locked_until ?? null;
        suggestions.value = data.suggestions;
    } catch {
        error.value = 'Kassenbuch konnte nicht geladen werden.';
    } finally {
        loading.value = false;
    }
}

onMounted(load);

function formatCents(cents: number): string {
    const sign = cents >= 0 ? '+' : '';

    return sign + (cents / 100).toFixed(2).replace('.', ',') + ' \u20ac';
}

function formatBalance(cents: number): string {
    return (cents / 100).toFixed(2).replace('.', ',') + ' \u20ac';
}

function formatDate(iso: string): string {
    return new Date(iso).toLocaleString('de-DE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function formatTicketDate(iso: string): string {
    const d = new Date(iso);
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const hour = String(d.getHours()).padStart(2, '0');
    const minute = String(d.getMinutes()).padStart(2, '0');

    return `${day}.${month}. ${hour}:${minute} Uhr`;
}

function isLocked(entry: CashEntry): boolean {
    if (!lockedUntil.value) {
        return false;
    }

    return new Date(entry.created_at) <= new Date(lockedUntil.value);
}

async function submitEntry() {
    entryFormError.value = null;
    submitting.value = true;

    const rawAmount = parseFloat(
        entryForm.amount_cents_display.replace(',', '.'),
    );

    if (isNaN(rawAmount) || rawAmount <= 0) {
        entryFormError.value = 'Bitte einen positiven Betrag eingeben.';
        submitting.value = false;

        return;
    }

    const sign = creditTypes.has(entryForm.type) ? 1 : -1;
    const amount_cents = Math.round(rawAmount * 100) * sign;

    try {
        await axios.post('/api/finance/cash-ledger/entries', {
            type: entryForm.type,
            amount_cents,
            description: entryForm.description || null,
            ticket_id: entryForm.ticket_id || null,
            counterpart_station_id: entryForm.counterpart_station_id || null,
        });

        entryForm.type = 'deposit';
        entryForm.amount_cents_display = '';
        entryForm.description = '';
        entryForm.ticket_id = '';
        entryForm.counterpart_station_id = '';
        showEntryForm.value = false;
        await load();
    } catch (e: unknown) {
        const msg = (e as { response?: { data?: { message?: string } } })
            ?.response?.data?.message;
        entryFormError.value = msg ?? 'Fehler beim Speichern.';
    } finally {
        submitting.value = false;
    }
}

async function acceptSuggestion(suggestion: Suggestion) {
    const amountCents = suggestion.suggested_amount_cents;

    if (amountCents === null) {
        entryForm.type = suggestion.suggested_type;
        entryForm.ticket_id = suggestion.ticket_id;
        entryForm.description = '';
        entryForm.counterpart_station_id = suggestion.station_id;
        showEntryForm.value = true;

        return;
    }

    try {
        await axios.post('/api/finance/cash-ledger/entries', {
            type: suggestion.suggested_type,
            amount_cents: amountCents,
            description: null,
            ticket_id: suggestion.ticket_id,
            counterpart_station_id: suggestion.station_id,
        });
        await load();
    } catch (e: unknown) {
        const msg = (e as { response?: { data?: { message?: string } } })
            ?.response?.data?.message;
        error.value = msg ?? 'Fehler beim Übernehmen.';
    }
}

async function reverseEntry(entry: CashEntry) {
    if (
        !confirm(
            `Buchung "${entry.description ?? entry.type_label}" wirklich stornieren?`,
        )
    ) {
        return;
    }

    try {
        await axios.post(
            `/api/finance/cash-ledger/entries/${entry.id}/reversal`,
        );
        await load();
    } catch (e: unknown) {
        const msg = (e as { response?: { data?: { message?: string } } })
            ?.response?.data?.message;
        error.value = msg ?? 'Stornierung fehlgeschlagen.';
    }
}

async function submitClosing() {
    closingFormError.value = null;
    submitting.value = true;

    try {
        await axios.post('/api/finance/cash-ledger/closings', {
            label: closingForm.label,
            closing_date: closingForm.closing_date,
            locked_until: new Date(closingForm.locked_until).toISOString(),
        });

        closingForm.label = '';
        showClosingForm.value = false;
        await load();
    } catch (e: unknown) {
        const msg = (e as { response?: { data?: { message?: string } } })
            ?.response?.data?.message;
        closingFormError.value =
            msg ?? 'Fehler beim Erstellen des Abschlusses.';
    } finally {
        submitting.value = false;
    }
}

function downloadExport(type: 'csv' | 'pdf') {
    window.open(`/api/finance/cash-ledger/export/${type}`, '_blank');
}
</script>

<template>
    <div class="p-6">
        <div class="mb-6 flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Kassenbuch</h1>
                <p class="mt-1 text-sm text-slate-500">Hauptkasse</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <button
                    @click="downloadExport('csv')"
                    class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                >
                    <DownloadIcon :size="16" />
                    CSV
                </button>
                <button
                    @click="downloadExport('pdf')"
                    class="flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                >
                    <DownloadIcon :size="16" />
                    PDF
                </button>
                <button
                    @click="showClosingForm = !showClosingForm"
                    class="flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                >
                    <BookCheckIcon :size="16" />
                    Abschluss
                </button>
                <button
                    @click="showEntryForm = !showEntryForm"
                    class="flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-700"
                >
                    <PlusIcon :size="16" />
                    Buchung
                </button>
            </div>
        </div>

        <div
            v-if="error"
            class="mb-4 flex items-center gap-2 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            <AlertIcon :size="16" />
            {{ error }}
        </div>

        <div class="mb-6 flex flex-wrap gap-4">
            <div
                class="flex min-w-48 flex-1 items-center justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div>
                    <p
                        class="text-xs font-semibold tracking-wide text-slate-500 uppercase"
                    >
                        Hauptkasse
                    </p>
                    <p
                        class="mt-1 text-3xl font-bold"
                        :class="
                            balanceCents >= 0
                                ? 'text-slate-900'
                                : 'text-red-600'
                        "
                    >
                        {{ formatBalance(balanceCents) }}
                    </p>
                </div>
                <div v-if="lockedUntil" class="text-right">
                    <p
                        class="text-xs font-semibold tracking-wide text-slate-500 uppercase"
                    >
                        Gesperrt bis
                    </p>
                    <p class="mt-1 text-sm font-medium text-amber-700">
                        {{ formatDate(lockedUntil) }}
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="showEntryForm"
            class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
        >
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-900">
                    Neue Buchung
                </h2>
                <button
                    @click="showEntryForm = false"
                    class="text-slate-400 hover:text-slate-700"
                >
                    <CloseIcon :size="18" />
                </button>
            </div>
            <form
                @submit.prevent="submitEntry"
                class="grid grid-cols-1 gap-4 sm:grid-cols-2"
            >
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-700"
                        >Typ</label
                    >
                    <select
                        v-model="entryForm.type"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none"
                    >
                        <option
                            v-for="t in entryTypes"
                            :key="t.value"
                            :value="t.value"
                        >
                            {{ t.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-slate-700"
                    >
                        Betrag (€)
                        <span class="text-slate-400">
                            &mdash;
                            {{
                                creditTypes.has(entryForm.type)
                                    ? 'Einnahme'
                                    : 'Ausgabe'
                            }}
                        </span>
                    </label>
                    <input
                        v-model="entryForm.amount_cents_display"
                        type="text"
                        inputmode="decimal"
                        placeholder="0,00"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none"
                    />
                </div>
                <div v-if="showCounterpart">
                    <label
                        class="mb-1 block text-xs font-medium text-slate-700"
                    >
                        Gegenkasse
                        <span class="text-slate-400">(optional)</span>
                    </label>
                    <select
                        v-model="entryForm.counterpart_station_id"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none"
                    >
                        <option value="">Keine</option>
                        <option v-for="s in stations" :key="s.id" :value="s.id">
                            {{ s.name }}
                        </option>
                    </select>
                </div>
                <div :class="showCounterpart ? '' : 'sm:col-span-2'">
                    <label class="mb-1 block text-xs font-medium text-slate-700"
                        >Beschreibung (optional)</label
                    >
                    <input
                        v-model="entryForm.description"
                        type="text"
                        placeholder="z.B. Kassenöffnung morgens"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:ring-2 focus:ring-slate-900 focus:outline-none"
                    />
                </div>
                <div v-if="entryFormError" class="sm:col-span-2">
                    <p class="text-xs text-red-600">{{ entryFormError }}</p>
                </div>
                <div class="sm:col-span-2">
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-700 disabled:opacity-50"
                    >
                        Speichern
                    </button>
                </div>
            </form>
        </div>

        <div
            v-if="showClosingForm"
            class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-5"
        >
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-amber-900">
                    Tagesabschluss erstellen
                </h2>
                <button
                    @click="showClosingForm = false"
                    class="text-amber-600 hover:text-amber-900"
                >
                    <CloseIcon :size="18" />
                </button>
            </div>
            <p class="mb-4 text-xs text-amber-800">
                Alle Buchungen bis zum gewählten Zeitpunkt werden gesperrt und
                können nicht mehr storniert werden.
            </p>
            <form
                @submit.prevent="submitClosing"
                class="grid grid-cols-1 gap-4 sm:grid-cols-3"
            >
                <div>
                    <label class="mb-1 block text-xs font-medium text-amber-900"
                        >Bezeichnung</label
                    >
                    <input
                        v-model="closingForm.label"
                        type="text"
                        required
                        placeholder="z.B. Tagesabschluss 22.03."
                        class="w-full rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-amber-900"
                        >Datum des Abschlusses</label
                    >
                    <input
                        v-model="closingForm.closing_date"
                        type="date"
                        required
                        class="w-full rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-amber-900"
                        >Gesperrt bis (Zeitpunkt)</label
                    >
                    <input
                        v-model="closingForm.locked_until"
                        type="datetime-local"
                        required
                        class="w-full rounded-lg border border-amber-200 bg-white px-3 py-2 text-sm focus:ring-2 focus:ring-amber-500 focus:outline-none"
                    />
                </div>
                <div v-if="closingFormError" class="sm:col-span-3">
                    <p class="text-xs text-red-600">{{ closingFormError }}</p>
                </div>
                <div class="sm:col-span-3">
                    <button
                        type="submit"
                        :disabled="submitting"
                        class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-amber-700 disabled:opacity-50"
                    >
                        Abschluss erstellen
                    </button>
                </div>
            </form>
        </div>

        <div v-if="suggestions.length > 0" class="mb-6">
            <h2 class="mb-3 text-sm font-semibold text-slate-700">
                Buchungsvorschläge aus erledigten Tickets
            </h2>
            <div class="space-y-2">
                <div
                    v-for="s in suggestions"
                    :key="s.ticket_id"
                    class="flex items-center justify-between gap-4 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3"
                >
                    <div>
                        <p class="text-sm font-medium text-blue-900">
                            {{ s.station_name }}
                            <span
                                class="ml-2 text-xs font-normal text-blue-600"
                            >
                                {{
                                    s.ticket_type === 'change_request'
                                        ? 'Wechselgeld ausgegeben'
                                        : 'Abschöpfung'
                                }}
                            </span>
                        </p>
                        <p
                            v-if="s.suggested_amount_cents !== null"
                            class="text-xs text-blue-700"
                        >
                            {{
                                formatBalance(
                                    Math.abs(s.suggested_amount_cents),
                                )
                            }}
                        </p>
                        <p v-else class="text-xs text-blue-600">
                            Betrag manuell eingeben
                        </p>
                    </div>
                    <button
                        @click="acceptSuggestion(s)"
                        class="flex flex-shrink-0 items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-blue-700"
                    >
                        <CheckIcon :size="14" />
                        Übernehmen
                    </button>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">Buchungen</h2>
            </div>

            <div v-if="loading" class="p-8 text-center text-sm text-slate-400">
                Wird geladen...
            </div>

            <div
                v-else-if="entries.length === 0"
                class="p-8 text-center text-sm text-slate-400"
            >
                Noch keine Buchungen vorhanden.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b border-slate-100 bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            <th class="px-4 py-3">Datum</th>
                            <th class="px-4 py-3">Typ</th>
                            <th class="px-4 py-3">Gegenkasse</th>
                            <th class="px-4 py-3">Beschreibung</th>
                            <th class="px-4 py-3 text-right">Betrag</th>
                            <th class="px-4 py-3 text-right">Saldo</th>
                            <th class="px-4 py-3">Erfasst von</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="entry in entriesWithRunning"
                            :key="entry.id"
                            :class="entry.reversed_at ? 'opacity-40' : ''"
                        >
                            <td
                                class="px-4 py-3 text-xs whitespace-nowrap text-slate-500"
                            >
                                {{ formatDate(entry.created_at) }}
                                <span
                                    v-if="isLocked(entry)"
                                    class="ml-1 text-amber-500"
                                    title="Gesperrt"
                                    >&#x1F512;</span
                                >
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-md px-2 py-0.5 text-xs font-medium"
                                    :class="{
                                        'bg-green-100 text-green-800': [
                                            'opening',
                                            'deposit',
                                            'transfer_in',
                                            'cash_drawer_close',
                                        ].includes(entry.type),
                                        'bg-red-100 text-red-800': [
                                            'withdrawal',
                                            'transfer_out',
                                            'cash_drawer_open',
                                        ].includes(entry.type),
                                        'bg-slate-100 text-slate-600':
                                            entry.type === 'reversal',
                                    }"
                                >
                                    {{ entry.type_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                {{ entry.counterpart_station_name ?? '' }}
                            </td>
                            <td class="px-4 py-3 text-slate-700">
                                <span
                                    :class="
                                        entry.reversed_at ? 'line-through' : ''
                                    "
                                >
                                    {{ entry.description ?? '' }}
                                </span>
                                <span
                                    v-if="entry.reversed_at"
                                    class="ml-2 rounded bg-red-100 px-1.5 py-0.5 text-xs text-red-600"
                                >
                                    Storniert
                                </span>
                                <span
                                    v-if="
                                        entry.ticket_id && entry.ticket_done_at
                                    "
                                    class="mt-0.5 block text-xs text-slate-400"
                                >
                                    Ticket vom
                                    {{ formatTicketDate(entry.ticket_done_at) }}
                                </span>
                            </td>
                            <td
                                class="px-4 py-3 text-right font-mono font-medium whitespace-nowrap"
                                :class="
                                    entry.amount_cents >= 0
                                        ? 'text-green-700'
                                        : 'text-red-700'
                                "
                            >
                                {{ formatCents(entry.amount_cents) }}
                            </td>
                            <td
                                class="px-4 py-3 text-right font-mono text-xs whitespace-nowrap text-slate-600"
                            >
                                {{ formatBalance(entry.running) }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                {{ entry.created_by }}
                            </td>
                            <td class="px-4 py-3">
                                <button
                                    v-if="
                                        !entry.reversed_at && !isLocked(entry)
                                    "
                                    @click="reverseEntry(entry)"
                                    class="text-slate-300 transition-colors hover:text-red-500"
                                    title="Stornieren"
                                >
                                    <UndoIcon :size="16" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-if="closings.length > 0"
            class="mt-6 rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-900">
                    Tagesabschlüsse
                </h2>
            </div>
            <table class="w-full text-sm">
                <thead>
                    <tr
                        class="border-b border-slate-100 bg-slate-50 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase"
                    >
                        <th class="px-4 py-3">Bezeichnung</th>
                        <th class="px-4 py-3">Datum</th>
                        <th class="px-4 py-3">Gesperrt bis</th>
                        <th class="px-4 py-3 text-right">Saldo</th>
                        <th class="px-4 py-3">Erstellt von</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="closing in closings" :key="closing.id">
                        <td class="px-4 py-3 font-medium text-slate-900">
                            {{ closing.label }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ closing.closing_date }}
                        </td>
                        <td class="px-4 py-3 text-xs text-amber-700">
                            {{ formatDate(closing.locked_until) }}
                        </td>
                        <td
                            class="px-4 py-3 text-right font-mono font-medium text-slate-900"
                        >
                            {{ formatBalance(closing.balance_cents) }}
                        </td>
                        <td class="px-4 py-3 text-xs text-slate-500">
                            {{ closing.created_by }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
