@extends('reports.layout')
@section('subtitle', 'Audit Logs Report')
@section('body')
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Date & Time</th>
            <th>Action</th>
            <th>Model</th>
            <th>User</th>
            <th>Beneficiary</th>
            <th>IP Address</th>
        </tr>
    </thead>
    <tbody>
        @foreach($logs as $i => $log)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $log->created_at?->format('M j, Y h:i A') }}</td>
            <td>{{ $log->action }}</td>
            <td>{{ class_basename($log->model_type) }}</td>
            <td>{{ $log->user?->name ?? '—' }}</td>
            <td>
                @if($log->beneficiary)
                    {{ $log->beneficiary->last_name }}, {{ $log->beneficiary->first_name }}
                @else
                    —
                @endif
            </td>
            <td>{{ $log->ip_address ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
