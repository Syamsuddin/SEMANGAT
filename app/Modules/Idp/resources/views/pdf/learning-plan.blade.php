@extends('layouts.pdf', ['title' => __('idp.pdf.title')])

@section('content')
    <h1>{{ __('idp.pdf.title') }}</h1>
    <p class="muted">{{ __('idp.pdf.subtitle', ['year' => $idp->period_year]) }}</p>

    <table class="meta">
        <tr><td>{{ __('idp.pdf.name') }}</td><td>: {{ $idp->employee->name }}</td></tr>
        <tr><td>NIP</td><td>: {{ $idp->employee->nip }}</td></tr>
        <tr><td>{{ __('idp.pdf.position') }}</td><td>: {{ $idp->employee->position?->name ?? '—' }}</td></tr>
        <tr><td>{{ __('idp.pdf.opd') }}</td><td>: {{ $idp->employee->opd?->name ?? '—' }}</td></tr>
        <tr><td>{{ __('idp.pdf.supervisor') }}</td><td>: {{ $idp->supervisor?->name ?? '—' }}</td></tr>
        <tr><td>{{ __('idp.pdf.approved_at') }}</td><td>: {{ $idp->approved_at?->translatedFormat('j M Y') ?? '—' }}</td></tr>
        <tr><td>{{ __('idp.pdf.target_jp') }}</td><td>: {{ $idp->target_jp }} JP</td></tr>
    </table>

    <h2>{{ __('idp.pdf.priorities') }}</h2>
    @foreach ($idp->items as $item)
        <h3>{{ $item->position }}. {{ $item->competency->name }}</h3>
        <table class="grid">
            <tr><th width="34%">{{ __('idp.editor.levels') }}</th><td>{{ __('idp.editor.level_range', ['start' => $item->start_level, 'target' => $item->target_level]) }}</td></tr>
            <tr><th>{{ __('idp.editor.work_result') }}</th><td>
                @if ($item->workResult)
                    {{ $item->workResult->code }} — {{ $item->workResult->description }}
                @else
                    <span class="flag">{{ __('idp.unlinked') }}</span>
                @endif
            </td></tr>
            <tr><th>{{ __('idp.attributes.success_indicator') }}</th><td>{{ $item->success_indicator ?: '—' }}</td></tr>
            <tr><th>{{ __('idp.attributes.mentor_employee_id') }}</th><td>{{ $item->mentor?->name ?? '—' }}</td></tr>
        </table>
        <table class="grid">
            <tr><th width="16%">{{ __('idp.editor.portion') }}</th><th>{{ __('idp.attributes.title') }}</th><th width="10%">JP</th><th width="26%">{{ __('idp.editor.schedule') }}</th></tr>
            @forelse ($item->activities as $activity)
                <tr>
                    <td>{{ $activity->portion->label() }}</td>
                    <td>{{ $activity->title }}</td>
                    <td>{{ $activity->planned_jp }}</td>
                    <td>{{ $activity->planned_start?->translatedFormat('j M Y') ?? '—' }} – {{ $activity->planned_end?->translatedFormat('j M Y') ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">{{ __('idp.editor.no_activities') }}</td></tr>
            @endforelse
        </table>
    @endforeach

    @if ($idp->career_aspiration)
        <h2>{{ __('idp.attributes.career_aspiration') }}</h2>
        <p>{{ $idp->career_aspiration }}</p>
    @endif

    @if ($idp->supervisor_note)
        <h2>{{ __('idp.pdf.supervisor_note') }}</h2>
        <p>{{ $idp->supervisor_note }}</p>
    @endif

    <h2>{{ __('idp.expectation.title') }}</h2>
    <div class="box">{{ $expectation }}</div>
    <p class="muted">{{ __('idp.expectation.hint') }}</p>
@endsection
