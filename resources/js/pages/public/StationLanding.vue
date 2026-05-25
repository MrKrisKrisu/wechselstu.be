<script lang="ts" setup>
import { ref } from 'vue';
import CashMultipleIcon from 'vue-material-design-icons/CashMultiple.vue';
import ClipboardTextIcon from 'vue-material-design-icons/ClipboardText.vue';
import CurrencyEurIcon from 'vue-material-design-icons/CurrencyEur.vue';
import { useRouter } from 'vue-router';
import PublicLayout from '@/layouts/PublicLayout.vue';
import type { TicketType } from '@/types';

const router = useRouter();

const domainType =
    (window.__APP_CONFIG__?.ticketType as TicketType | null) ?? null;

const selectedType = ref<TicketType | null>(domainType);
const token = ref('');

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

const corporateNames: Record<TicketType, string[]> = {
    cash_full: [
        'GPN KassenVoll-Notfall-Portal™ / Bargeld-Incident-Response',
        'Topf leer, Kasse voll® / GPN Emergency Finance Response',
        'Gulaschprogrammiernacht Kassenüberlauf-Meldestelle™',
    ],
    change_request: [
        'GPN Kleingeld-Logistik-Hub™ / Münzen as a Service',
        'Wechselstu.be® / Powered by Gulasch & Kaffeesatz',
        'Gulaschprogrammiernacht Münz-Beschaffungs-Portal™',
    ],
    other: [
        'GPN Finanz-Kommunikations-Kanal™ / Sonstiges Division',
        'Gulaschprogrammiernacht Kassen-Helpdesk® / Allgemein',
        'GPN Bargeld-Support-Infrastruktur™ / Catch-all Edition',
    ],
};

const genericCorporateNames = [
    'GPN Bargeld-Infrastruktur-Komitee e.V.',
    'Gulaschprogrammiernacht Finance Division™',
    'Topf & Thaler GmbH / Professionelles Kassenmanagement',
];

function pickRandom(arr: string[]): string {
    return arr[Math.floor(Math.random() * arr.length)];
}

const corporateName = domainType
    ? pickRandom(corporateNames[domainType])
    : pickRandom(genericCorporateNames);

function submit(): void {
    const t = token.value.trim().toUpperCase();

    if (!t || !selectedType.value) {
        return;
    }

    router.push({
        name: 'station.form',
        params: { token: t },
        query: { type: selectedType.value },
    });
}
</script>

<template>
    <PublicLayout :corporate-name="corporateName">
        <div class="bg-gpn-black px-6 py-6">
            <div class="mx-auto max-w-xl">
                <p
                    class="mb-1 font-heading text-[10px] font-bold tracking-[0.3em] text-gpn-orange uppercase"
                >
                    GPN24 / Kassenmeldung
                </p>
                <template v-if="domainType">
                    <h1
                        class="flex items-center gap-3 font-heading text-2xl font-black tracking-tight text-gpn-white"
                    >
                        <component
                            :is="typeIcons[domainType]"
                            :size="28"
                            class="flex-shrink-0 text-gpn-orange"
                        />
                        {{ typeLabels[domainType] }}
                    </h1>
                    <p class="mt-2 text-sm text-[#7a7370]">
                        {{ typeDescriptions[domainType] }}
                    </p>
                </template>
                <template v-else>
                    <h1
                        class="font-heading text-2xl font-black tracking-tight text-gpn-white"
                    >
                        Kassenmeldung einreichen
                    </h1>
                    <p class="mt-2 text-sm text-[#7a7370]">
                        Wähle dein Anliegen und gib den Token deiner Kasse ein.
                    </p>
                </template>
            </div>
        </div>

        <div class="mx-auto max-w-xl px-6 py-8">
            <form class="space-y-6" @submit.prevent="submit">
                <div v-if="!domainType">
                    <p
                        class="mb-2 font-heading text-xs font-bold tracking-[0.25em] text-gpn-black uppercase"
                    >
                        Vorgangsart
                    </p>
                    <div class="space-y-2">
                        <button
                            v-for="type in [
                                'cash_full',
                                'change_request',
                                'other',
                            ] as TicketType[]"
                            :key="type"
                            :class="
                                selectedType === type
                                    ? 'border-gpn-orange bg-[#fdf2ec]'
                                    : 'border-[#e0dedd] bg-white hover:border-gpn-orange hover:bg-[#fdf2ec]'
                            "
                            class="group w-full rounded-lg border-2 p-4 text-left transition-all"
                            type="button"
                            @click="selectedType = type"
                        >
                            <div class="flex items-center gap-3">
                                <component
                                    :is="typeIcons[type]"
                                    :size="24"
                                    class="flex-shrink-0 text-gpn-orange"
                                />
                                <div
                                    class="font-heading text-sm font-black tracking-tight text-gpn-black"
                                >
                                    {{ typeLabels[type] }}
                                </div>
                            </div>
                        </button>
                    </div>
                </div>

                <div>
                    <label
                        class="mb-1 block font-heading text-xs font-bold tracking-widest text-gpn-black uppercase"
                    >
                        Token der Kasse
                    </label>
                    <p class="mb-3 text-sm text-gpn-gray">
                        Den 4-stelligen Code findest du auf dem Schild an deiner
                        Kasse.
                    </p>
                    <input
                        :value="token"
                        autocapitalize="characters"
                        autocomplete="off"
                        autocorrect="off"
                        class="w-full rounded-lg border-2 border-[#d4d0ce] bg-white px-5 py-4 text-center font-mono text-2xl font-bold tracking-[0.5em] text-gpn-black uppercase placeholder-gpn-gray/30 transition focus:border-gpn-orange focus:ring-2 focus:ring-gpn-orange/20 focus:outline-none"
                        maxlength="4"
                        placeholder="z.B. ABCD"
                        spellcheck="false"
                        type="text"
                        @input="
                            token = (
                                $event.target as HTMLInputElement
                            ).value.toUpperCase()
                        "
                    />
                </div>

                <button
                    :disabled="token.trim().length === 0 || !selectedType"
                    class="w-full rounded-lg bg-gpn-orange py-4 font-heading text-sm font-black tracking-[0.15em] text-white uppercase shadow-lg transition-colors hover:bg-[#c94e0a] disabled:cursor-not-allowed disabled:opacity-40"
                    type="submit"
                >
                    Weiter →
                </button>

                <div class="flex items-center justify-between">
                    <RouterLink
                        class="text-[10px] tracking-widest text-gpn-gray/40 uppercase transition-colors hover:text-gpn-gray"
                        to="/login"
                    >
                        Login
                    </RouterLink>
                </div>
            </form>
        </div>
    </PublicLayout>
</template>
