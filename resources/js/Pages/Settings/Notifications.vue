<script setup>
import { computed } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';

const page = usePage();
const orgSlug = computed(() => page.props.organization.slug);
const prefs = computed(() => page.props.email_prefs ?? {
    mention: true,
    assignment: true,
    comment: true,
    due_reminder: true,
});

const rows = [
    { key: 'mention', label: 'Email me about mentions', description: 'Someone mentions you in a comment' },
    { key: 'assignment', label: 'Email me about assignments', description: 'A task gets assigned to you' },
    { key: 'comment', label: 'Email me about comments', description: 'Someone comments on a task assigned to you' },
    { key: 'due_reminder', label: 'Email me about due reminders', description: 'A task assigned to you is due today' },
];

function save(key, value) {
    router.patch(route('settings.notifications.update', { organization: orgSlug.value }), {
        email_mentions: key === 'mention' ? value : prefs.value.mention,
        email_assignments: key === 'assignment' ? value : prefs.value.assignment,
        email_comments: key === 'comment' ? value : prefs.value.comment,
        email_due_reminders: key === 'due_reminder' ? value : prefs.value.due_reminder,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ label: 'Settings' }, { label: 'Notifications' }]">
        <div class="mx-auto max-w-2xl space-y-6">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                    Notification settings
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Choose which events also send you email. Every event still lands in your
                    inbox — turning email off never deletes inbox rows.
                </p>
            </div>

            <section class="divide-y divide-gray-100 rounded-xl border border-gray-200 bg-white dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900">
                <div v-for="row in rows" :key="row.key" class="flex items-center justify-between gap-4 px-4 py-4">
                    <div>
                        <InputLabel :value="row.label" />
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                            {{ row.description }}
                        </p>
                    </div>
                    <button
                        type="button"
                        role="switch"
                        :aria-checked="prefs[row.key]"
                        class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors"
                        :class="prefs[row.key] ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700'"
                        @click="save(row.key, !prefs[row.key])"
                    >
                        <span
                            class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                            :class="prefs[row.key] ? 'translate-x-6' : 'translate-x-1'"
                        />
                    </button>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
