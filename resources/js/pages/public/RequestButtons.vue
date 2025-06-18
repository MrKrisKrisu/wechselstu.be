<script lang="ts" setup>
const props = defineProps<{
    form: any;
    hasActiveOverflowRequest: boolean;
    hasActiveChangeRequest: boolean;
    submitting: boolean;
}>();
const emit = defineEmits(['submitOverflow', 'toggleChangeForm']);
</script>

<template>
    <template v-if="(form.needsChange || form.hasOverflow) && !submitting">
        <button
            class="w-full rounded bg-gray-500 px-4 py-2 font-bold text-white hover:bg-gray-700"
            @click="
                () => {
                    form.hasOverflow = false;
                    form.needsChange = false;
                }
            "
        >
            ← Go back
        </button>
    </template>

    <template v-else>
        <button
            :class="{
                'bg-blue-500 hover:bg-blue-700': !hasActiveOverflowRequest && !submitting,
                'bg-gray-500 hover:bg-gray-700': hasActiveOverflowRequest || submitting,
            }"
            :disabled="submitting || hasActiveOverflowRequest"
            class="mt-3 w-full rounded px-4 py-2 font-bold text-white"
            @click="() => emit('submitOverflow')"
        >
            💸 Cash overflow (needs removal)
            <span v-if="hasActiveOverflowRequest" class="ml-2 text-sm font-bold text-green-500"> <br />There is already an active request. </span>
        </button>

        <button
            :class="{
                'bg-blue-500 hover:bg-blue-700': !hasActiveChangeRequest,
                'bg-gray-500 hover:bg-gray-700': hasActiveChangeRequest,
            }"
            :disabled="submitting || hasActiveChangeRequest"
            class="mt-3 w-full rounded px-4 py-2 font-bold text-white"
            @click="() => emit('toggleChangeForm')"
        >
            🔁 Change needed
            <span v-if="hasActiveChangeRequest" class="ml-2 text-sm font-bold text-green-500"> <br />There is already an active request. </span>
        </button>

        <template v-if="submitting">
            <div class="my-4 text-center text-green-400">
                ⏳ Please wait...
                <svg class="mx-auto mt-2 h-6 w-6 animate-spin text-green-700" fill="none" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" fill="currentColor"></path>
                </svg>
            </div>
        </template>
    </template>
</template>
