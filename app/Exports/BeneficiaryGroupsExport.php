<?php

namespace App\Exports;

use App\Models\BeneficiaryGroup;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BeneficiaryGroupsExport implements FromCollection, ShouldAutoSize, WithHeadings, WithStyles, WithTitle
{
    public function __construct(protected Request $request) {}

    public function collection()
    {
        $query = BeneficiaryGroup::query();
        $this->applyFilters($query);

        return $query->orderBy('group_name')->get()->map(fn ($g, $i) => [
            $i + 1,
            $g->group_name,
            $g->group_type,
            $g->date_organized?->format('Y-m-d'),
            $g->total_members,
            $g->male_members,
            $g->female_members,
        ]);
    }

    private function applyFilters($query): void
    {
        if ($this->request->filled('search')) {
            $q = $this->request->search;
            $query->where(fn ($q2) => $q2->where('group_name', 'like', "%$q%")->orWhere('group_type', 'like', "%$q%"));
        }
    }

    public function headings(): array
    {
        return ['#', 'Group Name', 'Type', 'Date Organized', 'Total Members', 'Male', 'Female'];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF1E40AF']]]];
    }

    public function title(): string
    {
        return 'Beneficiary Groups';
    }
}
