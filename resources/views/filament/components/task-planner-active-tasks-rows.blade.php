@php 

    $record = $record; 
    dd($record);

@endphp

<tr class="filament-tables-row">
    <td class="px-4 py-2">
        {{ $record->name }}
    </td>

    <td class="px-4 py-2">
        {!! implode('<br>', $record->teams->pluck('name')->merge(
            \App\Models\User::whereIn('id', $record->assignments['users'] ?? [])->get()
                ->map(fn($user) => "{$user->firstname} {$user->lastname}")
        )->toArray()) !!}
    </td>

    <td class="px-4 py-2">
        @include('filament.tables.columns.interval', [
            'frequency' => $record->frequency,
            'interval' => $record->interval,
        ])
    </td>

    <td class="px-4 py-2">
        {!! $record->campus?->name . '<br><span class="text-xs text-gray-500">' . $record->space?->name . '</span>' !!}
    </td>

    <td class="px-4 py-2">
        {!! $record->visit
            ? $record->taskType?->name . '<br><span class="text-xs text-gray-500">'
                . $record->visit->patient?->firstname . ' '
                . $record->visit->patient?->lastname
                . ' (' . $record->visit->patient?->gender . ') - '
                . $record->visit->bed?->room?->number . ' '
                . $record->visit->bed?->number . '<br>'
                . $record->visit->number . '</span>'
            : $record->taskType?->name !!}
    </td>

    <td class="px-4 py-2">
        {{ $record->tags->pluck('name')->implode(', ') }}
    </td>

    <td class="px-4 py-2">
        {{ $record->on_holiday }}
    </td>

    <td class="px-4 py-2">
        {{ $record->next_run_at?->format('j F Y H:i') }}
    </td>

    <td class="px-4 py-2">
        <x-filament::icon
            name="{{ $record->is_active ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle' }}"
            class="{{ $record->is_active ? 'text-success-600' : 'text-gray-400' }}"
        />
    </td>

    <td class="px-4 py-2">
        <x-filament::button
            icon="heroicon-o-cog"
            wire:click="$dispatch('open-modal', { name: 'execute-task', id: {{ $record->id }} })"
            size="sm"
        >
            Uitvoeren
        </x-filament::button>
    </td>
</tr>

<tr>
    <td colspan="100%" class="bg-gray-50 px-4 py-3">
        <div class="text-sm font-medium mb-2 text-gray-700">Gerelateerde taken:</div>
        <table class="w-full text-sm text-left border border-gray-200 rounded-md">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-3 py-2">Titel</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2">Vervaldatum</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($record->tasks as $task)
                    <tr class="border-t">
                        <td class="px-3 py-2">{{ $task->title }}</td>
                        <td class="px-3 py-2">{{ $task->status }}</td>
                        <td class="px-3 py-2">{{ $task->due_date?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-3 py-2 text-gray-500 italic">Geen taken gevonden</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </td>
</tr>