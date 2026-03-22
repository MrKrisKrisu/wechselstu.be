import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from '@/lib/axios';
import type { User } from '@/types';

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null);
    const loading = ref(false);

    async function fetchUser(): Promise<void> {
        try {
            const { data } = await axios.get('/api/finance/user');
            user.value = data.user;
        } catch {
            user.value = null;
        }
    }

    async function logout(): Promise<void> {
        await axios.post('/api/finance/logout');
        user.value = null;
    }

    return { user, loading, fetchUser, logout };
});
