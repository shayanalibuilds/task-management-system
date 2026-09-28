<script setup>
import { computed, ref } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';

const page = usePage();
const orgSlug = computed(() => page.props.organization.slug);
const members = computed(() => page.props.members ?? []);
const invites = computed(() => page.props.invites ?? []);
const currentUserId = computed(() => page.props.auth.user.id);
const flashSuccess = computed(() => page.props.flash?.success ?? null);

const roles = ['owner', 'admin', 'member', 'viewer'];

function isLastOwner(member) {
    if (member.role !== 'owner' || member.id !== currentUserId.value) {
        return false;
    }

    const owners = members.value.filter((candidate) => candidate.role === 'owner');

    return owners.length === 1;
}

function changeRole(member, event) {
    const role = event.target.value;

    if (role === member.role) {
        return;
    }

    router.patch(
        route('settings.members.update', {
            organization: orgSlug.value,
            membership: member.membership_id,
        }),
        { role },
        { preserveScroll: true },
    );
}

function removeMember(member) {
    router.delete(
        route('settings.members.destroy', {
            organization: orgSlug.value,
            membership: member.membership_id,
        }),
        { preserveScroll: true },
    );
}

const inviteForm = useForm({
    email: '',
    role: 'member',
});

function sendInvite() {
    inviteForm.post(route('members.invites.store', { organization: orgSlug.value }), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => inviteForm.reset('email'),
    });
}

function inviteLink(token) {
    return `${window.location.origin}/register?invite=${token}`;
}

function copyInvite(token) {
    navigator.clipboard?.writeText(inviteLink(token));
}

const copiedToken = ref(null);

function copy(token) {
    copyInvite(token);
    copiedToken.value = token;
    setTimeout(() => {
        copiedToken.value = null;
    }, 1500);
}

function revokeInvite(invite) {
    router.delete(
        route('settings.invites.destroy', {
            organization: orgSlug.value,
            invite: invite.id,
        }),
        { preserveScroll: true },
    );
}
</script>

<template>
    <AppLayout :breadcrumbs="[{ label: 'Settings' }, { label: 'Members' }]">
        <div class="mx-auto max-w-2xl space-y-6">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">
                    Members
                </h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Roles decide what people can change. Owners manage everything, viewers just
                    look.
                </p>
            </div>

            <p
                v-if="flashSuccess"
                class="rounded-lg border border-green-200 bg-green-50 px-4 py-2 text-sm text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-300"
            >
                {{ flashSuccess }}
            </p>

            <section
                class="divide-y divide-gray-100 rounded-xl border border-gray-200 bg-white dark:divide-gray-800 dark:border-gray-800 dark:bg-gray-900"
            >
                <div
                    v-for="member in members"
                    :key="member.id"
                    class="flex items-center justify-between gap-3 px-4 py-3"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                        >
                            {{
                                member.name
                                    .split(' ')
                                    .map((part) => part[0])
                                    .slice(0, 2)
                                    .join('')
                            }}
                        </span>
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-medium text-gray-900 dark:text-gray-100"
                            >
                                {{ member.name }}
                                <span
                                    v-if="member.id === currentUserId"
                                    class="ml-1 text-xs text-gray-400"
                                >
                                    (you)
                                </span>
                            </p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ member.email }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <select
                            :value="member.role"
                            class="rounded-lg border-gray-300 text-sm capitalize dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                            :disabled="isLastOwner(member)"
                            :aria-label="'Role for ' + member.name"
                            @change="changeRole(member, $event)"
                        >
                            <option v-for="role in roles" :key="role" :value="role">
                                {{ role }}
                            </option>
                        </select>
                        <button
                            v-if="!isLastOwner(member)"
                            type="button"
                            class="rounded px-2 py-1 text-xs text-gray-500 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950 dark:hover:text-red-300"
                            @click="removeMember(member)"
                        >
                            Remove
                        </button>
                        <span
                            v-else
                            class="rounded bg-gray-100 px-2 py-1 text-[11px] text-gray-500 dark:bg-gray-800 dark:text-gray-400"
                        >
                            Last owner
                        </span>
                    </div>
                </div>
            </section>

            <section
                class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
            >
                <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">Invite someone</h2>
                <form class="mt-3 flex flex-wrap items-end gap-2" @submit.prevent="sendInvite">
                    <div class="min-w-48 flex-1">
                        <InputLabel for="invite-email" value="Email" />
                        <TextInput
                            id="invite-email"
                            v-model="inviteForm.email"
                            type="email"
                            class="mt-1 block w-full"
                            required
                        />
                    </div>
                    <div>
                        <InputLabel for="invite-role" value="Role" />
                        <select
                            id="invite-role"
                            v-model="inviteForm.role"
                            class="mt-1 block rounded-lg border-gray-300 text-sm capitalize dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200"
                        >
                            <option v-for="role in roles" :key="role" :value="role">
                                {{ role }}
                            </option>
                        </select>
                    </div>
                    <PrimaryButton :disabled="inviteForm.processing">Send invite</PrimaryButton>
                </form>
                <InputError class="mt-2" :message="inviteForm.errors.email" />
            </section>

            <section
                class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900"
            >
                <h2 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Pending invites
                </h2>
                <ul class="mt-3 space-y-2">
                    <li
                        v-for="invite in invites"
                        :key="invite.id"
                        class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 px-3 py-2 dark:border-gray-800"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm text-gray-800 dark:text-gray-200">
                                {{ invite.email }}
                            </p>
                            <p class="truncate text-xs text-gray-400">
                                {{ inviteLink(invite.token) }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <span
                                class="rounded bg-gray-100 px-2 py-0.5 text-[11px] text-gray-500 capitalize dark:bg-gray-800 dark:text-gray-400"
                            >
                                {{ invite.role }}
                            </span>
                            <button
                                type="button"
                                class="rounded px-2 py-1 text-xs text-indigo-600 hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-950"
                                @click="copy(invite.token)"
                            >
                                {{ copiedToken === invite.token ? 'Copied' : 'Copy link' }}
                            </button>
                            <button
                                type="button"
                                class="rounded px-2 py-1 text-xs text-gray-500 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950 dark:hover:text-red-300"
                                @click="revokeInvite(invite)"
                            >
                                Revoke
                            </button>
                        </div>
                    </li>
                    <li
                        v-if="invites.length === 0"
                        class="rounded-lg border border-dashed border-gray-200 p-4 text-center text-xs text-gray-400 dark:border-gray-700"
                    >
                        No pending invites.
                    </li>
                </ul>
            </section>
        </div>
    </AppLayout>
</template>
