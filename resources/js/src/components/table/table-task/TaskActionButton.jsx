import {
  Button,
  AlertDialog,
  AlertDialogTrigger,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogFooter,
  AlertDialogTitle,
  AlertDialogDescription,
  AlertDialogAction,
  AlertDialogCancel,
  Lucide
} from '@/base-components'
import { updateTask } from '@/hooks'

export const TaskActionButton = ({
  task,
  user,
  handleTasksRecon,
  handleTaskUpdate
}) => {
  if (!task.capabilities?.can_modify) {
    return null
  }

  const configs = getTaskActionConfigs({
    task,
    user,
    handleTasksRecon,
    handleTaskUpdate
  })

  if (!configs?.length) {
    return null
  }

  return (
    <div className="flex justify-self-end gap-x-2">
      {configs.map(
        (
          { trigger, alertDialogDescription, alertDialogAction, onConfirm },
          idx
        ) => (
          <AlertDialog key={idx}>
            <AlertDialogTrigger asChild>{trigger}</AlertDialogTrigger>

            <AlertDialogContent>
              <AlertDialogHeader>
                <AlertDialogTitle>Ben je helemaal zeker?</AlertDialogTitle>
                <AlertDialogDescription>
                  {alertDialogDescription}
                </AlertDialogDescription>
              </AlertDialogHeader>

              <AlertDialogFooter>
                <AlertDialogCancel>Annuleren</AlertDialogCancel>
                <AlertDialogAction onClick={onConfirm}>
                  {alertDialogAction}
                </AlertDialogAction>
              </AlertDialogFooter>
            </AlertDialogContent>
          </AlertDialog>
        )
      )}
    </div>
  )
}

const getTaskActionConfigs = ({
  task,
  user,
  handleTasksRecon,
  handleTaskUpdate
}) => {
  const userId = user.id
  const isAssignedToCurrentUser = task.capabilities?.isAssignedToCurrentUser
  const hasAssignees = task.assignees?.length > 0
  const statusName = task.status?.name
  const isAdmin = task.capabilities?.can_reject ?? false
  const actions = []

  // 1) Start task (assign to self)
  if (statusName === 'Added' && !isAssignedToCurrentUser && !hasAssignees) {
    actions.push({
      trigger: (
        <Button className="w-24 h-6 font-normal rounded" variant="secondary" size="sm">
          Starten
        </Button>
      ),
      alertDialogDescription:
        'Met dit bevestig je dat je deze taak aan jezelf toewijst',
      alertDialogAction: 'Starten',
      onConfirm: () =>
        updateTask({ assignees: [userId], status: 'InProgress' }, task, {
          onBefore: ({ original, updatedAt }) => {
            handleTaskUpdate(
              {
                ...original,
                updated_at: updatedAt,
                assignees: '{{loading}}',
                status: {
                  ...original.status,
                  name: 'InProgress'
                },
                capabilities: {
                  ...original.capabilities,
                  isAssignedToCurrentUser: true
                }
              },
              { scroll: true }
            )
          },
          onSuccess: ({ data }) => handleTasksRecon(data),
          onError: ({ original }) => handleTaskUpdate(original)
        })
    })
  }

  // 2) Complete task (current user is assignee)
  if (statusName !== 'Completed' && isAssignedToCurrentUser) {
    actions.push({
      trigger: (
        <Button className="w-24 h-6 font-normal rounded" variant="success" size="sm">
          Afhandelen
        </Button>
      ),
      alertDialogDescription:
        'Met dit bevestig je dat je deze taak hebt afgehandeld',
      alertDialogAction: 'Afhandelen',
      onConfirm: () =>
        updateTask({ status: 'Completed' }, task, {
          onBefore: ({ original, updatedAt }) => {
            handleTaskUpdate({
              ...original,
              updated_at: updatedAt,
              status: {
                ...original.status,
                name: 'Completed'
              }
            })
          },
          onSuccess: ({ data }) => handleTasksRecon(data),
          onError: ({ original }) => handleTaskUpdate(original)
        })
    })
  }

  // 3) Help with task (assign self additionally)
  if (task.needs_help && !isAssignedToCurrentUser) {
    actions.push({
      trigger: (
        <Button className="w-24 h-6 font-normal rounded" variant="success" size="sm">
          Helpen
        </Button>
      ),
      alertDialogDescription:
        'Met dit bevestig je dat je jezelf ook aan deze taak gaat toewijzen',
      alertDialogAction: 'Helpen',
      onConfirm: () =>
        updateTask(
          { assignees: [userId], needs_help: false, status: 'InProgress' },
          task,
          {
            onSuccess: ({ data }) => handleTasksRecon(data),
            onError: ({ original }) => handleTaskUpdate(original)
          }
        )
    })
  }

  // 4) Admin can reject tasks (extra button — example with icon-only)
  if (isAdmin && statusName !== 'Rejected') {
    actions.push({
      trigger: (
        <Button className="h-6 w-6 p-0 rounded" variant="destructive">
          <Lucide icon="X"/>
        </Button>
      ),
      alertDialogDescription:
        'Met dit bevestig je dat je deze taak gaat afwijzen',
      alertDialogAction: 'Afwijzen',
      onConfirm: () =>
        updateTask({ status: 'Rejected' }, task, {
          onSuccess: ({ data }) => handleTasksRecon(data),
          onError: ({ original }) => handleTaskUpdate(original)
        })
    })
  }

  return actions
}
