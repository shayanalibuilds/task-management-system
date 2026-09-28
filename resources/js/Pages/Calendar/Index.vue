<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';

const page = usePage();
const weeks = computed(() => page.props.weeks ?? []);
const monthLabel = computed(() => page.props.month_label);
const prevMonth = computed(() => page.props.prev_month);
const nextMonth = computed(() => page.props.next_month);
const orgSlug = computed(() => page.props.organization.slug);

const dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
</script>

<template>
    <AppLayout :breadcrumbs="[{ label: 'Calendar' }]">
        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100"
                    >
                        Calendar
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Due dates across {{ page.props.organization.name }}.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link
                        :href="route('calendar.show', { organization: orgSlug, month: prevMonth })"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                        aria-label="Previous month"
                    >
                        <AppIcon name="chevron-left" />
                    </Link>
                    <span
                        class="min-w-32 text-center text-sm font-medium text-gray-900 dark:text-gray-100"
                    >
                        {{ monthLabel }}
                    </span>
                    <Link
                        :href="route('calendar.show', { organization: orgSlug, month: nextMonth })"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-800"
                        aria-label="Next month"
                    >
                        <AppIcon name="chevron-right" />
                    </Link>
                </div>
            </div>

            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="grid grid-cols-7 border-b border-gray-100 dark:border-gray-800">
                    <div
                        v-for="name in dayNames"
                        :key="name"
                        class="px-2 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400"
                    >
                        {{ name }}
                    </div>
                </div>

                <div class="grid grid-cols-7">
                    <template v-for="(week, weekIndex) in weeks" :key="weekIndex">
                        <div
                            v-for="day in week"
                            :key="day.date"
                            class="min-h-24 border-r border-b border-gray-100 p-1.5 last:border-r-0 dark:border-gray-800"
                            :class="day.in_month ? '' : 'bg-gray-50/60 dark:bg-gray-950/40'"
                        >
                            <div class="flex items-center justify-between px-1">
                                <span
                                    class="inline-flex h-5 min-w-5 items-center justify-center rounded-full px-1 text-[11px]"
                                    :class="
                                        day.is_today
                                            ? 'bg-indigo-600 font-semibold text-white'
                                            : 'text-gray-500 dark:text-gray-400'
                                    "
                                >
                                    {{ Number(day.date.slice(8, 10)) }}
                                </span>
                            </div>

                            <div class="mt-1 space-y-1">
                                <div
                                    v-for="task in day.tasks.slice(0, 3)"
                                    :key="task.id"
                                    class="truncate rounded px-1 py-0.5 text-[11px] font-medium"
                                    :class="
                                        task.column_category === 'done'
                                            ? 'bg-emerald-50 text-emerald-700 line-through dark:bg-emerald-950 dark:text-emerald-400'
                                            : 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/70 dark:text-indigo-300'
                                    "
                                    :title="`${task.project} — ${task.title}`"
                                >
                                    {{ task.title }}
                                </div>
                                <p
                                    v-if="day.tasks.length > 3"
                                    class="px-1 text-[10px] text-gray-400 dark:text-gray-500"
                                >
                                    +{{ day.tasks.length - 3 }} more
                                </p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
