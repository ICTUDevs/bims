<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import {
    DollarOutlined,
    ProjectOutlined,
    ReadOutlined,
    TeamOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';
import { formatDate } from '@/composables/useDateFormat';

interface ProjectOption {
    id: string;
    project_name: string;
}

type TabKey = 'beneficiaries' | 'projects' | 'trainings' | 'assistance' | 'groups';

const props = defineProps<{
    activeTab: TabKey;
    search: string;
    access: Record<TabKey, boolean>;
    projectsList: ProjectOption[];
    beneficiaries?: {
        stats: { total: number; active: number; inactive: number; male: number; female: number };
        topBarangays: { barangay: string; count: number }[];
        recent: { id: string; code: string; name: string; barangay: string; type: string; is_active: boolean; updated_at: string }[];
    };
    projects?: {
        stats: { total: number; active: number; with_beneficiaries: number; total_enrolled: number };
        recent: { id: string; code: string; name: string; status: string; beneficiaries_count: number; date_started: string; date_ended: string | null }[];
    };
    trainings?: {
        stats: { total: number; this_year: number; total_participants: number; upcoming: number };
        recent: { id: string; title: string; type: string; facilitator: string; date_conducted: string; participants: number; project: string | null }[];
    };
    assistance?: {
        stats: { total: number; total_amount: number; individual: number; group: number; this_year_amount: number };
        recent: { id: string; recipient: string; recipient_type: string; assistance_type: string; amount: number | null; date_released: string; project: string | null }[];
    };
    groups?: {
        stats: { total: number; total_members: number; male_members: number; female_members: number };
        recent: { id: string; name: string; type: string; total_members: number; date_organized: string | null }[];
    };
}>();

const activeTab = ref<TabKey>(props.activeTab);
const search = ref(props.search);
const filterIsActive = ref<string | undefined>(undefined);
const filterProjectId = ref<string | undefined>(undefined);
const filterRecipientType = ref<string | undefined>(undefined);

const beneficiaryColumns = [
    { title: 'Code', dataIndex: 'code', key: 'code', width: 110 },
    { title: 'Name', dataIndex: 'name', key: 'name' },
    { title: 'Barangay', dataIndex: 'barangay', key: 'barangay' },
    { title: 'Type', dataIndex: 'type', key: 'type', width: 120 },
    { title: 'Status', key: 'status', width: 90 },
    { title: 'Updated', key: 'updated_at', width: 130 },
];

const projectColumns = [
    { title: 'Code', dataIndex: 'code', key: 'code', width: 100 },
    { title: 'Project', dataIndex: 'name', key: 'name' },
    { title: 'Status', key: 'status', width: 110 },
    { title: 'Enrolled', dataIndex: 'beneficiaries_count', key: 'enrolled', width: 90, align: 'center' as const },
    { title: 'Started', key: 'date_started', width: 120 },
    { title: 'Ended', key: 'date_ended', width: 120 },
];

const trainingColumns = [
    { title: 'Title', dataIndex: 'title', key: 'title' },
    { title: 'Type', dataIndex: 'type', key: 'type', width: 120 },
    { title: 'Facilitator', dataIndex: 'facilitator', key: 'facilitator' },
    { title: 'Date', key: 'date_conducted', width: 120 },
    { title: 'Participants', dataIndex: 'participants', key: 'participants', width: 100, align: 'center' as const },
    { title: 'Project', dataIndex: 'project', key: 'project', width: 150 },
];

const assistanceColumns = [
    { title: 'Recipient', dataIndex: 'recipient', key: 'recipient' },
    { title: 'Type', key: 'recipient_type', width: 100 },
    { title: 'Assistance', dataIndex: 'assistance_type', key: 'assistance_type' },
    { title: 'Amount', key: 'amount', width: 120, align: 'right' as const },
    { title: 'Released', key: 'date_released', width: 120 },
    { title: 'Project', dataIndex: 'project', key: 'project', width: 150 },
];

const groupColumns = [
    { title: 'Group', dataIndex: 'name', key: 'name' },
    { title: 'Type', dataIndex: 'type', key: 'type', width: 140 },
    { title: 'Members', dataIndex: 'total_members', key: 'members', width: 90, align: 'center' as const },
    { title: 'Organized', key: 'date_organized', width: 120 },
];

const formatPeso = (val: number | null) => {
    if (val == null) return '—';
    return '₱ ' + val.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};

const tabItems = computed(() => {
    const items: { key: TabKey; label: string; icon: typeof UserOutlined }[] = [];
    if (props.access.beneficiaries) items.push({ key: 'beneficiaries', label: 'Beneficiaries', icon: UserOutlined });
    if (props.access.projects) items.push({ key: 'projects', label: 'Projects', icon: ProjectOutlined });
    if (props.access.trainings) items.push({ key: 'trainings', label: 'Trainings', icon: ReadOutlined });
    if (props.access.assistance) items.push({ key: 'assistance', label: 'Assistance', icon: DollarOutlined });
    if (props.access.groups) items.push({ key: 'groups', label: 'Groups', icon: TeamOutlined });
    return items;
});

const moduleRoutes: Record<TabKey, string> = {
    beneficiaries: 'beneficiaries.index',
    projects: 'projects.index',
    trainings: 'trainings.index',
    assistance: 'assistance-records.index',
    groups: 'beneficiary-groups.index',
};

const applyFilters = () => {
    const params: Record<string, string | undefined> = {
        tab: activeTab.value,
        search: search.value || undefined,
    };
    if (activeTab.value === 'beneficiaries' || activeTab.value === 'projects') {
        params.is_active = filterIsActive.value;
    }
    if (activeTab.value === 'trainings' || activeTab.value === 'assistance') {
        params.project_id = filterProjectId.value;
    }
    if (activeTab.value === 'assistance') {
        params.recipient_type = filterRecipientType.value;
    }
    router.get(route('monitoring.index'), params, { preserveState: true, replace: true });
};

let searchTimeout: ReturnType<typeof setTimeout>;
watch(search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 400);
});

