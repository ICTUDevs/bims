<?php

namespace App\Http\Controllers;

use App\Exports\AssistanceRecordsExport;
use App\Exports\AuditLogsExport;
use App\Exports\BeneficiariesExport;
use App\Exports\BeneficiaryGroupsExport;
use App\Exports\ProjectsExport;
use App\Exports\TrainingsExport;
use App\Models\AssistanceRecord;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\BeneficiaryGroup;
use App\Models\Project;
use App\Models\Training;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $modelTypes = AuditLog::select('model_type')->distinct()->pluck('model_type');

        return inertia('reports.index', [
            'projects' => Project::select('id', 'project_name')->orderBy('project_name')->get(),
            'modelTypes' => $modelTypes,
            'canViewAuditLogs' => $request->user()?->can('audit_logs.view') ?? false,
            'counts' => [
                'beneficiaries' => Beneficiary::count(),
                'projects' => Project::count(),
                'trainings' => Training::count(),
                'assistance' => AssistanceRecord::count(),
                'groups' => BeneficiaryGroup::count(),
                'audit_logs' => AuditLog::count(),
            ],
        ]);
    }

    /* ─── Beneficiaries ──────────────────────────────────────── */

    public function beneficiariesPdf(Request $request)
    {
        $query = Beneficiary::query();
        $this->applyBeneficiaryFilters($query, $request);
        $beneficiaries = $query->orderBy('last_name')->get();

        $data = [
            'beneficiaries' => $beneficiaries,
            'total' => $beneficiaries->count(),
            'active' => $beneficiaries->where('is_active', true)->count(),
            'inactive' => $beneficiaries->where('is_active', false)->count(),
            'male' => $beneficiaries->where('sex', 'Male')->count(),
            'female' => $beneficiaries->where('sex', 'Female')->count(),
            'filters' => $this->filterSummary($request, ['search', 'is_active']),
        ];

        $pdf = Pdf::loadView('reports.beneficiaries', $data)->setPaper('a4', 'landscape');

        return $pdf->download('beneficiaries-'.now()->format('Ymd').'.pdf');
    }

    public function beneficiariesExcel(Request $request)
    {
        return Excel::download(new BeneficiariesExport($request), 'beneficiaries-'.now()->format('Ymd').'.xlsx');
    }

    private function applyBeneficiaryFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($q2) => $q2->where('last_name', 'like', "%$q%")
                ->orWhere('first_name', 'like', "%$q%")
                ->orWhere('beneficiary_code', 'like', "%$q%"));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
    }

    /* ─── Projects ───────────────────────────────────────────── */

    public function projectsPdf(Request $request)
    {
        $query = Project::query()->withCount('beneficiaries');
        $this->applyProjectFilters($query, $request);
        $projects = $query->latest()->get();

        $pdf = Pdf::loadView('reports.projects', [
            'projects' => $projects,
            'total' => $projects->count(),
            'filters' => $this->filterSummary($request, ['search', 'is_active']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('projects-'.now()->format('Ymd').'.pdf');
    }

    public function projectsExcel(Request $request)
    {
        return Excel::download(new ProjectsExport($request), 'projects-'.now()->format('Ymd').'.xlsx');
    }

    private function applyProjectFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($q2) => $q2->where('project_name', 'like', "%$q%")->orWhere('project_code', 'like', "%$q%"));
        }
        if ($request->filled('is_active')) {
            if ($request->boolean('is_active')) {
                $query->currentlyActiveByDates();
            } else {
                $query->notCurrentlyActiveByDates();
            }
        }
    }

    /* ─── Trainings ──────────────────────────────────────────── */

    public function trainingsPdf(Request $request)
    {
        $query = Training::query()->with('project')->withCount('beneficiaries');
        $this->applyTrainingFilters($query, $request);
        $trainings = $query->latest()->get();

        $pdf = Pdf::loadView('reports.trainings', [
            'trainings' => $trainings,
            'total' => $trainings->count(),
            'filters' => $this->filterSummary($request, ['search', 'project_id']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('trainings-'.now()->format('Ymd').'.pdf');
    }

    public function trainingsExcel(Request $request)
    {
        return Excel::download(new TrainingsExport($request), 'trainings-'.now()->format('Ymd').'.xlsx');
    }

    private function applyTrainingFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($q2) => $q2->where('training_title', 'like', "%$q%")->orWhere('facilitator', 'like', "%$q%"));
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
    }

    /* ─── Assistance Records ─────────────────────────────────── */

    public function assistancePdf(Request $request)
    {
        $query = AssistanceRecord::query()->with(['beneficiary', 'beneficiaryGroup', 'project']);
        $this->applyAssistanceFilters($query, $request);
        $records = $query->latest()->get();

        $pdf = Pdf::loadView('reports.assistance-records', [
            'records' => $records,
            'total' => $records->count(),
            'totalAmount' => $records->sum('amount'),
            'individualCount' => $records->where('recipient_type', 'individual')->count(),
            'groupCount' => $records->where('recipient_type', 'group')->count(),
            'filters' => $this->filterSummary($request, ['search', 'project_id', 'recipient_type']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('assistance-records-'.now()->format('Ymd').'.pdf');
    }

    public function assistanceExcel(Request $request)
    {
        return Excel::download(new AssistanceRecordsExport($request), 'assistance-records-'.now()->format('Ymd').'.xlsx');
    }

    private function applyAssistanceFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($q2) => $q2
                ->whereHas('beneficiary', fn ($q3) => $q3->where('last_name', 'like', "%$q%")->orWhere('first_name', 'like', "%$q%"))
                ->orWhereHas('beneficiaryGroup', fn ($q3) => $q3->where('group_name', 'like', "%$q%")));
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('recipient_type')) {
            $query->where('recipient_type', $request->recipient_type);
        }
    }

    /* ─── Beneficiary Groups ─────────────────────────────────── */

    public function groupsPdf(Request $request)
    {
        $query = BeneficiaryGroup::query();
        $this->applyGroupFilters($query, $request);
        $groups = $query->orderBy('group_name')->get();

        $pdf = Pdf::loadView('reports.beneficiary-groups', [
            'groups' => $groups,
            'total' => $groups->count(),
            'totalMembers' => $groups->sum('total_members'),
            'filters' => $this->filterSummary($request, ['search']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('beneficiary-groups-'.now()->format('Ymd').'.pdf');
    }

    public function groupsExcel(Request $request)
    {
        return Excel::download(new BeneficiaryGroupsExport($request), 'beneficiary-groups-'.now()->format('Ymd').'.xlsx');
    }

    private function applyGroupFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(fn ($q2) => $q2->where('group_name', 'like', "%$q%")->orWhere('group_type', 'like', "%$q%"));
        }
    }

    /* ─── Audit Logs ─────────────────────────────────────────── */

    public function auditLogsPdf(Request $request)
    {
        $this->authorizeAuditLogs($request);

        $query = AuditLog::query()->with(['user', 'beneficiary'])->latest();
        $this->applyAuditLogFilters($query, $request);
        $logs = $query->get();

        $pdf = Pdf::loadView('reports.audit-logs', [
            'logs' => $logs,
            'total' => $logs->count(),
            'filters' => $this->filterSummary($request, ['search', 'action', 'model_type', 'date_from', 'date_to']),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('audit-logs-'.now()->format('Ymd').'.pdf');
    }

    public function auditLogsExcel(Request $request)
    {
        $this->authorizeAuditLogs($request);

        return Excel::download(new AuditLogsExport($request), 'audit-logs-'.now()->format('Ymd').'.xlsx');
    }

    private function applyAuditLogFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($q2) use ($q) {
                $q2->where('action', 'like', "%$q%")
                    ->orWhere('model_type', 'like', "%$q%")
                    ->orWhere('ip_address', 'like', "%$q%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$q%"));
            });
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
    }

    private function authorizeAuditLogs(Request $request): void
    {
        abort_unless($request->user()?->can('audit_logs.view'), 403);
    }

    /* ─── Helpers ────────────────────────────────────────────── */

    private function filterSummary(Request $request, array $keys): string
    {
        $parts = [];
        foreach ($keys as $key) {
            if ($request->filled($key)) {
                $parts[] = ucfirst(str_replace('_', ' ', $key)).': '.$request->input($key);
            }
        }

        return implode(', ', $parts);
    }
}
