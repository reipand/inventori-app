<template>
  <Teleport to="body">
    <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 w-80 pointer-events-none">
      <TransitionGroup name="toast">
        <div
          v-for="toast in toasts"
          :key="toast.id"
          class="pointer-events-auto flex items-start gap-3 rounded-lg border px-4 py-3 shadow-lg text-sm font-medium"
          :class="variantClass(toast.type)"
        >
          <span class="mt-0.5 shrink-0 text-base">{{ icon(toast.type) }}</span>
          <span class="flex-1">{{ toast.message }}</span>
          <button
            class="shrink-0 opacity-60 hover:opacity-100 transition-opacity"
            @click="dismiss(toast.id)"
          >✕</button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { useToast } from '@/composables/useToast';

const { toasts, dismiss } = useToast();

function variantClass(type: string) {
  return {
    success: 'bg-green-50 border-green-200 text-green-800',
    error: 'bg-red-50 border-red-200 text-red-800',
    warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
    info: 'bg-blue-50 border-blue-200 text-blue-800',
  }[type] ?? 'bg-card border text-foreground';
}

function icon(type: string) {
  return { success: '✓', error: '✕', warning: '⚠', info: 'ℹ' }[type] ?? 'ℹ';
}
</script>

<style scoped>
.toast-enter-active { animation: toast-in 0.25s ease forwards; }
.toast-leave-active { animation: toast-out 0.2s ease forwards; position: absolute; width: 100%; }
.toast-move { transition: transform 0.25s ease; }
@keyframes toast-in {
  from { transform: translateX(110%); opacity: 0; }
  to   { transform: translateX(0);    opacity: 1; }
}
@keyframes toast-out {
  from { transform: translateX(0);    opacity: 1; }
  to   { transform: translateX(110%); opacity: 0; }
}
</style>
