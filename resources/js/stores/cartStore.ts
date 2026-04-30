import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { CartItem } from '@/services/posService';

export const useCartStore = defineStore('cart', () => {
    // State
    const items = ref<CartItem[]>([]);
    const paymentMethod = ref<'cash' | 'qr'>('cash');
    const amountPaid = ref<number>(0);

    // Getters
    const subtotal = computed(() =>
        items.value.reduce((sum, item) => sum + item.sell_price * item.qty, 0)
    );

    const totalDiscount = computed(() =>
        items.value.reduce((sum, item) => sum + item.discount_per_item * item.qty, 0)
    );

    const total = computed(() => subtotal.value - totalDiscount.value);

    const change = computed(() =>
        Math.max(0, amountPaid.value - total.value)
    );

    const isValid = computed(() =>
        items.value.length > 0 &&
        (paymentMethod.value === 'qr' || amountPaid.value >= total.value)
    );

    // Actions
    function addItem(product: { id: string; name: string; sell_price: number; cogs: number }) {
        const existing = items.value.find((item) => item.product_id === product.id);
        if (existing) {
            existing.qty += 1;
        } else {
            items.value.push({
                product_id: product.id,
                product_name: product.name,
                qty: 1,
                sell_price: product.sell_price,
                cogs: product.cogs,
                discount_per_item: 0,
            });
        }
    }

    function removeItem(productId: string) {
        items.value = items.value.filter((item) => item.product_id !== productId);
    }

    function updateQty(productId: string, qty: number) {
        if (qty <= 0) {
            removeItem(productId);
            return;
        }
        const item = items.value.find((i) => i.product_id === productId);
        if (item) {
            item.qty = qty;
        }
    }

    function updateDiscount(productId: string, discount: number) {
        const item = items.value.find((i) => i.product_id === productId);
        if (item) {
            item.discount_per_item = discount;
        }
    }

    function clearCart() {
        items.value = [];
        paymentMethod.value = 'cash';
        amountPaid.value = 0;
    }

    return {
        // State
        items,
        paymentMethod,
        amountPaid,
        // Getters
        subtotal,
        totalDiscount,
        total,
        change,
        isValid,
        // Actions
        addItem,
        removeItem,
        updateQty,
        updateDiscount,
        clearCart,
    };
});
