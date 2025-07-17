@assets
<script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.10.2/lottie.min.js"></script>
@endassets

@script
<script>
    window.lottieAnimation = @json(json_decode(file_get_contents(public_path('images/loading.json')), true));
</script>
@endscript

<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field">

    <div x-data="{
    searchValue: '',
    patient: $wire.entangle('{{ $getStatePath() }}'),
    patientList: [],
    loading: false,
    showDropdown: false,
    patientNotFound: 'Geen patiënt gevonden',
    async fetchPatient() {
        if (this.searchValue.length === 8 || (isNaN(this.searchValue) && this.searchValue.length > 2)) {
            this.loading = true;
			this.startLottie();
						
            try {
                const response = await fetch('/patient/visitid', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ visitId: this.searchValue })
                });

                if (response.ok) {
                    const data = await response.json();
                    const dataIsFilled = data && Object.keys(data).length > 0;
                    console.log(data);
                    if (Array.isArray(data)) {
                        this.patientList = data;
                        this.showDropdown = true;
                    } else {
                        console.log(dataIsFilled)
                        console.log(data)
                        if (dataIsFilled) {
                            this.patient = data;
                        }

                        if(!dataIsFilled) {
                            this.searchValue = this.patientNotFound;
                        }


                        this.patientList = [];
                     
                    }

                    this.loading = false;
			        this.stopLottie();
                }
            } catch (e) {
                console.error(e)
            }

        } else {
        this.patientList = [];
        this.patient = null;
        this.showDropdown = false
        }
    },
    clear() {
        this.searchValue = '';
        this.patient = null;
        this.patientList = [];
    },

		startLottie() {
        if (this.$refs.lottieContainer && !this.lottieInstance) {
            this.lottieInstance = lottie.loadAnimation({
                container: this.$refs.lottieContainer,
                renderer: 'svg',
                loop: true,
                autoplay: true,
                animationData: window.lottieAnimation,
                 rendererSettings: {
                    preserveAspectRatio: 'xMidYMid slice',
                    width: '100%',
                    height: '100%',
                    viewBoxSize:'200 200 1920 1080'
                
                }
            });
        } else if (this.lottieInstance) {
            this.lottieInstance.play();
        }
    },

    stopLottie() {
        if (this.lottieInstance) {
            this.lottieInstance.stop();
        }
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
                :value="patient
                  ? `${patient.firstname ?? ''} ${patient.lastname ?? ''} (${patient.gender ?? ''}) - ${patient.visit?.bed?.room?.number ?? ''}, ${patient.visit?.bed?.number ?? ''}`
                  : searchValue"
                :maxlength="maxLength()"
                :readonly="patient || searchValue === patientNotFound">

            <!-- Dropdown arrow -->
            <div
                x-show="patientList?.length && !patient"
                @click="showDropdown = true"
                class="absolute cursor-pointer inset-y-0 right-2 flex items-center">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </div>

            <button x-show="(searchValue && !patientList?.length) || patient" type="button" class="text-gray-500 hover:text-red-600 absolute inset-y-0 right-2 flex items-center" @click="clear()">✕</button>

            <!-- Dropdown list -->
            <template x-if="showDropdown">
                <div
                    @click.outside="showDropdown = false"
                    class="absolute z-10 w-full bg-white border border-gray-300 rounded mt-1 max-h-48 overflow-y-auto">

                    <template x-if="!patientList?.length">
                        <div class="px-4 py-2 text-sm text-gray-500" x-text="patientNotFound">
                        </div>
                    </template>

                    <template x-for="(p, index) in patientList" :key="index">
                        <div
                            @click="patient = p; showDropdown = false;"
                            class="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm"
                            x-text="`${p.firstname ?? ''} ${p.lastname ?? ''} (${p.gender ?? ''}) - ${p.visit?.bed?.room?.number ?? ''}, ${p.visit?.bed?.number ?? ''}`"></div>
                    </template>
                </div>
            </template>

            <div x-ref="lottieContainer" class="absolute top-0 right-0 w-16 h-16" x-show="loading" wire:ignore></div>
        </div>

    </div>

</x-dynamic-component>