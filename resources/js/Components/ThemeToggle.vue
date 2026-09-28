<script setup>
import { onMounted, ref } from 'vue';

const theme = ref('light');

onMounted(() => {
    theme.value = window.localStorage.getItem('theme') ?? 'light';
    apply();
});

function toggle() {
    theme.value = theme.value === 'dark' ? 'light' : 'dark';
    window.localStorage.setItem('theme', theme.value);
    apply();
}

function apply() {
    document.documentElement.classList.toggle('dark', theme.value === 'dark');
}
</script>

<template>
    <button
        type="button"
        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
        aria-label="Toggle dark mode"
        @click="toggle"
    >
        <svg v-if="theme === 'dark'" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="4" />
            <path d="M12 2v2m0 16v2M4.93 4.93l1.41 1.41m11.32 11.32 1.41 1.41M2 12h2m16 0h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
        </svg>
        <svg v-else class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
        </svg>
    </button>
</template>
