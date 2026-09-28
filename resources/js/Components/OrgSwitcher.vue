<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';

const props = defineProps({
    collapsed: { type: Boolean, default: false },
});

const page = usePage();
const organization = computed(() => page.props.organization);
const organizations = computed(() => page.props.auth?.organizations ?? []);
</script>

<template>
    <Dropdown v-if="organization" width="60">
        <template #trigger>
            <button
                type="button"
                class="flex w-full items-center gap-2 rounded-lg border border-gray-200 px-3 py-2 text-left text-sm font-medium text-gray-900 hover:bg-gray-50 dark:border-gray-800 dark:text-gray-100 dark:hover:bg-gray-800"
            >
                <span
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-indigo-600 text-xs font-semibold text-white"
                >
                    {{ organization.name.charAt(0) }}
                </span>
                <span v-if="!collapsed" class="min-w-0 flex-1 truncate">
                    {{ organization.name }}
                </span>
                <svg
                    v-if="!collapsed"
                    class="h-4 w-4 shrink-0 text-gray-400"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="m7 15 5 5 5-5M7 9l5-5 5 5" />
                </svg>
            </button>
        </template>

        <template #content>
            <p class="px-3 pb-1 pt-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                Workspaces
            </p>
            <Link
                v-for="item in organizations"
                :key="item.id"
                :href="route('org.dashboard', { organization: item.slug })"
                class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800"
            >
                <span
                    class="flex h-5 w-5 items-center justify-center rounded bg-gray-100 text-[10px] font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200"
                >
                    {{ item.name.charAt(0) }}
                </span>
                <span class="truncate">{{ item.name }}</span>
                <svg
                    v-if="item.id === organization.id"
                    class="ml-auto h-4 w-4 text-indigo-600 dark:text-indigo-400"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <path d="M20 6 9 17l-5-5" />
                </svg>
            </Link>
        </template>
    </Dropdown>
</template>
