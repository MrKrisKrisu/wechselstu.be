<script lang="ts" setup>
import axios from 'axios';
import { computed, reactive, ref } from 'vue';

const props = defineProps<{
    cashRegister: {
        id: string;
        name: string;
    };
}>();

const token = computed(() => new URLSearchParams(window.location.search).get('token'));

const form = reactive({
    hasOverflow: false,
    needsChange: false,
    changeItems: [
        { denomination: 50, quantity: 0 },
        { denomination: 100, quantity: 0 },
        { denomination: 200, quantity: 0 },
    ],
});

const submitting = ref(false);
const submitted = ref(false);
const error = ref<string | null>(null);

function increment(item: (typeof form.changeItems)[number]) {
    item.quantity++;
}

function decrement(item: (typeof form.changeItems)[number]) {
    if (item.quantity > 0) {
        item.quantity--;
    }
}

async function submitRequest(type: 'overflow' | 'change_request') {
    const payload: Record<string, unknown> = { type };

    if (type === 'change_request') {
        const items = form.changeItems.filter((item) => item.quantity > 0);

        if (items.length === 0) {
            error.value = 'Please specify at least one coin roll needed.';
            return;
        }

        payload.items = items;
    }

    const url = `/api/cash-registers/${props.cashRegister.id}/work-orders`;

    try {
        await axios.post(`${url}?token=${token.value}`, payload);
        submitted.value = true;
    } catch (e) {
        console.error(e);
        error.value = 'An error occurred while submitting the request.';
    }
}

async function submit() {
    if (!token.value) {
        error.value = 'Token is missing.';
        return;
    }

    submitting.value = true;
    error.value = null;

    const requestType = form.hasOverflow ? 'overflow' : 'change_request';
    await submitRequest(requestType);

    submitting.value = false;
}
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-white p-4 text-gray-900 dark:bg-black dark:text-gray-100">
        <div class="w-full max-w-xl rounded-xl bg-gray-50 p-6 shadow dark:bg-gray-900">
            <h1 class="mb-4 text-center text-2xl font-bold">
                {{ cashRegister.name }}
            </h1>

            <p class="mb-4 text-center text-sm text-gray-600 dark:text-gray-300">
                ❓ For questions or issues, please call DECT <strong>4354</strong>.
            </p>

            <template v-if="submitted">
                <div class="text-center text-xl font-semibold text-green-600">✅ Request was successfully submitted.</div>
            </template>

            <template v-else>
                <template v-if="(form.needsChange || form.hasOverflow) && !submitting">
                    <button
                        class="w-full rounded bg-gray-500 px-4 py-2 font-bold text-white hover:bg-gray-700"
                        @click="
                            () => {
                                form.hasOverflow = false;
                                form.needsChange = false;
                            }
                        "
                    >
                        ← Go back
                    </button>
                </template>

                <template v-else>
                    <button
                        :disabled="submitting"
                        class="mt-3 w-full rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700"
                        @click="
                            () => {
                                form.hasOverflow = true;
                                submit();
                            }
                        "
                    >
                        💸 Cash overflow (needs removal)
                    </button>

                    <button
                        :disabled="submitting"
                        class="mt-3 w-full rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700"
                        @click="() => (form.needsChange = !form.needsChange)"
                    >
                        🔁 Change needed
                    </button>
                </template>

                <div v-if="form.needsChange" class="mt-4 flex flex-col gap-4">
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
                        @click="submit"
                    >
                        {{ submitting ? 'Submitting...' : 'Submit' }}
                    </button>
                </div>
            </template>
        </div>
    </div>
</template>

<style scoped>
body {
    margin: 0;
    font-family: system-ui, sans-serif;
}
</style>
