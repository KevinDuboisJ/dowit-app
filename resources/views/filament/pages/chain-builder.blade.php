<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        x-data="actionManager(@js($actions))"
        x-init="init()"
        x-on:destroy="destroy()"
        class="space-y-4"
    >
        <div class="flex gap-6">
            {{-- ─── Left Column: Selected actions ─── --}}
            <div class="w-2/3 border rounded-lg p-4 bg-white">
                <h3 class="text-lg font-semibold mb-3">Geselecteerde acties</h3>
                <ul
                    x-ref="actionsList"
                    x-sort="onSort"
                    class="space-y-2 min-h-[200px]"
                    @dragover.prevent
                    @drop="onDropLeft"
                >
                    {{-- Note: We now expose both "action" and "index" --}}
                    <template x-for="(action, index) in actions" :key="index">
                        <li
                            x-sort:item="action.id"
                            class="border rounded-md bg-gray-50"
                        >
                            <div class="flex justify-between items-center p-2 cursor-move">
                                <div class="flex items-center space-x-2">
                                    {{-- Sortable drag‐handle --}}
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                         class="h-5 w-5 text-gray-400 drag-handle"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M4 8h16M4 16h16" />
                                    </svg>
                                    {{-- Name of the action --}}
                                    <span x-text="action.name"></span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    {{-- Toggle open/closed --}}
                                    <button
                                        type="button"
                                        @click="action.expanded = !action.expanded"
                                        class="text-blue-500 hover:text-blue-700 focus:outline-none"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            :class="action.expanded ? 'transform rotate-90' : ''"
                                            class="h-5 w-5 transition-transform duration-150"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>

                                    {{-- Remove button --}}
                                    <button
                                        type="button"
                                        @click="removeAction(action.id)"
                                        class="text-red-500 hover:text-red-700 focus:outline-none"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg"
                                             class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            {{-- ─── Expanded config area (collapsed by default) ─── --}}
                            <template x-show="action.expanded">
                                <div class="border-t px-4 py-2 bg-white">
                                    {{-- Example: if action.type === 'email', show an email config field --}}
                                    <template x-if="action.type === 'email'">
                                        <div class="mb-2">
                                            <label for="" class="block text-sm font-medium text-gray-700">
                                                Emailadres
                                            </label>
                                            <input
                                                type="text"
                                                x-model="action.email_address"
                                                :name="`actions.${index}.email_address`"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                placeholder="bv. no-reply@example.com"
                                            />
                                        </div>
                                    </template>

                                    {{-- Example: if action.type === 'approval', show an approver field --}}
                                    <template x-if="action.type === 'approval'">
                                        <div class="mb-2">
                                            <label for="" class="block text-sm font-medium text-gray-700">
                                                Keurder (User ID)
                                            </label>
                                            <input
                                                type="text"
                                                x-model="action.approver_id"
                                                :name="`actions.${index}.approver_id`"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                placeholder="bv. 42"
                                            />
                                        </div>
                                    </template>

                                    {{-- Example: if action.type === 'notification', show a message field --}}
                                    <template x-if="action.type === 'notification'">
                                        <div class="mb-2">
                                            <label for="" class="block text-sm font-medium text-gray-700">
                                                Berichttekst
                                            </label>
                                            <textarea
                                                x-model="action.notification_message"
                                                :name="`actions.${index}.notification_message`"
                                                rows="3"
                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                                placeholder="Typ hier je bericht"
                                            ></textarea>
                                        </div>
                                    </template>

                                    {{-- … je kunt hier extra action.type‐specifieke velden toevoegen … --}}
                                </div>
                            </template>
                        </li>
                    </template>

                    {{-- Placeholder als er nog geen acties zijn --}}
                    <template x-if="actions.length === 0">
                        <li class="text-center text-gray-500 italic p-4">
                            Sleep hier acties naartoe om je keten op te bouwen.
                        </li>
                    </template>
                </ul>

                {{-- (optioneel) Button om het huidige array‐object in de console te loggen --}}
                <button type="button" @click="console.log('Current actions:', actions)"
                        class="mt-2 px-3 py-1 bg-gray-200 rounded hover:bg-gray-300 text-sm">
                    Check actions
                </button>
            </div>

            {{-- ─── Right Column: Beschikbare acties ─── --}}
            <div class="w-1/3 border rounded-lg p-4 bg-white">
                <h3 class="text-lg font-semibold mb-3">Acties</h3>
                <ul class="space-y-2">
                    {{-- Vervang deze lijst met je eigen types + names --}}
                    <template x-for="type in [
                        { id: 1, name: 'Email Task',        type: 'email' },
                        { id: 2, name: 'Approval Task',     type: 'approval' },
                        { id: 3, name: 'Notification Task', type: 'notification' }
                    ]" :key="type.type">
                        <li
                            class="p-2 border rounded-md bg-gray-100 cursor-pointer hover:bg-gray-200"
                            draggable="true"
                            @dragstart="onDragStart(type.id, type.type, type.name)"
                        >
                            <span x-text="type.name"></span>
                        </li>
                    </template>
                </ul>
            </div>
        </div>
    </div>
</x-dynamic-component>