<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();
const organization = computed(() => page.props.organization);
const role = computed(() => organization.value?.role ?? 'member');
const memberCount = computed(() => organization.value?.member_count ?? 1);
</script>

<template>
    <AppLayout :breadcrumbs="[{ label: 'Dashboard' }]">
        <div class="mx-auto max-w-3xl space-y-6">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                    Dashboard
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    An overview of {{ organization.name }}.
                </p>
            </div>

            <section
                class="rounded-xl border border-gray-200 bg-white p-8 text-center dark:border-gray-800 dark:bg-gray-900"
            >
                <p
                    class="text-xs font-medium tracking-wide text-indigo-600 uppercase dark:text-indigo-400"
                >
                    Workspace
                </p>
                <h2
                    class="mt-2 text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100"
                >
                    {{ organization.name }}
                </h2>
                <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                    You are signed in as
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ role }}</span>
                    with {{ memberCount }} {{ memberCount === 1 ? 'member' : 'members' }}.
                </p>
                <p class="mt-6 text-sm text-gray-500 dark:text-gray-500">
                    Projects, boards and tasks arrive with the next updates.
                </p>
            </section>
        </div>
    </AppLayout>
</template>
