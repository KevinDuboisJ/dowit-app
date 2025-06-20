@php
    use App\Enums\DaysOfWeek;

    $interval = $getState()['interval'] ?? null;
    $frequency = $getState()['frequency'] ?? null;
@endphp

@if (!empty($interval))
    @if ($frequency === 'WeekdayInMonth' && is_array($interval))
        <div class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white">
            <div>Week van de maand: {{ $interval['week_number'] ?? '?' }}</div>
            <div>Dag: {{ DaysOfWeek::fromName($interval['day_of_week'] ?? '')?->getLabel() ?? '?' }}</div>
        </div>
    @elseif (is_array($interval))
        @foreach ($interval as $value)
            <div class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white">
                {{ DaysOfWeek::fromName($value)?->getLabel() }}
            </div>
        @endforeach
    @else
        <span class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white">
            {{ $interval }}
        </span>
    @endif
@endif
