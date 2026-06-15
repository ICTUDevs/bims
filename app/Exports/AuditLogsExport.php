<?php

namespace App\Exports;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AuditLogsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(protected Request $request) {}

    public function collection()
    {
        $query = AuditLog::query()->with(['user', 'beneficiary'])->latest();
        $this->applyFilters($query);

        return $query->get()->map(fn ($log, $i) => [
            $i + 1,
            $log->created_at?->format('Y-m-d H:i'),
            $log->action,
            class_basename($log->model_type),
            $log->user?->name ?? '—',
            $log->beneficiary
                ? "{$log->beneficiary->last_name}, {$log->beneficiary->first_name}"
                : '—',
            $log->ip_address,
        ]);
    }

    private function applyFilters($query): void
    {
        if ($this->request->filled('search')) {
            $q = $this->request->search;
            $query->where(function ($q2) use ($q) {
                $q2->where('action', 'like', "%$q%")
                    ->orWhere('model_type', 'like', "%$q%")
                    ->orWhere('ip_address', 'like', "%$q%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%$q%"));
            });
        }
        if ($this->request->filled('action')) {
            $query->where('action', $this->request->action);
        }
        if ($this->request->filled('model_type')) {
            $query->where('model_type', $this->request->model_type);
        }
        if ($this->request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $this->request->date_from);
        }
        if ($this->request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $this->request->date_to);
        }
    }

    public function headings(): array
    {
        return ['#', 'Date & Time', 'Action', 'Model', 'User', 'Beneficiary', 'IP Address'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1E40AF']]]];
    }

    public function title(): string
    {
        return 'Audit Logs';
    }
}
