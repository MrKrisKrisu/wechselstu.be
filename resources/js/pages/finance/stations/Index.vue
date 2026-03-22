<script setup lang="ts">
import QRCode from 'qrcode';
import { ref, onMounted } from 'vue';
import CheckIcon from 'vue-material-design-icons/Check.vue';
import ContentCopyIcon from 'vue-material-design-icons/ContentCopy.vue';
import DownloadIcon from 'vue-material-design-icons/Download.vue';
import PencilIcon from 'vue-material-design-icons/Pencil.vue';
import PlusIcon from 'vue-material-design-icons/Plus.vue';
import TrashCanIcon from 'vue-material-design-icons/TrashCan.vue';
import { useRouter } from 'vue-router';
import axios from '@/lib/axios';
import type { Station } from '@/types';

const router = useRouter();

const stations = ref<Station[]>([]);
const loading = ref(true);
const error = ref('');
const copiedId = ref<string | null>(null);

onMounted(async () => {
    await loadStations();
});

async function loadStations() {
    loading.value = true;

    try {
        const { data } = await axios.get('/api/finance/stations');
        stations.value = data.stations ?? data;
    } catch {
        error.value = 'Fehler beim Laden der Kassen.';
    } finally {
        loading.value = false;
    }
}

async function deleteStation(station: Station) {
    if (!confirm(`Kasse "${station.name}" wirklich löschen?`)) {
        return;
    }

    try {
        await axios.delete(`/api/finance/stations/${station.id}`);
        stations.value = stations.value.filter((s) => s.id !== station.id);
    } catch {
        alert('Fehler beim Löschen.');
    }
}

function stationUrl(token: string): string {
    return `${window.location.origin}/s/${token}`;
}

async function downloadQr(station: Station) {
    try {
        const url = stationUrl(station.token);
        const dataUrl = await QRCode.toDataURL(url, { width: 512, margin: 2 });
        const a = document.createElement('a');
        a.href = dataUrl;
        a.download = `qr-${station.name.replace(/\s+/g, '-').toLowerCase()}.png`;
        a.click();
    } catch {
        alert('QR-Code konnte nicht erstellt werden.');
    }
}

async function copyLink(station: Station) {
    await navigator.clipboard.writeText(stationUrl(station.token));
    copiedId.value = station.id;
    setTimeout(() => {
        copiedId.value = null;
    }, 2000);
}

// Render QR code into canvas element
async function renderQr(el: HTMLCanvasElement | null, token: string) {
    if (!el) {
        return;
    }

    try {
        await QRCode.toCanvas(el, stationUrl(token), { width: 64, margin: 1 });
    } catch {
        // silently fail
    }
}
</script>

<template>
    <div class="p-6">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Kassen</h1>
            </div>
            <button
                @click="router.push('/finance/stations/create')"
                class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-700"
            >
                <PlusIcon :size="16" />
                Neue Kasse
            </button>
        </div>

        <div
            v-if="error"
            class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
        >
            {{ error }}
        </div>

        <div
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >
            <div v-if="loading" class="p-8 text-center text-sm text-slate-400">
                Lädt...
            </div>
            <div
                v-else-if="stations.length === 0"
                class="p-8 text-center text-sm text-slate-400"
            >
                Noch keine Kassen angelegt.
            </div>
            <table v-else class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Name
                        </th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Standort
                        </th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Token
                        </th>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            QR-Code
                        </th>
                        <th
                            class="px-4 py-3 text-right text-xs font-semibold tracking-wide text-slate-500 uppercase"
                        >
                            Aktionen
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr
                        v-for="station in stations"
                        :key="station.id"
                        class="hover:bg-slate-50"
                    >
                        <td class="px-4 py-3 font-medium text-slate-900">
                            {{ station.name }}
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ station.location }}
                        </td>
                        <td class="px-4 py-3">
                            <code
                                class="rounded bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-700"
                            >
                                {{ station.token }}
                            </code>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <canvas
                                    :ref="
                                        (el) =>
                                            renderQr(
                                                el as HTMLCanvasElement,
                                                station.token,
                                            )
                                    "
                                    class="rounded border border-slate-200"
                                />
                                <div class="flex flex-col gap-1">
                                    <button
                                        @click="downloadQr(station)"
                                        class="flex items-center gap-1 rounded-lg border border-slate-200 px-2 py-1 text-xs text-slate-600 transition-colors hover:bg-slate-100"
                                        title="QR-Code herunterladen"
                                    >
                                        <DownloadIcon :size="12" />
                                        PNG
                                    </button>
                                    <button
                                        @click="copyLink(station)"
                                        class="flex items-center gap-1 rounded-lg border px-2 py-1 text-xs transition-colors"
                                        :class="
                                            copiedId === station.id
                                                ? 'border-green-200 bg-green-50 text-green-700'
                                                : 'border-slate-200 text-slate-600 hover:bg-slate-100'
                                        "
                                        title="Link kopieren"
                                    >
                                        <CheckIcon
                                            v-if="copiedId === station.id"
                                            :size="12"
                                        />
                                        <ContentCopyIcon v-else :size="12" />
                                        Link
                                    </button>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    @click="
                                        router.push(
                                            `/finance/stations/${station.id}/edit`,
                                        )
                                    "
                                    class="flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-100"
                                >
                                    <PencilIcon :size="12" />
                                    Bearbeiten
                                </button>
                                <button
                                    @click="deleteStation(station)"
                                    class="flex items-center gap-1 rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50"
                                >
                                    <TrashCanIcon :size="12" />
                                    Löschen
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
