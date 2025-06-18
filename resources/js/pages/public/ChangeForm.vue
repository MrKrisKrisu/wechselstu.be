<script lang="ts" setup>
const props = defineProps<{
    form: any;
    submitting: boolean;
    error: string | null;
}>();
const emit = defineEmits(['submit']);

function increment(item: any) {
    item.quantity++;
}

function decrement(item: any) {
    if (item.quantity > 0) {
        item.quantity--;
    }
}
</script>

<template>
    <div class="mt-4 flex flex-col gap-4">
        <div v-for="item in form.changeItems" :key="item.denomination" class="flex items-center justify-between text-lg">
            <label class="w-32">
                <span v-if="item.denomination === 50">0.50 Euro</span>
                <span v-else-if="item.denomination === 100">1.00 Euro</span>
                <span v-else-if="item.denomination === 200">2.00 Euro</span>
            </label>
            <div class="flex items-center gap-2">
                <button
                    class="h-10 w-10 rounded bg-gray-300 font-bold text-black dark:bg-gray-700 dark:text-white"
                    type="button"
                    @click="() => decrement(item)"
                >
                    -
                </button>
                <span class="w-8 text-center">{{ item.quantity }}</span>
                <button
                    class="h-10 w-10 rounded bg-gray-300 font-bold text-black dark:bg-gray-700 dark:text-white"
                    type="button"
                    @click="() => increment(item)"
                >
                    +
                </button>
            </div>
        </div>

        <div v-if="error" class="text-sm font-semibold text-red-600">{{ error }}</div>

        <button
            :disabled="submitting"
            class="w-full rounded bg-green-600 px-4 py-2 font-bold text-white hover:bg-green-700"
            @click="() => emit('submit')"
        >
            {{ submitting ? 'Submitting...' : 'Submit' }}
        </button>
    </div>
</template>
