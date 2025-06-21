<script lang="ts" setup>
import axios from 'axios';
import { onMounted, ref } from 'vue';

type ApiTask = {
    id: string;
    status: 'pending' | 'in_progress' | 'done';
    type: 'overflow' | 'change_request';
    notes: string | null;
    cash_register: {
        id: string;
        name: string;
    };
    change_request_items?: Array<{
        denomination: number;
        quantity: number;
    }>;
    created_at: string;
};

const tasks = ref<ApiTask[]>([]);
const loading = ref(false);
const token = ref<string | null>(null);

const playNotificationSound = () => {
    const ctx = new (window.AudioContext || (window as any).webkitAudioContext)();
    const oscillator = ctx.createOscillator();
    const gain = ctx.createGain();
    oscillator.type = 'square';
    oscillator.frequency.value = 1200;
    gain.gain.value = 0.2;
    oscillator.connect(gain);
    gain.connect(ctx.destination);
    oscillator.start();
    setTimeout(() => oscillator.stop(), 200);
    setTimeout(() => {
        const osc2 = ctx.createOscillator();
        osc2.type = 'square';
        osc2.frequency.value = 800;
        osc2.connect(gain);
        osc2.start();
        setTimeout(() => osc2.stop(), 200);
    }, 250);
};

const updateTasks = (fetched: ApiTask[]) => {
    let changed = false;
    const existingMap = new Map(tasks.value.map((t) => [t.id, t]));

    fetched.forEach((fetchedTask) => {
        const existing = existingMap.get(fetchedTask.id);
        if (!existing) {
            tasks.value.push(fetchedTask);
            changed = true;
        } else {
            if (
                existing.status !== fetchedTask.status ||
                existing.notes !== fetchedTask.notes ||
                JSON.stringify(existing.change_request_items) !== JSON.stringify(fetchedTask.change_request_items)
            ) {
                Object.assign(existing, fetchedTask);
                changed = true;
            }
        }
    });

    // Remove tasks no longer present
    const fetchedIds = fetched.map((t) => t.id);
    if (tasks.value.length !== fetched.length) {
        tasks.value = tasks.value.filter((t) => fetchedIds.includes(t.id));
        changed = true;
    }

    if (changed) {
        playNotificationSound();
    }
};

const fetchTasks = async () => {
    if (!token.value) return;
    loading.value = true;
    try {
        const response = await axios.get<{ data: ApiTask[] }>('/api/screen/work-orders', {
            params: { token: token.value },
        });
        updateTasks(response.data.data);
    } catch (err) {
        console.error('Error fetching tasks:', err);
    } finally {
        loading.value = false;
    }
};

const relativeTime = (dateStr: string): string => {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const seconds = Math.floor(diffMs / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (seconds < 60) return `${seconds} seconds ago`;
    if (minutes < 60) return `${minutes} minutes ago`;
    if (hours < 24) return `${hours} hours ago`;
    return `${days} days ago`;
};

const startPolling = () => {
    const loop = async () => {
        await fetchTasks();
        setTimeout(loop, 1000);
    };
    loop();
};

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    token.value = urlParams.get('token');
    if (!token.value) {
        alert('Token is missing in URL');
        return;
    }
    startPolling();
});
</script>

<template>
    <div class="p-4">
        <h1 class="mb-4 text-center text-2xl font-bold">Public Work Orders Dashboard</h1>

        <div v-if="!loading && tasks.length === 0" class="text-center text-3xl text-green-600">🎉 No open tasks 🎉</div>

        <div v-if="tasks.length > 0" class="grid gap-4 md:grid-cols-2">
            <div v-for="task in tasks" :key="task.id" class="rounded border border-gray-300 p-4 shadow dark:border-gray-700 dark:bg-gray-800">
                <div class="font-semibold">{{ task.cash_register.name }}</div>
                <div class="mb-1 capitalize">
                    {{ task.type === 'change_request' ? 'Change Request' : 'Overflow' }}
                </div>
                <div class="mt-1 text-sm whitespace-pre-wrap">
                    <template v-if="task.type === 'change_request'">
                        <div v-if="(task.change_request_items ?? []).length > 0">
                            <div v-for="item in task.change_request_items" :key="item.denomination">
                                {{ item.quantity }} × {{ (item.denomination / 100).toFixed(2).replace('.', ',') }} €
                            </div>
                        </div>
                        <div v-else>No details provided</div>
                    </template>
                    <template v-else>
                        {{ task.notes ?? 'No details' }}
                    </template>
                </div>
                <div class="mt-2 text-sm text-gray-500">Created: {{ relativeTime(task.created_at) }}</div>
                <div class="mt-2 text-sm">
                    Status:
                    <span
                        :class="{
                            'text-yellow-500': task.status === 'pending',
                            'text-blue-500': task.status === 'in_progress',
                            'text-green-500': task.status === 'done',
                        }"
                    >
                        {{ task.status.replace('_', ' ') }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
