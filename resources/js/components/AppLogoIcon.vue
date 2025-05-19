<script lang="ts" setup>
import type {HTMLAttributes} from 'vue';

defineOptions({
  inheritAttrs: false,
});

interface Props {
  className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
  <!-- todo: logo just a quick draft -->
  <svg :class="className" v-bind="$attrs" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
    <!-- Cash register base -->
    <rect fill="currentColor" height="24" opacity="0.2" rx="4" width="40" x="4" y="16"/>
    <!-- Drawer slot -->
    <rect fill="currentColor" height="8" rx="2" width="32" x="8" y="28"/>
    <!-- Coin indicator circles -->
    <circle cx="14" cy="22" fill="currentColor" r="2"/>
    <circle cx="20" cy="22" fill="currentColor" r="2"/>
    <circle cx="26" cy="22" fill="currentColor" r="2"/>
    <circle cx="32" cy="22" fill="currentColor" r="2"/>
    <!-- Shield overlay -->
    <path d="M24 4l12 6v10c0 9-8 14-12 16-4-2-12-7-12-16V10l12-6z" fill="none" stroke="currentColor" stroke-width="2"/>
  </svg>
</template>
