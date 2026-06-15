<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';
import {
    AuditOutlined,
    DollarOutlined,
    FileExcelOutlined,
    FilePdfOutlined,
    ProjectOutlined,
    ReadOutlined,
    TeamOutlined,
    UserOutlined,
} from '@ant-design/icons-vue';

interface Project {
    id: string;
    project_name: string;
}

const props = defineProps<{
    projects: Project[];
    modelTypes: string[];
    canViewAuditLogs: boolean;
    counts: {
        beneficiaries: number;
        projects: number;
        trainings: number;
        assistance: number;
        groups: number;
        audit_logs: number;
    };
}>();

type ReportKey =
    | 'beneficiaries'
    | 'projects'
    | 'trainings'
    | 'assistance'
    | 'groups'
    | 'audit_logs';

const selectedReport = ref<ReportKey>('beneficiaries');

const filters = ref({
    search: '',
    is_active: undefined as string | undefined,
    project_id: undefined as string | undefined,
    recipient_type: undefined as string | undefined,
    action: undefined as string | undefined,
    model_type: undefined as string | undefined,
    date_from: undefined as string | undefined,
    date_to: undefined as string | undefined,
});

const reportTypes = computed(() => {
    const types = [
        {
            key: 'beneficiaries' as const,
            label: 'Beneficiaries',
            description: 'Registered beneficiaries with demographics and status.',
            icon: UserOutlined,
            count: props.counts.beneficiaries,
            pdfRoute: 'reports.beneficiaries.pdf',
            excelRoute: 'reports.beneficiaries.excel',
        },
        {
            key: 'projects' as const,
            label: 'Projects',
            description: 'Livelihood projects, timelines, and enrollment counts.',
            icon: ProjectOutlined,
            count: props.counts.projects,
            pdfRoute: 'reports.projects.pdf',
            excelRoute: 'reports.projects.excel',
        },
        {
            key: 'trainings' as const,
            label: 'Trainings',
            description: 'Training sessions, facilitators, and participant counts.',
            icon: ReadOutlined,
            count: props.counts.trainings,
            pdfRoute: 'reports.trainings.pdf',
            excelRoute: 'reports.trainings.excel',
        },
        {
            key: 'assistance' as const,
            label: 'Assistance Records',
            description: 'Released assistance by individual, group, and project.',
            icon: DollarOutlined,
            count: props.counts.assistance,
            pdfRoute: 'reports.assistance.pdf',
            excelRoute: 'reports.assistance.excel',
        },
        {
            key: 'groups' as const,
            label: 'Beneficiary Groups',
            description: 'Organized groups with membership breakdown.',
            icon: TeamOutlined,
            count: props.counts.groups,
            pdfRoute: 'reports.groups.pdf',
            excelRoute: 'reports.groups.excel',
        },
    ];

    if (props.canViewAuditLogs) {
        types.push({
            key: 'audit_logs' as const,
            label: 'Audit Logs',
            description: 'System activity trail for accountability and review.',
            icon: AuditOutlined,
            count: props.counts.audit_logs,
            pdfRoute: 'reports.audit-logs.pdf',
            excelRoute: 'reports.audit-logs.excel',
        });
    }

    return types;
});

const activeReport = computed(() => reportTypes.value.find((r) => r.key === selectedReport.value)!);

const shortModelType = (type: string) => type.split('\\').pop() ?? type;

const queryParams = computed(() => {
    const p: Record<string, string> = {};
    const f = filters.value;

    if (f.search) p.search = f.search;
    if (f.is_active !== undefined && f.is_active !== '') p.is_active = f.is_active;
    if (f.project_id) p.project_id = f.project_id;
    if (f.recipient_type) p.recipient_type = f.recipient_type;
    if (f.action) p.action = f.action;
    if (f.model_type) p.model_type = f.model_type;
    if (f.date_from) p.date_from = f.date_from;
    if (f.date_to) p.date_to = f.date_to;

    return p;
});

const buildUrl = (routeName: string) => {
    const url = new URL(route(routeName), window.location.origin);
    Object.entries(queryParams.value).forEach(([k, v]) => url.searchParams.set(k, v));
    return url.toString();
};

const resetFilters = () => {
    filters.value = {
        search: '',
        is_active: undefined,
        project_id: undefined,
        recipient_type: undefined,
        action: undefined,
        model_type: undefined,
        date_from: undefined,
        date_to: undefined,
    };
};

const selectReport = (key: ReportKey) => {
    selectedReport.value = key;
    resetFilters();
};
</script>

