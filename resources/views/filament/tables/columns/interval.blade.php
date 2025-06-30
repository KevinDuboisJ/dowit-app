@php
    use App\Enums\DaysOfWeek;

    $interval = $getState()['interval'] ?? null;
    $frequency = $getState()['frequency'] ?? null;
@endphp

@if (!empty($interval))
    @if ($frequency === 'WeekdayInMonth' && is_array($interval))
        <div class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white">
            <div>Week van de maand: {{ $interval['week_number'] ?? '?' }}</div>
            <div>Dag: {{ DaysOfWeek::fromCaseName($interval['day_of_week'] ?? '')?->getLabel() ?? '?' }}</div>
        </div>
    @elseif (is_array($interval))
    <div class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white">
        @foreach ($interval as $value)
            {{ DaysOfWeek::fromCaseName($value)?->getLabel() }}@if (!$loop->last), @endif
        @endforeach
    </div>
    @else
        <span class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white">
            {{DaysOfWeek::fromCaseName($interval)?->getLabel() }}
        </span>
    @endif
@endif
