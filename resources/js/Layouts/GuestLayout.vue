<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const appName = computed(() => usePage().props.app.name);
</script>

<template>
    <main class="grid min-h-dvh bg-white lg:grid-cols-2 dark:bg-gray-950">
        <section class="relative hidden flex-col justify-between overflow-hidden bg-indigo-600 p-10 lg:flex">
            <a href="/" class="relative z-10 text-lg font-semibold tracking-tight text-white">
                {{ appName }}
            </a>

            <div class="relative z-10">
                <h2 class="text-3xl leading-tight font-semibold tracking-tight text-white">
                    Every task, every project, one calm workspace.
                </h2>
                <p class="mt-4 max-w-md text-base leading-relaxed text-indigo-200">
                    Plan work on boards, lists and calendars. Follow what changes through
                    mentions and activity. Free and open source — self-host it anywhere.
                </p>
            </div>

            <p class="relative z-10 text-sm text-indigo-200">
                Self-hosted, MIT licensed, yours.
            </p>

            <div class="pointer-events-none absolute -top-24 -right-24 h-96 w-96 rounded-full bg-indigo-500/50 blur-3xl" />
            <div class="pointer-events-none absolute -bottom-32 -left-16 h-96 w-96 rounded-full bg-indigo-400/40 blur-3xl" />
        </section>

        <section class="flex flex-col items-center justify-center px-6 py-12">
            <a href="/" class="mb-8 text-lg font-semibold tracking-tight text-gray-900 lg:hidden dark:text-gray-100">
                {{ appName }}
            </a>

            <div class="w-full max-w-md">
                <slot />
            </div>
        </section>
    </main>
</template>
