<script lang="ts" setup>
import type {HTMLAttributes} from 'vue'
import {cn} from '@/lib/utils'

const props = defineProps<{
  class?: HTMLAttributes['class']
}>()
</script>

<template>
  <li
      :class="cn('group/menu-sub-item relative', props.class)"
      data-sidebar="menu-sub-item"
      data-slot="sidebar-menu-sub-item"
  >
    <slot/>
  </li>
</template>
