@php
    $user = filament()->auth()->user();
@endphp

<div
    data-sidebar-user-menu
    x-data
    class="flex w-full items-center gap-x-3 rounded-lg px-3 py-2
           hover:bg-gray-100 dark:hover:bg-white/5"
>
    <div class="shrink-0">
        <x-filament-panels::user-menu />
    </div>

    <button
        type="button"
        class="min-w-0 flex-1 text-left"
        x-on:click="
            $el.closest('[data-sidebar-user-menu]')
                .querySelector('.fi-user-menu button, .fi-user-menu-trigger')
                ?.click()
        "
    >
        <span class="block truncate text-sm font-medium text-gray-950 dark:text-white">
            {{ filament()->getUserName($user) }}
        </span>
    </button>

    <button
        type="button"
        class="shrink-0"
        x-on:click="
            $el.closest('[data-sidebar-user-menu]')
                .querySelector('.fi-user-menu button, .fi-user-menu-trigger')
                ?.click()
        "
    >
        <x-filament::icon
            icon="heroicon-m-chevron-up-down"
            class="h-5 w-5 text-gray-400"
        />
    </button>
</div>