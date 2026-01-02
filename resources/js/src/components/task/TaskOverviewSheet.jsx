import React, { useEffect, useMemo } from 'react';
import { TaskOverview } from '@/components';
import {
  Sheet,
  SheetContent,
  SheetTitle,
  SheetDescription
} from '@/base-components';

export const TaskOverviewSheet = ({
  sheetState,
  setSheetState,
  tasks,
  handleTaskUpdate,
  handleSheetClose,
}) => {
  // Derive the current task from tasks using the sheet state's task id
  const currentTask = useMemo(() => {
    return tasks.find(task => task.id === sheetState.taskId);
  }, [tasks, sheetState.taskId]);

  // If the sheet is open but the task no longer exists, close the sheet
  useEffect(() => {
    if (sheetState.open && !currentTask) {
      setSheetState({ open: false, task: null });
    }
  }, [sheetState.open, currentTask, setSheetState]);

  return (
    <Sheet open={sheetState.open} onOpenChange={handleSheetClose}>
      <SheetContent className="p-0 h-full bg-app-background-secondary w-full md:w-[768px] sm:max-w-screen-md">
        <SheetTitle className="sr-only">Taak details</SheetTitle>
        <SheetDescription className="sr-only">Details en bewerking van een taak</SheetDescription>
        {sheetState.open && currentTask && (
          <TaskOverview
            task={currentTask}
            handleTaskUpdate={handleTaskUpdate}
            handleSheetClose={handleSheetClose}
          />
        )}
      </SheetContent>
    </Sheet>
  );
};
