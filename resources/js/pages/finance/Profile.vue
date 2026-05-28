<script lang="ts" setup>
import { ref } from 'vue';
import axios from '@/lib/axios';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();

const bio = ref(auth.user?.member_bio ?? '');
const appearance = ref(auth.user?.member_appearance ?? '');
const avatarPreview = ref<string | null>(auth.user?.avatar_url ?? null);
const avatarFile = ref<File | null>(null);
const saving = ref(false);
const saved = ref(false);
const error = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

function onFileChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];

    if (!file) {
        return;
    }

    avatarFile.value = file;
    const reader = new FileReader();
    reader.onload = (ev) => {
        avatarPreview.value = ev.target?.result as string;
    };
    reader.readAsDataURL(file);
}

async function save() {
    saving.value = true;
    saved.value = false;
    error.value = null;

    try {
        const form = new FormData();
        form.append('member_bio', bio.value || '');
        form.append('member_appearance', appearance.value || '');

        if (avatarFile.value) {
            form.append('avatar', avatarFile.value);
        }

        // FormData needs PATCH via POST + _method override for file uploads
        form.append('_method', 'PATCH');

        const { data } = await axios.post('/api/finance/profile', form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        if (auth.user) {
            auth.user.member_bio = data.user.member_bio;
            auth.user.member_appearance = data.user.member_appearance;
            auth.user.avatar_url = data.user.avatar_url;
        }

        // Force browser to reload avatar by busting the cache
        if (data.user.avatar_url) {
            avatarPreview.value = data.user.avatar_url + '?t=' + Date.now();
        }

        avatarFile.value = null;
        saved.value = true;
        setTimeout(() => (saved.value = false), 3000);
    } catch {
        error.value = 'Speichern fehlgeschlagen.';
    } finally {
        saving.value = false;
    }
}

const memberDomain = window.__APP_CONFIG__?.memberDomain ?? 'member.localhost';
const profileUrl = auth.user?.member_token
    ? `https://${memberDomain}/${auth.user.member_token}`
    : null;

const currentPassword = ref('');
const newPassword = ref('');
const newPasswordConfirmation = ref('');
const changingPassword = ref(false);
const passwordSaved = ref(false);
const passwordError = ref<string | null>(null);

async function changePassword() {
    changingPassword.value = true;
    passwordSaved.value = false;
    passwordError.value = null;

    try {
        await axios.post('/api/finance/password', {
            current_password: currentPassword.value,
            password: newPassword.value,
            password_confirmation: newPasswordConfirmation.value,
        });

        currentPassword.value = '';
        newPassword.value = '';
        newPasswordConfirmation.value = '';
        passwordSaved.value = true;
        setTimeout(() => (passwordSaved.value = false), 3000);
    } catch (e: unknown) {
        const err = e as {
            response?: {
                data?: { errors?: Record<string, string[]>; message?: string };
            };
        };
        const errors = err.response?.data?.errors;

        if (errors) {
            passwordError.value =
                Object.values(errors).flat()[0] ??
                'Fehler beim Ändern des Passworts.';
        } else {
            passwordError.value =
                err.response?.data?.message ??
                'Fehler beim Ändern des Passworts.';
        }
    } finally {
        changingPassword.value = false;
    }
}
</script>

<template>
    <div class="p-6">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-900">Mein Profil</h1>
            <p class="mt-1 text-sm text-slate-500">
                Diese Informationen sind auf deiner öffentlichen Profilseite
                sichtbar.
            </p>
        </div>

        <div class="max-w-xl space-y-6">
            <div
                class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
            >
                <p
                    class="mb-3 text-xs font-semibold tracking-widest text-slate-400 uppercase"
                >
                    Profilbild
                </p>
                <div class="flex items-center gap-5">
                    <div
                        class="flex h-20 w-20 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100"
                    >
                        <img
                            v-if="avatarPreview"
                            :src="avatarPreview"
                            alt="Profilbild"
                            class="h-full w-full object-cover"
                        />
                        <span v-else class="text-2xl font-bold text-slate-300">
                            {{ auth.user?.name?.charAt(0).toUpperCase() }}
                        </span>
                    </div>
                    <div>
                        <button
                            class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            @click="fileInput?.click()"
                        >
                            Bild auswählen
                        </button>
                        <p class="mt-1 text-xs text-slate-400">
                            JPG, PNG, WebP · max. 5 MB
                        </p>
                        <input
                            ref="fileInput"
                            accept="image/*"
                            class="hidden"
                            type="file"
                            @change="onFileChange"
                        />
                    </div>
                </div>
            </div>

            <div
                class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
            >
                <p
                    class="text-xs font-semibold tracking-widest text-slate-400 uppercase"
                >
                    Name
                </p>
                <p class="mt-1 text-lg font-medium text-slate-900">
                    {{ auth.user?.name }}
                </p>
            </div>

            <div
                class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
            >
                <p
                    class="mb-4 text-xs font-semibold tracking-widest text-slate-400 uppercase"
                >
                    Profilinfos
                </p>
                <div class="space-y-4">
                    <div>
                        <label
                            class="mb-2 block text-xs font-semibold tracking-widest text-slate-400 uppercase"
                            for="appearance"
                        >
                            Woran erkennt man dich?
                        </label>
                        <input
                            id="appearance"
                            v-model="appearance"
                            class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition outline-none focus:border-gpn-orange focus:ring-2 focus:ring-gpn-orange/20"
                            maxlength="500"
                            placeholder="z.B. blaue Haare, Brille, ..."
                            type="text"
                        />
                        <p class="mt-1 text-right text-xs text-slate-400">
                            {{ appearance.length }}/500
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-2 block text-xs font-semibold tracking-widest text-slate-400 uppercase"
                            for="bio"
                        >
                            Profiltext
                        </label>
                        <textarea
                            id="bio"
                            v-model="bio"
                            class="w-full resize-none rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition outline-none focus:border-gpn-orange focus:ring-2 focus:ring-gpn-orange/20"
                            maxlength="1000"
                            placeholder="Schreib hier etwas über dich..."
                            rows="6"
                        />
                        <p class="mt-1 text-right text-xs text-slate-400">
                            {{ bio.length }}/1000
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <button
                        :disabled="saving"
                        class="rounded-lg bg-gpn-orange px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-50"
                        @click="save"
                    >
                        {{ saving ? 'Speichert...' : 'Speichern' }}
                    </button>
                    <span v-if="saved" class="text-sm text-green-600"
                        >Gespeichert!</span
                    >
                    <span v-if="error" class="text-sm text-red-600">{{
                        error
                    }}</span>
                </div>
            </div>

            <div
                class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
            >
                <p
                    class="mb-4 text-xs font-semibold tracking-widest text-slate-400 uppercase"
                >
                    Passwort ändern
                </p>
                <form class="space-y-3" @submit.prevent="changePassword">
                    <div>
                        <label
                            class="mb-1 block text-xs text-slate-500"
                            for="current_password"
                        >
                            Aktuelles Passwort
                        </label>
                        <input
                            id="current_password"
                            v-model="currentPassword"
                            autocomplete="current-password"
                            class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition outline-none focus:border-gpn-orange focus:ring-2 focus:ring-gpn-orange/20"
                            required
                            type="password"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-xs text-slate-500"
                            for="new_password"
                        >
                            Neues Passwort
                        </label>
                        <input
                            id="new_password"
                            v-model="newPassword"
                            autocomplete="new-password"
                            class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition outline-none focus:border-gpn-orange focus:ring-2 focus:ring-gpn-orange/20"
                            required
                            type="password"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1 block text-xs text-slate-500"
                            for="new_password_confirmation"
                        >
                            Neues Passwort bestätigen
                        </label>
                        <input
                            id="new_password_confirmation"
                            v-model="newPasswordConfirmation"
                            autocomplete="new-password"
                            class="w-full rounded-lg border border-slate-200 px-4 py-3 text-sm text-slate-900 placeholder-slate-400 transition outline-none focus:border-gpn-orange focus:ring-2 focus:ring-gpn-orange/20"
                            required
                            type="password"
                        />
                    </div>
                    <div class="flex items-center gap-3 pt-1">
                        <button
                            :disabled="changingPassword"
                            class="rounded-lg bg-gpn-orange px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90 disabled:opacity-50"
                            type="submit"
                        >
                            {{
                                changingPassword
                                    ? 'Wird geändert...'
                                    : 'Passwort ändern'
                            }}
                        </button>
                        <span
                            v-if="passwordSaved"
                            class="text-sm text-green-600"
                            >Gespeichert!</span
                        >
                        <span
                            v-if="passwordError"
                            class="text-sm text-red-600"
                            >{{ passwordError }}</span
                        >
                    </div>
                </form>
            </div>

            <div
                v-if="profileUrl"
                class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm"
            >
                <p
                    class="mb-2 text-xs font-semibold tracking-widest text-slate-400 uppercase"
                >
                    Dein Profil-Link
                </p>
                <code
                    class="block rounded bg-slate-100 px-3 py-2 text-sm break-all text-slate-800"
                >
                    {{ profileUrl }}
                </code>
            </div>
        </div>
    </div>
</template>
