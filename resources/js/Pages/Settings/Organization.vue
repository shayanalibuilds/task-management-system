<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const page = usePage();
const orgSlug = computed(() => page.props.organization.slug);
const organization = computed(() => page.props.organization);
const labels = computed(() => page.props.labels ?? []);
const flashSuccess = computed(() => page.props.flash?.success ?? null);

const form = useForm({
    name: organization.value.name,
    slug: organization.value.slug,
    webhook_url: organization.value.webhook_url ?? '',
});

const editing = ref(null);

const editForm = useForm({
    name: '',
    color: '#4F46E5',
});

const labelForm = useForm({
    name: '',
    color: '#4F46E5',
});

const showNewLabelForm = ref(false);

const palette = [
    '#4F46E5',
    '#059669',
    '#D97706',
    '#DC2626',
    '#0891B2',
    '#7C3AED',
    '#16A34A',
    '#64748B',
];

function pickColor(form, color) {
    form.color = color;
}

function saveGeneral() {
    form.patch(route('settings.update', { organization: orgSlug.value }), {
        preserveScroll: true,
    });
}

function startEdit(label) {
    editing.value = label.id;
    editForm.name = label.name;
    editForm.color = label.color;
}

function saveEdit() {
    editForm.patch(
        route('settings.labels.update', {
            organization: orgSlug.value,
            label: editing.value,
        }),
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                editing.value = null;
            },
        },
    );
}

function createLabel() {
    labelForm.post(route('settings.labels.store', { organization: orgSlug.value }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            labelForm.reset('name');
            showNewLabelForm.value = false;
        },
    });
}

function deleteLabel(label) {
    router.delete(
        route('settings.labels.destroy', {
            organization: orgSlug.value,
            label: label.id,
        }),
        { preserveScroll: true },
    );
}

const confirmingDelete = ref(false);
const deleteConfirm = useForm({ verb: '' });

