{{-- x-table: tabel di ≥768 px, kartu bertumpuk di bawahnya (pemanggil mengisi slot `cards`). --}}
@props(['cards' => null])
<div {{ $attributes }}>
    <div class="hidden overflow-x-auto rounded-lg border border-line bg-surface md:block">
        <table class="w-full text-left text-sm">{{ $slot }}</table>
    </div>
    @if ($cards)
        <ul class="space-y-3 md:hidden">{{ $cards }}</ul>
    @endif
</div>
