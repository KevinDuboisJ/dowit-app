@php
    use App\Enums\DaysOfWeek;

    $interval = $getState()['interval'] ?? null;
    $frequency = $getState()['frequency'] ?? null;

    // Helper map for week numbers
    $weekLabels = [
        1 => '1e week',
        2 => '2e week',
        3 => '3e week',
        4 => '4e week',
    ];

    // Resolve the selected week label
    $weekLabel = isset($interval['week_number'])
        ? ($weekLabels[$interval['week_number']] ?? $interval['week_number'].'e week')
        : '?'; 
@endphp

<div class="flex flex-col">
{{-- Frequency --}}
@if ($frequency->name)
    <div class="fi-ta-text-item-label text-sm text-gray-950 dark:text-white">
        {{ $frequency->getLabel() }}
    </div>
@endif
     
@if (!empty($interval))
<div class="text-xs text-gray-500 dark:text-gray-400">
    @if ($frequency->name === 'WeekdayInMonth' && is_array($interval))
        {{ DaysOfWeek::fromCaseName($interval['day_of_week'] ?? '')?->getLabel() ?? '?' }}, {{ $weekLabel }}</small>
    @elseif (is_array($interval))
        @foreach ($interval as $value)
            {{ DaysOfWeek::fromCaseName($value)?->getLabel() }}@if (!$loop->last), @endif
        @endforeach
    @else
        {{ DaysOfWeek::fromCaseName($interval)?->getLabel() ?? (string) $interval}}
    @endif
</div>
@endif
</div>