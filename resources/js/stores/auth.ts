import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { User, LoginRequest, ChangePasswordRequest } from '@/types/auth';
import * as authService from '@/services/authService';

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(authService.getUser());
    const token = ref<string | null>(authService.getToken());

    const isAuthenticated = computed(() => !!token.value);
    const isKasir = computed(() => user.value?.role === 'kasir');
    const isPengelola = computed(() => user.value?.role === 'pengelola');
    const mustChangePassword = computed(() => user.value?.must_change_password ?? false);

    async function login(credentials: LoginRequest) {
        const response = await authService.login(credentials);
        token.value = authService.getToken();
        user.value = response.data.user;
        return response;
    }

    async function logout() {
        await authService.logout();
        token.value = null;
        user.value = null;
    }

    async function changePassword(data: ChangePasswordRequest) {
        await authService.changePassword(data);
        // Refresh user — must_change_password should now be false
        user.value = authService.getUser();
    }

    function syncFromStorage() {
        token.value = authService.getToken();
        user.value = authService.getUser();
    }

    return {
        user,
        token,
        isAuthenticated,
        isKasir,
        isPengelola,
        mustChangePassword,
        login,
        logout,
        changePassword,
        syncFromStorage,
    };
});
