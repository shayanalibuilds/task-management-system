<script setup>
import { computed, ref } from 'vue';
import { usePage, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import TaskSheet from '@/Components/TaskSheet.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const page = usePage();
const orgSlug = computed(() => page.props.organization.slug);
const project = computed(() => page.props.project);
const canManage = computed(() => page.props.can_manage ?? false);
const view = computed(() => page.props.view ?? 'board');
const sheetTask = computed(() => page.props.sheet?.task ?? null);
const orgLabels = computed(() => page.props.org_labels ?? []);

function hrefFor(nextView) {
    return route('projects.show', {
        organization: orgSlug.value,
        project: project.value.id,
        _query: { view: nextView },
    });
}

const columns = ref([...(page.props.columns ?? [])]);
const dragging = ref(null);

function syncColumns() {
    columns.value = [...(page.props.columns ?? [])];
}

const showingAddColumn = ref(false);
const columnForm = useForm({
    name: '',
    category: 'not_started',
});

const showingAddTask = ref(null);
const taskForm = useForm({
    title: '',
    column_id: 0,
});

const categoryStyles = {
    not_started: 'bg-gray-400',
    in_flight: 'bg-indigo-500',
    done: 'bg-emerald-500',
};

const priorityStyles = {
    none: '',
    low: 'bg-sky-50 text-sky-700 dark:bg-sky-950 dark:text-sky-400',
    medium: 'bg-amber-50 text-amber-700 dark:bg-amber-950 dark:text-amber-400',
    high: 'bg-orange-50 text-orange-700 dark:bg-orange-950 dark:text-orange-400',
    urgent: 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-400',
};

function startDrag(task, column) {
    dragging.value = { task, from: column };
}

function dropOnColumn(column, index) {
    const drag = dragging.value;
    dragging.value = null;

    if (!drag || drag.task.id === undefined) {
        return;
    }

    const source = columns.value.find((c) => c.id === drag.from.id);
    const taskIndex = source.tasks.findIndex((t) => t.id === drag.task.id);
    const [moved] = source.tasks.splice(taskIndex, 1);

    const target = columns.value.find((c) => c.id === column.id);
    const insertAt = index === undefined ? target.tasks.length : index;
    target.tasks.splice(insertAt, 0, moved);

    const neighbor = target.tasks[insertAt - 1] ?? null;
    const neighborPosition = neighbor?.position ?? null;

    if (neighbor && neighbor.id === moved.id) {
        target.tasks.splice(insertAt, 1);
        target.tasks.splice(insertAt, 0, moved);
    }

    router.patch(route('projects.tasks.update', {
        organization: orgSlug.value,
        project: project.value.id,
        task: moved.id,
    }), {
        column_id: target.id,
        title: moved.title,
        position_after: neighborPosition,
    }, {
        preserveScroll: true,
        preserveState: true,
        onError: () => {
            syncColumns();
        },
    });
}

function submitColumn() {
    columnForm.post(route('projects.columns.store', { organization: orgSlug.value, project: project.value.id }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            showingAddColumn.value = false;
            columnForm.reset();
        },
    });
}

function openAddTask(column) {
    showingAddTask.value = column.id;
    taskForm.column_id = column.id;
    taskForm.title = '';
}

function submitTask() {
    taskForm.post(route('projects.tasks.store', { organization: orgSlug.value, project: project.value.id }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            taskForm.title = '';
            showingAddTask.value = null;
        },
    });
}

function dropBefore(column, index) {
    dropOnColumn(column, index);
}

function dropAfterList(column) {
    dropOnColumn(column, undefined);
}

function openTask(task) {
    if (dragging.value !== null) {
        return;
    }

    router.get(route('projects.show', {
        organization: orgSlug.value,
        project: project.value.id,
        _query: { task: task.id },
    }), {}, {
        preserveScroll: true,
        preserveState: true,
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ label: 'Projects', href: route('projects.index', { organization: orgSlug }) }, { label: project.name }]">
        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-lg"
                        :style="{ backgroundColor: project.color + '1A', color: project.color }"
                    >
                        <AppIcon :name="project.icon" />
                    </span>
                    <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                        {{ project.name }}
                    </h1>
                    <span
                        v-if="project.visibility === 'restricted'"
                        class="rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-400"
                    >
                        Restricted
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <div class="mr-2 flex items-center rounded-lg border border-gray-200 p-0.5 dark:border-gray-700">
                        <Link
                            :href="hrefFor('board')"
                            class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium"
                            :class="view === 'board'
                                ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300'
                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'"
                        >
                            <AppIcon name="dashboard" class="h-3.5 w-3.5" />
                            Board
                        </Link>
                        <Link
                            :href="hrefFor('list')"
                            class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium"
                            :class="view === 'list'
                                ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300'
                                : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'"
                        >
                            <AppIcon name="menu" class="h-3.5 w-3.5" />
                            List
                        </Link>
                    </div>
                    <Link
                        v-if="canManage"
                        :href="route('projects.settings', { organization: orgSlug, project: project.id })"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        aria-label="Project settings"
                    >
                        <AppIcon name="settings" />
                    </Link>
                </div>
            </div>

            <div v-if="view === 'board'" class="flex gap-4 overflow-x-auto pb-4">
                <section
                    v-for="column in columns"
                    :key="column.id"
                    class="flex w-72 shrink-0 flex-col rounded-xl border border-gray-200 bg-white transition-colors dark:border-gray-800 dark:bg-gray-900"
                    :class="dragging ? 'ring-2 ring-indigo-300/60 dark:ring-indigo-800/60' : ''"
                    @dragover.prevent
                    @drop.prevent="dropAfterList(column)"
                >
                    <header class="flex items-center gap-2 border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                        <span class="h-2 w-2 rounded-full" :class="categoryStyles[column.category]" />
                        <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ column.name }}
                        </h2>
                        <span class="ml-auto text-xs text-gray-400 dark:text-gray-500">
                            {{ column.tasks.length }}
                        </span>
                    </header>

                    <div class="flex-1 space-y-2 p-3">
                        <p
                            v-if="column.tasks.length === 0"
                            class="rounded-lg border border-dashed border-gray-200 p-4 text-center text-xs text-gray-400 dark:border-gray-700 dark:text-gray-500"
                        >
                            Drop a task here or add one below
                        </p>

                        <template v-for="(task, index) in column.tasks" :key="task.id">
                            <div
                                class="h-0.5 rounded bg-indigo-400 opacity-0 transition-opacity"
                                :class="dragging ? 'opacity-60' : ''"
                                @dragover.prevent.stop
                                @drop.prevent.stop="dropBefore(column, index)"
                            />
                            <div
                                draggable="true"
                                class="cursor-grab rounded-lg border border-gray-200 bg-white p-3 text-sm text-gray-800 shadow-sm active:cursor-grabbing dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                                @dragstart="startDrag(task, column)"
                                @dragend="dragging = null"
                                @click="openTask(task)"
                            >
                                <p class="font-medium">{{ task.title }}</p>
                                <div class="mt-2 flex items-center gap-2">
                                    <span
                                        v-if="task.priority !== 'none'"
                                        class="rounded px-1.5 py-0.5 text-[11px] font-medium capitalize"
                                        :class="priorityStyles[task.priority]"
                                    >
                                        {{ task.priority }}
                                    </span>
                                    <span
                                        v-if="task.due_on"
                                        class="text-[11px] text-gray-500 dark:text-gray-400"
                                    >
                                        {{ task.due_on }}
                                    </span>
                                </div>
                            </div>
                        
