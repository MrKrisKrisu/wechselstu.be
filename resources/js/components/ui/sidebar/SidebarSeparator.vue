<script lang="ts" setup>
import type {HTMLAttributes} from 'vue'
import {cn} from '@/lib/utils'
import {Separator} from '@/components/ui/separator'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <Separator
      :class="cn('bg-sidebar-border mx-2 w-auto', props.class)"
      data-sidebar="separator"
      data-slot="sidebar-separator"
  >
    <slot/>
  </Separator>
</template>
