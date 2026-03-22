<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import ArrowLeftIcon from 'vue-material-design-icons/ArrowLeft.vue';
import { useRoute, useRouter } from 'vue-router';
import axios from '@/lib/axios';

const route = useRoute();
const router = useRouter();

const id = computed(() => route.params.id as string | undefined);
const isEdit = computed(() => !!id.value);

const name = ref('');
const location = ref('');
const loading = ref(false);
const loadingData = ref(false);
const errors = ref<Record<string, string[]>>({});
const successMessage = ref('');

onMounted(async () => {
    if (isEdit.value) {
        loadingData.value = true;

        try {
            const { data } = await axios.get(
                `/api/finance/stations/${id.value}`,
            );
            const station = data.station ?? data;
            name.value = station.name;
            location.value = station.location;
        } catch {
            router.push('/finance/stations');
        } finally {
            loadingData.value = false;
        }
    }
});

async function handleSubmit() {
    errors.value = {};
    successMessage.value = '';
    loading.value = true;

    try {
        if (isEdit.value) {
            await axios.put(`/api/finance/stations/${id.value}`, {
                name: name.value,
                location: location.value,
            });
            successMessage.value = 'Kasse erfolgreich gespeichert.';
        } else {
            await axios.post('/api/finance/stations', {
                name: name.value,
                location: location.value,
            });
            router.push('/finance/stations');
        }
    } catch (err: any) {
        if (err.response?.status === 422) {
            errors.value = err.response.data?.errors ?? {};
        } else {
            errors.value = {
                general: ['Ein unbekannter Fehler ist aufgetreten.'],
            };
        }
    } finally {
        loading.value = false;
    }
}

function fieldError(field: string): string | null {
    return errors.value[field]?.[0] ?? null;
}
</script>

<template>
    <div class="max-w-xl p-6">
        <button
            @click="router.push('/finance/stations')"
            class="mb-6 flex items-center gap-2 text-sm text-slate-500 transition-colors hover:text-slate-900"
        >
            <ArrowLeftIcon :size="16" />
            Zurück zur Kassenliste
        </button>

        <h1 class="mb-6 text-2xl font-bold text-slate-900">
            {{ isEdit ? 'Kasse bearbeiten' : 'Neue Kasse' }}
        </h1>

        <div v-if="loadingData" class="text-sm text-slate-400">Lädt...</div>

        <form
            v-else
            @submit.prevent="handleSubmit"
            class="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            <div
                v-if="errors.general"
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
            >
                {{ errors.general[0] }}
            </div>

            <div
                v-if="successMessage"
                class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700"
            >
                {{ successMessage }}
            </div>

            <div>
                <label
                    for="name"
                    class="mb-1 block text-sm font-medium text-slate-700"
                    >Name</label
                >
                <input
                    id="name"
                    v-model="name"
                    type="text"
                    required
                    class="w-full rounded-lg border px-3 py-2.5 text-sm text-slate-900 transition-colors focus:ring-1 focus:outline-none"
                    :class="
                        fieldError('name')
                            ? 'border-red-300 focus:border-red-400 focus:ring-red-400'
                            : 'border-slate-300 focus:border-slate-500 focus:ring-slate-500'
                    "
                    placeholder="Tschunk Bar"
                />
                <p v-if="fieldError('name')" class="mt-1 text-xs text-red-600">
                    {{ fieldError('name') }}
                </p>
            </div>

            <div>
                <label
                    for="location"
                    class="mb-1 block text-sm font-medium text-slate-700"
                    >Standort</label
                >
                <input
                    id="location"
                    v-model="location"
                    type="text"
                    required
                    class="w-full rounded-lg border px-3 py-2.5 text-sm text-slate-900 transition-colors focus:ring-1 focus:outline-none"
                    :class="
                        fieldError('location')
                            ? 'border-red-300 focus:border-red-400 focus:ring-red-400'
                            : 'border-slate-300 focus:border-slate-500 focus:ring-slate-500'
                    "
                    placeholder="Außenbar"
                />
                <p
                    v-if="fieldError('location')"
                    class="mt-1 text-xs text-red-600"
                >
                    {{ fieldError('location') }}
                </p>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button
                    type="submit"
                    :disabled="loading"
                    class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span v-if="loading">Speichern...</span>
                    <span v-else>{{ isEdit ? 'Speichern' : 'Erstellen' }}</span>
                </button>
                <button
                    type="button"
                    @click="router.push('/finance/stations')"
                    class="rounded-lg border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50"
                >
                    Abbrechen
                </button>
            </div>
        </form>
    </div>
</template>
