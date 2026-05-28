<script lang="ts" setup>
import { computed, onMounted, ref } from 'vue';
import BankTransferIcon from 'vue-material-design-icons/BankTransfer.vue';
import CheckIcon from 'vue-material-design-icons/Check.vue';
import CloseIcon from 'vue-material-design-icons/Close.vue';
import PlusIcon from 'vue-material-design-icons/Plus.vue';
import RefreshIcon from 'vue-material-design-icons/Refresh.vue';
import axios from '@/lib/axios';

interface PretixBooking {
    direction: 'Income' | 'Expense';
    amount_cents: number;
    amount_eur: string;
    description: string;
    to_from: string;
    booking_text: string;
    metadata: Record<string, unknown>;
    date: string;
}

interface PretixSummary {
    count: number;
    income_eur: string;
    expense_eur: string;
}

interface KassenbuchEintrag {
    id_by_customer: string;
    to_from: string;
    amount: string;
    booking_date: string;
    booking_text?: string;
    purpose?: string;
}

interface KassenbuchSummary {
    count: number;
    income_eur: string;
    expense_eur: string;
    balance_eur: string;
}

interface Station {
    id: string;
    name: string;
    pretix_device_id: number | null;
}

const stations = ref<Station[]>([]);

const stationByDeviceId = computed(() => {
    const map = new Map<number, string>();

    for (const s of stations.value) {
        if (s.pretix_device_id !== null) {
            map.set(s.pretix_device_id, s.name);
        }
    }

    return map;
});

function stationName(deviceId: unknown): string | null {
    return stationByDeviceId.value.get(Number(deviceId)) ?? null;
}

const pretixBookings = ref<PretixBooking[]>([]);
const pretixSummary = ref<PretixSummary | null>(null);
const pretixLoading = ref(false);
const pretixError = ref<string | null>(null);
const pretixFetched = ref(false);
const buchungsStatus = ref<Record<number, 'loading' | 'done' | 'error'>>({});
const pretixExpanded = ref(false);

const kassenbuchEintraege = ref<KassenbuchEintrag[]>([]);
const kassenbuchSummary = ref<KassenbuchSummary | null>(null);
const kassenbuchLoading = ref(false);
const kassenbuchError = ref<string | null>(null);
const kassenbuchFetched = ref(false);
const kassenbuchExpanded = ref(false);

function metadataKey(m: Record<string, unknown>): string {
    return `${m.device_id}-${m.closing_id}-${m.transaction_id}-${m.pretix_transaction_type}`;
}

function parseBookingText(raw?: string): Record<string, unknown> | null {
    if (!raw) {
        return null;
    }

    try {
        return JSON.parse(raw);
    } catch {
        return null;
    }
}

const kassenbuchMetadataKeys = computed(() => {
    const keys = new Set<string>();

    for (const e of kassenbuchEintraege.value) {
        const meta = parseBookingText(e.purpose);

        if (meta) {
            keys.add(metadataKey(meta));
        }
    }

    return keys;
});

function isAlreadyBooked(booking: PretixBooking): boolean {
    return kassenbuchMetadataKeys.value.has(metadataKey(booking.metadata));
}

function isPretixRowVisible(booking: PretixBooking, i: number): boolean {
    if (pretixExpanded.value) {
        return true;
    }

    if (i < 5) {
        return true;
    }

    return !isAlreadyBooked(booking) && buchungsStatus.value[i] !== 'done';
}

const pretixHiddenCount = computed(
    () =>
        pretixBookings.value.filter(
            (b, i) =>
                i >= 5 &&
                (isAlreadyBooked(b) || buchungsStatus.value[i] === 'done'),
        ).length,
);

const kassenbuchHiddenCount = computed(() =>
    Math.max(0, kassenbuchEintraege.value.length - 5),
);

async function fetchStations() {
    try {
        const { data } = await axios.get('/api/finance/stations');
        stations.value = data.data;
    } catch (err) {
        console.error(err);
    }
}

onMounted(() => {
    fetchStations();
    fetchPretix();
    fetchKassenbuch();
});

const showManualModal = ref(false);
const manualForm = ref({
    to_from: '',
    amount: '',
    direction: 'Income' as 'Income' | 'Expense',
    booking_date: new Date().toISOString().slice(0, 10),
});
const manualLoading = ref(false);
const manualError = ref<string | null>(null);

