console.log('Loading Filament app...');
// 1) Our component logic
window.actionManager = function (entangledActions = []) {
 
  return {
    /** The array of step objects (id, type, name, expanded). */
    actions: entangledActions,

    /** Temporarily holds the type/name of whatever we start dragging from the right. */
    id: null,
    dragType: null,
    dragName: null,

    /** Called by x-init. No need to do anything for Sortable here—all of it is handled by the plugin directive. */
    init() {

        console.log(this.actions)
      // Nothing needed: x-sortable automatically binds to `actions`.
    },

    /**
     * When the user begins sorting a left‐pane item
     */
    onSort(item, position) {
      
      // 1. Find the current index of 'item'
      const oldIndex = this.actions.indexOf(item);
      if (oldIndex === -1) {
        // item wasn’t found in the array—nothing to do
        return;
      }

      // 2. Remove it from its old position
      this.actions.splice(oldIndex, 1);

      // 3. Insert it at the desired position
      // If 'position' is beyond bounds, splice will just append or
      // insert at the end as needed.
      this.actions.splice(position, 0, item);
      console.log(this.actions)
      this.actions = [...this.actions];
      
    },

    /**
     * When the user begins dragging a right‐pane item, stash its type+name.
     */
    onDragStart(id, type, name) {
      this.id = id
      this.dragType = type
      this.dragName = name
    },

    /**
     * When you drop onto the left <ul>:
     *  • Prevent default.
     *  • If dragType/dragName exist, push a new step object into `actions`.
     */
    onDropLeft(event) {
      event.preventDefault()

      if (!this.dragType || !this.dragName) {
        return
      }

      const newAction = {
        id: this.id,
        type: this.dragType,
        name: this.dragName,
        expanded: false,
      }
      console.log(newAction)
      // Append to `actions`. Because x-sortable is bound to `actions`,
      // the new <li> appears automatically at the bottom.
      this.actions.push(newAction)

      // Clear drag data
      this.dragType = null
      this.dragName = null
    },

    /**
     * Remove a given action from the left list.
     */
    removeAction(actionId) {
      this.actions = this.actions.filter(action => action.id !== actionId)
    },

    /**
     * If Alpine ever tears down this component, nothing special is required
     * because the plugin directive x-sortable cleans itself up automatically.
     */
    destroy() {
      // No manual destruction needed for the plugin.
    },
  }
}