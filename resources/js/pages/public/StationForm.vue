<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import CashMultipleIcon from 'vue-material-design-icons/CashMultiple.vue';
import ClipboardTextIcon from 'vue-material-design-icons/ClipboardText.vue';
import CurrencyEurIcon from 'vue-material-design-icons/CurrencyEur.vue';
import { useRoute, useRouter } from 'vue-router';
import DenominationPicker from '@/components/DenominationPicker.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import axios from '@/lib/axios';
import type { Station, TicketType } from '@/types';
import { DENOMINATIONS } from '@/types';

const route = useRoute();
const router = useRouter();

const token = route.params.token as string;
const typeFromQuery = route.query.type as TicketType | undefined;

const station = ref<Station | null>(null);
const loading = ref(true);
const submitting = ref(false);
const error = ref<string | null>(null);
const submitError = ref<string | null>(null);

const selectedType = ref<TicketType | null>(typeFromQuery ?? null);
const message = ref('');
const otherText = ref('');
const denominations = ref<Record<number, number>>(
    Object.fromEntries(DENOMINATIONS.map((d) => [d.cents, 0])),
);

const typeLabels: Record<TicketType, string> = {
    cash_full: 'Kasse voll',
    change_request: 'Wechselgeld anfordern',
    other: 'Sonstige Meldung',
};

const typeDescriptions: Record<TicketType, string> = {
    cash_full:
        'Eskaliere dein Kapazitätsproblem direkt an das zuständige Liquiditäts-Management-Center.',
    change_request:
        'Initiiere einen Kleinwährungsoptimierungs-Request zur Sicherstellung deiner operativen Transaktionsfähigkeit.',
    other: 'Übermittle ein generisches Anliegen an das GELD-Team zur weiteren Priorisierung und Bearbeitung.',
};

const typeIcons: Record<TicketType, typeof CashMultipleIcon> = {
    cash_full: CashMultipleIcon,
    change_request: CurrencyEurIcon,
    other: ClipboardTextIcon,
};

const corporateNames: Record<string, string[]> = {
    default: [
        'GPN Bargeld-Infrastruktur-Komitee e.V.',
        'Gulaschprogrammiernacht Finance Division™',
        'Topf & Thaler GmbH · Professionelles Kassenmanagement',
        'GPN Münzverwaltung AG · Liquid Assets since 2012',
    ],
    cash_full: [
        'GPN KassenVoll-Notfall-Portal™ · Bargeld-Incident-Response',
        'Topf leer, Kasse voll® · GPN Emergency Finance Response',
        'Gulaschprogrammiernacht Kassenüberlauf-Meldestelle™',
        'GPN Cash-Capacity-Alerting-Suite® · Scharf & Schnell',
    ],
    change_request: [
        'GPN Kleingeld-Logistik-Hub™ · Münzen as a Service',
        'Wechselstu.be® · Powered by Gulasch & Kaffeesatz',
        'Gulaschprogrammiernacht Münz-Beschaffungs-Portal™',
        'GPN WechselGeld-Fulfillment-Suite® · Coin Division',
    ],
    other: [
        'GPN Finanz-Kommunikations-Kanal™ · Sonstiges Division',
        'Gulaschprogrammiernacht Kassen-Helpdesk® · Allgemein',
        'GPN Bargeld-Support-Infrastruktur™ · Catch-all Edition',
        'Topf & Thaler GmbH · Sonstige Geldangelegenheiten',
    ],
};

function pickRandom(arr: string[]): string {
    return arr[Math.floor(Math.random() * arr.length)];
}

const corporateName = computed(() => {
    const key = selectedType.value ?? 'default';

    return pickRandom(corporateNames[key] ?? corporateNames.default);
});

const totalDenominationCount = computed(() =>
    Object.values(denominations.value).reduce((sum, qty) => sum + qty, 0),
);

onMounted(async () => {
    try {
        const res = await axios.get(`/api/stations/${token}`);
        station.value = res.data.station ?? res.data;
    } catch {
        error.value = 'Station nicht gefunden. Bitte überprüfe den QR-Code.';
    } finally {
        loading.value = false;
    }
});

