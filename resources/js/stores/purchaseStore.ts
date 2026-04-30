import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { Invoice, InvoicePayload, InvoiceListParams } from '@/services/invoiceService';
import * as invoiceService from '@/services/invoiceService';

export interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

export const usePurchaseStore = defineStore('purchase', () => {
    // State
    const invoices = ref<Invoice[]>([]);
    const currentInvoice = ref<Invoice | null>(null);
    const loading = ref<boolean>(false);
    const error = ref<string | null>(null);
    const pagination = ref<PaginationMeta | null>(null);

    // Actions
    async function fetchInvoices(params?: InvoiceListParams): Promise<void> {
        loading.value = true;
        error.value = null;
        try {
            const result = await invoiceService.getInvoices(params);
            invoices.value = result.data;
            pagination.value = {
                current_page: result.current_page,
                last_page: result.last_page,
                per_page: result.per_page,
                total: result.total,
            };
        } catch (err) {
            console.error('Failed to fetch invoices:', err);
            error.value = 'Gagal memuat daftar invoice';
        } finally {
            loading.value = false;
        }
    }

    async function createInvoice(payload: InvoicePayload): Promise<Invoice> {
        loading.value = true;
        error.value = null;
        try {
            const result = await invoiceService.createInvoice(payload);
            return result;
        } catch (err) {
            console.error('Failed to create invoice:', err);
            error.value = 'Gagal menyimpan invoice';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchInvoice(id: string): Promise<void> {
        loading.value = true;
        error.value = null;
        try {
            const result = await invoiceService.getInvoice(id);
            currentInvoice.value = result;
        } catch (err) {
            console.error('Failed to fetch invoice:', err);
            error.value = 'Gagal memuat detail invoice';
        } finally {
            loading.value = false;
        }
    }

    async function deleteInvoice(id: string): Promise<void> {
        loading.value = true;
        error.value = null;
        try {
            await invoiceService.deleteInvoice(id);
            invoices.value = invoices.value.filter((inv) => inv.id !== id);
            if (currentInvoice.value?.id === id) {
                currentInvoice.value = null;
            }
        } catch (err) {
            console.error('Failed to delete invoice:', err);
            error.value = 'Gagal menghapus invoice';
            throw err;
        } finally {
            loading.value = false;
        }
    }

    return {
        // State
        invoices,
        currentInvoice,
        loading,
        error,
        pagination,
        // Actions
        fetchInvoices,
        createInvoice,
        fetchInvoice,
        deleteInvoice,
    };
});
