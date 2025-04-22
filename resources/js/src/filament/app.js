document.addEventListener('continue-wizard-step', () => {
    $wire.call('__proceedToNextStep');
});

document.addEventListener('alpine:init', () => {
  console.log('alpine:init')
});

function stepManager(steps) {

    return {
        steps,
        sortable: null,
        sortableTypes: null,

        initSortable() {

            this.sortable = new Sortable(this.$refs.stepsList, {
                group: 'shared',
                animation: 150,

                onAdd: event => {
                    event.item.remove()

                    const type = event.item.getAttribute('data-step-type');
                    const name = event.item.getAttribute('data-step-name');
                    const newStep = this.newStep(name, type);
                    let newSteps = Alpine.raw(this.steps);
                   
                    // Create a new array to trigger Alpine's reactivity
                    newSteps = [
                        ...newSteps.slice(0, event.newIndex),
                        newStep,
                        ...newSteps.slice(event.newIndex)
                    ];
                    this.$wire.set('steps', newSteps)
                },

                onEnd: event => {

                    if (event.oldIndex !== event.newIndex) {
                        const reordered = [...this.steps];
                        const [moved] = reordered.splice(event.oldIndex, 1);
                        reordered.splice(event.newIndex, 0, moved);
                        this.steps = reordered;
                        //this.$refs.stepsList.querySelector("template")._x_prevKeys = this.steps.map((item) => item.id);
                    }
                }
            });

            this.sortableTypes = new Sortable(this.$refs.stepTypes, {
                group: {
                    name: 'shared',
                    pull: 'clone',
                    put: false
                },
                sort: false,
                animation: 150,
            });


        },

        destroySortable() {
            this.sortable.destroy();
            this.sortableTypes.destroy();
        },

        init() {
            this.initSortable();
        },

        destroy() {
            console.log('works')
            // Detach the handler, avoiding memory and side-effect leakage
            this.destroySortable();
        },

        onDragStart(event) {
            //event.dataTransfer.setData('stepType', event.target.dataset.stepType);
        },

        toggleExpand(stepId) {
            this.steps = this.steps.map(step =>
                step.id === stepId ? { ...step, expanded: !step.expanded } : step
            );
        },

        removeStep(stepId) {
            console.log('removeStep', stepId);
            let steps = this.steps;
            console.log('stepinremoeve', steps);
            steps = [...steps.filter(step => step.id !== stepId)];
            console.log('stepinremoeveAFTER', steps);
            // this.destroySortable();
            this.$wire.set('steps', steps);
        },

        newStep(name, type) {
            return {
                id: Math.floor(Math.random() * 10000),
                name,
                type,
                expanded: false,
            };
        },
    };
}