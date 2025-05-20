<script lang="ts" setup>
import type {HTMLAttributes} from 'vue'
import {cn} from '@/lib/utils'
import {AvatarRoot} from 'reka-ui'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <AvatarRoot
      :class="cn('relative flex size-8 shrink-0 overflow-hidden rounded-full', props.class)"
      data-slot="avatar"
  >
    <slot/>
  </AvatarRoot>
</template>
