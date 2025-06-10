<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';

interface CashRegister {
    id: string;
    name: string;
    token: string;
}

const registers = ref<CashRegister[]>([]);
const newName = ref('');
const error = ref<string | null>(null);

const fetchRegisters = async () => {
    try {
        const res = await axios.get('/api/cash-registers');
        registers.value = res.data;
    } catch (e) {
        console.error(e);
        error.value = 'Failed to load registers.';
    }
};

const createRegister = async () => {
    if (!newName.value.trim()) return;
    try {
        const res = await axios.post('/api/cash-registers', { name: newName.value.trim() });
        registers.value.push(res.data);
        newName.value = '';
    } catch (e) {
        console.error(e);
        error.value = 'Failed to create register.';
    }
};

const updateRegisterName = async (register: CashRegister) => {
    try {
        await axios.put(`/api/cash-registers/${register.id}`, { name: register.name });
    } catch (e) {
        console.error(e);
        error.value = 'Failed to update name.';
    }
};

const resetToken = async (register: CashRegister) => {
    try {
        const res = await axios.post(`/api/cash-registers/${register.id}/reset-token`);
        register.token = res.data.token;
    } catch (e) {
        console.error(e);
        error.value = 'Failed to reset token.';
    }
};

onMounted(() => {
    fetchRegisters();
});
</script>

<template>
    <Head title="Manage Registers" />
    <AppLayout :breadcrumbs="[{ title: 'Cash Registers', href: '/cash-registers/manage' }]">
        <div class="p-4">
            <h1 class="mb-4 text-2xl font-bold">Manage Cash Registers</h1>

            <div class="mb-6">
                <label class="block text-sm font-medium">New Cash Register Name</label>
                <div class="mt-1 flex gap-2">
                    <input v-model="newName" class="w-full rounded border px-3 py-2 dark:bg-gray-800 dark:text-white" placeholder="e.g. Bar 1" />
                    <button class="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700" @click="createRegister">Add</button>
                </div>
            </div>

            <div v-if="error" class="mb-4 text-sm text-red-600">{{ error }}</div>

            <table class="min-w-full divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Name</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Token</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Link</th>
                        <th class="px-4 py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="register in registers" :key="register.id" class="even:bg-gray-100 dark:even:bg-gray-800">
                        <td class="px-4 py-2">
                            <input
                                v-model="register.name"
                                class="w-full rounded border px-2 py-1 text-sm dark:bg-gray-800 dark:text-white"
                                @blur="updateRegisterName(register)"
                            />
                        </td>
                        <td class="px-4 py-2 font-mono text-sm text-gray-700 dark:text-gray-200">{{ register.token }}</td>
                        <td class="px-4 py-2">
                            <a
                                :href="`/cash-registers/${register.id}/${register.token}`"
                                class="text-sm text-blue-600 hover:underline dark:text-blue-400"
                                target="_blank"
                            >
                                Open Public Page
                            </a>
                        </td>
                        <td class="px-4 py-2">
                            <button class="rounded bg-yellow-500 px-3 py-1 text-sm text-white hover:bg-yellow-600" @click="resetToken(register)">
                                Reset Token
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>

<style scoped>
input:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
}
</style>
