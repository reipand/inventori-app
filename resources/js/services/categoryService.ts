import axios from 'axios';

export interface Category {
    id: string;
    name: string;
    description: string | null;
    created_at: string;
    updated_at: string;
}

export interface CategoryPayload {
    name: string;
    description?: string;
}

interface ApiResponse<T> {
    success: boolean;
    data: T;
}

export async function getCategories(): Promise<Category[]> {
    const response = await axios.get<ApiResponse<Category[]>>('/api/categories');
    return response.data.data;
}

export async function createCategory(payload: CategoryPayload): Promise<Category> {
    const response = await axios.post<ApiResponse<Category>>('/api/categories', payload);
    return response.data.data;
}

export async function updateCategory(id: string, payload: CategoryPayload): Promise<Category> {
    const response = await axios.put<ApiResponse<Category>>(`/api/categories/${id}`, payload);
    return response.data.data;
}

export async function deleteCategory(id: string): Promise<void> {
    await axios.delete(`/api/categories/${id}`);
}
