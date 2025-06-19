<script lang="ts" setup>
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';
import ChangeForm from './ChangeForm.vue';
import RequestButtons from './RequestButtons.vue';

const props = defineProps<{
    cashRegister: {
        id: string;
        name: string;
    };
}>();

const url = window.location.href;
const token = ref<string | null>(url.split('/').pop() ?? null);

const form = reactive({
    hasOverflow: false,
    needsChange: false,
    changeItems: [
        { denomination: 50, quantity: 0 },
        { denomination: 100, quantity: 0 },
        { denomination: 200, quantity: 0 },
        { denomination: 500, quantity: 0 },
        { denomination: 1000, quantity: 0 },
        { denomination: 2000, quantity: 0 },
    ],
});

const submitting = ref(false);
const submitted = ref(false);
const error = ref<string | null>(null);
const hasActiveChangeRequest = ref(false);
const hasActiveOverflowRequest = ref(false);

onMounted(async () => {
    try {
        const response = await axios.get(`/api/cash-registers/${props.cashRegister.id}/status?token=${token.value}`);
        if (response.data) {
            hasActiveChangeRequest.value = response.data.change_request_pending;
            hasActiveOverflowRequest.value = response.data.overflow_pending;
        }
    } catch (e) {
        console.error(e);
        error.value = 'Unable to check current work order status.';
    }
});

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

    const apiUrl = `/api/cash-registers/${props.cashRegister.id}/work-orders`;

    try {
        await axios.post(`${apiUrl}?token=${token.value}`, payload);
        submitted.value = true;
    } catch (e: any) {
        console.error(e);
        error.value =
            e.response?.status === 409
                ? (e.response.data.message ?? 'There is already an active request. Please call 2274 (CASH) via DECT.')
                : 'An error occurred while submitting the request.';
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
            <h1 class="mb-4 text-center text-2xl font-bold">{{ cashRegister.name }}</h1>
            <p class="mb-4 text-center text-sm text-gray-600 dark:text-gray-300">
                ❓ For questions or issues, please call DECT <strong>4353 (GELD)</strong>.
            </p>

            <template v-if="submitted">
                <div class="text-center text-xl font-semibold text-green-600">
                    ✅ Request was successfully submitted.
                    <img alt="Success" class="mx-auto mt-4" src="/success.gif" />
                </div>
            </template>

            <template v-else>
                <RequestButtons
                    :form="form"
                    :hasActiveChangeRequest="hasActiveChangeRequest"
                    :hasActiveOverflowRequest="hasActiveOverflowRequest"
                    :submitting="submitting"
                    @resetForm="
                        () => {
                            form.hasOverflow = false;
                            form.needsChange = false;
                        }
                    "
                    @submitOverflow="
                        () => {
                            form.hasOverflow = true;
                            submit();
                        }
                    "
                    @toggleChangeForm="
                        () => {
                            form.needsChange = !form.needsChange;
                        }
                    "
                />

                <ChangeForm v-if="form.needsChange" :error="error" :form="form" :submitting="submitting" @submit="submit" />
            </template>
        </div>
    </div>
</template>
