import { router } from '@inertiajs/react'
import {
  Button,
  AlertDialog,
  AlertDialogPortal,
  AlertDialogOverlay,
  AlertDialogTrigger,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogFooter,
  AlertDialogTitle,
  AlertDialogDescription,
  AlertDialogAction,
  AlertDialogCancel,
} from '@/base-components'
import { updateTask } from '@/hooks';


export const TaskActionButton = ({ task, user, handleRowUpdate }) => {
  let buttonText = null;
  let variant = null;
  let icon = null; // <Heroicon className="w-2 h-2" icon='Check' />;
  let alertDialogDescription = '';
  let alertDialogAction = '';
  let actionCallback = null;

  if (task.status.name == 'Added' && !task.capabilities.isAssignedToCurrentUser && task.assigned_users.length === 0) {

    alertDialogDescription = 'Met dit bevestig je dat je deze taak aan jezelf toewijst';
    alertDialogAction = 'Starten';
    buttonText = 'Starten';
    variant = 'secondary';
    actionCallback = () => updateTask({ usersToAssign: [user.id], status: 'InProgress' }, task, {
      onBefore: ({ originalRow, updatedAt }) => {
        // Optimistically update the UI
        handleRowUpdate({
          ...originalRow,
          updated_at: updatedAt,
          assigned_users: '{{loading}}',
          status: {
            ...originalRow.status,
            name: 'InProgress',
          },
          capabilities: {
            ...originalRow.capabilities,
            isAssignedToCurrentUser: true
          },
        }, { scroll: true });
      },
      onSuccess: ({ updatedRow }) => handleRowUpdate(updatedRow),
      onError: ({ originalRow }) => handleRowUpdate(originalRow)
    });
  }

  if (task.status.name !== 'Completed' && task.capabilities.isAssignedToCurrentUser) {
    alertDialogDescription = 'Met dit bevestig je dat je deze taak hebt afgehandeld';
    alertDialogAction = 'Afhandelen';
    buttonText = 'Afhandelen';
    variant = 'success';
    actionCallback = () => updateTask({ status: 'Completed' }, task, {
      onBefore: ({ originalRow, updatedAt }) => {

        // Optimistically update the UI
        handleRowUpdate({
          ...originalRow,
          updated_at: updatedAt,
          status: {
            ...originalRow.status,
            name: 'Completed',
          },
        });
      },
      onSuccess: ({ updatedRow }) => handleRowUpdate(updatedRow),
      onError: ({ originalRow }) => handleRowUpdate(originalRow)
    }
    );

  }

  if (task.needs_help && !task.capabilities.isAssignedToCurrentUser) {

    alertDialogDescription = 'Met dit bevestig je dat je jezelf ook aan deze taak gaat toewijzen';
    alertDialogAction = 'Helpen';
    buttonText = 'Helpen';
    variant = 'success';
    actionCallback = () => updateTask({ usersToAssign: [user.id], needs_help: false }, task, {
      onSuccess: ({ updatedRow }) => handleRowUpdate(updatedRow),
      onError: ({ originalRow }) => handleRowUpdate(originalRow)
    });

  }

  if (buttonText) {
    return (
      <AlertDialog>
        <AlertDialogTrigger asChild>
          <Button className="fadeInUp w-24 h-6 font-normal" variant={variant} size={'sm'} >{buttonText} {icon}</Button>
        </AlertDialogTrigger>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Ben je helemaal zeker?</AlertDialogTitle>
            <AlertDialogDescription>
              {alertDialogDescription}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Annuleren</AlertDialogCancel>
            <AlertDialogAction onClick={actionCallback}>{alertDialogAction}</AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>

    );
  }

}