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
const projects = computed(() => page.props.projects ?? []);
const canCreate = computed(() => page.props.can_create ?? false);

const showingCreate = ref(false);
const visibility = ref('open');

const form = useForm({
    name: '',
    color: '#4F46E5',
    icon: 'folder',
    visibility: 'open',
});

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

function submitCreate() {
    form.post(route('projects.store', { organization: page.props.organization.slug }), {
        preserveScroll: true,
        onSuccess: () => {
            showingCreate.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ label: 'Projects' }]">
        <div class="mx-auto max-w-5xl space-y-6">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                    Projects
                </h1>
                <PrimaryButton v-if="canCreate" type="button" @click="showingCreate = true">
                    New project
                </PrimaryButton>
            </div>

            <p
                v-if="projects.length === 0"
                class="rounded-xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400"
            >
                No projects yet. Create the first project to start planning work.
            </p>

            <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="project in projects"
                    :key="project.id"
                    :href="
                        route('projects.show', {
                            organization: page.props.organization.slug,
                            project: project.id,
                        })
                    "
                    class="rounded-xl border border-gray-200 bg-white p-5 transition-shadow hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                            :style="{ backgroundColor: project.color + '1A', color: project.color }"
                        >
                            <AppIcon :name="project.icon" />
                        </span>
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-medium text-gray-900 dark:text-gray-100"
                            >
                                {{ project.name }}
                            </p>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ project.tasks_count }}
                                {{ project.tasks_count === 1 ? 'task' : 'tasks' }}
                                <span class="mx-1 text-gray-300 dark:text-gray-600">·</span>
                                <span
                                    v-if="project.visibility === 'restricted'"
                                    class="text-amber-600 dark:text-amber-400"
                                    >Restricted</span
                                >
                                <span v-else class="text-gray-400 dark:text-gray-500">Open</span>
                            </p>
                        </div>
                    </div>
                </Link>
            </div>
        </div>

        <Modal :open="showingCreate" title="New project" @closed="showingCreate = false">
            <form class="space-y-4" @submit.prevent="submitCreate">
                <div>
                    <InputLabel for="name" value="Name" />
                    <TextInput
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 w-full"
                        required
                        autofocus
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

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                        @click="showingCreate = false"
                    >
                        Cancel
                    </button>
                    <PrimaryButton type="submit" :disabled="form.processing">
                        Create project
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
