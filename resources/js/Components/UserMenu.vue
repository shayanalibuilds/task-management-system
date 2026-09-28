<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Dropdown from '@/Components/Dropdown.vue';

const props = defineProps({
    collapsed: { type: Boolean, default: false },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const initials = computed(() => {
    const parts = (user.value?.name ?? '').trim().split(/\s+/);

    return parts
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
});
</script>

<template>
    <Dropdown width="60">
        <template #trigger>
            <button
                type="button"
                class="flex w-full items-center gap-2 rounded-lg px-2 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-800"
            >
                <span
                    class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                >
                    {{ initials }}
                </span>
                <span v-if="!collapsed" class="min-w-0 flex-1">
                    <span
                        class="block truncate text-sm font-medium text-gray-900 dark:text-gray-100"
                    >
                        {{ user?.name }}
                    </span>
                    <span class="block truncate text-xs text-gray-500 dark:text-gray-400">
                        {{ user?.email }}
                    </span>
                </span>
            </button>
        </template>

        <template #content>
            <Link
                :href="route('profile.edit')"
                class="block px-3 py-2 text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800"
            >
                Account settings
            </Link>

            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-800"
            >
                Log out
            </Link>
        </template>
    </Dropdown>
</template>
