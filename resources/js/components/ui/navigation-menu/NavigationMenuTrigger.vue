<script lang="ts" setup>
import {cn} from '@/lib/utils'
import {ChevronDown} from 'lucide-vue-next'
import {NavigationMenuTrigger, type NavigationMenuTriggerProps, useForwardProps,} from 'reka-ui'
import {computed, type HTMLAttributes} from 'vue'
import {navigationMenuTriggerStyle} from '.'

const props = defineProps<NavigationMenuTriggerProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const {class: _, ...delegated} = props

  return delegated
})

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <NavigationMenuTrigger
      :class="cn(navigationMenuTriggerStyle(), 'group', props.class)"
      data-slot="navigation-menu-trigger"
      v-bind="forwardedProps"
  >
    <slot/>
    <ChevronDown
        aria-hidden="true"
        class="relative top-[1px] ml-1 size-3 transition duration-300 group-data-[state=open]:rotate-180"
    />
  </NavigationMenuTrigger>
</template>
