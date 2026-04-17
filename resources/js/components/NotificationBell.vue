<template>
  <button
    type="button"
    class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 bg-white transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
    aria-label="Notifikasi"
    @click="$emit('toggle')"
  >
    <Bell class="h-5 w-5 text-gray-600" />

    <!-- Badge: angka 1–9 -->
    <span
      v-if="unreadCount > 0 && unreadCount <= 9"
      :key="unreadCount"
      class="badge absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[11px] font-bold leading-none text-white badge-bounce"
    >
      {{ unreadCount }}
    </span>

    <!-- Badge: "9+" jika > 9 -->
    <span
      v-else-if="unreadCount > 9"
      :key="'overflow'"
      class="badge absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-[11px] font-bold leading-none text-white badge-bounce"
    >
      9+
    </span>
  </button>
</template>

<script setup lang="ts">
import { watch, ref } from 'vue'
import { Bell } from 'lucide-vue-next'
import { useNotificationStore } from '@/stores/notification'

defineEmits<{
  toggle: []
}>()

const notifStore = useNotificationStore()
const { unreadCount } = notifStore

// Track previous unreadCount to detect increases
const prevCount = ref(unreadCount.value ?? 0)

watch(
  () => notifStore.unreadCount,
  (newVal, oldVal) => {
    prevCount.value = oldVal ?? 0
  }
)
</script>

<style scoped>
@keyframes badge-bounce {
  0%, 100% { transform: scale(1); }
  50%       { transform: scale(1.3); }
}

.badge-bounce {
  animation: badge-bounce 0.4s ease;
}
</style>