async function submit(): Promise<void> {
    if (!selectedType.value) {
        return;
    }

    submitError.value = null;
    submitting.value = true;

    try {
        const payload: Record<string, unknown> = {
            type: selectedType.value,
        };

        if (selectedType.value === 'change_request') {
            payload.denominations = denominations.value;
        }

        if (
            (selectedType.value === 'cash_full' ||
                selectedType.value === 'change_request') &&
            message.value.trim()
        ) {
            payload.message = message.value.trim();
        }

        if (selectedType.value === 'other') {
            if (!otherText.value.trim()) {
                submitError.value = 'Bitte gib eine Nachricht ein.';
                submitting.value = false;

                return;
            }

            payload.message = otherText.value.trim();
        }

        const res = await axios.post(`/api/stations/${token}/tickets`, payload);
        const ticket = res.data.ticket ?? res.data;
        await router.push({ name: 'ticket.status', params: { id: ticket.id } });
    } catch (e: unknown) {
        const err = e as { response?: { data?: { message?: string } } };
        submitError.value =
            err.response?.data?.message ??
            'Ein unerwarteter Fehler ist aufgetreten. Bitte versuche es erneut.';
    } finally {
        submitting.value = false;
    }
}
</script>

<template>
    <PublicLayout :corporate-name="corporateName">
        <div v-if="loading" class="mx-auto max-w-xl px-6 py-20 text-center">
            <div
                class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-[#e0dedd] border-t-gpn-orange"
            ></div>
            <p class="mt-4 text-sm tracking-widest text-gpn-gray uppercase">
                System wird initialisiert...
            </p>
        </div>

        <div v-else-if="error" class="mx-auto max-w-xl px-6 py-20 text-center">
            <div class="rounded-lg border border-red-200 bg-red-50 p-8">
                <p class="font-semibold text-red-700">{{ error }}</p>
            </div>
        </div>

        <template v-else-if="station">
            <div class="bg-gpn-black px-6 py-6">
                <div class="mx-auto max-w-xl">
                    <p
                        class="mb-1 font-heading text-[10px] font-bold tracking-[0.3em] text-gpn-orange uppercase"
                    >
                        GPN24 · Kassenmeldung
                    </p>
                    <h1
                        class="font-heading text-2xl font-black tracking-tight text-gpn-white"
                    >
                        {{ station.name }}
                    </h1>
                    <p class="mt-1 text-sm tracking-wide text-[#7a7370]">
                        Standort:
                        <span class="text-[#b0acaa]">{{
                            station.location
                        }}</span>
                    </p>
                </div>
            </div>

            <div v-if="!selectedType" class="mx-auto max-w-xl px-6 py-8">
                <p
                    class="mb-2 font-heading text-xs font-bold tracking-[0.25em] text-gpn-black uppercase"
                >
                    Vorgangsart auswählen
                </p>
                <div class="space-y-3">
                    <button
                        v-for="type in [
                            'cash_full',
                            'change_request',
                            'other',
                        ] as TicketType[]"
                        :key="type"
                        type="button"
                        class="group w-full rounded-lg border-2 border-[#e0dedd] bg-white p-5 text-left transition-all hover:border-gpn-orange hover:bg-[#fdf2ec]"
                        @click="selectedType = type"
                    >
                        <div class="flex items-start gap-4">
                            <component
                                :is="typeIcons[type]"
                                :size="32"
                                class="mt-0.5 flex-shrink-0 text-gpn-orange"
                            />
                            <div>
                                <div
                                    class="font-heading text-base font-black tracking-tight text-gpn-black group-hover:text-gpn-orange"
                                >
                                    {{ typeLabels[type] }}
                                </div>
                                <div class="mt-0.5 text-sm text-gpn-gray">
                                    {{ typeDescriptions[type] }}
                                </div>
                            </div>
                        </div>
                    </button>
                </div>

                <p class="mt-6 text-xs text-gpn-gray/70">
                    Bitte wähle die Art deines Anliegens aus. Deine Meldung wird
                    KI-gestützt triagiert, initiiert automatisch den
                    entsprechenden SAP-S/4HANA-Workflow in unserem
                    Bargeldmanagement-ERP-System, durchläuft unsere
                    blockchain-basierte Audit-Trail-Pipeline und wird
                    anschließend gemäß unserer ITIL-v4-konformen, agilen
                    Service-Management-Prozesse unter Berücksichtigung deines
                    individuellen Customer-Lifetime-Value priorisiert
                    bearbeitet. Bitte halte deine Kunden- und Kassennummer
                    bereit.
                </p>
            </div>

            <div v-else class="mx-auto max-w-xl px-6 py-8">
                <button
                    v-if="!typeFromQuery"
                    type="button"
                    class="mb-6 flex items-center gap-1 text-xs tracking-widest text-gpn-gray uppercase transition-colors hover:text-gpn-black"
                    @click="selectedType = null"
                >
                    ← Zurück zur Auswahl
                </button>

                <div class="mb-6">
                    <p
                        class="mb-1 flex items-center gap-1.5 font-heading text-[10px] font-bold tracking-[0.3em] text-gpn-orange uppercase"
                    >
                        <component :is="typeIcons[selectedType]" :size="14" />
                        {{ typeLabels[selectedType] }}
                    </p>
                    <p class="text-sm text-gpn-gray">
                        Deine professionelle Anfrage wird umgehend bearbeitet.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <template v-if="selectedType === 'cash_full'">
                        <div
                            class="rounded-lg border border-[#f2c4a3] bg-[#fdf2ec] p-4"
                        >
                            <p class="text-sm font-semibold text-[#8c3a0a]">
                                Durch Absenden dieses Formulars wird das
                                GELD-Team sofort über den Kassenfüllstand
                                informiert.
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-2 block font-heading text-xs font-bold tracking-widest text-gpn-gray uppercase"
                            >
                                Optionale Anmerkung
                            </label>
                            <textarea
                                v-model="message"
                                rows="3"
                                placeholder="Weitere Informationen (optional)..."
                                class="w-full resize-none rounded-lg border border-[#d4d0ce] bg-white px-4 py-3 text-sm text-gpn-black placeholder-gpn-gray/60 transition focus:border-gpn-orange focus:ring-2 focus:ring-gpn-orange/20 focus:outline-none"
                            />
                        </div>
                    </template>

                    <template v-else-if="selectedType === 'change_request'">
                        <div>
                            <label
                                class="mb-2 block font-heading text-xs font-bold tracking-widest text-gpn-gray uppercase"
                            >
                                Wechselgeld-Spezifikation
                            </label>
                            <DenominationPicker v-model="denominations" />
                            <p
                                class="mt-2 text-xs tracking-wide text-gpn-gray/70"
                            >
                                Gewünschte Stückelungen:
                                {{ totalDenominationCount }} Einheit(en)
                                ausgewählt
                            </p>
                        </div>
                        <div>
                            <label
                                class="mb-2 block font-heading text-xs font-bold tracking-widest text-gpn-gray uppercase"
                            >
                                Optionale Anmerkung
                            </label>
                            <textarea
                                v-model="message"
                                rows="2"
                                placeholder="Weitere Informationen (optional)..."
                                class="w-full resize-none rounded-lg border border-[#d4d0ce] bg-white px-4 py-3 text-sm text-gpn-black placeholder-gpn-gray/60 transition focus:border-gpn-orange focus:ring-2 focus:ring-gpn-orange/20 focus:outline-none"
                            />
                        </div>
                    </template>

                    <template v-else-if="selectedType === 'other'">
                        <div>
                            <label
                                class="mb-2 block font-heading text-xs font-bold tracking-widest text-gpn-gray uppercase"
                            >
                                Deine Nachricht
                                <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="otherText"
                                rows="5"
                                placeholder="Bitte beschreibe dein Anliegen ausführlich..."
                                required
                                class="w-full resize-none rounded-lg border border-[#d4d0ce] bg-white px-4 py-3 text-sm text-gpn-black placeholder-gpn-gray/60 transition focus:border-gpn-orange focus:ring-2 focus:ring-gpn-orange/20 focus:outline-none"
                            />
                        </div>
                    </template>

                    <div
                        v-if="submitError"
                        class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    >
                        {{ submitError }}
                    </div>

                    <button
                        type="submit"
                        :disabled="submitting"
                        class="w-full rounded-lg bg-gpn-orange py-4 font-heading text-sm font-black tracking-[0.15em] text-white uppercase shadow-lg transition-colors hover:bg-[#c94e0a] disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span v-if="submitting">
                            <span
                                class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white align-middle"
                            ></span>
                            Wird übermittelt...
                        </span>
                        <span v-else-if="selectedType === 'cash_full'"
                            >Kasse voll melden</span
                        >
                        <span v-else-if="selectedType === 'change_request'"
                            >Wechselgeld anfordern</span
                        >
                        <span v-else>Nachricht senden</span>
                    </button>

                    <p
                        class="text-center text-[10px] tracking-widest text-gpn-gray/60 uppercase"
                    >
                        256-Bit-verschlüsselt · DSGVO-konform · ISO
                        9001:2025-zertifiziert · TLS 1.3 · Zero-Trust ·
                        ITIL-v4-prozessiert · blockchain-ready · KI-optimiert ·
                        nachhaltig · barrierefrei · agil
                    </p>
                </form>
            </div>
        </template>
    </PublicLayout>
</template>
