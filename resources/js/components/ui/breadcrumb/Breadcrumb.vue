<script lang="ts" setup>
import type {HTMLAttributes} from 'vue'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <nav
      :class="props.class"
      aria-label="breadcrumb"
      data-slot="breadcrumb"
  >
    <slot/>
  </nav>
</template>
