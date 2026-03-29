<script lang="ts" setup>
import { onMounted, ref } from 'vue';
import AlertIcon from 'vue-material-design-icons/Alert.vue';
import CheckIcon from 'vue-material-design-icons/Check.vue';
import ContentCopyIcon from 'vue-material-design-icons/ContentCopy.vue';
import FilePdfBoxIcon from 'vue-material-design-icons/FilePdfBox.vue';
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

async function copyLink(station: Station) {
    await navigator.clipboard.writeText(
        `${window.location.origin}/s/${station.token}`,
    );
    copiedId.value = station.id;
    setTimeout(() => {
        copiedId.value = null;
    }, 2000);
}

async function downloadSign(station: Station) {
    try {
        const response = await axios.get(
            `/api/finance/stations/${station.id}/sign`,
            {
                responseType: 'blob',
            },
        );
        const url = URL.createObjectURL(
            new Blob([response.data], { type: 'application/pdf' }),
        );
        const a = document.createElement('a');
        a.href = url;
        a.download = `station-sign-${station.token}.pdf`;
        a.click();
        URL.revokeObjectURL(url);
    } catch {
        alert('Aushang konnte nicht erstellt werden.');
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
                class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-700"
                @click="router.push('/finance/stations/create')"
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
                            <div class="flex items-center gap-2">
                                {{ station.name }}
                                <span
                                    v-if="!station.printer_ip"
                                    class="flex items-center gap-1 rounded-md bg-amber-100 px-1.5 py-0.5 text-xs font-medium text-amber-700"
                                    title="Kein Drucker konfiguriert"
                                >
                                    <AlertIcon :size="12" />
                                    Kein Drucker konfiguriert
                                </span>
                            </div>
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
                            <div class="flex items-center justify-end gap-2">
                                <button
                                    :class="
                                        copiedId === station.id
                                            ? 'border-green-200 bg-green-50 text-green-700'
                                            : 'border-slate-200 text-slate-600 hover:bg-slate-100'
                                    "
                                    class="flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors"
                                    title="Link kopieren"
                                    @click="copyLink(station)"
                                >
                                    <CheckIcon
                                        v-if="copiedId === station.id"
                                        :size="12"
                                    />
                                    <ContentCopyIcon v-else :size="12" />
                                    Link
                                </button>
                                <button
                                    class="flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-100"
                                    title="Aushang als PDF herunterladen"
                                    @click="downloadSign(station)"
                                >
                                    <FilePdfBoxIcon :size="12" />
                                    Aushang
                                </button>
                                <button
                                    class="flex items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-100"
                                    @click="
                                        router.push(
                                            `/finance/stations/${station.id}/edit`,
                                        )
                                    "
                                >
                                    <PencilIcon :size="12" />
                                    Bearbeiten
                                </button>
                                <button
                                    class="flex items-center gap-1 rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50"
                                    @click="deleteStation(station)"
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
