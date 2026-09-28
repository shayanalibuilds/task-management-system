<script setup>
import { computed, ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';

const page = usePage();
const orgSlug = computed(() => page.props.organization.slug);
const notifications = computed(() => page.props.notifications ?? []);
const unreadCount = computed(() => page.props.unread_count ?? 0);

const typeMeta = {
    mention: { label: 'Mention', classes: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' },
    assignment: { label: 'Assignment', classes: 'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-400' },
    comment: { label: 'Comment', classes: 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-400' },
    due_reminder: { label: 'Reminder', classes: 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-400' },
};

const clearing = ref(false);

function markAllRead() {
    clearing.value = true;

    router.post(route('inbox.mark_all', { organization: orgSlug.value }), {}, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            clearing.value = false;
        },
    });
}

function taskHref(notification) {
    if (notification.task === null) {
        return '#';
    }

    return route('projects.show', {
        organization: orgSlug.value,
        project: notification.task.project_id,
        _query: { task: notification.task.id },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ label: 'Inbox' }]">
        <div class="mx-auto max-w-3xl space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                        Inbox
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ unreadCount }} unread {{ unreadCount === 1 ? 'notification' : 'notifications' }}.
                    </p>
                </div>
                <button
                    v-if="unreadCount > 0"
                    type="button"
                    class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                    :disabled="clearing"
                    @click="markAllRead"
                >
                    Mark all as read
                </button>
            </div>

            <p
                v-if="notifications.length === 0"
                class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400"
            >
                Your inbox is empty. Mentions, assignments, comments and due reminders land here.
            </p>

            <section
                v-else
                class="divide-y divide-gray-100 overflow-hidden rounded-xl border border-gray-200 bg-white dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900"
            >
                <component
                    :is="notification.task ? 'a' : 'div'"
                    v-for="notification in notifications"
                    :key="notification.id"
                    :href="notification.task ? taskHref(notification) : undefined"
                    class="flex items-start gap-3 px-4 py-3"
                    :class="notification.task ? 'hover:bg-gray-50 dark:hover:bg-gray-800' : ''"
                >
                    <span
                        class="mt-1.5 h-2 w-2 shrink-0 rounded-full"
                        :class="notification.read_at ? 'bg-transparent' : 'bg-indigo-600'"
                        aria-hidden="true"
                    />
                    <div class="min-w-0 flex-1">
                        <p class="text-sm" :class="notification.read_at ? 'text-gray-600 dark:text-gray-400' : 'font-medium text-gray-900 dark:text-gray-100'">
                            {{ notification.message }}
                        </p>
                        <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">
                            {{ new Date(notification.created_at).toLocaleString() }}
                        </p>
                    </div>
                    <span
                        class="rounded px-1.5 py-0.5 text-[11px] font-medium"
                        :class="typeMeta[notification.type]?.classes ?? ''"
                    >
                        {{ typeMeta[notification.type]?.label ?? notification.type }}
                    </span>
                </component>
            </section>
        </div>
    </AppLayout>
</template>
