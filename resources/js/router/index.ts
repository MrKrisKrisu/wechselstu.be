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
            {
                path: 'hauptkasse',
                name: 'finance.hauptkasse',
                component: () => import('@/pages/finance/Hauptkasse.vue'),
            },
            {
                path: 'profile',
                name: 'finance.profile',
                component: () => import('@/pages/finance/Profile.vue'),
            },
            {
                path: 'users',
                name: 'finance.users',
                component: () => import('@/pages/finance/Users.vue'),
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
    {
        path: '/member/:token',
        name: 'member.profile',
        component: () => import('@/pages/public/MemberProfile.vue'),
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
        name: 'landing',
        component: () => import('@/pages/public/StationLanding.vue'),
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
    const memberDomain =
        window.__APP_CONFIG__?.memberDomain ?? 'member.localhost';

    if (
        window.location.hostname === memberDomain &&
        !to.path.startsWith('/member/') &&
        to.path !== '/'
    ) {
        const segment = to.path.replace(/^\//, '');

        if (segment && !segment.includes('/')) {
            return { name: 'member.profile', params: { token: segment } };
        }
    }

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
