@extends('reports.layout')
@section('subtitle', 'Beneficiary Groups Report')
@section('body')
<div class="summary">
    <div class="summary-card"><div class="val">{{ $total }}</div><div class="lbl">Total Groups</div></div>
    <div class="summary-card"><div class="val">{{ $totalMembers }}</div><div class="lbl">Total Members</div></div>
</div>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Group Name</th>
            <th>Type</th>
            <th>Date Organized</th>
            <th>Members</th>
            <th>Male</th>
            <th>Female</th>
        </tr>
    </thead>
    <tbody>
        @foreach($groups as $i => $g)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $g->group_name }}</td>
            <td>{{ $g->group_type ?? '—' }}</td>
            <td>{{ $g->date_organized ? $g->date_organized->format('M j, Y') : '—' }}</td>
            <td style="text-align:center">{{ $g->total_members ?? 0 }}</td>
            <td style="text-align:center">{{ $g->male_members ?? 0 }}</td>
            <td style="text-align:center">{{ $g->female_members ?? 0 }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
