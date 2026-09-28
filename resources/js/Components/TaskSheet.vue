<script setup>
import { computed, ref, watch } from 'vue';
import { usePage, router, useForm } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    sheetTask: { type: Object, default: null },
});

const emit = defineEmits(['closed']);

const page = usePage();
const orgSlug = computed(() => page.props.organization.slug);
const project = computed(() => page.props.project);
const members = computed(() => page.props.members ?? []);
const canManage = computed(() => page.props.can_manage ?? false);

const priorities = ['none', 'low', 'medium', 'high', 'urgent'];

const task = computed(() => props.sheetTask);

const detail = useForm({
    title: task.value?.title ?? '',
    description: task.value?.description ?? '',
    priority: task.value?.priority ?? 'none',
    assignee_id: task.value?.assignee?.id ?? '',
    start_on: task.value?.start_on ?? '',
    due_on: task.value?.due_on ?? '',
    column_id: task.value?.column?.id ?? 0,
});

watch(task, (value) => {
    if (value) {
        detail.title = value.title;
        detail.description = value.description ?? '';
        detail.priority = value.priority;
        detail.assignee_id = value.assignee?.id ?? '';
        detail.start_on = value.start_on ?? '';
        detail.due_on = value.due_on ?? '';
        detail.column_id = value.column?.id ?? 0;
    }
});

const subtasks = computed(() => task.value?.subtasks ?? []);
const labels = computed(() => task.value?.labels ?? []);
const comments = ref([]);
watch(
    task,
    (value) => {
        comments.value = value ? [...(value.comments ?? [])] : [];
    },
    { immediate: true },
);

const newSubtask = ref('');
const addingSubtask = ref(false);
const showLabelPicker = ref(false);
const showNewLabel = ref(false);

const newLabel = useForm({ name: '', color: '#4F46E5' });
const palette = [
    '#4F46E5',
    '#0EA5E9',
    '#10B981',
    '#F59E0B',
    '#EF4444',
    '#EC4899',
    '#8B5CF6',
    '#64748B',
];
const commentBody = ref('');
const postingComment = ref(false);

/**
 * Split a comment body into plain text and mention chips. Mention markup is
 * authored as @[Full Name](user:1) — only the stored mention ids get chips.
 */
function commentSegments(body) {
    const segments = [];
    const pattern = /@\[([^\]]+)\]\(user:(\d+)\)/g;
    let cursor = 0;
    let match;

    while ((match = pattern.exec(body)) !== null) {
        if (match.index > cursor) {
            segments.push({ type: 'text', value: body.slice(cursor, match.index) });
        }

        segments.push({ type: 'mention', value: match[1] });
        cursor = match.index + match[0].length;
    }

    if (cursor < body.length) {
        segments.push({ type: 'text', value: body.slice(cursor) });
    }

    return segments;
}

function saveDetail() {
    router.patch(
        route('projects.tasks.update', {
            organization: orgSlug.value,
            project: project.value.id,
            task: task.value.id,
        }),
        {
            column_id: detail.column_id,
            title: detail.title,
            description: detail.description,
            priority: detail.priority,
            assignee_id: detail.assignee_id === '' ? null : detail.assignee_id,
            start_on: detail.start_on === '' ? null : detail.start_on,
            due_on: detail.due_on === '' ? null : detail.due_on,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                task.value.title = detail.title;
                task.value.description = detail.description;
                task.value.priority = detail.priority;
            },
        },
    );
}

function addSubtask() {
    const title = newSubtask.value.trim();

    if (title === '') {
        return;
    }

    router.post(
        route('projects.tasks.subtasks.store', {
            organization: orgSlug.value,
            project: project.value.id,
            task: task.value.id,
        }),
        { title },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                subtasks.value.push({ id: Date.now(), title, completed: false });
                newSubtask.value = '';
            },
        },
    );
}

function toggleSubtask(subtask) {
    subtask.completed = !subtask.completed;

    router.patch(
        route('projects.tasks.subtasks.update', {
            organization: orgSlug.value,
            project: project.value.id,
            task: task.value.id,
            subtask: subtask.id,
        }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onError: () => {
                subtask.completed = !subtask.completed;
            },
        },
    );
}

