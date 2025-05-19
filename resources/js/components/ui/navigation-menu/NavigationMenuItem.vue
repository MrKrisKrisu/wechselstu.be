<script lang="ts" setup>
import type {HTMLAttributes} from 'vue'
import {cn} from '@/lib/utils'
import {reactiveOmit} from '@vueuse/core'
import {NavigationMenuItem, type NavigationMenuItemProps} from 'reka-ui'

const props = defineProps<NavigationMenuItemProps & { class?: HTMLAttributes['class'] }>()

const delegatedProps = reactiveOmit(props, 'class')
</script>

<template>
  <NavigationMenuItem
      :class="cn('relative', props.class)"
      data-slot="navigation-menu-item"
      v-bind="delegatedProps"
  >
    <slot/>
  </NavigationMenuItem>
</template>
