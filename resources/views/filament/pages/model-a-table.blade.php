<!-- resources/views/components/model-a-table.blade.php -->
<table class="table-auto w-full">
    <thead>
        <tr>
            <th class="px-4 py-2">Naam</th>
            <th class="px-4 py-2">Team</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($records as $record)
            <tr>
                <td class="border px-4 py-2">{{ $record->name }}</td>
                <td class="border px-4 py-2">{{ $record->team->name }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
