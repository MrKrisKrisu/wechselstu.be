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
    has_overflow: false,
    needs_change: false,
    notes: '',
    change_items: [
        { denomination: 50, quantity: 0 }, // 50 cent
        { denomination: 100, quantity: 0 }, // 1 Euro
        { denomination: 200, quantity: 0 }, // 2 Euro
    ],
});

const submitting = ref(false);
const submitted = ref(false);
const error = ref<string | null>(null);

function increment(item: { denomination: number; quantity: number }) {
    item.quantity++;
}

function decrement(item: { denomination: number; quantity: number }) {
    if (item.quantity > 0) item.quantity--;
}

async function submitOverflow() {
    axios
        .post(`/api/cash-registers/${props.cashRegister.id}/work-orders?token=${token.value}`, {
            type: 'overflow',
            notes: form.notes,
        })
        .then(() => {
            submitted.value = true;
        })
        .catch((e) => {
            console.error(e);
            error.value = 'An error occurred while submitting the request.';
        });
}

async function submitChangeRequest() {
    const items = form.change_items.filter((i) => i.quantity > 0);

    if (items.length === 0) {
        error.value = 'Please specify at least one coin roll needed.';
        submitting.value = false;
        return;
    }

    axios
        .post(`/api/cash-registers/${props.cashRegister.id}/work-orders?token=${token.value}`, {
            type: 'change_request',
            notes: form.notes,
            items,
        })
        .then(() => {
            submitted.value = true;
        })
        .catch((e) => {
            console.error(e);
            error.value = 'An error occurred while submitting the request.';
        });
}

async function submit() {
    if (!token.value) {
        error.value = 'Token is missing.';
        return;
    }

    submitting.value = true;
    error.value = null;
    if (form.has_overflow) {
        await submitOverflow();
    } else {
        await submitChangeRequest();
    }
}
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-white p-4 text-gray-900 dark:bg-black dark:text-gray-100">
        <div class="w-full max-w-xl rounded-xl bg-gray-50 p-6 shadow dark:bg-gray-900">
            <h1 class="mb-4 text-center text-2xl font-bold">
                {{ cashRegister.name }}
            </h1>

            <div v-if="submitted" class="text-center text-xl font-semibold text-green-600">✅ Request was successfully submitted.</div>
            <div v-else>
                <div v-if="form.needs_change || form.has_overflow" class="flex flex-col gap-2">
                    <button
                        class="w-full rounded bg-gray-500 px-4 py-2 font-bold text-white hover:bg-gray-700"
                        @click="
                            form.has_overflow = false;
                            form.needs_change = false;
                        "
                    >
                        Go back
                    </button>
                </div>
                <div v-else class="space-y-4">
                    <button
                        :disabled="submitting"
                        class="w-full rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700"
                        @click="form.has_overflow = !form.has_overflow"
                    >
                        <i class="fa-solid fa-money-bill-wave"></i>
                        💸 Cash overflow (needs removal)
                    </button>

                    <button
                        class="w-full rounded bg-blue-500 px-4 py-2 font-bold text-white hover:bg-blue-700"
                        @click="form.needs_change = !form.needs_change"
                    >
                        🔁 Change needed
                    </button>
                </div>

                <div v-if="form.needs_change" class="mt-3 flex flex-col gap-2">
                    <div v-for="item in form.change_items" :key="item.denomination" class="flex items-center justify-between text-lg">
                        <label class="w-32">
                            <span v-if="item.denomination === 50">0,50 €</span>
                            <span v-else-if="item.denomination === 100">1,00 €</span>
                            <span v-else-if="item.denomination === 200">2,00 €</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <button
                                class="h-12 w-12 rounded bg-gray-300 font-bold text-black dark:bg-gray-700 dark:text-white"
                                type="button"
                                @click="decrement(item)"
                            >
                                -
                            </button>
                            <span class="w-6 text-center">{{ item.quantity }}</span>
                            <button
                                class="h-12 w-12 rounded bg-gray-300 font-bold text-black dark:bg-gray-700 dark:text-white"
                                type="button"
                                @click="increment(item)"
                            >
                                +
                            </button>
                        </div>
                    </div>
                </div>
                <div v-if="form.needs_change || form.has_overflow" class="flex flex-col gap-2">
                    <div>
                        <label class="mt-3 mb-1 block text-sm font-medium">Some (optional) comments?</label>
                        <textarea v-model="form.notes" class="w-full rounded border px-2 py-1" rows="3"></textarea>
                    </div>

                    <div v-if="error" class="text-sm font-semibold text-red-600">{{ error }}</div>

                    <button :disabled="submitting" class="w-full rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" @click="submit">
                        {{ submitting ? 'Submitting...' : 'Submit' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
body {
    margin: 0;
    font-family: system-ui, sans-serif;
}
</style>
