<script lang="ts" setup>
import {DialogTrigger, type DialogTriggerProps} from 'reka-ui'

const props = defineProps<DialogTriggerProps>()
</script>

<template>
  <DialogTrigger
      data-slot="sheet-trigger"
      v-bind="props"
  >
    <slot/>
  </DialogTrigger>
</template>
