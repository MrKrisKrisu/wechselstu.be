<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from '@/lib/axios';

const router = useRouter();

const email = ref('');
const password = ref('');
const error = ref('');
const loading = ref(false);

async function handleSubmit() {
    error.value = '';
    loading.value = true;

    try {
        await axios.get('/sanctum/csrf-cookie');
        await axios.post('/login', {
            email: email.value,
            password: password.value,
        });
        router.push('/finance');
    } catch (err: any) {
        if (err.response?.status === 422) {
            const errors = err.response.data?.errors;

            if (errors) {
                const firstKey = Object.keys(errors)[0];
                error.value = errors[firstKey]?.[0] ?? 'Ungültige Eingabe.';
            } else {
                error.value =
                    err.response.data?.message ?? 'Anmeldung fehlgeschlagen.';
            }
        } else {
            error.value = 'Anmeldung fehlgeschlagen. Bitte erneut versuchen.';
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
        <div class="w-full max-w-sm">
            <div
                class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
            >
                <div class="mb-8 text-center">
                    <h1
                        class="text-2xl font-bold tracking-tight text-slate-900"
                    >
                        wechselstu.be
                    </h1>
                </div>

                <div
                    v-if="error"
                    class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                >
                    {{ error }}
                </div>

                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <div>
                        <label
                            for="email"
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            E-Mail
                        </label>
                        <input
                            id="email"
                            v-model="email"
                            type="email"
                            required
                            autocomplete="email"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-500 focus:ring-1 focus:ring-slate-500 focus:outline-none"
                            placeholder="name@example.com"
                        />
                    </div>

                    <div>
                        <label
                            for="password"
                            class="mb-1 block text-sm font-medium text-slate-700"
                        >
                            Passwort
                        </label>
                        <input
                            id="password"
                            v-model="password"
                            type="password"
                            required
                            autocomplete="current-password"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-slate-500 focus:ring-1 focus:ring-slate-500 focus:outline-none"
                            placeholder="••••••••"
                        />
                    </div>

                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-700 focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span v-if="loading">Anmelden...</span>
                        <span v-else>Anmelden</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>
