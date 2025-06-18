<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { onMounted, ref } from 'vue';

type ApiActivity = {
    description: string;
    status: {
        before: string | null;
        after: string;
    };
    caused_by: { id: number; name: string } | null;
    created_at: string;
};

type ApiTask = {
    id: string;
    status: 'pending' | 'in_progress' | 'done';
    type: 'overflow' | 'change_request';
    notes: string | null;
    cash_register: { id: string; name: string; token: string };
    change_request_items: Array<{ denomination: number; quantity: number }>;
    activities: ApiActivity[];
    created_at: string;
};

type Task = {
    id: string;
    register: string;
    registerId: string;
    registerToken: string;
    type: 'overflow' | 'change_request';
    details: string;
    status: 'pending' | 'in_progress' | 'done';
    activities: ApiActivity[];
};

type CountResponse = { total: number; pending: number; in_progress: number; done: number };
type ApiResponse = {
    data: ApiTask[];
    links: { next: string | null; prev: string | null };
    meta: { next_cursor: string | null; prev_cursor: string | null; per_page: number };
};

const breadcrumbs = ref<BreadcrumbItem[]>([{ title: 'Dashboard', href: '/dashboard' }]);
const tasks = ref<Task[]>([]);
const counts = ref<CountResponse>({ total: 0, pending: 0, in_progress: 0, done: 0 });
const loading = ref(false);
const nextCursor = ref<string | null>(null);
const prevCursor = ref<string | null>(null);
const statusFilter = ref<string>('pending,in_progress'); // Default filter to pending and in_progress

const statuses: Array<{ value: Task['status']; label: string; color: string }> = [
    { value: 'pending', label: 'Pending', color: 'yellow-500' },
    { value: 'in_progress', label: 'In Progress', color: 'blue-500' },
    { value: 'done', label: 'Done', color: 'green-500' },
];

const formatDetails = (task: ApiTask): string => {
    if (task.type === 'change_request') {
        return task.change_request_items.map((i) => `${i.quantity} x ${(i.denomination / 100).toFixed(2).replace('.', ',')} €`).join('\n');
    }
    return task.notes ?? '';
};
const fetchCounts = async () => {
    try {
        const response = await axios.get<CountResponse>('/api/work-orders/count');
        counts.value = response.data;
    } catch (error) {
        console.error('Error loading counts:', error);
    }
};

const fetchTasks = async (cursor: string | null = null) => {
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
            activities: item.activities.filter((a) => a && Object.keys(a).length > 0),
        }));
        nextCursor.value = meta.next_cursor;
        prevCursor.value = meta.prev_cursor;
    } catch (error) {
        console.error('Error loading tasks:', error);
    } finally {
        loading.value = false;
    }
};

const updateStatus = async (task: Task, newStatus: Task['status']) => {
    try {
        await axios.put(`/api/work-orders/${task.id}`, { status: newStatus });
        task.status = newStatus;
        await fetchCounts();

        if (newStatus === 'done') {
            tasks.value = tasks.value.filter((t) => t.id !== task.id);
        }
    } catch (error) {
        console.error('Error updating status:', error);
    }
};

const onStatusChange = (task: Task, newStatus: Task['status']) => {
    const backward = (task.status === 'done' && newStatus !== 'done') || (task.status === 'in_progress' && newStatus === 'pending');
    if (backward && !confirm('Are you sure you want to reset the status?')) return;
    updateStatus(task, newStatus);
};

const loadAll = async () => {
    statusFilter.value = '';
    await fetchTasks();
};

const timeAgo = (isoDate: string): string => {
    const now = new Date();
    const date = new Date(isoDate);
    const diffMs = now.getTime() - date.getTime();

    const seconds = Math.floor(diffMs / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`;
    if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    return `${seconds} second${seconds > 1 ? 's' : ''} ago`;
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
            <div class="flex flex-wrap gap-2">
                <div class="min-w-[6rem] flex-1 rounded border p-2 text-center">
                    <div class="text-sm">Pending</div>
                    <div class="text-xl">{{ counts.pending }}</div>
                </div>
                <div class="min-w-[6rem] flex-1 rounded border p-2 text-center">
                    <div class="text-sm">In Progress</div>
                    <div class="text-xl">{{ counts.in_progress }}</div>
                </div>
                <div class="min-w-[6rem] flex-1 rounded border p-2 text-center">
                    <div class="text-sm">Done</div>
                    <div class="text-xl">{{ counts.done }}</div>
                </div>
            </div>

            <button class="rounded bg-gray-500 px-3 py-1 text-white" @click="loadAll">Load All Tasks</button>

            <div v-if="!loading && tasks.length === 0" class="mt-6 text-center text-2xl text-green-600">🎉 No open tasks 🎉</div>

            <div class="grid gap-4 md:grid-cols-2">
                <div v-for="task in tasks" :key="task.id" class="rounded border p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="font-semibold">{{ task.register }}</div>
                    <div class="capitalize">{{ task.type === 'change_request' ? 'Change Request' : 'Overflow' }}</div>
                    <div class="mt-1 text-sm">
                        <pre class="whitespace-pre-wrap">{{ task.details }}</pre>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-for="s in statuses"
                            :key="s.value"
                            :class="task.status === s.value ? `bg-${s.color}` : 'bg-gray-500'"
                            class="flex-1 rounded px-3 py-2 text-white"
                            @click="onStatusChange(task, s.value)"
                        >
                            {{ s.label }}
                        </button>
                    </div>

                    <div class="mt-3 text-sm text-gray-500">
                        <ul class="ml-2 list-disc">
                            <li v-for="(activity, idx) in task.activities" :key="idx">
                                <small>
                                    <span v-if="activity.description === 'created'"> created </span>
                                    <span v-else-if="activity.caused_by">{{ activity.caused_by.name }}&nbsp;</span>
                                    <span>{{ timeAgo(activity.created_at) }}</span>
                                    <span
                                        v-if="
                                            activity.status && activity.status.before !== activity.status.after && activity.description !== 'created'
                                        "
                                    >
                                        ({{ activity.status.before ?? '' }} → {{ activity.status.after }})
                                    </span>
                                </small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="mt-4 flex justify-between">
                <button
                    v-if="prevCursor"
                    :disabled="!prevCursor || loading"
                    class="rounded border px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700"
                    @click="fetchTasks(prevCursor)"
                >
                    Previous
                </button>
                <button
                    v-if="nextCursor"
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
