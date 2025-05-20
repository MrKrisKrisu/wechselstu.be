<script lang="ts" setup>
import {cn} from '@/lib/utils'
import {NavigationMenuList, type NavigationMenuListProps, useForwardProps} from 'reka-ui'
import {computed, type HTMLAttributes} from 'vue'

const props = defineProps<NavigationMenuListProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = computed(() => {
  const {class: _, ...delegated} = props

  return delegated
})

const forwardedProps = useForwardProps(delegatedProps)
</script>

<template>
  <NavigationMenuList
      :class="
      cn(
        'group flex flex-1 list-none items-center justify-center gap-1',
        props.class,
      )
    "
      data-slot="navigation-menu-list"
      v-bind="forwardedProps"
  >
    <slot/>
  </NavigationMenuList>
</template>
