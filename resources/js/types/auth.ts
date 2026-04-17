export interface User {
    id: string;
    name: string;
    email: string;
    role: 'pengelola' | 'kasir';
    must_change_password: boolean;
}

export interface LoginRequest {
    email: string;
    password: string;
}

export interface LoginResponse {
    success: boolean;
    data: {
        token: string;
        user: User;
    };
}

export interface ChangePasswordRequest {
    current_password: string;
    new_password: string;
    new_password_confirmation: string;
}
