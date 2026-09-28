<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';

const props = defineProps({
    href: { type: String, required: true },
    label: { type: String, required: true },
    icon: { type: String, required: true },
    badge: { type: Number, default: 0 },
    collapsed: { type: Boolean, default: false },
});

const page = usePage();
const active = computed(() => page.url === props.href || page.url.startsWith(props.href + '/'));
</script>

<template>
    <Link
        :href="href"
        class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
        :class="active
            ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300'
            : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-gray-100'"
        :title="collapsed ? label : undefined"
        :aria-label="collapsed ? label : undefined"
    >
        <AppIcon :name="icon" />
        <span v-if="!collapsed" class="flex-1 truncate">{{ label }}</span>
        <span
            v-if="!collapsed && badge > 0"
            class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1.5 text-xs font-semibold text-white"
        >
            {{ badge }}
        </span>
    </Link>
</template>
