<style>
    .login-logs-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: #9ca3af transparent;
    }

    .login-logs-scrollbar::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .login-logs-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }

    .login-logs-scrollbar::-webkit-scrollbar-thumb {
        background-color: #9ca3af;
        border-radius: 9999px;
    }

    .login-logs-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: #6b7280;
    }
</style>

<div class="space-y-4">
    @if ($logs->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Geen login- of logoutlogs gevonden voor deze gebruiker.
        </p>
    @else
        <div class="max-h-[70vh] overflow-auto rounded-xl border border-gray-200 dark:border-gray-700 login-logs-scrollbar">
            <table class="w-full min-w-[700px] text-sm">
                <thead class="sticky top-0 z-10 bg-gray-50 dark:bg-gray-900">
                    <tr class="border-b border-gray-200 dark:border-gray-700">
                        <th class="px-4 py-3 text-left font-medium">Datum</th>
                        <th class="px-4 py-3 text-left font-medium">Event</th>
                        <th class="px-4 py-3 text-left font-medium">Commentaar</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($logs as $log)
                        @php
                            $event = $log->event instanceof \BackedEnum
                                ? $log->event->value
                                : $log->event;
                        @endphp

                        <tr class="border-b border-gray-100 last:border-0 dark:border-gray-800">
                            <td class="whitespace-nowrap px-4 py-3">
                                {{ $log->created_at?->format('d/m/Y H:i:s') }}
                            </td>

                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($event === \App\Enums\EventEnum::UserLoggedIn->value)
                                    Aangemeld
                                @elseif ($event === \App\Enums\EventEnum::UserLoggedOut->value)
                                    Afgemeld
                                @else
                                    {{ $event }}
                                @endif
                            </td>

                            <td class="px-4 py-3 break-words">
                                {{ $log->content ?? '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>