<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold">Transaksi</h1>

      <!-- Tab switcher: hidden for kasir -->
      <div v-if="!authStore.isKasir" class="flex rounded-lg border bg-muted p-1 gap-1">
        <button
          class="tab-btn"
          :class="{ 'tab-btn-active': activeTab === 'in' }"
          @click="activeTab = 'in'"
        >
          Transaksi Masuk
        </button>
        <button
          class="tab-btn"
          :class="{ 'tab-btn-active': activeTab === 'out' }"
          @click="activeTab = 'out'"
        >
          Transaksi Keluar
        </button>
      </div>
    </div>

    <TransactionInForm
      v-if="activeTab === 'in'"
      @success="onTransactionInSuccess"
    />
    <TransactionOutForm
      v-else
      @success="onTransactionOutSuccess"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import { useNotificationStore } from '@/stores/notification';
import TransactionInForm from '@/components/TransactionInForm.vue';
import TransactionOutForm from '@/components/TransactionOutForm.vue';

const route = useRoute();
const authStore = useAuthStore();
const notifStore = useNotificationStore();

function getInitialTab(): 'in' | 'out' {
  if (authStore.isKasir) return 'out';
  const tabParam = route.query.tab;
  if (tabParam === 'out') return 'out';
  return 'in';
}

const activeTab = ref<'in' | 'out'>(getInitialTab());

function onTransactionInSuccess(payload: { productName: string; quantity: number; currentStock: number }) {
  notifStore.addTransactionAlert('transaction_in', payload);
}

function onTransactionOutSuccess(payload: { productName: string; quantity: number; currentStock: number }) {
  notifStore.addTransactionAlert('transaction_out', payload);
}
</script>

<style scoped>
@reference "../../css/app.css";
.tab-btn {
  @apply px-4 py-1.5 rounded-md text-sm font-medium transition-colors text-muted-foreground hover:text-foreground;
}
.tab-btn-active {
  @apply bg-background text-foreground shadow-sm;
}
</style>