function removeSubtask(subtask) {
    router.delete(
        route('projects.tasks.subtasks.destroy', {
            organization: orgSlug.value,
            project: project.value.id,
            task: task.value.id,
            subtask: subtask.id,
        }),
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                subtasks.value = subtasks.value.filter((item) => item.id !== subtask.id);
            },
        },
    );
}

function attachLabel(labelId) {
    router.post(
        route('projects.tasks.labels.attach', {
            organization: orgSlug.value,
            project: project.value.id,
            task: task.value.id,
        }),
        { label_id: labelId },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                showLabelPicker.value = false;
            },
        },
    );
}

function createLabel() {
    newLabel.post(
        route('projects.tasks.labels.create', {
            organization: orgSlug.value,
            project: project.value.id,
            task: task.value.id,
        }),
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                showNewLabel.value = false;
                newLabel.reset();
                showLabelPicker.value = false;
            },
        },
    );
}

function detachLabel(label) {
    router.delete(
        route('projects.tasks.labels.detach', {
            organization: orgSlug.value,
            project: project.value.id,
            task: task.value.id,
            label: label.id,
        }),
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                labels.value = labels.value.filter((item) => item.id !== label.id);
            },
        },
    );
}

function addMention(member) {
    commentBody.value += `@[${member.name}](user:${member.id})`;
}

function postComment() {
    const body = commentBody.value.trim();

    if (body === '') {
        return;
    }

    postingComment.value = true;

    router.post(
        route('projects.tasks.comments.store', {
            organization: orgSlug.value,
            project: project.value.id,
            task: task.value.id,
        }),
        { body },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                comments.value.push({
                    id: Date.now(),
                    body,
                    author: { name: page.props.auth.user.name },
                    created_at: new Date().toISOString(),
                });
                commentBody.value = '';
                postingComment.value = false;
            },
            onError: () => {
                postingComment.value = false;
            },
        },
    );
}

