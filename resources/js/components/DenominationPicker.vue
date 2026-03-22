<script setup lang="ts">
import { DENOMINATIONS } from '@/types';

const props = defineProps<{
    modelValue: Record<number, number>;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: Record<number, number>];
}>();

function getQty(cents: number): number {
    return props.modelValue[cents] ?? 0;
}

function setQty(cents: number, delta: number): void {
    const current = getQty(cents);
    const next = Math.max(0, current + delta);
    emit('update:modelValue', { ...props.modelValue, [cents]: next });
}
</script>

<template>
    <div
        class="divide-y divide-slate-200 overflow-hidden rounded border border-slate-200"
    >
        <div
            v-for="denom in DENOMINATIONS"
            :key="denom.cents"
            class="flex items-center justify-between bg-white px-4 py-3"
        >
            <span
                class="w-16 text-sm font-semibold tracking-wide text-slate-800"
                >{{ denom.label }}</span
            >
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="flex h-8 w-8 items-center justify-center rounded bg-slate-100 text-lg font-bold text-slate-700 transition-colors hover:bg-slate-200 active:bg-slate-300 disabled:cursor-not-allowed disabled:opacity-40"
                    :disabled="getQty(denom.cents) === 0"
                    @click="setQty(denom.cents, -1)"
                >
                    −
                </button>
                <span
                    class="w-8 text-center text-base font-bold text-slate-900 tabular-nums"
                >
                    {{ getQty(denom.cents) }}
                </span>
                <button
                    type="button"
                    class="flex h-8 w-8 items-center justify-center rounded bg-[#0f172a] text-lg font-bold text-amber-400 transition-colors hover:bg-slate-700 active:bg-slate-600"
                    @click="setQty(denom.cents, 1)"
                >
                    +
                </button>
            </div>
        </div>
    </div>
</template>
