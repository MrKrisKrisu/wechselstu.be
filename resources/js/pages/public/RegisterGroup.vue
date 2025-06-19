<script lang="ts" setup>
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';
import ChangeForm from './ChangeForm.vue';

type LastOrder = {
    id: string;
    status: string;
    created_at: string;
    updated_at: string;
} | null;

type Register = {
    id: string;
    name: string;
    token: string;
    group: {
        id: string;
        name: string;
    };
    last_order: {
        overflow: LastOrder;
        change: LastOrder;
    };
};

type RequestState = {
    submitting: boolean;
    submitted: boolean;
    error: string | null;
};

const registers = ref<Register[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const requestStates = reactive<Record<string, RequestState>>({});
const showChangeForm = reactive<Record<string, boolean>>({});
const changeForms = reactive<Record<string, any>>({});
const password = ref<string>('');
const passwordEntered = ref(false);

const params = new URLSearchParams(window.location.search);
const groupId = params.get('groupId');

function savePassword(pw: string) {
    try {
        localStorage.setItem('cash_register_password', pw);
        password.value = pw;
    } catch (e) {
        console.error('Could not save password to localStorage', e);
    }
}

function clearPassword() {
    try {
        localStorage.removeItem('cash_register_password');
    } catch (e) {
        console.error('Could not clear password from localStorage', e);
    }
    password.value = '';
    passwordEntered.value = false;
}

function loadPasswordFromStorage(): string | null {
    try {
        return localStorage.getItem('cash_register_password');
    } catch (e) {
        console.error('Could not load password from localStorage', e);
        return null;
    }
}

async function loadRegisters() {
    loading.value = true;
    try {
        const response = await axios.get(`/api/register-groups/${groupId}?password=${encodeURIComponent(password.value)}`);
        registers.value = response.data;
        registers.value.forEach((reg) => {
            if (!requestStates[reg.id]) {
                requestStates[reg.id] = {
                    submitting: false,
                    submitted: false,
                    error: null,
                };
            }
            if (!showChangeForm[reg.id]) {
                showChangeForm[reg.id] = false;
            }
            if (!changeForms[reg.id]) {
                changeForms[reg.id] = {
                    changeItems: [
                        { denomination: 50, quantity: 0 },
                        { denomination: 100, quantity: 0 },
                        { denomination: 200, quantity: 0 },
                        { denomination: 500, quantity: 0 },
                        { denomination: 1000, quantity: 0 },
                        { denomination: 2000, quantity: 0 },
                    ],
                };
            }
        });
    } catch (e: any) {
        console.error(e);
        error.value = 'Request failed. Please enter the password again.';
        clearPassword();
    } finally {
        loading.value = false;
    }
}

async function submitRequest(register: Register, type: 'overflow') {
    const state = requestStates[register.id];
    state.submitting = true;
    state.error = null;

    try {
        await axios.post(`/api/cash-registers/${register.id}/work-orders?token=${register.token}`, {
            type,
        });
        state.submitted = true;
        await loadRegisters();
    } catch (e: any) {
        console.error(e);
        state.error = 'Request failed. Please enter the password again.';
    } finally {
        state.submitting = false;
    }
}

async function submitChangeRequest(register: Register) {
    const state = requestStates[register.id];
    state.submitting = true;
    state.error = null;

    try {
        await axios.post(`/api/cash-registers/${register.id}/work-orders?token=${register.token}&password=${encodeURIComponent(password.value)}`, {
            type: 'change_request',
            items: changeForms[register.id].changeItems.filter((item: any) => item.quantity > 0),
        });
        state.submitted = true;
        showChangeForm[register.id] = false;
        changeForms[register.id].changeItems.forEach((item: any) => (item.quantity = 0));
        await loadRegisters();
    } catch (e: any) {
        console.error(e);
        state.error = 'Request failed. Please enter the password again.';
        clearPassword();
    } finally {
        state.submitting = false;
    }
}

function formatDate(dateString: string | null): string {
    if (!dateString) return 'Never';

    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffSec = Math.floor(diffMs / 1000);

    if (diffSec < 60) return `${diffSec} second${diffSec !== 1 ? 's' : ''} ago`;
    const diffMin = Math.floor(diffSec / 60);
    if (diffMin < 60) return `${diffMin} minute${diffMin !== 1 ? 's' : ''} ago`;
    const diffHrs = Math.floor(diffMin / 60);
    if (diffHrs < 24) return `${diffHrs} hour${diffHrs !== 1 ? 's' : ''} ago`;
    const diffDays = Math.floor(diffHrs / 24);
    return `${diffDays} day${diffDays !== 1 ? 's' : ''} ago`;
}

onMounted(() => {
    if (!groupId) {
        error.value = 'Missing groupId in URL.';
        loading.value = false;
        return;
    }
    const storedPw = loadPasswordFromStorage();
    if (storedPw) {
        password.value = storedPw;
        passwordEntered.value = true;
        loadRegisters();
    }
});
</script>

<template>
    <div class="min-h-screen bg-white p-4 text-gray-900 dark:bg-black dark:text-gray-100">
        <h1 class="mb-6 text-center text-2xl font-bold">Cash Registers</h1>

        <div v-if="!passwordEntered" class="mx-auto max-w-sm">
            <label class="mb-2 block font-semibold">Enter password:</label>
            <input v-model="password" class="w-full rounded border p-2 dark:bg-gray-800 dark:text-white" type="password" />
            <button
                class="mt-2 w-full rounded bg-blue-600 p-2 font-bold text-white hover:bg-blue-700"
                @click="
                    () => {
                        savePassword(password);
                        passwordEntered = true;
                        loadRegisters();
                    }
                "
            >
                Submit
            </button>
            <div v-if="error" class="mt-2 text-center text-sm text-red-600">{{ error }}</div>
        </div>

        <div v-else>
            <div v-if="loading" class="text-center text-lg">⏳ Loading...</div>
            <div v-else-if="error" class="text-center font-semibold text-red-600">{{ error }}</div>
            <div v-else>
                <div v-if="registers.length === 0" class="text-center text-lg font-semibold text-green-600">No registers found in this group.</div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="register in registers" :key="register.id" class="rounded-lg border bg-gray-50 p-4 shadow dark:bg-gray-900">
                        <h2 class="mb-2 text-lg font-bold">{{ register.name }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Group: {{ register.group.name }}</p>

                        <div class="text-sm">
                            <div>
                                <p class="font-semibold">💸 Overflow:</p>
                                <p v-if="register.last_order.overflow">
                                    <template v-if="register.last_order.overflow.status === 'done'">
                                        completed {{ formatDate(register.last_order.overflow.updated_at) }}
                                    </template>
                                    <template v-else-if="register.last_order.overflow.status === 'in_progress'">
                                        prepared {{ formatDate(register.last_order.overflow.created_at) }}
                                    </template>
                                    <template v-else> created {{ formatDate(register.last_order.overflow.created_at) }}</template>
                                </p>
                                <p v-else class="text-gray-400 italic">No overflow request yet</p>
                            </div>

                            <div class="mt-1">
                                <p class="font-semibold">🔁 Change:</p>
                                <p v-if="register.last_order.change">
                                    <template v-if="register.last_order.change.status === 'done'">
                                        completed {{ formatDate(register.last_order.change.updated_at) }}
                                    </template>
                                    <template v-else-if="register.last_order.change.status === 'in_progress'">
                                        prepared {{ formatDate(register.last_order.change.created_at) }}
                                    </template>
                                    <template v-else> created {{ formatDate(register.last_order.change.created_at) }}</template>
                                </p>
                                <p v-else class="text-gray-400 italic">No change request yet</p>
                            </div>
                        </div>

                        <div class="mt-2 flex flex-col gap-2">
                            <button
                                :class="[
                                    'w-full rounded px-4 py-2 font-bold text-white',
                                    requestStates[register.id]?.submitting ? 'bg-gray-500' : 'bg-blue-600 hover:bg-blue-700',
                                ]"
                                :disabled="requestStates[register.id]?.submitting"
                                @click="() => submitRequest(register, 'overflow')"
                            >
                                💸 Overflow
                            </button>
                            <button
                                class="w-full rounded bg-blue-600 px-4 py-2 font-bold text-white hover:bg-blue-700"
                                @click="() => (showChangeForm[register.id] = !showChangeForm[register.id])"
                            >
                                🔁 Change request
                            </button>
                        </div>

                        <ChangeForm
                            v-if="showChangeForm[register.id]"
                            :error="requestStates[register.id]?.error"
                            :form="changeForms[register.id]"
                            :submitting="requestStates[register.id]?.submitting"
                            @submit="() => submitChangeRequest(register)"
                        />

                        <div v-if="requestStates[register.id]?.submitting" class="mt-2 text-center text-sm text-gray-500">⏳ Sending request...</div>
                        <div v-if="requestStates[register.id]?.submitted" class="mt-2 text-center font-semibold text-green-600">
                            ✅ Request submitted
                        </div>
                        <div v-if="requestStates[register.id]?.error" class="mt-2 text-center text-sm text-red-600">
                            {{ requestStates[register.id]?.error }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
