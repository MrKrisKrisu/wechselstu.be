<script lang="ts" setup>
import type {HTMLAttributes} from 'vue'
import {cn} from '@/lib/utils'
import {ChevronRight} from 'lucide-vue-next'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <li
      :class="cn('[&>svg]:size-3.5', props.class)"
      aria-hidden="true"
      data-slot="breadcrumb-separator"
      role="presentation"
  >
    <slot>
      <ChevronRight/>
    </slot>
  </li>
</template>
