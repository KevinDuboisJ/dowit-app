{{-- Field Label --}}
<div class="flex items-center gap-x-3 justify-between ">
@if($field->getLabel())
        <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
            <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
            {{ $field->getLabel() }}
            </span>
        </label>
@endif

{{-- Hint (e.g. tooltip) --}}
@if($field->getHint())
     <div class="fi-fo-field-wrp-hint flex items-center gap-x-3 text-sm">
        <span class="fi-fo-field-wrp-hint-label text-gray-500 fi-color-gray" style="--c-400:var(--gray-400);--c-600:var(--gray-600);">
            {!! $field->getHint() !!}
        </span>
    </div>
@endif
</div>

{{-- Main Team Diff UI --}}
@if(empty($suggested) && empty($existing) && !$errors->has($field->getStatePath()))
    <span class="text-sm text-gray-500">
        Er is geen teamtaaktoewijzingsregel voor uw selectie
    </span>
@else
    <ul class="space-y-1 mt-2">
        @foreach($toAdd as $id)
            <li>• <span class="text-sm/5 text-green-600">{{ $names[$id] ?? '–' }} +</span></li>
        @endforeach

        @foreach($toRemove as $id)
            <li>• <span class="text-sm/5 text-red-600">{{ $names[$id] ?? '–' }} −</span></li>
        @endforeach

        @foreach($unchanged as $id)
            <li>• <span class="text-sm/5 text-gray-700">{{ $names[$id] ?? '–' }}</span></li>
        @endforeach
    </ul>
@endif

{{-- Validation error --}}
@error($field->getStatePath())
    @if(empty($suggested))
        <p class="mt-2 text-sm text-danger-600">{{ $message }}</p>
    @endif
@enderror