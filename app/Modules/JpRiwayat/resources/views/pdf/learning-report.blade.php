@extends('layouts.pdf', ['title' => __('jp.pdf.title')])

@section('content')
    <h1>{{ __('jp.pdf.title') }}</h1>
    <p class="muted">{{ __('jp.pdf.period', ['year' => $idp->period_year]) }}</p>

    <table class="meta">
        <tr><td>{{ __('jp.pdf.employee') }}</td><td>: {{ $idp->employee->name }}</td></tr>
        <tr><td>NIP</td><td>: {{ $idp->employee->nip }}</td></tr>
        <tr><td>{{ __('jp.pdf.position') }}</td><td>: {{ $idp->employee->position?->name ?? '—' }}</td></tr>
        <tr><td>{{ __('jp.pdf.opd') }}</td><td>: {{ $idp->employee->opd?->name ?? '—' }}</td></tr>
        <tr><td>{{ __('jp.pdf.supervisor') }}</td><td>: {{ $idp->supervisor?->name ?? '—' }}</td></tr>
    </table>

    <h2>{{ __('jp.pdf.targets') }}</h2>
    @foreach ($idp->items as $item)
        <h3>{{ $item->position }}. {{ $item->competency->name }}</h3>
        <table class="grid">
            <tr><th width="34%">{{ __('jp.pdf.level') }}</th><td>{{ __('idp.editor.level_range', ['start' => $item->start_level, 'target' => $item->target_level]) }}
                {{-- Level awal dari kesenjangan indikatif/sementara tidak boleh tampil bergaya terverifikasi (docs/20). --}}
                <br><span @class(['muted' => $item->gap?->confidence === \App\Support\Enums\Confidence::Verified, 'flag' => $item->gap?->confidence !== \App\Support\Enums\Confidence::Verified])>{{ $item->gap ? __('idp.editor.level_confidence', ['label' => $item->gap->confidence->label()]) : __('idp.editor.level_unassessed') }}</span>
            </td></tr>
            <tr><th>{{ __('idp.editor.work_result') }}</th><td>
                @if ($item->workResult)
                    {{ $item->workResult->code }} — {{ $item->workResult->description }}
                @else
                    <span class="flag">{{ __('idp.unlinked') }}</span>
                @endif
            </td></tr>
            <tr><th>{{ __('jp.pdf.indicator') }}</th><td>{{ $item->success_indicator ?: '—' }}</td></tr>
            <tr><th>{{ __('jp.pdf.progress') }}</th><td>{{ (int) ($progress[$item->id] ?? 0) }}%</td></tr>
        </table>
        <table class="grid">
            <tr><th width="16%">{{ __('idp.editor.portion') }}</th><th>{{ __('jp.pdf.activities') }}</th><th width="10%">JP</th><th width="20%">Status</th></tr>
            @forelse ($item->activities as $activity)
                <tr>
                    <td>{{ $activity->portion->label() }}</td>
                    <td>{{ $activity->title }}</td>
                    <td>{{ $activity->planned_jp }}</td>
                    <td>{{ $activity->status->label() }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">{{ __('idp.editor.no_activities') }}</td></tr>
            @endforelse
        </table>
    @endforeach

    <h2>{{ __('jp.pdf.jp_summary') }}</h2>
    <table class="meta">
        <tr><td>{{ __('jp.pdf.jp_target') }}</td><td>: {{ $idp->target_jp }} JP</td></tr>
        <tr><td>{{ __('jp.pdf.jp_verified', ['year' => $balance['year']]) }}</td><td>: {{ number_format($balance['verified'], 1, ',', '.') }} JP</td></tr>
        <tr><td>{{ __('jp.pdf.jp_status', ['target' => $balance['target']]) }}</td><td>: {{ $balance['fulfilled'] ? __('jp.overview.fulfilled') : __('jp.overview.not_fulfilled') }}</td></tr>
    </table>

    <h2>{{ __('jp.pdf.evidence') }}</h2>
    <table class="grid">
        <tr><th>{{ __('jp.attributes.title') }}</th><th width="22%">{{ __('jp.attributes.issuer') }}</th><th width="24%">{{ __('jp.attributes.activity_start') }}</th><th width="10%">JP</th></tr>
        @forelse ($records as $record)
            <tr>
                <td>{{ $record->title }} <span class="muted">({{ $record->portion->label() }})</span></td>
                <td>{{ $record->certificate?->issuer ?? __('jp.history.automatic') }}@if ($record->certificate?->number)<br><span class="muted">{{ $record->certificate->number }}</span>@endif</td>
                <td>{{ $record->activity_start->translatedFormat('j M Y') }} – {{ $record->activity_end->translatedFormat('j M Y') }}</td>
                <td>{{ number_format((float) $record->jp, 1, ',', '.') }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">{{ __('jp.pdf.no_evidence') }}</td></tr>
        @endforelse
    </table>

    <h2>{{ __('jp.pdf.reflection') }}</h2>
    <div class="box">{{ $idp->closing_reflection }}</div>

    <h2>{{ __('jp.pdf.supervisor_note') }}</h2>
    <div class="box">{{ $idp->closing_assessment }}</div>

    <p class="muted">{{ __('jp.pdf.disclaimer') }}</p>
@endsection