function deleteWorkspace() {
    deleteConfirm.delete(route('settings.destroy', { organization: orgSlug.value }));
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ label: 'Settings' }, { label: 'Workspace' }]">
        <div class="mx-auto max-w-2xl space-y-6">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                    Workspace settings
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    General details, shared labels and the danger zone.
                </p>
            </div>

            <p
                v-if="flashSuccess"
                class="rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-300"
            >
                {{ flashSuccess }}
            </p>

            <section
                class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
            >
                <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">General</h2>
                <form class="mt-4 space-y-4" @submit.prevent="saveGeneral">
                    <div>
                        <InputLabel for="org-name" value="Workspace name" />
                        <TextInput
                            id="org-name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>
                    <div>
                        <InputLabel for="org-slug" value="URL slug" />
                        <TextInput
                            id="org-slug"
                            v-model="form.slug"
                            type="text"
                            class="mt-1 block w-full"
                            required
                        />
                        <InputError class="mt-2" :message="form.errors.slug" />
                    </div>
                    <div>
                        <InputLabel for="org-webhook" value="Webhook URL (optional)" />
                        <TextInput
                            id="org-webhook"
                            v-model="form.webhook_url"
                            type="url"
                            class="mt-1 block w-full"
                            placeholder="https://example.com/hooks/dailytm"
                        />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            We POST task events to this URL. Self-hosted means you own the hook.
                        </p>
                        <InputError class="mt-2" :message="form.errors.webhook_url" />
                    </div>
                    <div class="flex items-center gap-3">
                        <PrimaryButton :disabled="form.processing">Save changes</PrimaryButton>
                    </div>
                </form>
            </section>

            <section
                class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
            >
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">Labels</h2>
                    <SecondaryButton type="button" @click="showNewLabelForm = !showNewLabelForm">
                        {{ showNewLabelForm ? 'Cancel' : 'New label' }}
                    </SecondaryButton>
                </div>

                <form
                    v-if="showNewLabelForm"
                    class="mt-4 space-y-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700"
                    @submit.prevent="createLabel"
                >
                    <div>
                        <InputLabel for="label-name" value="Name" />
                        <TextInput
                            id="label-name"
                            v-model="labelForm.name"
                            type="text"
                            class="mt-1 block w-full"
                            maxlength="40"
                            required
                        />
                        <InputError class="mt-2" :message="labelForm.errors.name" />
                    </div>
                    <div>
                        <InputLabel value="Color" />
                        <div class="mt-1.5 flex flex-wrap gap-1.5">
                            <button
                                v-for="color in palette"
                                :key="color"
                                type="button"
                                class="h-6 w-6 rounded-full border-2"
                                :class="
                                    labelForm.color === color
                                        ? 'border-gray-900 dark:border-white'
                                        : 'border-transparent'
                                "
                                :style="{ backgroundColor: color }"
                                :aria-label="'Pick color ' + color"
                                @click="pickColor(labelForm, color)"
                            />
                        </div>
                        <InputError class="mt-2" :message="labelForm.errors.color" />
                    </div>
                    <PrimaryButton :disabled="labelForm.processing">Create label</PrimaryButton>
                </form>

                <ul class="mt-4 space-y-2">
                    <li
                        v-for="label in labels"
                        :key="label.id"
                        class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2 dark:border-gray-800"
                    >
                        <template v-if="editing === label.id">
                            <form class="flex flex-1 items-center gap-2" @submit.prevent="saveEdit">
                                <input
                                    v-model="editForm.color"
                                    type="color"
                                    class="h-7 w-9 cursor-pointer rounded border-gray-300 dark:border-gray-700"
                                    aria-label="Label color"
                                />
                                <TextInput
                                    v-model="editForm.name"
                                    type="text"
                                    class="flex-1"
                                    maxlength="40"
                                    required
                                />
                                <PrimaryButton :disabled="editForm.processing">Save</PrimaryButton>
                                <SecondaryButton type="button" @click="editing = null">
                                    Cancel
                                </SecondaryButton>
                            </form>
                        </template>
                        <template v-else>
                            <div class="flex items-center gap-2.5">
                                <span
                                    class="h-3 w-3 rounded-full"
                                    :style="{ backgroundColor: label.color }"
                                />
                                <span class="text-sm text-gray-800 dark:text-gray-200">
                                    {{ label.name }}
                                </span>
                            </div>
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    class="rounded px-2 py-1 text-xs text-gray-500 hover:bg-gray-50 hover:text-gray-800 dark:hover:bg-gray-800 dark:hover:text-gray-200"
                                    @click="startEdit(label)"
                                >
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="rounded px-2 py-1 text-xs text-gray-500 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950 dark:hover:text-red-300"
                                    @click="deleteLabel(label)"
                                >
                                    Delete
                                </button>
                            </div>
                        </template>
                    </li>
                    <li
                        v-if="labels.length === 0"
                        class="rounded-lg border border-dashed border-gray-200 p-4 text-center text-xs text-gray-400 dark:border-gray-700"
                    >
                        No labels yet — they keep boards scannable.
                    </li>
                </ul>
            </section>

            <section
                class="rounded-xl border border-red-200 bg-white p-4 dark:border-red-900 dark:bg-gray-900"
            >
                <h2 class="text-sm font-medium text-red-600 dark:text-red-400">Danger zone</h2>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Deleting a workspace removes its projects, tasks and history for everyone. This
                    cannot be undone.
                </p>
                <button
                    type="button"
                    class="mt-3 rounded-lg border border-red-300 px-3 py-1.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-900 dark:hover:bg-red-950"
                    @click="confirmingDelete = true"
                >
                    Delete workspace
                </button>
            </section>
        </div>

        <Modal :open="confirmingDelete" title="Delete workspace" @closed="confirmingDelete = false">
            <p class="text-sm text-gray-600 dark:text-gray-400">
                This permanently deletes
                <span class="font-medium text-gray-900 dark:text-gray-200">
                    {{ organization.name }}
                </span>
                for all members. Type
                <span class="font-mono font-medium text-red-600 dark:text-red-400">DELETE</span>
                to confirm.
            </p>
            <form class="mt-4 space-y-4" @submit.prevent="deleteWorkspace">
                <TextInput
                    v-model="deleteConfirm.verb"
                    type="text"
                    class="w-full"
                    placeholder="DELETE"
                    aria-label="Type DELETE to confirm"
                />
                <div class="flex justify-end gap-2">
                    <SecondaryButton type="button" @click="confirmingDelete = false">
                        Cancel
                    </SecondaryButton>
                    <button
                        type="submit"
                        :disabled="deleteConfirm.verb !== 'DELETE' || deleteConfirm.processing"
                        class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Delete forever
                    </button>
                </div>
            </form>
        </Modal>
    </AppLayout>
</template>
