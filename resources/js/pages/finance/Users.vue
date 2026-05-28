<script lang="ts" setup>
import { onMounted, ref } from 'vue';
import axios from '@/lib/axios';

interface TeamMember {
    id: string;
    name: string;
    member_token: string;
    avatar_url: string | null;
}

const users = ref<TeamMember[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

const memberDomain = window.__APP_CONFIG__?.memberDomain ?? 'member.localhost';

function profileUrl(token: string): string {
    return `https://${memberDomain}/${token}`;
}

const teamPhotoUrl = ref<string | null>(null);
const teamPhotoPreview = ref<string | null>(null);
const teamPhotoFile = ref<File | null>(null);
const photoFileInput = ref<HTMLInputElement | null>(null);
const photoSaving = ref(false);
const photoError = ref<string | null>(null);

function onPhotoChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    teamPhotoFile.value = file;
    const reader = new FileReader();
    reader.onload = (ev) => {
        teamPhotoPreview.value = ev.target?.result as string;
    };
    reader.readAsDataURL(file);
}

async function uploadPhoto() {
    if (!teamPhotoFile.value) {
        return;
    }

    photoSaving.value = true;
    photoError.value = null;

    try {
        const form = new FormData();
        form.append('photo', teamPhotoFile.value);
        const { data } = await axios.post('/api/finance/team-photo', form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        teamPhotoUrl.value = data.photo_url + '?t=' + Date.now();
        teamPhotoPreview.value = null;
        teamPhotoFile.value = null;
    } catch {
        photoError.value = 'Upload fehlgeschlagen.';
    } finally {
        photoSaving.value = false;
    }
}

async function deletePhoto() {
    photoSaving.value = true;
    photoError.value = null;

    try {
        await axios.delete('/api/finance/team-photo');
        teamPhotoUrl.value = null;
        teamPhotoPreview.value = null;
        teamPhotoFile.value = null;
    } catch {
        photoError.value = 'Löschen fehlgeschlagen.';
    } finally {
        photoSaving.value = false;
    }
}

onMounted(async () => {
    try {
        const [usersRes, photoRes] = await Promise.all([
            axios.get('/api/finance/users'),
            axios.get('/api/finance/team-photo'),
        ]);
        users.value = usersRes.data.users;
        teamPhotoUrl.value = photoRes.data.photo_url
            ? photoRes.data.photo_url + '?t=' + Date.now()
            : null;
    } catch {
        error.value = 'Laden fehlgeschlagen.';
    } finally {
        loading.value = false;
    }
});
</script>

<template>
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-900">Team</h1>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-24">
            <div
                class="h-8 w-8 animate-spin rounded-full border-4 border-gpn-orange border-t-transparent"
            />
        </div>

        <div v-else-if="error" class="py-12 text-center text-red-600">
            {{ error }}
        </div>

        <div v-else class="space-y-6">
            <div
                class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm"
            >
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold tracking-widest text-slate-500 uppercase"
                            >
                                Name
                            </th>
                            <th
                                class="px-5 py-3 text-left text-xs font-semibold tracking-widest text-slate-500 uppercase"
                            >
                                Profil-Link
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="user in users"
                            :key="user.id"
                            class="border-b border-slate-100 last:border-0 hover:bg-slate-50"
                        >
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-200"
                                    >
                                        <img
                                            v-if="user.avatar_url"
                                            :alt="user.name"
                                            :src="user.avatar_url"
                                            class="h-full w-full object-cover"
                                        />
                                        <span
                                            v-else
                                            class="text-xs font-bold text-slate-400"
                                        >
                                            {{
                                                user.name
                                                    .charAt(0)
                                                    .toUpperCase()
                                            }}
                                        </span>
                                    </div>
                                    <span class="font-medium text-slate-900">{{
                                        user.name
                                    }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <a
                                    :href="profileUrl(user.member_token)"
                                    class="font-mono text-xs text-gpn-orange underline-offset-2 hover:underline"
                                    rel="noopener"
                                    target="_blank"
                                >
                                    {{ memberDomain }}/{{ user.member_token }}
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div
                class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
            >
                <p
                    class="mb-3 text-xs font-semibold tracking-widest text-slate-400 uppercase"
                >
                    Teamfoto (Aushang)
                </p>
                <div class="flex items-start gap-5">
                    <div
                        class="flex h-24 w-32 flex-shrink-0 items-center justify-center overflow-hidden rounded-lg bg-slate-100"
                    >
                        <img
                            v-if="teamPhotoPreview ?? teamPhotoUrl"
                            :src="(teamPhotoPreview ?? teamPhotoUrl)!"
                            alt="Teamfoto"
                            class="h-full w-full object-cover"
                        />
                        <span v-else class="text-xs text-slate-400"
                            >Kein Foto</span
                        >
                    </div>
                    <div class="space-y-2">
                        <div class="flex flex-wrap gap-2">
                            <button
                                class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                @click="photoFileInput?.click()"
                            >
                                Foto auswählen
                            </button>
                            <button
                                v-if="teamPhotoFile"
                                :disabled="photoSaving"
                                class="rounded-lg bg-gpn-orange px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-50"
                                @click="uploadPhoto"
                            >
                                {{ photoSaving ? 'Lädt hoch...' : 'Hochladen' }}
                            </button>
                            <button
                                v-if="teamPhotoUrl && !teamPhotoFile"
                                :disabled="photoSaving"
                                class="rounded-lg border border-red-200 px-4 py-2 text-sm font-medium text-red-600 transition hover:bg-red-50 disabled:opacity-50"
                                @click="deletePhoto"
                            >
                                Entfernen
                            </button>
                        </div>
                        <p class="text-xs text-slate-400">
                            JPG, PNG, WebP · max. 10 MB
                        </p>
                        <p v-if="photoError" class="text-xs text-red-600">
                            {{ photoError }}
                        </p>
                        <input
                            ref="photoFileInput"
                            accept="image/*"
                            class="hidden"
                            type="file"
                            @change="onPhotoChange"
                        />
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