<TaskSheet :sheet-task="sheetTask" :org-labels="orgLabels" />
</template>
                    </div>

                    <button
                        type="button"
                        class="mx-3 mb-3 flex items-center gap-2 rounded-lg px-2 py-1.5 text-left text-xs font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-800 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                        @click="openAddTask(column)"
                    >
                        <AppIcon name="plus" class="h-3.5 w-3.5" />
                        New task
                    </button>
                </section>

                <button
                    v-if="canManage"
                    type="button"
                    class="flex h-10 w-10 shrink-0 items-center justify-center self-start rounded-lg border border-dashed border-gray-300 text-gray-400 hover:border-indigo-400 hover:text-indigo-600 dark:border-gray-700 dark:hover:border-indigo-500"
                    aria-label="Add column"
                    @click="showingAddColumn = true"
                >
                    <AppIcon name="plus" />
                </button>
            </div>

            <div v-else class="space-y-6">
                <p
                    v-if="columns.every((column) => column.tasks.length === 0)"
                    class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400"
                >
                    No tasks yet. Create one from the board or the + button.
                </p>

                <section
                    v-for="column in columns.filter((c) => c.tasks.length > 0)"
                    :key="column.id"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"
                >
                    <header class="flex items-center gap-2 border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                        <span class="h-2 w-2 rounded-full" :class="categoryStyles[column.category]" />
                        <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ column.name }}
                        </h2>
                        <span class="ml-auto text-xs text-gray-400 dark:text-gray-500">
                            {{ column.tasks.length }}
                        </span>
                    </header>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        <div
                            v-for="task in column.tasks"
                            :key="task.id"
                            class="flex items-center gap-3 px-4 py-3 text-sm"
                        >
                            <p class="min-w-0 flex-1 truncate font-medium text-gray-900 dark:text-gray-100">
                                {{ task.title }}
                            </p>
                            <span
                                v-if="task.priority !== 'none'"
                                class="rounded px-1.5 py-0.5 text-[11px] font-medium capitalize"
                                :class="priorityStyles[task.priority]"
                            >
                                {{ task.priority }}
                            </span>
                            <span v-if="task.due_on" class="shrink-0 text-xs text-gray-500 dark:text-gray-400">
                                {{ task.due_on }}
                            </span>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <Modal :open="showingAddColumn" title="Add column" @closed="showingAddColumn = false">
            <form class="space-y-4" @submit.prevent="submitColumn">
                <div>
                    <InputLabel for="board-column-name" value="Name" />
                    <TextInput id="board-column-name" v-model="columnForm.name" type="text" class="mt-1 w-full" required />
                    <InputError :message="columnForm.errors.name" class="mt-2" />
                </div>

                <div>
                    <InputLabel value="Category" />
                    <select
                        v-model="columnForm.category"
                        class="mt-1 w-full rounded-lg border-gray-300 text-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                    >
                        <option value="not_started">Not started</option>
                        <option value="in_flight">In flight</option>
                        <option value="done">Done</option>
                    </select>
                    <InputError :message="columnForm.errors.category" class="mt-2" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                        @click="showingAddColumn = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton type="submit" :disabled="columnForm.processing">
                        Add column
                    </PrimaryButton>
                </div>
            </form>
        </Modal>

        <Modal :open="showingAddTask !== null" title="New task" @closed="showingAddTask = null">
            <form class="space-y-4" @submit.prevent="submitTask">
                <div>
                    <InputLabel for="task-title" value="Title" />
                    <TextInput id="task-title" v-model="taskForm.title" type="text" class="mt-1 w-full" required />
                    <InputError :message="taskForm.errors.title" class="mt-2" />
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                        @click="showingAddTask = null"
                    >
                        Cancel
                    </button>
                    <PrimaryButton type="submit" :disabled="taskForm.processing">
                        Create task
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AppLayout>

<TaskSheet :sheet-task="sheetTask" :org-labels="orgLabels" />
</template>
