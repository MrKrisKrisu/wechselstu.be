<script lang="ts" setup>
import {CollapsibleTrigger, type CollapsibleTriggerProps} from 'reka-ui'

const props = defineProps<CollapsibleTriggerProps>()
</script>

<template>
  <CollapsibleTrigger
      data-slot="collapsible-trigger"
      v-bind="props"
  >
    <slot/>
  </CollapsibleTrigger>
</template>
