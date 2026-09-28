<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';

const page = usePage();
const organization = computed(() => page.props.organization);
const orgSlug = computed(() => organization.value?.slug ?? '');
const stats = computed(() => page.props.stats ?? {});
const distribution = computed(() => page.props.distribution ?? { not_started: 0, in_flight: 0, done: 0 });
const today = computed(() => page.props.today ?? []);
const dueSoon = computed(() => page.props.due_soon ?? []);
const recentProjects = computed(() => page.props.recent_projects ?? []);

const distributionTotal = computed(() =>
    distribution.value.not_started + distribution.value.in_flight + distribution.value.done,
);

const distributionRows = computed(() => [
    { key: 'not_started', label: 'Not started', count: distribution.value.not_started, color: 'bg-gray-400' },
    { key: 'in_flight', label: 'In flight', count: distribution.value.in_flight, color: 'bg-indigo-500' },
    { key: 'done', label: 'Done', count: distribution.value.done, color: 'bg-emerald-500' },
]);

const statCards = computed(() => [
    { label: 'Open tasks', value: stats.value.open_tasks ?? 0, accent: 'text-gray-900 dark:text-gray-100' },
    { label: 'Due today', value: stats.value.due_today ?? 0, accent: 'text-indigo-600 dark:text-indigo-400' },
    { label: 'Overdue', value: stats.value.overdue ?? 0, accent: 'text-red-600 dark:text-red-400' },
    { label: 'Completed', value: stats.value.completed ?? 0, accent: 'text-emerald-600 dark:text-emerald-400' },
]);

const priorityStyles = {
    none: '',
    low: 'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-400',
    medium: 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
    high: 'bg-orange-50 text-orange-700 dark:bg-orange-950 dark:text-orange-400',
    urgent: 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-400',
};

function projectHref(project) {
    return route('projects.show', { organization: orgSlug.value, project: project.id });
}

function taskHref(task) {
    return route('projects.show', { organization: orgSlug.value, project: task.project_id, _query: { task: task.id } });
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ label: 'Dashboard' }]">
        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex items-end justify-between">
                <div>
                    <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                        Good to see you, {{ page.props.auth.user?.name?.split(' ')[0] }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Here is how {{ organization.name }} is doing.
                    </p>
                </div>
                <Link
                    :href="route('projects.index', { organization: orgSlug })"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                >
                    All projects
                </Link>
            </div>

            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <section
                    v-for="card in statCards"
                    :key="card.label"
                    class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
                >
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ card.label }}</p>
                    <p class="mt-1 text-2xl font-semibold tracking-tight" :class="card.accent">
                        {{ card.value }}
                    </p>
                </section>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <header class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Due today</h2>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ today.length }}</span>
                    </header>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        <p
                            v-if="today.length === 0"
                            class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400"
                        >
                            Nothing due today. Nice.
                        </p>
                        <Link
                            v-for="task in today"
                            :key="task.id"
                            :href="taskHref(task)"
                            class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-gray-900 dark:text-gray-100">{{ task.title }}</p>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ task.project }}</p>
                            </div>
                            <span
                                v-if="task.priority !== 'none'"
                                class="rounded px-1.5 py-0.5 text-[11px] font-medium capitalize"
                                :class="priorityStyles[task.priority]"
                            >
                                {{ task.priority }}
                            </span>
                        </Link>
                    </div>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                    <header class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                        <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Due soon</h2>
                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ dueSoon.length }}</span>
                    </header>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        <p
                            v-if="dueSoon.length === 0"
                            class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400"
                        >
                            Nothing due this week.
                        </p>
                        <Link
                            v-for="task in dueSoon"
                            :key="task.id"
                            :href="taskHref(task)"
                            class="flex items-center gap-3 px-4 py-3 text-sm hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-gray-900 dark:text-gray-100">{{ task.title }}</p>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ task.project }}</p>
                            </div>
                            <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">{{ task.due_on }}</span>
                        </Link>
                    </div>
                </section>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Task distribution</h2>

                    <div v-if="distributionTotal === 0" class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        No tasks yet — create a project to start planning.
                    </div>

                    <template v-else>
                        <div class="mt-4 flex h-2.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div
                                v-for="row in distributionRows"
                                :key="row.key"
                                class="h-full"
                                :class="row.color"
                                :style="{ width: `${(row.count / distributionTotal) * 100}%` }"
                            />
                        </div>
                        <div class="mt-3 space-y-2">
                            <div
                                v-for="row in distributionRows"
                                :key="row.key"
                                class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300"
                            >
                                <span class="h-2 w-2 rounded-full" :class="row.color" />
                                <span class="flex-1">{{ row.label }}</span>
                                <span class="font-medium text-gray-900 dark:text-gray-100">{{ row.count }}</span>
                            </div>
                        </div>
                    </template>
                </section>

                <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Recent projects</h2>
                    <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <Link
                            v-for="project in recentProjects"
                            :key="project.id"
                            :href="projectHref(project)"
                            class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                        >
                            <span
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                :style="{ backgroundColor: project.color + '1A', color: project.color }"
                            >
                                <AppIcon :name="project.icon" />
                            </span>
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-gray-900 dark:text-gray-100">{{ project.name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ project.open_tasks }} open {{ project.open_tasks === 1 ? 'task' : 'tasks' }}
                                </p>
                            </div>
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
