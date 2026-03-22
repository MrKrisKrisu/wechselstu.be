<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import CashMultipleIcon from 'vue-material-design-icons/CashMultiple.vue';
import ClipboardTextIcon from 'vue-material-design-icons/ClipboardText.vue';
import CurrencyEurIcon from 'vue-material-design-icons/CurrencyEur.vue';
import { useRoute } from 'vue-router';
import { useStationChannel } from '@/composables/useEcho';
import PublicLayout from '@/layouts/PublicLayout.vue';
import axios from '@/lib/axios';
import type { Ticket, TicketStatus } from '@/types';
import { DENOMINATIONS } from '@/types';

const route = useRoute();
const ticketId = route.params.id as string;

const ticket = ref<Ticket | null>(null);
const loading = ref(true);
const error = ref<string | null>(null);
let leaveChannel: (() => void) | null = null;

const statusConfig = computed(
    (): {
        label: string;
        sublabel: string;
        badgeClass: string;
        bgClass: string;
        borderClass: string;
    } => {
        const s = ticket.value?.status as TicketStatus | undefined;

        switch (s) {
            case 'open':
                return {
                    label: 'Offen',
                    sublabel:
                        'Deine Meldung wurde empfangen und wartet auf Bearbeitung.',
                    badgeClass: 'bg-gpn-orange text-white',
                    bgClass: 'bg-[#fdf2ec]',
                    borderClass: 'border-[#f2c4a3]',
                };
            case 'accepted':
                return {
                    label: 'In Bearbeitung',
                    sublabel:
                        'Jemand vom GELD-Team hat deine Meldung angenommen und ist auf dem Weg.',
                    badgeClass: 'bg-gpn-black text-gpn-white',
                    bgClass: 'bg-gpn-white',
                    borderClass: 'border-[#d4d0ce]',
                };
            case 'done':
                return {
                    label: 'Erledigt',
                    sublabel:
                        'Deine Meldung wurde erfolgreich abgeschlossen. Vielen Dank!',
                    badgeClass: 'bg-green-600 text-white',
                    bgClass: 'bg-green-50',
                    borderClass: 'border-green-200',
                };
            default:
                return {
                    label: 'Unbekannt',
                    sublabel: '',
                    badgeClass: 'bg-gpn-gray text-white',
                    bgClass: 'bg-gpn-white',
                    borderClass: 'border-[#d4d0ce]',
                };
        }
    },
);

const typeIcons: Record<string, typeof CashMultipleIcon> = {
    cash_full: CashMultipleIcon,
    change_request: CurrencyEurIcon,
    other: ClipboardTextIcon,
};

const relevantDenominations = computed(() =>
    (ticket.value?.denominations ?? []).filter((d) => d.quantity > 0),
);

const denominationLabel = computed(() => (cents: number): string => {
    return (
        DENOMINATIONS.find((d) => d.cents === cents)?.label ??
        `${cents / 100} €`
    );
});

onMounted(async () => {
    try {
        const res = await axios.get(`/api/tickets/${ticketId}`);
        ticket.value = res.data.ticket ?? res.data;

        // Subscribe to live updates on the station channel
        if (ticket.value?.station?.id) {
            leaveChannel = useStationChannel(
                ticket.value.station.id,
                (updated: Ticket) => {
                    if (updated.id === ticketId) {
                        ticket.value = updated;
                    }
                },
            );
        }
    } catch {
        error.value = 'Ticket nicht gefunden.';
    } finally {
        loading.value = false;
    }
});

onUnmounted(() => {
    leaveChannel?.();
});
</script>

