<script setup>
const props = defineProps({
    organization: { type: Object, required: true },
});

const role = props.organization.role ?? 'member';
const memberCount = props.organization.member_count ?? 1;
</script>

<template>
    <main class="flex min-h-dvh items-center justify-center bg-gray-100 px-4">
        <section class="w-full max-w-md rounded-xl border border-gray-200 bg-white p-8 shadow-sm">
            <p class="text-xs font-medium tracking-wide text-indigo-600 uppercase">
                Workspace
            </p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight text-gray-900">
                {{ organization.name }}
            </h1>
            <p class="mt-3 text-sm text-gray-600">
                You are signed in as <span class="font-medium text-gray-900">{{ role }}</span>
                with {{ memberCount }} {{ memberCount === 1 ? 'member' : 'members' }}.
            </p>
            <p class="mt-6 text-sm text-gray-500">
                The full dashboard arrives with the next updates — projects, boards and tasks
                are on the way.
            </p>
        </section>
    </main>
</template>
