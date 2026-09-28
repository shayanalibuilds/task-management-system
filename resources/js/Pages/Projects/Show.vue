<script setup>
import { computed, ref } from 'vue';
import { Link, usePage, useForm } from '@inertiajs/vue3';
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
const canManage = computed(() => page.props.can_manage ?? false);

const showingAddColumn = ref(false);

const columnForm = useForm({
    name: '',
    category: 'not_started',
});

const categoryStyles = {
    not_started: 'bg-gray-400',
    in_flight: 'bg-indigo-500',
    done: 'bg-emerald-500',
};

function submitColumn() {
    columnForm.post(
        route('projects.columns.store', { organization: orgSlug.value, project: project.value.id }),
        {
            preserveScroll: true,
            onSuccess: () => {
                showingAddColumn.value = false;
                columnForm.reset();
            },
        },
    );
}
</script>

<template>
    <AppLayout
        :breadcrumbs="[
            { label: 'Projects', href: route('projects.index', { organization: orgSlug }) },
            { label: project.name },
        ]"
    >
        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-9 w-9 items-center justify-center rounded-lg"
                        :style="{ backgroundColor: project.color + '1A', color: project.color }"
                    >
                        <AppIcon :name="project.icon" />
                    </span>
                    <h1
                        class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100"
                    >
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
                    <Link
                        v-if="canManage"
                        :href="
                            route('projects.settings', {
                                organization: orgSlug,
                                project: project.id,
                            })
                        "
                        class="inline-flex h-8 w-8 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-gray-100"
                        aria-label="Project settings"
                    >
                        <AppIcon name="settings" />
                    </Link>
                </div>
            </div>

            <div class="flex gap-4 overflow-x-auto pb-4">
                <section
                    v-for="column in columns"
                    :key="column.id"
                    class="flex w-72 shrink-0 flex-col rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900"
                >
                    <header
                        class="flex items-center gap-2 border-b border-gray-100 px-4 py-3 dark:border-gray-800"
                    >
                        <span
                            class="h-2 w-2 rounded-full"
                            :class="categoryStyles[column.category]"
                        />
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
                            No tasks here yet
                        </p>
                        <div
                            v-for="task in column.tasks"
                            :key="task.id"
                            class="cursor-default rounded-lg border border-gray-200 bg-white p-3 text-sm text-gray-800 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                            {{ task.title }}
                        </div>
                    </div>
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
        </div>

        <Modal :open="showingAddColumn" title="Add column" @closed="showingAddColumn = false">
            <form class="space-y-4" @submit.prevent="submitColumn">
                <div>
                    <InputLabel for="column-name" value="Name" />
                    <TextInput
                        id="column-name"
                        v-model="columnForm.name"
                        type="text"
                        class="mt-1 w-full"
                        required
                    />
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
    </AppLayout>
</template>