<template>
    <AppLayout title="Reports">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Reports</h2>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto px-3 sm:px-6 lg:px-8 space-y-6">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Generate PDF or Excel exports for any module. Apply filters before downloading to narrow the dataset.
                </p>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                    <!-- Report type picker -->
                    <div class="lg:col-span-4 space-y-2">
                        <button
                            v-for="report in reportTypes"
                            :key="report.key"
                            type="button"
                            class="w-full rounded-lg border p-4 text-left transition"
                            :class="selectedReport === report.key
                                ? 'border-indigo-500 bg-indigo-50 dark:border-indigo-400 dark:bg-indigo-950/40'
                                : 'border-gray-200 bg-white hover:border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:hover:border-gray-600'"
                            @click="selectReport(report.key)"
                        >
                            <div class="flex items-start gap-3">
                                <component
                                    :is="report.icon"
                                    class="mt-0.5 shrink-0 text-lg"
                                    :class="selectedReport === report.key ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400'"
                                />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ report.label }}</span>
                                        <a-tag :color="selectedReport === report.key ? 'blue' : 'default'">{{ report.count }}</a-tag>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 line-clamp-2">{{ report.description }}</p>
                                </div>
                            </div>
                        </button>
                    </div>

                    <!-- Filters + download -->
                    <div class="lg:col-span-8">
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-4 sm:p-6">
                            <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ activeReport.label }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ activeReport.description }}</p>
                                </div>
                                <a-button size="small" @click="resetFilters">Clear filters</a-button>
                            </div>

                            <a-form layout="vertical" class="mb-6">
                                <div class="grid grid-cols-1 gap-x-4 sm:grid-cols-2">
                                    <a-form-item
                                        v-if="['beneficiaries', 'projects', 'trainings', 'assistance', 'groups', 'audit_logs'].includes(selectedReport)"
                                        label="Search"
                                        class="sm:col-span-2"
                                    >
                                        <a-input-search
                                            v-model:value="filters.search"
                                            :placeholder="selectedReport === 'audit_logs'
                                                ? 'User, action, model, IP...'
                                                : 'Keywords to filter records...'"
                                            allow-clear
                                        />
                                    </a-form-item>

                                    <a-form-item v-if="selectedReport === 'beneficiaries'" label="Status">
                                        <a-select v-model:value="filters.is_active" placeholder="All statuses" allow-clear>
                                            <a-select-option value="1">Active only</a-select-option>
                                            <a-select-option value="0">Inactive only</a-select-option>
                                        </a-select>
                                    </a-form-item>

                                    <a-form-item v-if="selectedReport === 'projects'" label="Project status">
                                        <a-select v-model:value="filters.is_active" placeholder="All projects" allow-clear>
                                            <a-select-option value="1">Currently active</a-select-option>
                                            <a-select-option value="0">Not active</a-select-option>
                                        </a-select>
                                    </a-form-item>

                                    <a-form-item v-if="['trainings', 'assistance'].includes(selectedReport)" label="Project">
                                        <a-select v-model:value="filters.project_id" placeholder="All projects" allow-clear show-search option-filter-prop="label">
                                            <a-select-option
                                                v-for="p in projects"
                                                :key="p.id"
                                                :value="p.id"
                                                :label="p.project_name"
                                            >
                                                {{ p.project_name }}
                                            </a-select-option>
                                        </a-select>
                                    </a-form-item>

                                    <a-form-item v-if="selectedReport === 'assistance'" label="Recipient type">
                                        <a-select v-model:value="filters.recipient_type" placeholder="All types" allow-clear>
                                            <a-select-option value="individual">Individual</a-select-option>
                                            <a-select-option value="group">Group</a-select-option>
                                        </a-select>
                                    </a-form-item>

                                    <template v-if="selectedReport === 'audit_logs'">
                                        <a-form-item label="Action">
                                            <a-select v-model:value="filters.action" placeholder="All actions" allow-clear>
                                                <a-select-option value="created">Created</a-select-option>
                                                <a-select-option value="updated">Updated</a-select-option>
                                                <a-select-option value="deleted">Deleted</a-select-option>
                                                <a-select-option value="member_added">Member added</a-select-option>
                                                <a-select-option value="member_removed">Member removed</a-select-option>
                                                <a-select-option value="permissions_updated">Permissions updated</a-select-option>
                                                <a-select-option value="roles_updated">Roles updated</a-select-option>
                                            </a-select>
                                        </a-form-item>
                                        <a-form-item label="Model">
                                            <a-select v-model:value="filters.model_type" placeholder="All models" allow-clear>
                                                <a-select-option v-for="m in modelTypes" :key="m" :value="m">
                                                    {{ shortModelType(m) }}
                                                </a-select-option>
                                            </a-select>
                                        </a-form-item>
                                        <a-form-item label="Date from">
                                            <a-date-picker
                                                v-model:value="filters.date_from"
                                                value-format="YYYY-MM-DD"
                                                format="MMM D, YYYY"
                                                class="w-full"
                                                allow-clear
                                            />
                                        </a-form-item>
                                        <a-form-item label="Date to">
                                            <a-date-picker
                                                v-model:value="filters.date_to"
                                                value-format="YYYY-MM-DD"
                                                format="MMM D, YYYY"
                                                class="w-full"
                                                allow-clear
                                            />
                                        </a-form-item>
                                    </template>
                                </div>
                            </a-form>

                            <div class="rounded-lg border border-dashed border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 p-5">
                                <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                                    Download up to <strong>{{ activeReport.count }}</strong> records matching your filters.
                                </p>
                                <a-space wrap>
                                    <a :href="buildUrl(activeReport.pdfRoute)" target="_blank" rel="noopener" style="text-decoration: none">
                                        <a-button type="primary" danger size="large">
                                            <template #icon><FilePdfOutlined /></template>
                                            Download PDF
                                        </a-button>
                                    </a>
                                    <a :href="buildUrl(activeReport.excelRoute)" target="_blank" rel="noopener" style="text-decoration: none">
                                        <a-button size="large" style="color: #166534; border-color: #166534">
                                            <template #icon><FileExcelOutlined /></template>
                                            Download Excel
                                        </a-button>
                                    </a>
                                </a-space>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
