@extends('plan-manager::admin.layout')

@section('title', 'Usage Corrections')
@section('topbar', 'Usage Corrections')

@section('content')
    <div class="page-header">
        <div>
            <h1 class="page-title">Usage Corrections</h1>
            <p class="page-subtitle">Record operational usage fixes as ledger entries. Corrections append compensating usage records instead of mutating history.</p>
        </div>
    </div>

    <form method="post" action="{{ route(config('plan-manager.admin.route_name_prefix', 'plan-manager.') . 'usage-corrections.store') }}" class="panel">
        @csrf
        <h2 class="section-title">New Correction</h2>
        <div class="form-grid">
            <label>Subject Type <input name="subject_type" placeholder="App\Models\Team"></label>
            <label>Subject ID <input name="subject_id"></label>
            <label>Meter <select name="meter_code">@foreach($meters as $meter)<option value="{{ $meter->code }}">{{ $meter->code }}</option>@endforeach</select></label>
            <label>Type <select name="type"><option value="adjustment">adjustment</option><option value="set_to">set_to</option></select></label>
            <label>Quantity <input name="quantity" type="number" step="0.000001"></label>
            <label>Reason <input name="reason" placeholder="Duplicate import adjustment"></label>
        </div>
        <div class="form-actions">
            <button>Record Correction</button>
        </div>
    </form>

    <div class="table-shell">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Subject</th><th>Meter</th><th>Type</th><th>Quantity</th><th>Reason</th></tr></thead>
                <tbody>
                @forelse($corrections as $correction)
                    <tr>
                        <td>
                            <code>{{ $correction->subject_type }}</code>
                            <div class="muted">ID {{ $correction->subject_id }}</div>
                        </td>
                        <td><span class="badge">{{ $correction->meter->code }}</span></td>
                        <td>{{ $correction->correction_type->value }}</td>
                        <td><strong>{{ $correction->quantity }}</strong></td>
                        <td>{{ $correction->reason }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state">No usage corrections have been recorded.</div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="pagination">{{ $corrections->links() }}</div>
@endsection