<template>
    <PublicLayout
        corporate-name="Ticket-Status-Portal™ · Real-Time-Tracking Edition"
    >
        <div v-if="loading" class="mx-auto max-w-xl px-6 py-20 text-center">
            <div
                class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-[#e0dedd] border-t-gpn-orange"
            ></div>
            <p class="mt-4 text-sm tracking-widest text-gpn-gray uppercase">
                Ticketstatus wird abgerufen...
            </p>
        </div>

        <div v-else-if="error" class="mx-auto max-w-xl px-6 py-20 text-center">
            <div class="rounded-lg border border-red-200 bg-red-50 p-8">
                <p class="font-semibold text-red-700">{{ error }}</p>
            </div>
        </div>

        <template v-else-if="ticket">
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
                        {{ ticket.station.name }}
                    </h1>
                    <p class="mt-1 text-sm text-[#7a7370]">
                        Standort:
                        <span class="text-[#b0acaa]">{{
                            ticket.station.location
                        }}</span>
                    </p>
                </div>
            </div>

            <div class="mx-auto max-w-xl space-y-5 px-6 py-8">
                <div
                    :class="[statusConfig.bgClass, statusConfig.borderClass]"
                    class="rounded-xl border-2 p-6 text-center transition-all duration-500"
                >
                    <span
                        :class="statusConfig.badgeClass"
                        class="mb-3 inline-block rounded-full px-6 py-2 font-heading text-lg font-black tracking-wide uppercase shadow-md"
                    >
                        {{ statusConfig.label }}
                    </span>
                    <p class="mt-2 text-sm text-gpn-gray">
                        {{ statusConfig.sublabel }}
                    </p>

                    <div
                        v-if="
                            ticket.assigned_user && ticket.status === 'accepted'
                        "
                        class="mt-3"
                    >
                        <p
                            class="font-heading text-xs font-bold tracking-widest text-[#8c3a0a] uppercase"
                        >
                            Bearbeitet von
                        </p>
                        <p class="mt-0.5 text-sm font-semibold text-[#8c3a0a]">
                            {{ ticket.assigned_user.name }}
                        </p>
                    </div>
                    <div
                        v-if="ticket.assigned_user && ticket.status === 'done'"
                        class="mt-3"
                    >
                        <p class="text-sm text-green-700">
                            Wir hoffen, du warst mit der Arbeit von
                            <strong>{{ ticket.assigned_user.name }}</strong>
                            zufrieden und hinterlässt ein positives Feedback bei
                            Google. ⭐
                        </p>
                    </div>
                </div>

                <div
                    class="space-y-4 rounded-xl border border-[#e0dedd] bg-white p-5"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p
                                class="font-heading text-[10px] font-bold tracking-[0.25em] text-gpn-gray/70 uppercase"
                            >
                                Vorgangsart
                            </p>
                            <p
                                class="mt-0.5 flex items-center gap-1.5 font-semibold text-gpn-black"
                            >
                                <component
                                    :is="
                                        typeIcons[ticket.type] ??
                                        ClipboardTextIcon
                                    "
                                    :size="16"
                                    class="text-gpn-orange"
                                />
                                {{ ticket.type_label }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p
                                class="font-heading text-[10px] font-bold tracking-[0.25em] text-gpn-gray/70 uppercase"
                            >
                                Ticket-ID
                            </p>
                            <p
                                class="mt-0.5 font-mono text-xs break-all text-gpn-gray"
                            >
                                {{ ticket.id }}
                            </p>
                        </div>
                    </div>

                    <div v-if="relevantDenominations.length > 0">
                        <p
                            class="mb-2 font-heading text-[10px] font-bold tracking-[0.25em] text-gpn-gray/70 uppercase"
                        >
                            Angeforderte Stückelungen
                        </p>
                        <div class="space-y-1">
                            <div
                                v-for="denom in relevantDenominations"
                                :key="denom.id"
                                class="flex items-center justify-between rounded bg-gpn-white px-3 py-2"
                            >
                                <span
                                    class="text-sm font-medium text-gpn-black"
                                >
                                    {{
                                        denominationLabel(
                                            denom.denomination_cents,
                                        )
                                    }}
                                </span>
                                <span class="text-sm font-bold text-gpn-orange">
                                    × {{ denom.quantity }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="ticket.message">
                        <p
                            class="mb-1 font-heading text-[10px] font-bold tracking-[0.25em] text-gpn-gray/70 uppercase"
                        >
                            Nachricht
                        </p>
                        <p
                            class="rounded bg-gpn-white px-3 py-2 text-sm whitespace-pre-wrap text-gpn-black"
                        >
                            {{ ticket.message }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="mb-0.5 font-heading text-[10px] font-bold tracking-[0.25em] text-gpn-gray/70 uppercase"
                        >
                            Eingegangen am
                        </p>
                        <p class="text-xs text-gpn-gray">
                            {{
                                new Date(ticket.created_at).toLocaleString(
                                    'de-DE',
                                )
                            }}
                        </p>
                    </div>
                </div>

                <p
                    class="text-center text-[10px] tracking-widest text-gpn-gray/50 uppercase"
                >
                    Diese Seite aktualisiert sich automatisch in Echtzeit.
                </p>
            </div>
        </template>
    </PublicLayout>
</template>
