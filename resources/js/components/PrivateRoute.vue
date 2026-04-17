<template>
  <slot v-if="isAuthenticated" />
  <component :is="redirect" v-else />
</template>

<script setup lang="ts">
import { computed, h } from 'vue';
import { RouterView, useRouter } from 'vue-router';
import { getToken, getUser } from '@/services/authService';

const router = useRouter();
const token = getToken();
const user = getUser();
const currentPath = router.currentRoute.value.path;

const isAuthenticated = computed(() => !!token);

const redirect = computed(() => {
  if (!token) {
    router.replace({ path: '/login' });
    return null;
  }
  if (user?.must_change_password && currentPath !== '/change-password') {
    router.replace({ path: '/change-password' });
    return null;
  }
  return null;
});
</script>
