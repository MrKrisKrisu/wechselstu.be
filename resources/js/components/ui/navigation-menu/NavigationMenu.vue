<script lang="ts" setup>
import type {HTMLAttributes} from 'vue'
import {cn} from '@/lib/utils'
import {reactiveOmit} from '@vueuse/core'
import {
  NavigationMenuRoot,
  type NavigationMenuRootEmits,
  type NavigationMenuRootProps,
  useForwardPropsEmits,
} from 'reka-ui'
import NavigationMenuViewport from './NavigationMenuViewport.vue'

const props = withDefaults(defineProps<NavigationMenuRootProps & {
  class?: HTMLAttributes['class']
  viewport?: boolean
}>(), {
  viewport: true,
})
const emits = defineEmits<NavigationMenuRootEmits>()

const delegatedProps = reactiveOmit(props, 'class', 'viewport')
const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <NavigationMenuRoot
      :class="cn('group/navigation-menu relative flex max-w-max flex-1 items-center justify-center', props.class)"
      :data-viewport="viewport"
      data-slot="navigation-menu"
      v-bind="forwarded"
  >
    <slot/>
    <NavigationMenuViewport v-if="viewport"/>
  </NavigationMenuRoot>
</template>
