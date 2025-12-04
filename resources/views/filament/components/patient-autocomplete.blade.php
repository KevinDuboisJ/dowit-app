<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field">

    <div
        wire:ignore
        x-init="$nextTick(() => initLottie())"
        x-data="{
        searchValue: @js(
            $getState()
                ? ($getState()['patient']['firstname'] . ' ' . $getState()['patient']['lastname'] . ' (' . ($getState()['patient']['gender'] ?? '') . ') - ' . ($getState()['bed']['room']['number'] ?? '') . ', ' . ($getState()['bed']['number'] ?? ''))
                : ''
        ),
        visit: $wire.{{ $applyStateBindingModifiers("entangle('{$getStatePath()}')") }},
        visitList: [],
        loading: false,
        showDropdown: false,
        lottieInstance: null,

        async initLottie() {
            if (!this.$refs.lottieContainer) return

            if (!this.lottieInstance) {
                this.lottieInstance = await window.initLottie(
                    this.$refs.lottieContainer,
                    '{{ asset('images/loading.json') }}'
                )
            }
        },

        async fetchPatient() {
            if (this.searchValue.length === 8 || (isNaN(this.searchValue) && this.searchValue.length > 2)) {
                this.loading = true
                this.startLottie()
                try {
                    const response = await fetch('/visit/search', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ search: this.searchValue }),
                    })

                    if (response.ok) {
                        const data = await response.json()
                        this.visitList = data
                        this.showDropdown = true
                    }
                } catch (e) {
                    console.error(e)
                } finally {
                    this.loading = false
                    this.stopLottie()
                }
            } else {
                this.visitList = []
                this.visit = null
                this.showDropdown = false
            }
        },

        startLottie() {

            this.lottieInstance.play()
        },

        stopLottie() {
            if (this.lottieInstance) {
                this.lottieInstance.stop()
            }
        },

        clear() {
        this.searchValue = '';
        this.visit = null;
        this.visitList = [];
    },

     formatPatientDisplay(visit) {
        if (!visit || !visit.patient) return '';
        const p = visit.patient;
        const name = `${p.firstname ?? ''} ${p.lastname ?? ''}`;
        const gender = p.gender ? ` (${p.gender})` : '';
        const room = visit.bed?.room?.number ?? '';
        const bedNum = visit.bed?.number ?? '';
        const bedInfo = (room || bedNum) ? ` - ${room}, ${bedNum}` : '';
        return `${name}${gender}${bedInfo}`.trim();
    },

    maxLength() {
        return /^\d+$/.test(this.searchValue) ? 8 : null;
    },
}">

        <div class="relative">

            <input
                type="text"
                class="block w-full border-gray-300 rounded-lg py-1.5 text-base text-gray-950 transition duration-75 placeholder:text-gray-400 focus:ring-0 disabled:text-gray-500 disabled:[-webkit-text-fill-color:theme(colors.gray.500)] disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.400)] dark:text-white dark:placeholder:text-gray-500 dark:disabled:text-gray-400 dark:disabled:[-webkit-text-fill-color:theme(colors.gray.400)] dark:disabled:placeholder:[-webkit-text-fill-color:theme(colors.gray.500)] sm:text-sm sm:leading-6 bg-white/0 ps-3 pe-3 pr-8"
                x-model="searchValue"
                @input.debounce.500ms="fetchPatient"
                placeholder="Zoek een patiënt"
                :maxlength="maxLength()">

            <!-- Dropdown arrow -->
            <div
                x-show="visitList?.length && !visit"
                @click="showDropdown = true"
                class="absolute cursor-pointer inset-y-0 right-2 flex items-center">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </div>

            <button x-show="(searchValue && !visitList?.length) || visit" type="button" class="text-gray-500 hover:text-red-600 absolute inset-y-0 right-2 flex items-center" @click="clear()">✕</button>

            <!-- Dropdown list -->
            <template x-if="showDropdown">
                <div
                    @click.outside="showDropdown = false"
                    class="absolute z-10 w-full bg-white border border-gray-300 rounded mt-1 max-h-48 overflow-y-auto">

                    <template x-if="!visitList?.length">
                        <div class="px-4 py-2 text-sm text-gray-500" x-text="'Geen patiënt gevonden'">
                        </div>
                    </template>

                    <template x-for="(v, index) in visitList" :key="index">
                        <div
                            @click="visit = v; showDropdown = false; searchValue = formatPatientDisplay(v);"
                            class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm"
                            x-text="formatPatientDisplay(v)"></div>
                    </template>
                </div>
            </template>

            <div x-ref="lottieContainer" class="absolute top-0 right-0 w-16 h-16" x-show="loading" wire:ignore></div>
        </div>

    </div>

</x-dynamic-component>