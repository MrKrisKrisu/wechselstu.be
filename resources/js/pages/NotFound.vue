<script setup lang="ts">
import { useRouter } from 'vue-router';

const router = useRouter();
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
        <div class="text-center">
            <p class="text-7xl font-bold text-slate-200 select-none">404</p>
            <h1 class="mt-4 text-2xl font-bold text-slate-900">
                Seite nicht gefunden
            </h1>
            <p class="mt-2 text-sm text-slate-500">
                Die angeforderte Seite existiert nicht.
            </p>
            <button
                @click="router.push('/')"
                class="mt-6 inline-flex items-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-medium text-white transition-colors hover:bg-slate-700"
            >
                Zur Startseite
            </button>
        </div>
    </div>
</template>
