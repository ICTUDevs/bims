<?php

namespace App\Http\Controllers;

use App\Models\AssistanceRecord;
use App\Models\Beneficiary;
use App\Models\BeneficiaryGroup;
use App\Models\Project;
use App\Models\Training;
use App\Support\PermissionChecker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MonitoringController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $tab = $request->get('tab', 'beneficiaries');
        $search = $request->get('search', '');

        $access = [
            'beneficiaries' => PermissionChecker::allows($user, 'beneficiaries.view', 'beneficiaries.manage'),
            'projects' => PermissionChecker::allows($user, 'projects.view', 'projects.manage'),
            'trainings' => PermissionChecker::allows($user, 'trainings.view', 'trainings.manage'),
            'assistance' => PermissionChecker::allows($user, 'assistance.view', 'assistance.manage'),
            'groups' => PermissionChecker::allows($user, 'groups.view', 'groups.manage'),
        ];

        if (! $access[$tab] ?? false) {
            $tab = collect($access)->search(true, true) ?: 'beneficiaries';
        }

        $payload = [
            'activeTab' => $tab,
            'search' => $search,
            'access' => $access,
        ];

        if ($access['beneficiaries']) {
            $payload['beneficiaries'] = $this->beneficiariesPanel($request, $tab === 'beneficiaries' ? $search : '');
        }
        if ($access['projects']) {
            $payload['projects'] = $this->projectsPanel($request, $tab === 'projects' ? $search : '');
        }
        if ($access['trainings']) {
            $payload['trainings'] = $this->trainingsPanel($request, $tab === 'trainings' ? $search : '');
        }
        if ($access['assistance']) {
            $payload['assistance'] = $this->assistancePanel($request, $tab === 'assistance' ? $search : '');
        }
        if ($access['groups']) {
            $payload['groups'] = $this->groupsPanel($request, $tab === 'groups' ? $search : '');
        }

        $payload['projectsList'] = Project::select('id', 'project_name')->orderBy('project_name')->get();

        return inertia('monitoring.index', $payload);
    }

    /** @return array<string, mixed> */
    private function beneficiariesPanel(Request $request, string $search): array
    {
        $base = Beneficiary::query();

        return [
            'stats' => [
                'total' => (clone $base)->count(),
                'active' => (clone $base)->where('is_active', true)->count(),
                'inactive' => (clone $base)->where('is_active', false)->count(),
                'male' => (clone $base)->where('sex', 'Male')->count(),
                'female' => (clone $base)->where('sex', 'Female')->count(),
            ],
            'topBarangays' => Beneficiary::query()
                ->select('barangay', DB::raw('count(*) as count'))
                ->whereNotNull('barangay')
                ->groupBy('barangay')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
            'recent' => $this->beneficiaryQuery($search, $request)
                ->orderByDesc('updated_at')
                ->limit(20)
                ->get()
                ->map(fn ($b) => [
                    'id' => $b->id,
                    'code' => $b->beneficiary_code,
                    'name' => "{$b->last_name}, {$b->first_name}",
                    'barangay' => $b->barangay,
                    'type' => $b->beneficiary_type,
                    'is_active' => $b->is_active,
                    'updated_at' => $b->updated_at?->toDateTimeString(),
                ]),
        ];
    }

    private function beneficiaryQuery(string $search, Request $request)
    {
        $query = Beneficiary::query();

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('last_name', 'like', "%$search%")
                ->orWhere('first_name', 'like', "%$search%")
                ->orWhere('beneficiary_code', 'like', "%$search%"));
        }
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return $query;
    }

    /** @return array<string, mixed> */
    private function projectsPanel(Request $request, string $search): array
    {
        $all = Project::query();
        $active = Project::query()->currentlyActiveByDates();

        return [
            'stats' => [
                'total' => (clone $all)->count(),
                'active' => (clone $active)->count(),
                'with_beneficiaries' => (clone $all)->has('beneficiaries')->count(),
                'total_enrolled' => DB::table('beneficiary_project')->count(),
            ],
            'recent' => $this->projectQuery($search, $request)
                ->withCount('beneficiaries')
                ->latest('updated_at')
                ->limit(20)
                ->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'code' => $p->project_code,
                    'name' => $p->project_name,
                    'status' => $p->lifecycleStatus(),
                    'beneficiaries_count' => $p->beneficiaries_count,
                    'date_started' => $p->date_started?->toDateString(),
                    'date_ended' => $p->date_ended?->toDateString(),
                ]),
        ];
    }

    private function projectQuery(string $search, Request $request)
    {
        $query = Project::query();

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('project_name', 'like', "%$search%")
                ->orWhere('project_code', 'like', "%$search%"));
        }
        if ($request->filled('is_active')) {
            if ($request->boolean('is_active')) {
                $query->currentlyActiveByDates();
            } else {
                $query->notCurrentlyActiveByDates();
            }
        }

        return $query;
    }

    /** @return array<string, mixed> */
    private function trainingsPanel(Request $request, string $search): array
    {
        $base = Training::query();

        return [
            'stats' => [
                'total' => (clone $base)->count(),
                'this_year' => (clone $base)->whereYear('date_conducted', now()->year)->count(),
                'total_participants' => DB::table('beneficiary_training')->count(),
                'upcoming' => (clone $base)->whereDate('date_conducted', '>=', now()->toDateString())->count(),
            ],
            'recent' => $this->trainingQuery($search, $request)
                ->with('project')
                ->withCount('beneficiaries')
                ->latest('date_conducted')
                ->limit(20)
                ->get()
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->training_title,
                    'type' => $t->training_type,
                    'facilitator' => $t->facilitator,
                    'date_conducted' => $t->date_conducted?->toDateString(),
                    'participants' => $t->beneficiaries_count,
                    'project' => $t->project?->project_name,
                ]),
        ];
    }

    private function trainingQuery(string $search, Request $request)
    {
        $query = Training::query();

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('training_title', 'like', "%$search%")
                ->orWhere('facilitator', 'like', "%$search%"));
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        return $query;
    }

    /** @return array<string, mixed> */
    private function assistancePanel(Request $request, string $search): array
    {
        $base = AssistanceRecord::query();

        return [
            'stats' => [
                'total' => (clone $base)->count(),
                'total_amount' => (float) (clone $base)->sum('amount'),
                'individual' => (clone $base)->where('recipient_type', 'individual')->count(),
                'group' => (clone $base)->where('recipient_type', 'group')->count(),
                'this_year_amount' => (float) (clone $base)
                    ->whereYear('date_released', now()->year)
                    ->sum('amount'),
            ],
            'recent' => $this->assistanceQuery($search, $request)
                ->with(['beneficiary', 'beneficiaryGroup', 'project'])
                ->latest('date_released')
                ->limit(20)
                ->get()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'recipient' => $r->recipient_type === 'individual'
                        ? ($r->beneficiary ? "{$r->beneficiary->last_name}, {$r->beneficiary->first_name}" : '—')
                        : ($r->beneficiaryGroup?->group_name ?? '—'),
                    'recipient_type' => $r->recipient_type,
                    'assistance_type' => $r->assistance_type,
                    'amount' => $r->amount,
                    'date_released' => $r->date_released?->toDateString(),
                    'project' => $r->project?->project_name,
                ]),
        ];
    }

    private function assistanceQuery(string $search, Request $request)
    {
        $query = AssistanceRecord::query();

        if ($search !== '') {
            $query->where(fn ($q) => $q
                ->whereHas('beneficiary', fn ($q3) => $q3->where('last_name', 'like', "%$search%")->orWhere('first_name', 'like', "%$search%"))
                ->orWhereHas('beneficiaryGroup', fn ($q3) => $q3->where('group_name', 'like', "%$search%"))
                ->orWhere('assistance_type', 'like', "%$search%"));
        }
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }
        if ($request->filled('recipient_type')) {
            $query->where('recipient_type', $request->recipient_type);
        }

        return $query;
    }

    /** @return array<string, mixed> */
    private function groupsPanel(Request $request, string $search): array
    {
        $base = BeneficiaryGroup::query();

        return [
            'stats' => [
                'total' => (clone $base)->count(),
                'total_members' => (int) (clone $base)->sum('total_members'),
                'male_members' => (int) (clone $base)->sum('male_members'),
                'female_members' => (int) (clone $base)->sum('female_members'),
            ],
            'recent' => $this->groupQuery($search, $request)
                ->latest('updated_at')
                ->limit(20)
                ->get()
                ->map(fn ($g) => [
                    'id' => $g->id,
                    'name' => $g->group_name,
                    'type' => $g->group_type,
                    'total_members' => $g->total_members,
                    'date_organized' => $g->date_organized?->toDateString(),
                ]),
        ];
    }

    private function groupQuery(string $search, Request $request)
    {
        $query = BeneficiaryGroup::query();

        if ($search !== '') {
            $query->where(fn ($q) => $q->where('group_name', 'like', "%$search%")
                ->orWhere('group_type', 'like', "%$search%"));
        }

        return $query;
    }
}