async function fetchPretix() {
    pretixLoading.value = true;
    pretixError.value = null;

    try {
        const { data } = await axios.get(
            '/api/finance/hauptkasse/pretix-bookings',
        );
        pretixBookings.value = data.bookings;
        pretixSummary.value = data.summary;
        pretixFetched.value = true;
    } catch (err) {
        pretixError.value = 'Fehler beim Laden der Pretix-Daten.';
        console.error(err);
    } finally {
        pretixLoading.value = false;
    }
}

async function fetchKassenbuch() {
    kassenbuchLoading.value = true;
    kassenbuchError.value = null;

    try {
        const { data } = await axios.get('/api/finance/hauptkasse/kassenbuch');
        kassenbuchEintraege.value = (data.eintraege as KassenbuchEintrag[])
            .slice()
            .sort(
                (a, b) => Number(b.id_by_customer) - Number(a.id_by_customer),
            );
        kassenbuchSummary.value = data.summary;
        kassenbuchFetched.value = true;
    } catch (err: any) {
        kassenbuchError.value =
            err?.response?.data?.message ??
            'Fehler beim Laden des Kassenbuchs.';
        console.error(err);
    } finally {
        kassenbuchLoading.value = false;
    }
}

async function buchePretixBooking(booking: PretixBooking, index: number) {
    buchungsStatus.value[index] = 'loading';

    try {
        await axios.post('/api/finance/hauptkasse/kassenbuch/from-pretix', {
            direction: booking.direction,
            amount_cents: booking.amount_cents,
            to_from: booking.to_from,
            booking_text: booking.booking_text,
            metadata: booking.metadata,
            date: booking.date,
        });
        buchungsStatus.value[index] = 'done';

        if (kassenbuchFetched.value) {
            fetchKassenbuch();
        }
    } catch (err) {
        buchungsStatus.value[index] = 'error';
        console.error(err);
    }
}

async function submitManualBuchung() {
    manualLoading.value = true;
    manualError.value = null;

    try {
        await axios.post('/api/finance/hauptkasse/kassenbuch', {
            to_from: manualForm.value.to_from,
            direction: manualForm.value.direction,
            amount_cents: Math.round(
                parseFloat(manualForm.value.amount.replace(',', '.')) * 100,
            ),
            booking_date: manualForm.value.booking_date,
        });
        showManualModal.value = false;
        manualForm.value = {
            to_from: '',
            amount: '',
            direction: 'Income',
            booking_date: new Date().toISOString().slice(0, 10),
        };

        if (kassenbuchFetched.value) {
            fetchKassenbuch();
        }
    } catch (err: any) {
        manualError.value =
            err?.response?.data?.message ?? 'Fehler beim Speichern.';
        console.error(err);
    } finally {
        manualLoading.value = false;
    }
}

function reloadPage() {
    location.reload();
}
</script>

