<script lang="ts" setup>
import {computed} from 'vue';

const patternId = computed(() => `pattern-${Math.random().toString(36).substring(2, 9)}`);
</script>

<template>
  <svg class="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" fill="none">
    <defs>
      <pattern :id="patternId" height="8" patternUnits="userSpaceOnUse" width="8" x="0" y="0">
        <path d="M-1 5L5 -1M3 9L8.5 3.5" stroke-width="0.5"></path>
      </pattern>
    </defs>
    <rect :fill="`url(#${patternId})`" height="100%" stroke="none" width="100%"></rect>
  </svg>
</template>
