<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import NotificationBell from '@/Components/NotificationBell.vue';
import { useAppearance } from '../composables/useAppearance';
import { useAuthorization } from '@/composables/useAuthorization';
import {
    AuditOutlined,
    ContactsOutlined,
    DashboardOutlined,
    DollarOutlined,
    DownOutlined,
    FileTextOutlined,
    KeyOutlined,
    LineChartOutlined,
    LogoutOutlined,
    ProfileOutlined,
    ProjectOutlined,
    ReadOutlined,
    SafetyOutlined,
    SettingOutlined,
    TeamOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import { ConfigProvider } from 'ant-design-vue';
import { theme } from 'ant-design-vue';
import { computed, onBeforeMount, onMounted } from 'vue';

defineProps({
    title: String,
});

const { can, canAny } = useAuthorization();

const showingNavigationDropdown = ref(false);

const switchToTeam = (team: any) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const logout = () => {
    router.post(route('logout'));
};

const { appearance } = useAppearance();
const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
const systemPrefersDark = ref(mediaQuery.matches);

const handleSystemPreferenceChange = (event: MediaQueryListEvent) => {
    systemPrefersDark.value = event.matches;
};

onMounted(() => {
    mediaQuery.addEventListener('change', handleSystemPreferenceChange);
});

onBeforeMount(() => {
    mediaQuery.removeEventListener('change', handleSystemPreferenceChange);
});

const isDarkMode = computed(() => {
    return appearance.value === 'dark' || (appearance.value === 'system' && systemPrefersDark.value);
});

const themeAlgorithm = computed(() => {
    return isDarkMode.value ? theme.darkAlgorithm : theme.defaultAlgorithm;
});

const showSystemMenu = computed(
    () => can('reports.export') || can('audit_logs.view') || can('users.manage') || can('roles.manage'),
);

const systemMenuActive = computed(
    () =>
        Boolean(
            route().current('monitoring.*')
            || route().current('reports.*')
            || route().current('audit-logs.*')
            || route().current('users.*')
            || route().current('roles.*'),
        ),
);

const navTriggerBase =
    'box-border flex h-16 shrink-0 items-center gap-1.5 whitespace-nowrap border-b-2 px-2.5 text-xs font-medium leading-none transition duration-150 ease-in-out focus:outline-none';
const navTriggerActiveClass = `${navTriggerBase} border-indigo-500 text-gray-900 dark:border-indigo-400 dark:text-white`;
const navTriggerInactiveClass = `${navTriggerBase} border-transparent text-gray-600 hover:border-slate-300 hover:text-gray-900 dark:text-slate-400 dark:hover:border-slate-500 dark:hover:text-slate-100`;
</script>

<template>
    <div>
        <ConfigProvider
            :theme="{
                token: { fontFamily: 'Figtree', borderRadius: 6, fontSize: 12 },
                algorithm: themeAlgorithm,
            }"
        >
            <Head :title="title"/>

            <Banner/>

            <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
                <nav class="border-b border-gray-200 bg-white dark:border-slate-700/80 dark:bg-slate-900">
                    <!-- Primary Navigation Menu -->
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="flex h-16 justify-between">
                            <div class="flex min-h-0 min-w-0 flex-1 items-stretch">
                                <!-- Logo -->
                                <div class="flex shrink-0 items-center self-center">
                                    <Link :href="route('dashboard')">
                                        <ApplicationMark class="block h-9 w-auto"/>
                                    </Link>
                                </div>

                                <!-- Navigation Links (core modules + System dropdown); stretch to bar height for bottom border alignment -->
                                <div class="hidden min-h-0 min-w-0 flex-1 items-stretch gap-x-1 sm:ms-5 sm:flex lg:ms-7 lg:gap-x-1.5">
                                    <NavLink v-if="can('dashboard.access')" :href="route('dashboard')" :active="route().current('dashboard')">
                                        <DashboardOutlined class="text-[14px] shrink-0 translate-y-[0.5px]" />
                                        <span>Dashboard</span>
                                    </NavLink>
                                    <NavLink
                                        v-if="canAny(['beneficiaries.view', 'beneficiaries.manage'])"
                                        :href="route('beneficiaries.index')"
                                        :active="route().current('beneficiaries.*')"
                                    >
                                        <UserOutlined class="text-[14px] shrink-0 translate-y-[0.5px]" />
                                        <span>Beneficiaries</span>
                                    </NavLink>
                                    <NavLink
                                        v-if="canAny(['projects.view', 'projects.manage'])"
                                        :href="route('projects.index')"
                                        :active="route().current('projects.*')"
                                    >
                                        <ProjectOutlined class="text-[14px] shrink-0 translate-y-[0.5px]" />
                                        <span>Projects</span>
                                    </NavLink>
                                    <NavLink
                                        v-if="canAny(['trainings.view', 'trainings.manage'])"
                                        :href="route('trainings.index')"
                                        :active="route().current('trainings.*')"
                                    >
                                        <ReadOutlined class="text-[14px] shrink-0 translate-y-[0.5px]" />
                                        <span>Trainings</span>
                                    </NavLink>
                                    <NavLink
                                        v-if="canAny(['assistance.view', 'assistance.manage'])"
                                        :href="route('assistance-records.index')"
                                        :active="route().current('assistance-records.*')"
                                    >
                                        <DollarOutlined class="text-[14px] shrink-0 translate-y-[0.5px]" />
                                        <span>Assistance</span>
                                    </NavLink>
                                    <NavLink
                                        v-if="canAny(['groups.view', 'groups.manage'])"
                                        :href="route('beneficiary-groups.index')"
                                        :active="route().current('beneficiary-groups.*')"
                                    >
                                        <TeamOutlined class="text-[14px] shrink-0 translate-y-[0.5px]" />
                                        <span class="whitespace-nowrap"><span class="hidden xl:inline">Beneficiary </span>Groups</span>
                                    </NavLink>

                                    <div v-if="showSystemMenu" class="relative flex shrink-0 items-stretch">
                                    <Dropdown
                                        align="left"
                                        width="56"
                                        :content-classes="['py-1', 'bg-white', 'dark:bg-slate-800']"
                                    >
                                        <template #trigger>
                                            <button
                                                type="button"
                                                :class="systemMenuActive ? navTriggerActiveClass : navTriggerInactiveClass"
                                            >
                                                <SettingOutlined class="text-[14px] shrink-0 translate-y-[0.5px]" />
                                                <span class="whitespace-nowrap">System</span>
                                                <DownOutlined class="text-[9px] shrink-0 translate-y-[0.5px] opacity-60" />
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink
                                                v-if="can('dashboard.access')"
                                                :href="route('monitoring.index')"
                                            >
                                                <span class="inline-flex items-center gap-1.5">
                                                    <LineChartOutlined class="text-sm opacity-90" />
                                                    Monitoring
                                                </span>
                                            </DropdownLink>
                                            <DropdownLink
                                                v-if="can('reports.export')"
                                                :href="route('reports.index')"
                                            >
                                                <span class="inline-flex items-center gap-1.5">
                                                    <FileTextOutlined class="text-sm opacity-90" />
                                                    Reports
                                                </span>
                                            </DropdownLink>
                                            <DropdownLink
                                                v-if="can('audit_logs.view')"
                                                :href="route('audit-logs.index')"
                                            >
                                                <span class="inline-flex items-center gap-1.5">
                                                    <AuditOutlined class="text-sm opacity-90" />
                                                    Audit logs
                                                </span>
                                            </DropdownLink>
                                            <DropdownLink
                                                v-if="can('users.manage')"
                                                :href="route('users.index')"
                                            >
                                                <span class="inline-flex items-center gap-1.5">
                                                    <ContactsOutlined class="text-sm opacity-90" />
                                                    Users
                                                </span>
                                            </DropdownLink>
                                            <DropdownLink
                                                v-if="can('roles.manage')"
                                                :href="route('roles.index')"
                                            >
                                                <span class="inline-flex items-center gap-1.5">
                                                    <SafetyOutlined class="text-sm opacity-90" />
                                                    Roles
                                                </span>
                                            </DropdownLink>
                                        </template>
                                    </Dropdown>
                                    </div>
                                </div>
                            </div>

                            <div class="hidden shrink-0 items-center sm:ms-3 sm:flex">
                                <!-- Notification Bell -->
                                <div class="ms-3 relative">
                                    <NotificationBell />
                                </div>

                                <div class="ms-3 relative">
                                    <!-- Teams Dropdown -->
                                    <Dropdown v-if="$page.props.jetstream.hasTeamFeatures" align="right" width="60">
                                        <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button type="button"
                                                    class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                                {{ $page.props.auth.user.current_team.name }}

                                                <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg"
                                                     fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                     stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"/>
                                                </svg>
                                            </button>
                                        </span>
                                        </template>

                                        <template #content>
                                            <div class="w-60">
                                                <!-- Team Management -->
                                                <div class="block px-4 py-2 text-xs text-gray-400">
                                                    Manage Team
                                                </div>

                                                <!-- Team Settings -->
                                                <DropdownLink
                                                    :href="route('teams.show', $page.props.auth.user.current_team)">
                                                    Team Settings
                                                </DropdownLink>

                                                <DropdownLink v-if="$page.props.jetstream.canCreateTeams"
                                                              :href="route('teams.create')">
                                                    Create New Team
                                                </DropdownLink>

                                                <!-- Team Switcher -->
                                                <template v-if="$page.props.auth.user.all_teams.length > 1">
                                                    <div class="border-t border-gray-200 dark:border-gray-600"/>

                                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                                        Switch Teams
                                                    </div>

                                                    <template v-for="team in $page.props.auth.user.all_teams"
                                                              :key="team.id">
                                                        <form @submit.prevent="switchToTeam(team)">
                                                            <DropdownLink as="button">
                                                                <div class="flex items-center">
                                                                    <svg
                                                                        v-if="team.id == $page.props.auth.user.current_team_id"
                                                                        class="me-2 size-5 text-green-400"
                                                                        xmlns="http://www.w3.org/2000/svg" fill="none"
                                                                        viewBox="0 0 24 24" stroke-width="1.5"
                                                                        stroke="currentColor">
                                                                        <path stroke-linecap="round"
                                                                              stroke-linejoin="round"
                                                                              d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                                    </svg>

                                                                    <div>{{ team.name }}</div>
                                                                </div>
                                                            </DropdownLink>
                                                        </form>
                                                    </template>
                                                </template>
                                            </div>
                                        </template>
                                    </Dropdown>
                                </div>

                                <!-- Settings Dropdown -->
                                <div class="ms-3 relative">
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <button v-if="$page.props.jetstream.managesProfilePhotos"
                                                    class="flex border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                                <img class="size-8 rounded-full object-cover"
                                                     :src="$page.props.auth.user.profile_photo_url"
                                                     :alt="$page.props.auth.user.name">
                                            </button>

                                            <span v-else class="inline-flex rounded-md">
                                            <button type="button"
                                                    class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                                {{ $page.props.auth.user.name }}

                                                <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg"
                                                     fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                     stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                                                </svg>
                                            </button>
                                        </span>
                                        </template>

                                        <template #content>
                                            <!-- Account Management -->
                                            <div class="block px-4 py-2 text-xs text-gray-400">
                                                Manage Account
                                            </div>

                                            <DropdownLink :href="route('profile.show')">
                                                <span class="inline-flex items-center gap-2">
                                                    <ProfileOutlined class="text-sm opacity-90" />
                                                    Profile
                                                </span>
                                            </DropdownLink>

                                            <DropdownLink v-if="$page.props.jetstream.hasApiFeatures"
                                                          :href="route('api-tokens.index')">
                                                <span class="inline-flex items-center gap-2">
                                                    <KeyOutlined class="text-sm opacity-90" />
                                                    API Tokens
                                                </span>
                                            </DropdownLink>

                                            <div class="border-t border-gray-200 dark:border-gray-600"/>

                                            <!-- Authentication -->
                                            <form @submit.prevent="logout">
                                                <DropdownLink as="button">
                                                    <span class="inline-flex items-center gap-2">
                                                        <LogoutOutlined class="text-sm opacity-90" />
                                                        Log Out
                                                    </span>
                                                </DropdownLink>
                                            </form>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>

                            <!-- Mobile: notifications + menu -->
                            <div class="-me-2 flex items-center gap-1 sm:hidden">
                                <NotificationBell />
                                <button
                                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out"
                                    @click="showingNavigationDropdown = ! showingNavigationDropdown">
                                    <svg
                                        class="size-6"
                                        stroke="currentColor"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 6h16M4 12h16M4 18h16"
                                        />
                                        <path
                                            :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M6 18L18 6M6 6l12 12"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Responsive Navigation Menu -->
                    <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}"
                         class="sm:hidden">
                        <div class="pt-2 pb-3 space-y-1">
                            <ResponsiveNavLink v-if="can('dashboard.access')" :href="route('dashboard')" :active="route().current('dashboard')">
                                <span class="inline-flex items-center gap-2">
                                    <DashboardOutlined class="text-base opacity-90" />
                                    Dashboard
                                </span>
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                v-if="canAny(['beneficiaries.view', 'beneficiaries.manage'])"
                                :href="route('beneficiaries.index')"
                                :active="route().current('beneficiaries.*')"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <UserOutlined class="text-base opacity-90" />
                                    Beneficiaries
                                </span>
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                v-if="canAny(['projects.view', 'projects.manage'])"
                                :href="route('projects.index')"
                                :active="route().current('projects.*')"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <ProjectOutlined class="text-base opacity-90" />
                                    Projects
                                </span>
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                v-if="canAny(['trainings.view', 'trainings.manage'])"
                                :href="route('trainings.index')"
                                :active="route().current('trainings.*')"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <ReadOutlined class="text-base opacity-90" />
                                    Trainings
                                </span>
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                v-if="canAny(['assistance.view', 'assistance.manage'])"
                                :href="route('assistance-records.index')"
                                :active="route().current('assistance-records.*')"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <DollarOutlined class="text-base opacity-90" />
                                    Assistance Records
                                </span>
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                v-if="canAny(['groups.view', 'groups.manage'])"
                                :href="route('beneficiary-groups.index')"
                                :active="route().current('beneficiary-groups.*')"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <TeamOutlined class="text-base opacity-90" />
                                    Beneficiary groups
                                </span>
                            </ResponsiveNavLink>

                            <template v-if="showSystemMenu">
                                <div class="border-t border-gray-200 px-4 pb-1 pt-3 dark:border-gray-600">
                                    <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                        System
                                    </p>
                                </div>
                                <ResponsiveNavLink
                                    v-if="can('dashboard.access')"
                                    :href="route('monitoring.index')"
                                    :active="route().current('monitoring.*')"
                                >
                                    <span class="inline-flex items-center gap-2">
                                        <LineChartOutlined class="text-base opacity-90" />
                                        Monitoring
                                    </span>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    v-if="can('reports.export')"
                                    :href="route('reports.index')"
                                    :active="route().current('reports.*')"
                                >
                                    <span class="inline-flex items-center gap-2">
                                        <FileTextOutlined class="text-base opacity-90" />
                                        Reports
                                    </span>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    v-if="can('audit_logs.view')"
                                    :href="route('audit-logs.index')"
                                    :active="route().current('audit-logs.*')"
                                >
                                    <span class="inline-flex items-center gap-2">
                                        <AuditOutlined class="text-base opacity-90" />
                                        Audit logs
                                    </span>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    v-if="can('users.manage')"
                                    :href="route('users.index')"
                                    :active="route().current('users.*')"
                                >
                                    <span class="inline-flex items-center gap-2">
                                        <ContactsOutlined class="text-base opacity-90" />
                                        Users
                                    </span>
                                </ResponsiveNavLink>
                                <ResponsiveNavLink
                                    v-if="can('roles.manage')"
                                    :href="route('roles.index')"
                                    :active="route().current('roles.*')"
                                >
                                    <span class="inline-flex items-center gap-2">
                                        <SafetyOutlined class="text-base opacity-90" />
                                        Roles
                                    </span>
                                </ResponsiveNavLink>
                            </template>
                        </div>

                        <!-- Responsive Settings Options -->
                        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                            <div class="flex items-center px-4">
                                <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 me-3">
                                    <img class="size-10 rounded-full object-cover"
                                         :src="$page.props.auth.user.profile_photo_url"
                                         :alt="$page.props.auth.user.name">
                                </div>

                                <div>
                                    <div class="font-medium text-base text-gray-800 dark:text-gray-200">
                                        {{ $page.props.auth.user.name }}
                                    </div>
                                    <div class="font-medium text-sm text-gray-500">
                                        {{ $page.props.auth.user.email }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 space-y-1">
                                <ResponsiveNavLink :href="route('profile.show')"
                                                   :active="route().current('profile.show')">
                                    <span class="inline-flex items-center gap-2">
                                        <ProfileOutlined class="text-base opacity-90" />
                                        Profile
                                    </span>
                                </ResponsiveNavLink>

                                <ResponsiveNavLink v-if="$page.props.jetstream.hasApiFeatures"
                                                   :href="route('api-tokens.index')"
                                                   :active="route().current('api-tokens.index')">
                                    <span class="inline-flex items-center gap-2">
                                        <KeyOutlined class="text-base opacity-90" />
                                        API Tokens
                                    </span>
                                </ResponsiveNavLink>

                                <!-- Authentication -->
                                <form method="POST" @submit.prevent="logout">
                                    <ResponsiveNavLink as="button">
                                        <span class="inline-flex items-center gap-2">
                                            <LogoutOutlined class="text-base opacity-90" />
                                            Log Out
                                        </span>
                                    </ResponsiveNavLink>
                                </form>

                                <!-- Team Management -->
                                <template v-if="$page.props.jetstream.hasTeamFeatures">
                                    <div class="border-t border-gray-200 dark:border-gray-600"/>

                                    <div class="block px-4 py-2 text-xs text-gray-400">
                                        Manage Team
                                    </div>

                                    <!-- Team Settings -->
                                    <ResponsiveNavLink :href="route('teams.show', $page.props.auth.user.current_team)"
                                                       :active="route().current('teams.show')">
                                        Team Settings
                                    </ResponsiveNavLink>

                                    <ResponsiveNavLink v-if="$page.props.jetstream.canCreateTeams"
                                                       :href="route('teams.create')"
                                                       :active="route().current('teams.create')">
                                        Create New Team
                                    </ResponsiveNavLink>

                                    <!-- Team Switcher -->
                                    <template v-if="$page.props.auth.user.all_teams.length > 1">
                                        <div class="border-t border-gray-200 dark:border-gray-600"/>

                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            Switch Teams
                                        </div>

                                        <template v-for="team in $page.props.auth.user.all_teams" :key="team.id">
                                            <form @submit.prevent="switchToTeam(team)">
                                                <ResponsiveNavLink as="button">
                                                    <div class="flex items-center">
                                                        <svg v-if="team.id == $page.props.auth.user.current_team_id"
                                                             class="me-2 size-5 text-green-400"
                                                             xmlns="http://www.w3.org/2000/svg" fill="none"
                                                             viewBox="0 0 24 24" stroke-width="1.5"
                                                             stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                  d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                        <div>{{ team.name }}</div>
                                                    </div>
                                                </ResponsiveNavLink>
                                            </form>
                                        </template>
                                    </template>
                                </template>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Page Heading -->
                <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto min-w-0 py-5 px-3 sm:py-6 sm:px-6 lg:px-8">
                        <div class="min-w-0">
                            <slot name="header"/>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="min-w-0">
                    <slot/>
                </main>
            </div>
        </ConfigProvider>
    </div>
</template>
