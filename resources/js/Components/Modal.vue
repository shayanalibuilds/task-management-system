<script setup>
defineProps({
    open: { type: Boolean, required: true },
    title: { type: String, required: true },
    wide: { type: Boolean, default: false },
});

const emit = defineEmits(['closed']);
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-[60] overflow-y-auto" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-gray-900/50" @click="emit('closed')" />

        <div class="flex min-h-full items-start justify-center p-4">
            <div
                class="relative mt-10 w-full rounded-xl bg-white p-6 shadow-xl dark:bg-gray-900"
                :class="wide ? 'max-w-2xl' : 'max-w-md'"
            >
                <div class="flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-gray-100">
                        {{ title }}
                    </h2>
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                        aria-label="Close dialog"
                        @click="emit('closed')"
                    >
                        <svg
                            class="h-4 w-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mt-4">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
