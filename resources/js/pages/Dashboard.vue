<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';

type Task = {
    id: string;
    register: string;
    type: 'overflow' | 'change_request';
    details: string;
    status: 'pending' | 'in_progress' | 'done';
};
type ApiTask = {
    id: string;
    status: 'pending' | 'in_progress' | 'done';
    type: 'overflow' | 'change_request';
    notes: string | null;
    cash_register: { id: string; name: string; token: string };
    change_request_items: Array<{ denomination: number; quantity: number }>;
    created_at: string;
};
type CountResponse = { total: number; pending: number; in_progress: number; done: number };
type ApiResponse = {
    data: ApiTask[];
    links: { first: string | null; prev: string | null; next: string | null; last: string | null };
    meta: { next_cursor: string | null; prev_cursor: string | null; per_page: number };
};

const breadcrumbs = ref<BreadcrumbItem[]>([{ title: 'Dashboard', href: '/dashboard' }]);
const tasks = ref<Task[]>([]);
const counts = ref<CountResponse>({ total: 0, pending: 0, in_progress: 0, done: 0 });
const loading = ref<boolean>(false);
const nextCursor = ref<string | null>(null);
const prevCursor = ref<string | null>(null);
const statusFilter = ref<string | null>(null);

const formatDetails = (task: ApiTask): string =>
    task.type === 'change_request' ? task.change_request_items.map((i) => `${i.denomination}€ × ${i.quantity}`).join(', ') : (task.notes ?? '');

const fetchCounts = async (): Promise<void> => {
    try {
        const response = await axios.get<CountResponse>('/api/work-orders/count');
        counts.value = response.data;
    } catch (error) {
        console.error('Error loading counts:', error);
    }
};

const fetchTasks = async (cursor: string | null = null): Promise<void> => {
    loading.value = true;
    try {
        const params: Record<string, any> = {};
        if (statusFilter.value) params.status = statusFilter.value;
        if (cursor) params.cursor = cursor;
        const response = await axios.get<ApiResponse>('/api/work-orders', { params });
        const { data, meta } = response.data;
        tasks.value = data.map((item) => ({
            id: item.id,
            register: item.cash_register.name,
            registerId: item.cash_register.id,
            registerToken: item.cash_register.token,
            type: item.type,
            details: formatDetails(item),
            status: item.status,
        }));
        nextCursor.value = meta.next_cursor;
        prevCursor.value = meta.prev_cursor;
    } catch (error) {
        console.error('Error loading tasks:', error);
    } finally {
        loading.value = false;
    }
};

const updateStatus = async (task: Task, newStatus: Task['status']): Promise<void> => {
    try {
        await axios.put(`/api/work-orders/${task.id}`, { status: newStatus });
        task.status = newStatus;
        await fetchCounts();
    } catch (error) {
        console.error('Error updating status:', error);
    }
};

const onStatusChange = (task: Task, value: Task['status']) => {
    const backward =
        (task.status === 'done' && (value === 'in_progress' || value === 'pending')) || (task.status === 'in_progress' && value === 'pending');
    if (backward && !confirm('Are you sure you want to reset the status?')) {
        fetchTasks(prevCursor.value);
        return;
    }
    updateStatus(task, value);
};

onMounted(async () => {
    await fetchCounts();
    await fetchTasks();
});
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-4">
            <div class="mb-4 flex items-center">
                <h1 class="text-2xl font-semibold">Dashboard</h1>
            </div>

            <!-- Filter -->
            <div class="mb-4 flex items-center gap-2">
                <label class="font-medium">Status Filter:</label>
                <select v-model="statusFilter" class="rounded border px-2 py-1 text-sm">
                    <option value="">All</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="done">Done</option>
                </select>
                <button class="ml-2 rounded bg-green-500 px-3 py-1 text-white hover:bg-green-600" @click="() => fetchTasks()">Apply</button>
            </div>

            <!-- Summary Cards Mobile Friendly -->
            <div class="mb-4 overflow-x-auto">
                <div class="flex gap-4 md:grid md:grid-cols-4">
                    <div class="min-w-[8rem] flex-shrink-0 rounded-lg border p-3 text-center shadow-sm">
                        <h3 class="text-sm font-medium">Total</h3>
                        <p class="text-2xl">{{ counts.total }}</p>
                    </div>
                    <div class="min-w-[8rem] flex-shrink-0 rounded-lg border p-3 text-center shadow-sm">
                        <h3 class="text-sm font-medium">Pending</h3>
                        <p class="text-2xl">{{ counts.pending }}</p>
                    </div>
                    <div class="min-w-[8rem] flex-shrink-0 rounded-lg border p-3 text-center shadow-sm">
                        <h3 class="text-sm font-medium">In Progress</h3>
                        <p class="text-2xl">{{ counts.in_progress }}</p>
                    </div>
                    <div class="min-w-[8rem] flex-shrink-0 rounded-lg border p-3 text-center shadow-sm">
                        <h3 class="text-sm font-medium">Done</h3>
                        <p class="text-2xl">{{ counts.done }}</p>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto rounded-lg shadow">
                <table class="min-w-full divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Register</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-300">Details</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900">
                        <tr
                            v-for="task in tasks"
                            :key="task.id"
                            :class="[
                                'odd:bg-white even:bg-gray-50 dark:odd:bg-gray-900 dark:even:bg-gray-800',
                                task.status === 'pending' ? 'bg-yellow-50 dark:bg-yellow-900 dark:text-yellow-100' : '',
                                task.status === 'in_progress' ? 'bg-blue-50 dark:bg-blue-900 dark:text-blue-100' : '',
                            ]"
                        >
                            <td class="px-6 py-4 whitespace-nowrap">
                                <select
                                    v-model="task.status"
                                    :class="[
                                        'rounded border px-2 py-1 text-sm',
                                        task.status === 'pending' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-100' : '',
                                        task.status === 'in_progress' ? 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100' : '',
                                        task.status === 'done' ? 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100' : '',
                                    ]"
                                    @change="onStatusChange(task, $event.target.value)"
                                >
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="done">Done</option>
                                </select>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap dark:text-gray-200">
                                <a
                                    :href="'/cash-registers/' + task.registerId + '/' + task.registerToken"
                                    class="text-blue-600 hover:underline"
                                    target="_blank"
                                >
                                    {{ task.register }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap capitalize dark:text-gray-200">
                                {{ task.type === 'change_request' ? 'Change Request' : 'Overflow' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap dark:text-gray-200">{{ task.details }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 flex items-center justify-between">
                <button
                    :disabled="!prevCursor || loading"
                    class="rounded border px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700"
                    @click="fetchTasks(prevCursor)"
                >
                    Previous
                </button>
                <button
                    :disabled="!nextCursor || loading"
                    class="rounded border px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700"
                    @click="fetchTasks(nextCursor)"
                >
                    Next
                </button>
            </div>
        </div>
    </AppLayout>
</template>
