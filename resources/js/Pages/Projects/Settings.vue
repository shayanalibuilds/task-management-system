<script setup>
import { computed, ref } from 'vue';
import { usePage, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import AppIcon from '@/Components/AppIcon.vue';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const page = usePage();
const orgSlug = computed(() => page.props.organization.slug);
const project = computed(() => page.props.project);
const columns = computed(() => page.props.columns ?? []);

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
const icons = [
    'folder',
    'rocket',
    'sparkles',
    'dashboard',
    'calendar',
    'check-square',
    'tag',
    'clock',
    'inbox',
    'activity',
];
const categories = [
    { value: 'not_started', label: 'Not started' },
    { value: 'in_flight', label: 'In flight' },
    { value: 'done', label: 'Done' },
];

const form = useForm({
    name: project.value.name,
    color: project.value.color,
    icon: project.value.icon,
    visibility: project.value.visibility,
});

function submitGeneral() {
    form.patch(
        route('projects.update', { organization: orgSlug.value, project: project.value.id }),
        {
            preserveScroll: true,
        },
    );
}

function moveColumn(index, direction) {
    const order = [...columns.value].map((column) => column.id);
    const target = index + direction;

    if (target < 0 || target >= order.length) {
        return;
    }

    [order[index], order[target]] = [order[target], order[index]];

    router.patch(
        route('projects.columns.reorder', {
            organization: orgSlug.value,
            project: project.value.id,
        }),
        {
            columns: order,
        },
        {
            preserveScroll: true,
        },
    );
}

const columnToDelete = ref(null);
const deleteError = ref('');

function confirmDeleteColumn() {
    router.delete(
        route('projects.columns.destroy', {
            organization: orgSlug.value,
            project: project.value.id,
            column: columnToDelete.value.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                columnToDelete.value = null;
                deleteError.value = '';
            },
            onError: (errors) => {
                deleteError.value = errors.column ?? 'This column could not be deleted.';
            },
        },
    );
}

const confirmingDeleteProject = ref(false);
const deleteProjectForm = useForm({});

function confirmDeleteProject() {
    deleteProjectForm.delete(
        route('projects.destroy', {
            organization: orgSlug.value,
            project: project.value.id,
        }),
    );
}
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { label: 'Projects', href: route('projects.index', { organization: orgSlug }) },
            {
                label: project.name,
                href: route('projects.show', { organization: orgSlug, project: project.id }),
            },
            { label: 'Settings' },
        ]"
    >
        <div class="mx-auto max-w-3xl space-y-8">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                    Project settings
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Appearance, visibility and board columns.
                </p>
            </div>

            <section
                class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900"
            >
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">General</h2>
                <form class="mt-4 space-y-4" @submit.prevent="submitGeneral">
                    <div>
                        <InputLabel for="settings-name" value="Name" />
                        <TextInput
                            id="settings-name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 w-full"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Color" />
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="color in palette"
                                :key="color"
                                type="button"
                                class="h-7 w-7 rounded-full ring-offset-2 dark:ring-offset-gray-900"
                                :class="
                                    form.color === color
                                        ? 'ring-2 ring-gray-900 dark:ring-gray-100'
                                        : ''
                                "
                                :style="{ backgroundColor: color }"
                                :aria-label="`Use color ${color}`"
                                @click="form.color = color"
                            />
                        </div>
                        <InputError :message="form.errors.color" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Icon" />
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button
                                v-for="icon in icons"
                                :key="icon"
                                type="button"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border"
                                :class="
                                    form.icon === icon
                                        ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300'
                                        : 'border-gray-200 text-gray-500 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800'
                                "
                                :aria-label="`Use icon ${icon}`"
                                @click="form.icon = icon"
                            >
                                <AppIcon :name="icon" />
                            </button>
                        </div>
                        <InputError :message="form.errors.icon" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel value="Visibility" />
                        <div class="mt-2 space-y-2">
                            <label
                                class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300"
                            >
                                <input
                                    v-model="form.visibility"
                                    type="radio"
                                    value="open"
                                    class="text-indigo-600"
                                />
                                Open — visible to everyone in the organization
                            </label>
                            <label
                                class="flex cursor-pointer items-center gap-2 text-sm text-gray-700 dark:text-gray-300"
                            >
                                <input
                                    v-model="form.visibility"
                                    type="radio"
                                    value="restricted"
                                    class="text-indigo-600"
                                />
                                Restricted — visible only to included members, owners and admins
                            </label>
                        </div>
                        <InputError :message="form.errors.visibility" class="mt-2" />
                    </div>

                    <div class="flex justify-end">
                        <PrimaryButton type="submit" :disabled="form.processing">
                            Save changes
                        </PrimaryButton>
                    </div>
                </form>
            </section>

            <section
                class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-gray-900"
            >
                <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Columns</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Columns group the board. A column with tasks cannot be deleted.
                </p>

                <div class="mt-4 space-y-2">
                    <div
                        v-for="(column, index) in columns"
                        :key="column.id"
                        class="flex items-center gap-2 rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                    >
                        <div class="flex flex-col">
                            <button
                                type="button"
                                class="text-gray-400 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:text-gray-200"
                                :disabled="index === 0"
                                aria-label="Move column left"
                                @click="moveColumn(index, -1)"
                            >
                                <AppIcon name="chevron-left" />
                            </button>
                            <button
                                type="button"
                                class="text-gray-400 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-30 dark:hover:text-gray-200"
                                :disabled="index === columns.length - 1"
                                aria-label="Move column right"
                                @click="moveColumn(index, 1)"
                            >
                                <AppIcon name="chevron-right" />
                            </button>
                        </div>

                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-medium text-gray-900 dark:text-gray-100"
                            >
                                {{ column.name }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ categories.find((c) => c.value === column.category)?.label }}
                                <span class="mx-1 text-gray-300 dark:text-gray-600">·</span>
                                {{ column.tasks_count }}
                                {{ column.tasks_count === 1 ? 'task' : 'tasks' }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-red-600 dark:hover:bg-gray-800 dark:hover:text-red-400"
                            :aria-label="`Delete column ${column.name}`"
                            @click="
                                columnToDelete = column;
                                deleteError = '';
                            "
                        >
                            <AppIcon name="close" />
                        </button>
                    </div>
                </div>
            </section>

            <section
                class="rounded-xl border border-red-200 bg-red-50/50 p-6 dark:border-red-900/50 dark:bg-red-950/20"
            >
                <h2 class="text-sm font-semibold text-red-700 dark:text-red-400">Danger zone</h2>
                <p class="mt-1 text-sm text-red-600/80 dark:text-red-400/80">
                    Deleting this project removes every column, task and comment inside it.
                </p>
                <button
                    type="button"
                    class="mt-4 rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700"
                    @click="confirmingDeleteProject = true"
                >
                    Delete project
                </button>
            </section>
        </div>

        <Modal
            :open="columnToDelete !== null"
            :title="`Delete column ${columnToDelete?.name ?? ''}`"
            @closed="columnToDelete = null"
        >
            <p class="text-sm text-gray-600 dark:text-gray-300">
                This permanently deletes the column
                <span class="font-medium">{{ columnToDelete?.name }}</span
                >. Move its tasks first.
            </p>
            <p
                v-if="deleteError"
                class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-950 dark:text-red-400"
            >
                {{ deleteError }}
            </p>
            <div class="mt-5 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                    @click="columnToDelete = null"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700"
                    @click="confirmDeleteColumn"
                >
                    Delete column
                </button>
            </div>
        </Modal>

        <Modal
            :open="confirmingDeleteProject"
            title="Delete project"
            @closed="confirmingDeleteProject = false"
        >
            <p class="text-sm text-gray-600 dark:text-gray-300">
                This permanently deletes
                <span class="font-medium">{{ project.name }}</span> and everything inside it.
            </p>
            <div class="mt-5 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                    @click="confirmingDeleteProject = false"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700"
                    :disabled="deleteProjectForm.processing"
                    @click="confirmDeleteProject"
                >
                    Delete project
                </button>
            </div>
        </Modal>
    </AppLayout>
</template>