watch(activeTab, () => {
    filterIsActive.value = undefined;
    filterProjectId.value = undefined;
    filterRecipientType.value = undefined;
    search.value = '';
    applyFilters();
});

watch([filterIsActive, filterProjectId, filterRecipientType], applyFilters);
</script>

<template>
    <AppLayout title="Monitoring">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Monitoring</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8">
                <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">
                    Live overview of program data across all modules. Use filters to narrow records, or open a module for full management.
                </p>

                <div class="bg-white dark:bg-gray-800 shadow-xl sm:rounded-lg overflow-hidden p-3 sm:p-4">
                    <a-tabs v-model:activeKey="activeTab">
                        <a-tab-pane v-for="tab in tabItems" :key="tab.key" :tab="tab.label">
                            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                                <div class="flex flex-col gap-2 sm:flex-row sm:flex-wrap sm:items-center">
                                    <a-input-search
                                        v-model:value="search"
                                        class="w-full sm:!w-[260px]"
                                        placeholder="Search current module..."
                                        allow-clear
                                    />
                                    <a-select
                                        v-if="tab.key === 'beneficiaries' || tab.key === 'projects'"
                                        v-model:value="filterIsActive"
                                        :placeholder="tab.key === 'beneficiaries' ? 'All statuses' : 'All projects'"
                                        class="w-full sm:!w-[160px]"
                                        allow-clear
                                    >
                                        <a-select-option value="1">{{ tab.key === 'beneficiaries' ? 'Active' : 'Currently active' }}</a-select-option>
                                        <a-select-option value="0">{{ tab.key === 'beneficiaries' ? 'Inactive' : 'Not active' }}</a-select-option>
                                    </a-select>
                                    <a-select
                                        v-if="tab.key === 'trainings' || tab.key === 'assistance'"
                                        v-model:value="filterProjectId"
                                        placeholder="All projects"
                                        class="w-full sm:!w-[200px]"
                                        allow-clear
                                        show-search
                                        option-filter-prop="label"
                                    >
                                        <a-select-option
                                            v-for="p in projectsList"
                                            :key="p.id"
                                            :value="p.id"
                                            :label="p.project_name"
                                        >
                                            {{ p.project_name }}
                                        </a-select-option>
                                    </a-select>
                                    <a-select
                                        v-if="tab.key === 'assistance'"
                                        v-model:value="filterRecipientType"
                                        placeholder="All recipients"
                                        class="w-full sm:!w-[150px]"
                                        allow-clear
                                    >
                                        <a-select-option value="individual">Individual</a-select-option>
                                        <a-select-option value="group">Group</a-select-option>
                                    </a-select>
                                </div>
                                <Link
                                    :href="route(moduleRoutes[tab.key])"
                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400"
                                >
                                    Open full module →
                                </Link>
                            </div>

                            <template v-if="tab.key === 'beneficiaries' && beneficiaries">
                                <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ beneficiaries.stats.total }}</div>
                                        <div class="text-xs text-gray-500">Total</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold text-green-600">{{ beneficiaries.stats.active }}</div>
                                        <div class="text-xs text-gray-500">Active</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold text-red-500">{{ beneficiaries.stats.inactive }}</div>
                                        <div class="text-xs text-gray-500">Inactive</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold">{{ beneficiaries.stats.male }}</div>
                                        <div class="text-xs text-gray-500">Male</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold">{{ beneficiaries.stats.female }}</div>
                                        <div class="text-xs text-gray-500">Female</div>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <a-table
                                        :data-source="beneficiaries.recent"
                                        :columns="beneficiaryColumns"
                                        :pagination="false"
                                        row-key="id"
                                        size="small"
                                        :scroll="{ x: 'max-content' }"
                                    >
                                        <template #bodyCell="{ column, record }">
                                            <template v-if="column.key === 'status'">
                                                <a-tag :color="record.is_active ? 'green' : 'red'">{{ record.is_active ? 'Active' : 'Inactive' }}</a-tag>
                                            </template>
                                            <template v-else-if="column.key === 'updated_at'">{{ formatDate(record.updated_at) }}</template>
                                        </template>
                                    </a-table>
                                </div>
                            </template>

                            <template v-else-if="tab.key === 'projects' && projects">
                                <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ projects.stats.total }}</div>
                                        <div class="text-xs text-gray-500">Total projects</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold text-green-600">{{ projects.stats.active }}</div>
                                        <div class="text-xs text-gray-500">Active now</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold">{{ projects.stats.with_beneficiaries }}</div>
                                        <div class="text-xs text-gray-500">With enrollees</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold">{{ projects.stats.total_enrolled }}</div>
                                        <div class="text-xs text-gray-500">Total enrolled</div>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <a-table
                                        :data-source="projects.recent"
                                        :columns="projectColumns"
                                        :pagination="false"
                                        row-key="id"
                                        size="small"
                                        :scroll="{ x: 'max-content' }"
                                    >
                                        <template #bodyCell="{ column, record }">
                                            <template v-if="column.key === 'status'"><a-tag>{{ record.status }}</a-tag></template>
                                            <template v-else-if="column.key === 'date_started'">{{ formatDate(record.date_started) }}</template>
                                            <template v-else-if="column.key === 'date_ended'">{{ record.date_ended ? formatDate(record.date_ended) : '—' }}</template>
                                        </template>
                                    </a-table>
                                </div>
                            </template>

                            <template v-else-if="tab.key === 'trainings' && trainings">
                                <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ trainings.stats.total }}</div>
                                        <div class="text-xs text-gray-500">Total trainings</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold">{{ trainings.stats.this_year }}</div>
                                        <div class="text-xs text-gray-500">This year</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold">{{ trainings.stats.total_participants }}</div>
                                        <div class="text-xs text-gray-500">Participant links</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold text-cyan-600">{{ trainings.stats.upcoming }}</div>
                                        <div class="text-xs text-gray-500">Upcoming / today+</div>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <a-table
                                        :data-source="trainings.recent"
                                        :columns="trainingColumns"
                                        :pagination="false"
                                        row-key="id"
                                        size="small"
                                        :scroll="{ x: 'max-content' }"
                                    >
                                        <template #bodyCell="{ column, record }">
                                            <template v-if="column.key === 'date_conducted'">{{ formatDate(record.date_conducted) }}</template>
                                            <template v-else-if="column.key === 'project'">{{ record.project ?? '—' }}</template>
                                        </template>
                                    </a-table>
                                </div>
                            </template>

                            <template v-else-if="tab.key === 'assistance' && assistance">
                                <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ assistance.stats.total }}</div>
                                        <div class="text-xs text-gray-500">Records</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3 sm:col-span-2">
                                        <div class="text-xl font-bold text-green-600">{{ formatPeso(assistance.stats.total_amount) }}</div>
                                        <div class="text-xs text-gray-500">Total released</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold">{{ assistance.stats.individual }}</div>
                                        <div class="text-xs text-gray-500">Individual</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold">{{ assistance.stats.group }}</div>
                                        <div class="text-xs text-gray-500">Group</div>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <a-table
                                        :data-source="assistance.recent"
                                        :columns="assistanceColumns"
                                        :pagination="false"
                                        row-key="id"
                                        size="small"
                                        :scroll="{ x: 'max-content' }"
                                    >
                                        <template #bodyCell="{ column, record }">
                                            <template v-if="column.key === 'recipient_type'">
                                                <a-tag>{{ record.recipient_type }}</a-tag>
                                            </template>
                                            <template v-else-if="column.key === 'amount'">{{ formatPeso(record.amount) }}</template>
                                            <template v-else-if="column.key === 'date_released'">{{ formatDate(record.date_released) }}</template>
                                            <template v-else-if="column.key === 'project'">{{ record.project ?? '—' }}</template>
                                        </template>
                                    </a-table>
                                </div>
                            </template>

                            <template v-else-if="tab.key === 'groups' && groups">
                                <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ groups.stats.total }}</div>
                                        <div class="text-xs text-gray-500">Groups</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold">{{ groups.stats.total_members }}</div>
                                        <div class="text-xs text-gray-500">Total members</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold">{{ groups.stats.male_members }}</div>
                                        <div class="text-xs text-gray-500">Male</div>
                                    </div>
                                    <div class="rounded-lg border border-gray-100 dark:border-gray-700 p-3">
                                        <div class="text-2xl font-bold">{{ groups.stats.female_members }}</div>
                                        <div class="text-xs text-gray-500">Female</div>
                                    </div>
                                </div>
                                <div class="overflow-x-auto">
                                    <a-table
                                        :data-source="groups.recent"
                                        :columns="groupColumns"
                                        :pagination="false"
                                        row-key="id"
                                        size="small"
                                        :scroll="{ x: 'max-content' }"
                                    >
                                        <template #bodyCell="{ column, record }">
                                            <template v-if="column.key === 'date_organized'">
                                                {{ record.date_organized ? formatDate(record.date_organized) : '—' }}
                                            </template>
                                        </template>
                                    </a-table>
                                </div>
                            </template>
                        </a-tab-pane>
                    </a-tabs>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
