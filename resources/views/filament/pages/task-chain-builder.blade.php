<div
    x-data="stepManager(@JS($steps))"
    x-init="initSortable()"
    class="flex space-x-4">
    <!-- Left Panel -->
    <div x-ref="stepsList" class="w-5/6 bg-white border rounded-md p-4">
        <?php for ($i = 0, $len = count($steps); $i < $len; $i++): $step = $steps[$i]; ?>
            <div class="flex justify-between items-center mb-2 border p-2 rounded-md">
                <div class="flex items-center space-x-2">
                    <button>
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4 transform transition-transform duration-200 <?php echo ($step['expanded'] ?? false) ? 'rotate-180' : ''; ?>"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <span><?php echo htmlspecialchars($step['name']); ?></span>
                </div>
                <button @click.stop="removeStep(<?= $step['id']; ?>)">Verwijderen</button>
            </div>
        <?php endfor; ?>
    </div>
    <!-- This is my template. Drag and Drop. -->

    <div class="text-xs text-gray-400">
        <ul>
            @foreach ($steps as $key => $value)
            <li>{{ $key }}: {{ $value['name'] }}</li>
            @endforeach
        </ul>
        </pre>
    </div>


    <!-- <p>This list will update</p>
    <template x-for="step in steps" :key="step.name + Math.random()">
        <div x-text="step.name">
        </div>
    </template> -->

    <!-- Right Panel (Drag & Drop Step Types) -->
    <div x-ref="stepTypes" class="w-1/6 space-y-2 bg-white border rounded-md p-4">
        <div
            class="p-2 bg-gray-100 cursor-move"
            data-step-type="create_task"
            data-step-name="Een taak maken"
            >
            Een taak maken
        </div>
        <div
            class="p-2 bg-gray-100 cursor-move"
            data-step-type="send_email"
            data-step-name="E-mail verzenden"
            >
            E-mail verzenden
        </div>
        <div
            class="p-2 bg-gray-100 cursor-move"
            data-step-type="pause"
            data-step-name="Pauze"
            >
            Pauze
        </div>
    </div>
</div>