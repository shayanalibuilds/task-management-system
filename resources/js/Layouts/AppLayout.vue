<script setup>
import { computed, onMounted, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import AppIcon from '@/Components/AppIcon.vue';
import NavLink from '@/Components/NavLink.vue';
import OrgSwitcher from '@/Components/OrgSwitcher.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import UserMenu from '@/Components/UserMenu.vue';

defineProps({
    breadcrumbs: { type: Array, default: () => [] },
});

const page = usePage();
const organization = computed(
    () => page.props.organization ?? page.props.auth?.organizations?.[0] ?? null,
);
const orgSlug = computed(() => organization.value?.slug ?? '');

function hasRoute(name) {
    try {
        return route().has(name);
    } catch {
        return false;
    }
}

const navItems = computed(() => [
    { name: 'org.dashboard', label: 'Dashboard', icon: 'dashboard', badge: 0 },
    { name: 'projects.index', label: 'Projects', icon: 'folder', badge: 0 },
    { name: 'my-tasks.show', label: 'My Tasks', icon: 'check-square', badge: 0 },
    { name: 'calendar.show', label: 'Calendar', icon: 'calendar', badge: 0 },
    { name: 'inbox.index', label: 'Inbox', icon: 'inbox', badge: page.props.inbox?.unread_count ?? 0 },
    { name: 'settings.show', label: 'Settings', icon: 'settings', badge: 0 },
]
    .filter((item) => hasRoute(item.name))
    .map((item) => ({
        ...item,
        href: route(item.name, { organization: orgSlug.value }),
    })));

const pinnedProjects = computed(() => page.props.projects?.pinned ?? []);

const collapsed = ref(false);
const mobileOpen = ref(false);

onMounted(() => {
    collapsed.value = window.localStorage.getItem('sidebar') === 'collapsed';
});

function toggleSidebar() {
    collapsed.value = !collapsed.value;
    window.localStorage.setItem('sidebar', collapsed.value ? 'collapsed' : 'open');
}
</script>

<template>
    <div class="min-h-dvh bg-gray-100 dark:bg-gray-950">
        <div v-if="!organization" class="flex min-h-dvh items-center justify-center">
            <p class="text-sm text-gray-500">No workspace selected.</p>
        </div>

        <template v-else>
            <button
                type="button"
                class="fixed left-3 top-3 z-40 inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 shadow-sm md:hidden dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300"
                aria-label="Open navigation"
                @click="mobileOpen = true"
            >
                <AppIcon name="menu" />
            </button>

            <div
                v-if="mobileOpen"
                class="fixed inset-0 z-40 bg-gray-900/40 md:hidden"
                @click="mobileOpen = false"
            />

            <aside
                class="fixed inset-y-0 left-0 z-50 flex flex-col border-r border-gray-200 bg-white transition-all duration-200 md:translate-x-0 dark:border-gray-800 dark:bg-gray-900"
                :class="[
                    collapsed ? 'w-[68px]' : 'w-[260px]',
                    mobileOpen ? 'translate-x-0' : '-translate-x-full',
                ]"
            >
                <div class="flex items-center gap-2 p-3">
                    <OrgSwitcher :collapsed="collapsed" />
                    <button
                        type="button"
                        class="hidden h-8 w-8 shrink-0 items-center justify-center rounded-md text-gray-400 hover:bg-gray-50 hover:text-gray-700 md:inline-flex dark:hover:bg-gray-800 dark:hover:text-gray-200"
                        :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                        @click="toggleSidebar"
                    >
                        <AppIcon :name="collapsed ? 'chevron-right' : 'chevron-left'" />
                    </button>
                    <button
                        type="button"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-gray-400 hover:bg-gray-50 hover:text-gray-700 md:hidden"
                        aria-label="Close navigation"
                        @click="mobileOpen = false"
                    >
                        <AppIcon name="close" />
                    </button>
                </div>

                <nav class="mt-2 flex-1 space-y-1 overflow-y-auto px-3" :aria-label="`${organization.name} navigation`">
                    <NavLink
                        v-for="item in navItems"
                        :key="item.name"
                        :href="item.href"
                        :label="item.label"
                        :icon="item.icon"
                        :badge="item.badge"
                        :collapsed="collapsed"
                        @click="mobileOpen = false"
                    />

                    <div v-if="!collapsed && pinnedProjects.length > 0" class="pt-4">
                        <p class="px-3 pb-1 text-xs font-medium tracking-wide text-gray-400 uppercase dark:text-gray-500">
                            Pinned projects
                        </p>
                        <NavLink
                            v-for="project in pinnedProjects"
                            :key="project.id"
                            :href="route('projects.show', { organization: orgSlug, project: project.id })"
                            :label="project.name"
                            icon="folder"
                            :collapsed="collapsed"
                            @click="mobileOpen = false"
                        />
                    </div>
                </nav>

                <div class="border-t border-gray-100 p-3 dark:border-gray-800">
                    <UserMenu :collapsed="collapsed" />
                </div>
            </aside>

            <div
                class="flex min-h-dvh flex-col transition-all duration-200"
                :class="collapsed ? 'md:pl-[68px]' : 'md:pl-[260px]'"
            >
                <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-gray-200 bg-white/90 px-4 pl-14 backdrop-blur md:pl-4 dark:border-gray-800 dark:bg-gray-900/90">
                    <nav class="flex items-center gap-1.5 text-sm" aria-label="Breadcrumb">
                        <Link
                            :href="route('org.dashboard', { organization: orgSlug })"
                            class="font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                        >
                            {{ organization.name }}
                        </Link>
                        <template v-for="crumb in breadcrumbs" :key="crumb.label">
                            <AppIcon name="chevron-right" class="h-3.5 w-3.5 text-gray-300 dark:text-gray-600" />
                            <Link
                                v-if="crumb.href"
                                :href="crumb.href"
                                class="font-medium text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-100"
                            >
                                {{ crumb.label }}
                            </Link>
                            <span v-else class="font-medium text-gray-900 dark:text-gray-100">
                                {{ crumb.label }}
                            </span>
                        </template>
                    </nav>

                    <div class="flex items-center gap-1">
                        <slot name="actions" />
                        <ThemeToggle />
                    </div>
                </header>

                <main class="flex-1 p-4 md:p-6">
                    <slot />
                </main>
            </div>
        </template>
    </div>
</template>
