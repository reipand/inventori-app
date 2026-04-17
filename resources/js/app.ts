import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router';
import '../css/app.css';
import { getToken, getUser } from '@/services/authService';

// Lazy-loaded page components
const LoginPage = () => import('@/pages/LoginPage.vue');
const ChangePasswordPage = () => import('@/pages/ChangePasswordPage.vue');
const DashboardPage = () => import('@/pages/DashboardPage.vue');
const CategoryPage = () => import('@/pages/CategoryPage.vue');
const ProductListPage = () => import('@/pages/ProductListPage.vue');
const ProductFormPage = () => import('@/pages/ProductFormPage.vue');
const TransactionPage = () => import('@/pages/TransactionPage.vue');
const NotificationPage = () => import('@/pages/NotificationPage.vue');
const LowStockPage = () => import('@/pages/LowStockPage.vue');
const ReportPage = () => import('@/pages/ReportPage.vue');
const AuditTrailPage = () => import('@/pages/AuditTrailPage.vue');
const UserManagementPage = () => import('@/pages/UserManagementPage.vue');

const routes: RouteRecordRaw[] = [
    // Public routes
    { path: '/login', component: LoginPage, meta: { public: true } },

    // Auth-only: change password (no layout)
    { path: '/change-password', component: ChangePasswordPage, meta: { requiresAuth: true } },

    // Protected routes — pengelola + kasir
    {
        path: '/products',
        component: () => import('@/components/Layout.vue'),
        meta: { requiresAuth: true, roles: ['pengelola', 'kasir'] },
        children: [{ path: '', component: ProductListPage }],
    },
    {
        path: '/transactions',
        component: () => import('@/components/Layout.vue'),
        meta: { requiresAuth: true, roles: ['pengelola', 'kasir'] },
        children: [{ path: '', component: TransactionPage }],
    },
    {
        path: '/notifications',
        component: () => import('@/components/Layout.vue'),
        meta: { requiresAuth: true, roles: ['pengelola', 'kasir'] },
        children: [{ path: '', component: NotificationPage }],
    },
    // Redirects from old routes
    { path: '/transactions/in', redirect: '/transactions?tab=in' },
    { path: '/transactions/out', redirect: '/transactions?tab=out' },

    // Protected routes — pengelola only
    {
        path: '/dashboard',
        component: () => import('@/components/Layout.vue'),
        meta: { requiresAuth: true, roles: ['pengelola'] },
        children: [{ path: '', component: DashboardPage }],
    },
    {
        path: '/categories',
        component: () => import('@/components/Layout.vue'),
        meta: { requiresAuth: true, roles: ['pengelola'] },
        children: [{ path: '', component: CategoryPage }],
    },
    {
        path: '/products/new',
        component: () => import('@/components/Layout.vue'),
        meta: { requiresAuth: true, roles: ['pengelola'] },
        children: [{ path: '', component: ProductFormPage }],
    },
    {
        path: '/products/:id/edit',
        component: () => import('@/components/Layout.vue'),
        meta: { requiresAuth: true, roles: ['pengelola'] },
        children: [{ path: '', component: ProductFormPage }],
    },
    {
        path: '/low-stock',
        component: () => import('@/components/Layout.vue'),
        meta: { requiresAuth: true, roles: ['pengelola'] },
        children: [{ path: '', component: LowStockPage }],
    },
    {
        path: '/reports',
        component: () => import('@/components/Layout.vue'),
        meta: { requiresAuth: true, roles: ['pengelola'] },
        children: [{ path: '', component: ReportPage }],
    },
    {
        path: '/audit-trail',
        component: () => import('@/components/Layout.vue'),
        meta: { requiresAuth: true, roles: ['pengelola'] },
        children: [{ path: '', component: AuditTrailPage }],
    },
    {
        path: '/users',
        component: () => import('@/components/Layout.vue'),
        meta: { requiresAuth: true, roles: ['pengelola'] },
        children: [{ path: '', component: UserManagementPage }],
    },

    // Default redirect
    { path: '/', redirect: () => {
        const user = getUser();
        return user?.role === 'kasir' ? '/products' : '/dashboard';
    }},

    // Catch-all
    { path: '/:pathMatch(.*)*', redirect: '/login' },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Navigation guard
router.beforeEach((to) => {
    const token = getToken();
    const user = getUser();

    // Redirect to login if not authenticated
    if (to.meta.requiresAuth && !token) {
        return { path: '/login' };
    }

    // Redirect to change-password if must_change_password
    if (token && user?.must_change_password && to.path !== '/change-password') {
        return { path: '/change-password' };
    }

    // Role-based access control
    if (to.meta.roles && user) {
        const allowedRoles = to.meta.roles as string[];
        if (!allowedRoles.includes(user.role)) {
            // Redirect kasir to their default page
            return user.role === 'kasir' ? { path: '/products' } : { path: '/dashboard' };
        }
    }

    // Redirect authenticated users away from login
    if (to.path === '/login' && token) {
        return user?.role === 'kasir' ? { path: '/products' } : { path: '/dashboard' };
    }
});

// Root Vue component
import App from '@/App.vue';

const app = createApp(App);
app.use(createPinia());
app.use(router);
app.mount('#app');
