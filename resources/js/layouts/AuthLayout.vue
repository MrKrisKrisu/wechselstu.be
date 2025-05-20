<script lang="ts" setup>
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';

defineProps<{
  title?: string;
  description?: string;
}>();
</script>

<template>
  <AuthLayout :description="description" :title="title">
    <slot/>
  </AuthLayout>
</template>
