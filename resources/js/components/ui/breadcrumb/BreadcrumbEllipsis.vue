<script lang="ts" setup>
import type {HTMLAttributes} from 'vue'
import {cn} from '@/lib/utils'
import {MoreHorizontal} from 'lucide-vue-next'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <span
      :class="cn('flex size-9 items-center justify-center', props.class)"
      aria-hidden="true"
      data-slot="breadcrumb-ellipsis"
      role="presentation"
  >
    <slot>
      <MoreHorizontal class="size-4"/>
    </slot>
    <span class="sr-only">More</span>
  </span>
</template>
