import axios from 'axios';
import type { LoginRequest, LoginResponse, ChangePasswordRequest, User } from '../types/auth';
import { useFCM } from '../composables/useFCM';

const TOKEN_KEY = 'auth_token';
const USER_KEY = 'auth_user';

// Token helpers
export function getToken(): string | null {
    return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token: string): void {
    localStorage.setItem(TOKEN_KEY, token);
}

export function removeToken(): void {
    localStorage.removeItem(TOKEN_KEY);
}

// User helpers
export function getUser(): User | null {
    const raw = localStorage.getItem(USER_KEY);
    if (!raw) return null;
    try {
        return JSON.parse(raw) as User;
    } catch {
        return null;
    }
}

export function setUser(user: User): void {
    localStorage.setItem(USER_KEY, JSON.stringify(user));
}

export function removeUser(): void {
    localStorage.removeItem(USER_KEY);
}

// --- JWT Silent Refresh State ---
let isRefreshing = false;
let failedQueue: Array<{ resolve: (value: unknown) => void; reject: (reason?: unknown) => void }> = [];

function processQueue(error: unknown, token: string | null): void {
    failedQueue.forEach(({ resolve, reject }) => {
        if (error) {
            reject(error);
        } else {
            resolve(token);
        }
    });
    failedQueue = [];
}

/** Decode JWT payload and return the `exp` field (Unix seconds), or null if invalid. */
function getTokenExpiry(token: string): number | null {
    try {
        const parts = token.split('.');
        if (parts.length !== 3) return null;
        const payload = JSON.parse(atob(parts[1].replace(/-/g, '+').replace(/_/g, '/')));
        return typeof payload.exp === 'number' ? payload.exp : null;
    } catch {
        return null;
    }
}

// Configure axios to always send the token, and proactively refresh if < 2 minutes remain (Task 5.1)
axios.interceptors.request.use(async (config) => {
    const token = getToken();
    if (token) {
        const exp = getTokenExpiry(token);
        const secondsRemaining = exp !== null ? exp - Date.now() / 1000 : null;

        // Proactive silent refresh: if token expires in less than 2 minutes
        if (secondsRemaining !== null && secondsRemaining < 120) {
            // Only refresh if not already refreshing and not targeting the refresh endpoint itself
            const isRefreshEndpoint = config.url?.includes('/auth/refresh');
            if (!isRefreshing && !isRefreshEndpoint) {
                isRefreshing = true;
                try {
                    const { data } = await axios.post('/api/auth/refresh');
                    const newToken: string = data.data.token;
                    setToken(newToken);
                    processQueue(null, newToken);
                    config.headers = config.headers ?? {};
                    config.headers['Authorization'] = `Bearer ${newToken}`;
                    return config;
                } catch (refreshError) {
                    processQueue(refreshError, null);
                    removeToken();
                    removeUser();
                    window.location.href = '/login';
                    return Promise.reject(refreshError);
                } finally {
                    isRefreshing = false;
                }
            }
        }

        config.headers = config.headers ?? {};
        config.headers['Authorization'] = `Bearer ${token}`;
    }
    return config;
});

// Handle 401 responses with isRefreshing + failedQueue pattern (Task 5.2)
axios.interceptors.response.use(
    (response) => response,
    async (error) => {
        const originalRequest = error.config;
        const is401 = error?.response?.status === 401;
        const isLogout = originalRequest?.url?.includes('/auth/logout');
        const isRefreshEndpoint = originalRequest?.url?.includes('/auth/refresh');

        if (is401 && !isLogout && !isRefreshEndpoint && !originalRequest._retry) {
            if (isRefreshing) {
                // Queue this request until the ongoing refresh completes
                return new Promise((resolve, reject) => {
                    failedQueue.push({ resolve, reject });
                }).then((token) => {
                    originalRequest.headers = originalRequest.headers ?? {};
                    originalRequest.headers['Authorization'] = `Bearer ${token}`;
                    return axios(originalRequest);
                }).catch((err) => Promise.reject(err));
            }

            originalRequest._retry = true;
            isRefreshing = true;

            try {
                const { data } = await axios.post('/api/auth/refresh');
                const newToken: string = data.data.token;
                setToken(newToken);
                processQueue(null, newToken);
                originalRequest.headers = originalRequest.headers ?? {};
                originalRequest.headers['Authorization'] = `Bearer ${newToken}`;
                return axios(originalRequest);
            } catch (refreshError) {
                processQueue(refreshError, null);
                removeToken();
                removeUser();
                window.location.href = '/login';
                return Promise.reject(refreshError);
            } finally {
                isRefreshing = false;
            }
        }

        return Promise.reject(error);
    }
);

// Auth API calls
export async function login(credentials: LoginRequest): Promise<LoginResponse> {
    const response = await axios.post<LoginResponse>('/api/auth/login', credentials);
    const { token, user } = response.data.data;
    setToken(token);
    setUser(user);
    return response.data;
}

export async function logout(): Promise<void> {
    // Unregister FCM device token before clearing session
    try {
        const { unregisterDevice } = useFCM();
        await unregisterDevice();
    } catch {
        // Ignore errors — proceed with logout regardless
    }
    // Clear local state immediately — don't wait for server
    // (token may already be expired, server 401 is fine)
    removeToken();
    removeUser();
    try {
        await axios.post('/api/auth/logout');
    } catch {
        // Ignore errors — local session is already cleared
    }
}

export async function changePassword(data: ChangePasswordRequest): Promise<void> {
    await axios.post('/api/auth/change-password', data);
    // Refresh user info after password change
    const meResponse = await axios.get<{ success: boolean; data: User }>('/api/auth/me');
    setUser(meResponse.data.data);
}

export async function getMe(): Promise<User> {
    const response = await axios.get<{ success: boolean; data: User }>('/api/auth/me');
    setUser(response.data.data);
    return response.data.data;
}
