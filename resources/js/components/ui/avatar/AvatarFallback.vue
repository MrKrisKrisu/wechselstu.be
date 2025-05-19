<script lang="ts" setup>
import {cn} from '@/lib/utils'
import {AvatarFallback, type AvatarFallbackProps} from 'reka-ui'
import {computed, type HTMLAttributes} from 'vue'

const props = defineProps<AvatarFallbackProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const {class: _, ...delegated} = props

  return delegated
})
</script>

<template>
  <AvatarFallback
      :class="cn('bg-muted flex size-full items-center justify-center rounded-full', props.class)"
      data-slot="avatar-fallback"
      v-bind="delegatedProps"
  >
    <slot/>
  </AvatarFallback>
</template>
