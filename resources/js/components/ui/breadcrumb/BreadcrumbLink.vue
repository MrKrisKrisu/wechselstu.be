<script lang="ts" setup>
import type {HTMLAttributes} from 'vue'
import {cn} from '@/lib/utils'
import {Primitive, type PrimitiveProps} from 'reka-ui'

const props = withDefaults(defineProps<PrimitiveProps & { class?: HTMLAttributes['class'] }>(), {
  as: 'a',
})
</script>

<template>
  <Primitive
      :as="as"
      :as-child="asChild"
      :class="cn('hover:text-foreground transition-colors', props.class)"
      data-slot="breadcrumb-link"
  >
    <slot/>
  </Primitive>
</template>