function close() {
    router.get(
        route('projects.show', {
            organization: orgSlug.value,
            project: project.value.id,
            _query: { task: null },
        }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );

    emit('closed');
}
</script>

<template>
    <div
        v-if="task"
        class="fixed inset-0 z-[55]"
        role="dialog"
        aria-modal="true"
        aria-label="Task details"
    >
        <div class="absolute inset-0 bg-gray-900/30" @click="close" />

        <aside
            class="absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-2xl sm:inset-y-2 sm:right-2 sm:rounded-xl dark:bg-gray-900"
        >
            <header
                class="flex items-start justify-between gap-2 border-b border-gray-100 p-4 dark:border-gray-800"
            >
                <input
                    v-model="detail.title"
                    type="text"
                    class="w-full rounded-md border-0 bg-transparent text-base font-semibold text-gray-900 hover:bg-gray-50 focus:bg-gray-50 focus:ring-0 dark:text-gray-100 dark:hover:bg-gray-800"
                    aria-label="Task title"
                />
                <button
                    type="button"
                    class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                    aria-label="Close task details"
                    @click="close"
                >
                    <AppIcon name="close" />
                </button>
            </header>

            <div class="flex-1 space-y-5 overflow-y-auto p-4">
                <section class="grid grid-cols-2 gap-3">
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-500 dark:text-gray-400"
                            for="sheet-column"
                            >Column</label
                        >
                        <select
                            id="sheet-column"
                            v-model="detail.column_id"
                            class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                            <option
                                v-for="column in page.props.columns ?? []"
                                :key="column.id"
                                :value="column.id"
                            >
                                {{ column.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-500 dark:text-gray-400"
                            for="sheet-priority"
                            >Priority</label
                        >
                        <select
                            id="sheet-priority"
                            v-model="detail.priority"
                            class="mt-1 w-full rounded-lg border-gray-300 text-sm capitalize dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                            <option
                                v-for="priority in priorities"
                                :key="priority"
                                :value="priority"
                            >
                                {{ priority }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="block text-xs font-medium text-gray-500 dark:text-gray-400"
                            for="sheet-assignee"
                            >Assignee</label
                        >
                        <select
                            id="sheet-assignee"
                            v-model="detail.assignee_id"
                            class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                            <option value="">Unassigned</option>
                            <option v-for="member in members" :key="member.id" :value="member.id">
                                {{ member.name }}
                            </option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-500 dark:text-gray-400"
                                for="sheet-start"
                                >Start</label
                            >
                            <input
                                id="sheet-start"
                                v-model="detail.start_on"
                                type="date"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-medium text-gray-500 dark:text-gray-400"
                                for="sheet-due"
                                >Due</label
                            >
                            <input
                                id="sheet-due"
                                v-model="detail.due_on"
                                type="date"
                                class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            />
                        </div>
                    </div>
                </section>

                <p
                    v-if="detail.recentlySuccessful"
                    class="text-xs text-emerald-600 dark:text-emerald-400"
                >
                    Details saved.
                </p>
                <InputError :message="detail.errors.due_on" class="-mt-3" />

                <button
                    type="button"
                    class="w-full rounded-lg bg-indigo-600 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    @click="saveDetail"
                >
                    Save details
                </button>

                <section>
                    <h3
                        class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                    >
                        Labels
                    </h3>
                    <div class="mt-2 flex flex-wrap items-center gap-1.5">
                        <span
                            v-for="label in labels"
                            :key="label.id"
                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                            :style="{ backgroundColor: label.color + '1A', color: label.color }"
                        >
                            {{ label.name }}
                            <button
                                v-if="task.can_manage"
                                type="button"
                                :aria-label="`Remove label ${label.name}`"
                                @click="detachLabel(label)"
                            >
                                <AppIcon name="close" class="h-3 w-3" />
                            </button>
                        </span>
                        <button
                            v-if="canManage"
                            type="button"
                            class="rounded-full border border-dashed border-gray-300 px-2 py-0.5 text-xs text-gray-500 hover:border-indigo-400 hover:text-indigo-600 dark:border-gray-700 dark:text-gray-400"
                            @click="showLabelPicker = !showLabelPicker"
                        >
                            + Label
                        </button>
                    </div>
                    <div
                        v-if="showLabelPicker"
                        class="mt-2 space-y-2 rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                    >
                        <div class="flex flex-wrap gap-1.5">
                            <button
                                v-for="label in (page.props.org_labels ?? []).filter(
                                    (l) => !labels.some((t) => t.id === l.id),
                                )"
                                :key="label.id"
                                type="button"
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :style="{ backgroundColor: label.color + '1A', color: label.color }"
                                @click="attachLabel(label.id)"
                            >
                                {{ label.name }}
                            </button>
                        </div>
                        <button
                            v-if="canManage"
                            type="button"
                            class="text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400"
                            @click="showNewLabel = true"
                        >
                            New label
                        </button>
                    </div>
                </section>

                <section>
                    <div class="flex items-center justify-between">
                        <h3
                            class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                        >
                            Subtasks
                        </h3>
                        <span class="text-xs text-gray-400 dark:text-gray-500">
                            {{ subtasks.filter((s) => s.completed).length }}/{{ subtasks.length }}
                        </span>
                    </div>

                    <div class="mt-2 space-y-1">
                        <p
                            v-if="subtasks.length === 0"
                            class="text-xs text-gray-400 dark:text-gray-500"
                        >
                            No subtasks yet.
                        </p>
                        <div
                            v-for="subtask in subtasks"
                            :key="subtask.id"
                            class="group flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800"
                        >
                            <button
                                type="button"
                                class="inline-flex h-4 w-4 items-center justify-center rounded border"
                                :class="
                                    subtask.completed
                                        ? 'border-emerald-500 bg-emerald-500 text-white'
                                        : 'border-gray-300 dark:border-gray-600'
                                "
                                :aria-label="
                                    subtask.completed
                                        ? 'Mark subtask incomplete'
                                        : 'Mark subtask complete'
                                "
                                @click="toggleSubtask(subtask)"
                            >
                                <AppIcon
                                    v-if="subtask.completed"
                                    name="check-square"
                                    class="h-3 w-3"
                                />
                            </button>
                            <span
                                class="flex-1 text-sm"
                                :class="
                                    subtask.completed
                                        ? 'text-gray-400 line-through dark:text-gray-500'
                                        : 'text-gray-800 dark:text-gray-200'
                                "
                            >
                                {{ subtask.title }}
                            </span>
                            <button
                                v-if="task.can_manage"
                                type="button"
                                class="text-gray-300 opacity-0 transition-opacity group-hover:opacity-100 hover:text-red-500 dark:text-gray-600"
                                :aria-label="`Delete subtask ${subtask.title}`"
                                @click="removeSubtask(subtask)"
                            >
                                <AppIcon name="close" class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>

                    <form class="mt-2 flex gap-2" @submit.prevent="addSubtask">
                        <input
                            v-model="newSubtask"
                            type="text"
                            placeholder="Add a subtask"
                            class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            aria-label="New subtask"
                        />
                        <button
                            type="submit"
                            :disabled="addingSubtask"
                            class="rounded-lg bg-gray-100 px-3 text-sm font-medium text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                        >
                            Add
                        </button>
                    </form>
                </section>

                <section>
                    <h3
                        class="text-xs font-semibold tracking-wide text-gray-500 uppercase dark:text-gray-400"
                    >
                        Comments
                    </h3>

                    <div class="mt-2 space-y-3">
                        <p
                            v-if="comments.length === 0"
                            class="text-xs text-gray-400 dark:text-gray-500"
                        >
                            No comments yet.
                        </p>
                        <div
                            v-for="comment in comments"
                            :key="comment.id"
                            class="rounded-lg bg-gray-50 p-3 dark:bg-gray-800"
                        >
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                {{ comment.author.name }}
                            </p>
                            <p
                                class="mt-1 text-sm whitespace-pre-wrap text-gray-800 dark:text-gray-200"
                            >
                                <template
                                    v-for="(segment, index) in commentSegments(comment.body)"
                                    :key="index"
                                    ><span
                                        v-if="segment.type === 'mention'"
                                        class="rounded bg-indigo-50 px-1 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                                        >@{{ segment.value }}</span
                                    ><template v-else>{{ segment.value }}</template></template
                                >
                            </p>
                        </div>
                    </div>

                    <form class="mt-3" @submit.prevent="postComment">
                        <textarea
                            v-model="commentBody"
                            rows="3"
                            placeholder="Write a comment. Type @ to mention."
                            class="w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            aria-label="Write a comment"
                        />
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <button
                                v-for="member in members.slice(0, 6)"
                                :key="member.id"
                                type="button"
                                class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 hover:bg-indigo-50 hover:text-indigo-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-indigo-950"
                                @click="addMention(member)"
                            >
                                @{{ member.name.split(' ')[0] }}
                            </button>
                        </div>
                        <button
                            type="submit"
                            :disabled="postingComment || commentBody.trim() === ''"
                            class="mt-2 rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                        >
                            Comment
                        </button>
                    </form>
                </section>
            </div>
        </aside>
    </div>

    <Modal :open="showNewLabel" title="New label" @closed="showNewLabel = false">
        <form class="space-y-4" @submit.prevent="createLabel">
            <div>
                <label
                    class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                    for="label-name"
                    >Name</label
                >
                <input
                    id="label-name"
                    v-model="newLabel.name"
                    type="text"
                    class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                    required
                />
            </div>
            <div>
                <span class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                    >Color</span
                >
                <div class="mt-2 flex flex-wrap gap-2">
                    <button
                        v-for="color in palette"
                        :key="color"
                        type="button"
                        class="h-7 w-7 rounded-full ring-offset-2 dark:ring-offset-gray-900"
                        :class="
                            newLabel.color === color
                                ? 'ring-2 ring-gray-900 dark:ring-gray-100'
                                : ''
                        "
                        :style="{ backgroundColor: color }"
                        :aria-label="`Use color ${color}`"
                        @click="newLabel.color = color"
                    />
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                    @click="showNewLabel = false"
                >
                    Cancel
                </button>
                <button
                    type="submit"
                    class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                >
                    Create label
                </button>
            </div>
        </form>
    </Modal>
</template>
