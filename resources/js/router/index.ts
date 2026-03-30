import { createRouter, createWebHistory } from 'vue-router';
import type { RouteRecordRaw } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const financeRoutes: RouteRecordRaw[] = [
    {
        path: '/login',
        name: 'login',
        component: () => import('@/pages/finance/Login.vue'),
        meta: { requiresGuest: true },
    },
    {
        path: '/finance',
        component: () => import('@/layouts/FinanceLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'finance.dashboard',
                component: () => import('@/pages/finance/Dashboard.vue'),
            },
            {
                path: 'stations',
                name: 'finance.stations',
                component: () => import('@/pages/finance/stations/Index.vue'),
            },
            {
                path: 'stations/create',
                name: 'finance.stations.create',
                component: () => import('@/pages/finance/stations/Form.vue'),
            },
            {
                path: 'stations/:id/edit',
                name: 'finance.stations.edit',
                component: () => import('@/pages/finance/stations/Form.vue'),
            },
            {
                path: 'dashboard-access',
                name: 'finance.dashboard-access',
                component: () => import('@/pages/finance/DashboardAccess.vue'),
            },
        ],
    },
];

const publicRoutes: RouteRecordRaw[] = [
    {
        path: '/s/:token',
        name: 'station.form',
        component: () => import('@/pages/public/StationForm.vue'),
    },
    {
        path: '/t/:id',
        name: 'ticket.status',
        component: () => import('@/pages/public/TicketStatus.vue'),
    },
];

const monitorRoutes: RouteRecordRaw[] = [
    {
        path: '/monitor',
        name: 'monitor',
        component: () => import('@/pages/monitor/Dashboard.vue'),
    },
];

const globalRoutes: RouteRecordRaw[] = [
    {
        path: '/',
        redirect: '/login',
    },
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('@/pages/NotFound.vue'),
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes: [
        ...financeRoutes,
        ...publicRoutes,
        ...monitorRoutes,
        ...globalRoutes,
    ],
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    if (to.meta.requiresAuth) {
        if (!auth.user) {
            await auth.fetchUser();
        }

        if (!auth.user) {
            return { name: 'login' };
        }
    }

    if (to.meta.requiresGuest && auth.user) {
        return { name: 'finance.dashboard' };
    }

    return true;
});

export default router;
