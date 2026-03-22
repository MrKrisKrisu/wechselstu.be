<script setup lang="ts">
import { ref, onMounted } from 'vue';
import CheckIcon from 'vue-material-design-icons/Check.vue';
import ContentCopyIcon from 'vue-material-design-icons/ContentCopy.vue';
import MonitorIcon from 'vue-material-design-icons/Monitor.vue';
import PlusIcon from 'vue-material-design-icons/Plus.vue';
import TrashCanIcon from 'vue-material-design-icons/TrashCan.vue';
import axios from '@/lib/axios';
import type { DashboardAccess } from '@/types';

const accesses = ref<DashboardAccess[]>([]);
const loading = ref(true);
const newLabel = ref('');
const creating = ref(false);
const createError = ref('');
const copiedId = ref<string | null>(null);

onMounted(async () => {
    await loadAccesses();
});

async function loadAccesses() {
    loading.value = true;

    try {
        const { data } = await axios.get('/api/finance/dashboard-access');
        accesses.value = data.accesses ?? data;
    } catch {
        // silently fail
    } finally {
        loading.value = false;
    }
}

async function createAccess() {
    if (!newLabel.value.trim()) {
        createError.value = 'Bitte ein Label eingeben.';

        return;
    }

    createError.value = '';
    creating.value = true;

    try {
        const { data } = await axios.post('/api/finance/dashboard-access', {
            label: newLabel.value.trim(),
        });
        const access = data.access ?? data;
        accesses.value.unshift(access);
        newLabel.value = '';
    } catch (err: any) {
        createError.value =
            err.response?.data?.message ?? 'Fehler beim Erstellen.';
    } finally {
        creating.value = false;
    }
}

async function deleteAccess(access: DashboardAccess) {
    if (!confirm(`Zugang "${access.label}" wirklich löschen?`)) {
        return;
    }

    try {
        await axios.delete(`/api/finance/dashboard-access/${access.id}`);
        accesses.value = accesses.value.filter((a) => a.id !== access.id);
    } catch {
        alert('Fehler beim Löschen.');
    }
}

function monitorUrl(token: string): string {
    return `${window.location.origin}/monitor?token=${token}`;
}

async function copyToClipboard(text: string, id: string) {
    try {
        await navigator.clipboard.writeText(text);
        copiedId.value = id;
        setTimeout(() => {
            if (copiedId.value === id) {
                copiedId.value = null;
            }
        }, 2000);
    } catch {
        alert('Kopieren fehlgeschlagen.');
    }
}
</script>

<template>
    <div class="max-w-3xl p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">Monitor-Zugang</h1>
        </div>

        <div
            class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            <h2 class="mb-3 text-sm font-semibold text-slate-900">
                Neuen Zugang erstellen
            </h2>
            <div
                v-if="createError"
                class="mb-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700"
            >
                {{ createError }}
            </div>
            <div class="flex gap-3">
                <input
                    v-model="newLabel"
                    type="text"
                    placeholder="Büro"
                    class="flex-1 rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 focus:border-slate-500 focus:ring-1 focus:ring-slate-500 focus:outline-none"
                    @keyup.enter="createAccess"
                />
                <button
                    @click="createAccess"
                    :disabled="creating"
                    class="flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <PlusIcon :size="16" />
                    <span v-if="creating">Erstellen...</span>
                    <span v-else>Erstellen</span>
                </button>
            </div>
        </div>

        <div class="space-y-4">
            <div v-if="loading" class="text-sm text-slate-400">Lädt...</div>
            <p
                v-else-if="accesses.length === 0"
                class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-sm text-slate-400"
            >
                Noch keine Zugänge erstellt.
            </p>

            <div
                v-for="access in accesses"
                :key="access.id"
                class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="mb-4 flex items-start justify-between gap-4">
                    <div>
                        <p class="font-semibold text-slate-900">
                            {{ access.label }}
                        </p>
                        <p class="mt-0.5 text-xs text-slate-400">
                            Erstellt:
                            {{
                                new Date(access.created_at).toLocaleDateString(
                                    'de-DE',
                                )
                            }}
                        </p>
                    </div>
                    <button
                        @click="deleteAccess(access)"
                        class="flex flex-shrink-0 items-center gap-1 rounded-lg border border-red-200 px-2.5 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50"
                    >
                        <TrashCanIcon :size="12" />
                        Löschen
                    </button>
                </div>

                <div class="mb-3">
                    <label class="mb-1 block text-xs font-medium text-slate-500"
                        >Token</label
                    >
                    <div class="flex items-center gap-2">
                        <code
                            class="flex-1 rounded-lg bg-slate-100 px-3 py-2 font-mono text-xs break-all text-slate-700"
                        >
                            {{ access.token }}
                        </code>
                        <button
                            @click="
                                copyToClipboard(
                                    access.token,
                                    `token-${access.id}`,
                                )
                            "
                            class="flex flex-shrink-0 items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-2 text-xs text-slate-600 transition-colors hover:bg-slate-100"
                            title="Token kopieren"
                        >
                            <CheckIcon
                                v-if="copiedId === `token-${access.id}`"
                                :size="14"
                                class="text-green-600"
                            />
                            <ContentCopyIcon v-else :size="14" />
                        </button>
                    </div>
                </div>

                <div>
                    <label
                        class="mb-1 block flex items-center gap-1 text-xs font-medium text-slate-500"
                    >
                        <MonitorIcon :size="12" />
                        Monitor-URL
                    </label>
                    <div class="flex items-center gap-2">
                        <code
                            class="flex-1 rounded-lg bg-slate-100 px-3 py-2 font-mono text-xs break-all text-slate-700"
                        >
                            {{ monitorUrl(access.token) }}
                        </code>
                        <button
                            @click="
                                copyToClipboard(
                                    monitorUrl(access.token),
                                    `url-${access.id}`,
                                )
                            "
                            class="flex flex-shrink-0 items-center gap-1 rounded-lg border border-slate-200 px-2.5 py-2 text-xs text-slate-600 transition-colors hover:bg-slate-100"
                            title="URL kopieren"
                        >
                            <CheckIcon
                                v-if="copiedId === `url-${access.id}`"
                                :size="14"
                                class="text-green-600"
                            />
                            <ContentCopyIcon v-else :size="14" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