<template>
    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-slate-900">
                Hauptkassenverwaltung
            </h1>
            <button
                class="flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-2 text-xs font-medium text-white transition-colors hover:bg-slate-700"
                @click="reloadPage"
            >
                <RefreshIcon :size="14" />
                Neu laden
            </button>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Pretix panel -->
            <div
                class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                <div class="border-b border-slate-100 px-5 py-4">
                    <h2 class="text-sm font-semibold text-slate-900">
                        Pretix Kassenbewegungen
                    </h2>
                </div>

                <div
                    v-if="pretixLoading"
                    class="flex items-center justify-center py-16"
                >
                    <div
                        class="h-7 w-7 animate-spin rounded-full border-2 border-slate-200 border-t-slate-700"
                    ></div>
                </div>

                <div
                    v-else-if="pretixError"
                    class="mx-4 mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-xs text-red-700"
                >
                    {{ pretixError }}
                </div>

                <template v-if="pretixFetched && pretixSummary">
                    <div
                        class="grid grid-cols-3 gap-px border-b border-slate-100 bg-slate-100"
                    >
                        <div class="bg-white px-4 py-3">
                            <p class="text-xs text-slate-400">Buchungen</p>
                            <p class="mt-0.5 text-lg font-bold text-slate-900">
                                {{ pretixSummary.count }}
                            </p>
                        </div>
                        <div class="bg-white px-4 py-3">
                            <p class="text-xs text-red-500">Entnahmen</p>
                            <p class="mt-0.5 text-lg font-bold text-red-600">
                                {{ pretixSummary.income_eur }} €
                            </p>
                        </div>
                        <div class="bg-white px-4 py-3">
                            <p class="text-xs text-green-600">Einlagen</p>
                            <p class="mt-0.5 text-lg font-bold text-green-700">
                                {{ pretixSummary.expense_eur }} €
                            </p>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-slate-50">
                                <tr class="border-b border-slate-100">
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-semibold tracking-wide text-slate-400 uppercase"
                                    >
                                        Datum
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-semibold tracking-wide text-slate-400 uppercase"
                                    >
                                        Buchungstext
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-right text-xs font-semibold tracking-wide text-slate-400 uppercase"
                                    >
                                        Betrag
                                    </th>
                                    <th class="px-4 py-2.5"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr
                                    v-for="(booking, i) in pretixBookings"
                                    :key="i"
                                    v-show="isPretixRowVisible(booking, i)"
                                    class="group"
                                    :class="
                                        buchungsStatus[i] === 'done' ||
                                        isAlreadyBooked(booking)
                                            ? 'hover:bg-slate-50'
                                            : 'bg-red-50 hover:bg-red-100'
                                    "
                                >
                                    <td
                                        class="px-4 py-3 align-top whitespace-nowrap tabular-nums"
                                    >
                                        <div class="text-xs text-slate-700">
                                            {{ booking.date.slice(0, 10) }}
                                        </div>
                                        <div class="text-xs text-slate-400">
                                            {{ booking.date.slice(11) }}
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-xs text-slate-600"
                                    >
                                        <div class="flex items-start gap-1.5">
                                            <BankTransferIcon
                                                :size="13"
                                                class="mt-0.5 shrink-0"
                                                :class="
                                                    booking.direction ===
                                                    'Expense'
                                                        ? 'text-green-500'
                                                        : 'text-red-400'
                                                "
                                            />
                                            <div>
                                                <span class="break-all">{{
                                                    booking.to_from
                                                }}</span>
                                                <div
                                                    class="mt-0.5 text-xs text-slate-400"
                                                >
                                                    Closing
                                                    {{
                                                        booking.metadata
                                                            .closing_id
                                                    }}
                                                    | Tx
                                                    {{
                                                        booking.metadata
                                                            .transaction_id
                                                    }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right align-top text-xs font-medium whitespace-nowrap tabular-nums"
                                        :class="
                                            booking.direction === 'Expense'
                                                ? 'text-green-700'
                                                : 'text-red-600'
                                        "
                                    >
                                        {{
                                            booking.direction === 'Expense'
                                                ? '+'
                                                : '-'
                                        }}{{ booking.amount_eur }} €
                                    </td>
                                    <td class="px-4 py-3 text-right align-top">
                                        <button
                                            v-if="
                                                buchungsStatus[i] === 'done' ||
                                                isAlreadyBooked(booking)
                                            "
                                            disabled
                                            class="flex cursor-default items-center gap-1 rounded-md border border-green-200 bg-green-50 px-2 py-1 text-xs font-medium text-green-600"
                                        >
                                            <CheckIcon :size="12" />
                                            Gebucht
                                        </button>
                                        <button
                                            v-else
                                            :disabled="
                                                buchungsStatus[i] === 'loading'
                                            "
                                            class="flex items-center gap-1 rounded-md border px-2 py-1 text-xs font-medium transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                                            :class="
                                                buchungsStatus[i] === 'error'
                                                    ? 'border-red-200 text-red-500 hover:bg-red-50'
                                                    : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'
                                            "
                                            @click="
                                                buchePretixBooking(booking, i)
                                            "
                                        >
                                            <RefreshIcon
                                                v-if="
                                                    buchungsStatus[i] ===
                                                    'loading'
                                                "
                                                :size="12"
                                                class="animate-spin"
                                            />
                                            <CheckIcon v-else :size="12" />
                                            {{
                                                buchungsStatus[i] === 'error'
                                                    ? 'Fehler'
                                                    : 'Buchen'
                                            }}
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div
                            v-if="!pretixExpanded && pretixHiddenCount > 0"
                            class="border-t border-slate-100 px-4 py-2.5"
                        >
                            <button
                                class="w-full text-center text-xs font-medium text-slate-500 transition-colors hover:text-slate-700"
                                @click="pretixExpanded = true"
                            >
                                + {{ pretixHiddenCount }} weitere anzeigen
                            </button>
                        </div>
                        <div
                            v-else-if="
                                pretixExpanded && pretixBookings.length > 5
                            "
                            class="border-t border-slate-100 px-4 py-2.5"
                        >
                            <button
                                class="w-full text-center text-xs font-medium text-slate-500 transition-colors hover:text-slate-700"
                                @click="pretixExpanded = false"
                            >
                                Weniger anzeigen
                            </button>
                        </div>
                    </div>
                </template>
            </div>

            <div
                class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
                >
                    <h2 class="text-sm font-semibold text-slate-900">
                        Kassenbuch Hauptkasse
                    </h2>
                    <button
                        class="flex items-center gap-1.5 rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50"
                        @click="showManualModal = true"
                    >
                        <PlusIcon :size="14" />
                        Manuell buchen
                    </button>
                </div>

                <div
                    v-if="kassenbuchLoading"
                    class="flex items-center justify-center py-16"
                >
                    <div
                        class="h-7 w-7 animate-spin rounded-full border-2 border-slate-200 border-t-slate-700"
                    ></div>
                </div>

                <div
                    v-else-if="kassenbuchError"
                    class="mx-4 mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2.5 text-xs text-red-700"
                >
                    {{ kassenbuchError }}
                </div>

                <template v-if="kassenbuchFetched && kassenbuchSummary">
                    <div
                        class="grid grid-cols-4 gap-px border-b border-slate-100 bg-slate-100"
                    >
                        <div class="bg-white px-4 py-3">
                            <p class="text-xs text-slate-400">Einträge</p>
                            <p class="mt-0.5 text-lg font-bold text-slate-900">
                                {{ kassenbuchSummary.count }}
                            </p>
                        </div>
                        <div class="bg-white px-4 py-3">
                            <p class="text-xs text-green-600">Einlagen</p>
                            <p class="mt-0.5 text-lg font-bold text-green-700">
                                {{ kassenbuchSummary.income_eur }} €
                            </p>
                        </div>
                        <div class="bg-white px-4 py-3">
                            <p class="text-xs text-red-500">Entnahmen</p>
                            <p class="mt-0.5 text-lg font-bold text-red-600">
                                {{ kassenbuchSummary.expense_eur }} €
                            </p>
                        </div>
                        <div class="bg-white px-4 py-3">
                            <p class="text-xs text-slate-400">Saldo</p>
                            <p class="mt-0.5 text-lg font-bold text-slate-900">
                                {{ kassenbuchSummary.balance_eur }} €
                            </p>
                        </div>
                    </div>

                    <div class="flex-1 overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-slate-50">
                                <tr class="border-b border-slate-100">
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-semibold tracking-wide text-slate-400 uppercase"
                                    >
                                        ID
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-semibold tracking-wide text-slate-400 uppercase"
                                    >
                                        Datum
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-left text-xs font-semibold tracking-wide text-slate-400 uppercase"
                                    >
                                        Buchungstext
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-right text-xs font-semibold tracking-wide text-slate-400 uppercase"
                                    >
                                        Betrag
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                <tr
                                    v-for="(eintrag, i) in kassenbuchEintraege"
                                    :key="eintrag.id_by_customer"
                                    v-show="kassenbuchExpanded || i < 5"
                                    class="hover:bg-slate-50"
                                >
                                    <td
                                        class="px-4 py-3 font-mono text-xs whitespace-nowrap text-slate-400 tabular-nums"
                                    >
                                        {{ eintrag.id_by_customer }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-xs whitespace-nowrap text-slate-500 tabular-nums"
                                    >
                                        {{ eintrag.booking_date.slice(0, 10) }}
                                    </td>
                                    <td
                                        class="px-4 py-3 text-xs text-slate-700"
                                    >
                                        <div>{{ eintrag.to_from }}</div>
                                        <template
                                            v-for="(meta, _i) in [
                                                parseBookingText(
                                                    eintrag.purpose,
                                                ),
                                            ]"
                                            :key="_i"
                                        >
                                            <div
                                                v-if="meta"
                                                class="mt-0.5 text-xs text-slate-400"
                                            >
                                                {{
                                                    stationName(
                                                        meta.device_id,
                                                    ) ??
                                                    `Device ${meta.device_id}`
                                                }}
                                                | Closing
                                                {{ meta.closing_id }} | Tx
                                                {{ meta.transaction_id }}
                                            </div>
                                        </template>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-right text-xs font-medium whitespace-nowrap tabular-nums"
                                        :class="
                                            parseFloat(eintrag.amount) >= 0
                                                ? 'text-green-700'
                                                : 'text-red-600'
                                        "
                                    >
                                        {{
                                            parseFloat(eintrag.amount) >= 0
                                                ? '+'
                                                : ''
                                        }}{{ eintrag.amount }} €
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div
                            v-if="
                                !kassenbuchExpanded && kassenbuchHiddenCount > 0
                            "
                            class="border-t border-slate-100 px-4 py-2.5"
                        >
                            <button
                                class="w-full text-center text-xs font-medium text-slate-500 transition-colors hover:text-slate-700"
                                @click="kassenbuchExpanded = true"
                            >
                                + {{ kassenbuchHiddenCount }} weitere anzeigen
                            </button>
                        </div>
                        <div
                            v-else-if="
                                kassenbuchExpanded &&
                                kassenbuchEintraege.length > 5
                            "
                            class="border-t border-slate-100 px-4 py-2.5"
                        >
                            <button
                                class="w-full text-center text-xs font-medium text-slate-500 transition-colors hover:text-slate-700"
                                @click="kassenbuchExpanded = false"
                            >
                                Weniger anzeigen
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <Transition name="fade">
        <div
            v-if="showManualModal"
            class="fixed inset-0 z-40 flex items-center justify-center bg-black/50 p-4"
            @click.self="showManualModal = false"
        >
            <div class="w-full max-w-sm rounded-xl bg-white shadow-xl">
                <div
                    class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
                >
                    <h3 class="text-sm font-semibold text-slate-900">
                        Manuelle Buchung
                    </h3>
                    <button
                        class="text-slate-400 hover:text-slate-600"
                        @click="showManualModal = false"
                    >
                        <CloseIcon :size="18" />
                    </button>
                </div>

                <form
                    class="space-y-4 px-5 py-4"
                    @submit.prevent="submitManualBuchung"
                >
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-slate-700"
                            >Datum</label
                        >
                        <input
                            v-model="manualForm.booking_date"
                            type="date"
                            required
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 focus:border-slate-400 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-slate-700"
                            >Buchungstext</label
                        >
                        <input
                            v-model="manualForm.to_from"
                            type="text"
                            required
                            placeholder="z.B. Bankeinzahlung Hauptkasse"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-400 focus:outline-none"
                        />
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium text-slate-700"
                            >Betrag</label
                        >
                        <div class="flex gap-2">
                            <div
                                class="flex overflow-hidden rounded-lg border border-slate-200 text-xs font-medium"
                            >
                                <button
                                    type="button"
                                    class="px-3 py-2 transition-colors"
                                    :class="
                                        manualForm.direction === 'Income'
                                            ? 'bg-green-600 text-white'
                                            : 'bg-white text-slate-500 hover:bg-slate-50'
                                    "
                                    @click="manualForm.direction = 'Income'"
                                >
                                    + Einlage
                                </button>
                                <button
                                    type="button"
                                    class="px-3 py-2 transition-colors"
                                    :class="
                                        manualForm.direction === 'Expense'
                                            ? 'bg-red-500 text-white'
                                            : 'bg-white text-slate-500 hover:bg-slate-50'
                                    "
                                    @click="manualForm.direction = 'Expense'"
                                >
                                    − Entnahme
                                </button>
                            </div>
                            <input
                                v-model="manualForm.amount"
                                type="text"
                                inputmode="decimal"
                                required
                                placeholder="0,00"
                                class="min-w-0 flex-1 rounded-lg border border-slate-200 px-3 py-2 text-right text-sm text-slate-900 placeholder-slate-400 focus:border-slate-400 focus:outline-none"
                            />
                        </div>
                    </div>

                    <div
                        v-if="manualError"
                        class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700"
                    >
                        {{ manualError }}
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button
                            type="button"
                            class="flex-1 rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
                            @click="showManualModal = false"
                        >
                            Abbrechen
                        </button>
                        <button
                            type="submit"
                            :disabled="manualLoading"
                            class="flex-1 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {{
                                manualLoading ? 'Wird gespeichert' : 'Speichern'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
