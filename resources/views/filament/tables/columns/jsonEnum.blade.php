@php
    use App\Enums\DaysOfWeek;
@endphp

@if(!empty($getState()))

@if(is_string($getState()))
    <span class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white">
        {{ $getState() }}
    </span>
    @else
    @foreach($getState() as $variable => $value)
    <span class="fi-ta-text-item-label text-sm leading-6 text-gray-950 dark:text-white">
        {{ DaysOfWeek::fromName($value)?->getLabel() }}@if(!$loop->last),@endif&nbsp;
    </papn>
    @endforeach

    @endif
@endif