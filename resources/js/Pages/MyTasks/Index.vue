<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const page = usePage();
const sections = computed(() => page.props.sections ?? []);
const orgSlug = computed(() => page.props.organization.slug);

const priorityStyles = {
    none: '',
    low: 'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-400',
    medium: 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
    high: 'bg-orange-50 text-orange-700 dark:bg-orange-950 dark:text-orange-400',
    urgent: 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-400',
};

const hasOverdue = sections.value.some((section) => section.key === 'overdue' && section.tasks.length > 0);
</script>

<template>
    <AppLayout :breadcrumbs="[{ label: 'My Tasks' }]">
        <div class="mx-auto max-w-4xl space-y-6">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                    My Tasks
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Everything assigned to you across {{ page.props.organization.name }}.
                </p>
            </div>

            <div v-if="sections.every((section) => section.tasks.length === 0)" class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400">
                Nothing assigned to you. Enjoy the calm.
            </div>

            <section
                v-for="section in sections.filter((s) => s.tasks.length > 0)"
                :key="section.key"
                class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"
            >
                <header class="flex items-center gap-2 border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                    <h2 class="text-sm font-semibold" :class="hasOverdue && section.key === 'overdue' ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-gray-100'">
                        {{ section.label }}
                    </h2>
                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ section.tasks.length }}</span>
                </header>

                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <a
                        v-for="task in section.tasks"
                        :key="task.id"
                        :href="route('projects.show', { organization: orgSlug, project: task.project_id })"
                        class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
                    >
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium text-gray-900 dark:text-gray-100">
                                {{ task.title }}
                            </p>
                            <p class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ task.project.name }} <span class="mx-1 text-gray-300 dark:text-gray-600">·</span> {{ task.column.name }}
                            </p>
                        </div>
                        <span
                            v-if="task.priority !== 'none'"
                            class="rounded px-1.5 py-0.5 text-[11px] font-medium capitalize"
                            :class="priorityStyles[task.priority]"
                        >
                            {{ task.priority }}
                        </span>
                        <span
                            v-if="task.due_on"
                            class="shrink-0 text-xs"
                            :class="section.key === 'overdue' ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'"
                        >
                            {{ task.due_on }}
                        </span>
                    </a>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
