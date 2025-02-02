import React, {
  useEffect,
  useRef,
  useState,
  useMemo,
  useCallback,
} from 'react';
import axios from 'axios';
import { router, usePoll } from '@inertiajs/react';
import { createPortal } from 'react-dom';

import { useIsMobile } from '@/hooks';
import { inertiaResourceSync, getRecord } from '@/hooks';
import { __ } from '@/stores';
import { cn } from '@/utils';

import { Sheet, SheetContent } from '@/base-components';
import {
  TaskDetail,
  AnnouncementSheet,
  TaskSheet,
  TaskTabulator,
  TaskMobileView,
  AnnouncementFeed,
  FilterBar,
} from '@/components';

const Dashboard = ({
  user: initialUser,
  teams,
  tasks: initialTasks,
  settings,
  statuses,
  announcements,
}) => {
  const [tasks, setTasks] = useState(initialTasks);
  const [user, setUser] = useState(initialUser);
  const [sheetState, setSheetState] = useState({ open: false, task: {} });
  const [placeholderAnnouncements, setPlaceholderAnnouncements] = useState(null);
  const { isMobile, hasTransitionedToMobile } = useIsMobile();
  const lastUpdatedTaskRef = useRef(null);
  const tabulatorRef = useRef(null);
  const filterBarRef = useRef({
    getFilters: () => null,
    resetFilters: () => null,
  })

  // Poll every 5 minutes (300000 ms)
  usePoll(300000, {
    onSuccess: ({ props }) => {
      setTasks(props.tasks);
    },
  });

  // Determines whether a broadcast event should be processed
  const shouldProcessEvent = useCallback((event, lastTask) => {
    const { data, timestamp } = event;
    return (
      !lastTask ||
      lastTask.id !== data.id ||
      new Date(timestamp) > new Date(lastTask.updated_at)
    );
  }, []);

  // Sync announcements by triggering a resource update
  const syncAnnouncements = useCallback(() => {
    inertiaResourceSync(['announcements']);
  }, []);

  // Handle task creation and update events
  const processEvent = useCallback(
    async (event, onCreate, onUpdate, onSync) => {
      const { type, data } = event;
      if (!shouldProcessEvent(event, lastUpdatedTaskRef.current)) {
        return;
      }

      console.log(`Event for task ${data.id}:`, event);

      if (type === 'task_created') {
        const task = await getRecord({ url: `tasks/${data.id}` });
        onCreate(task);
      }

      if (type === 'task_updated') {
        const task = await getRecord({ url: `tasks/${data.id}` });
        onUpdate(task);
      }

      if (
        type === 'comment_created' ||
        type === 'comment_updated' ||
        type === 'announcement_created'
      ) {
        onSync();
      }
    },
    [shouldProcessEvent]
  );

  // Update state when a new task is created
  const handleRowCreate = useCallback((newTask) => {
    setTasks((prevTasks) => ({
      ...prevTasks,
      data: [...prevTasks.data, newTask],
    }));
  }, []);

  // Update state when a task is updated
  const handleRowUpdate = useCallback((updatedTask, options = {}) => {
    const { scroll = false } = options;
    lastUpdatedTaskRef.current = updatedTask;
    // Optionally save scroll information if needed:
    // lastUpdatedTaskRef.current.scroll = scroll;

    setTasks((prevTasks) => {
      const index = prevTasks.data.findIndex(
        (task) => task.id === updatedTask.id
      );
      if (index === -1) {
        return prevTasks;
      }
      const newData = [...prevTasks.data];
      newData[index] = updatedTask;
      return { ...prevTasks, data: newData };
    });

    setSheetState((prevState) => ({
      ...prevState,
      task: {
        ...prevState.task,
        ...updatedTask,
        action: '',
      },
    }));
  }, []);

  // Establish WebSocket connections for the current user and their teams
  const connectWebSocket = useCallback(
    (currentUser) => {
      if (!currentUser?.id) return;

      try {
        // Listen for broadcasts to the authenticated user
        window.Echo.private(`user.${currentUser.id}`).listen(
          'BroadcastEvent',
          async (event) => {
            await processEvent(
              event,
              handleRowCreate,
              handleRowUpdate,
              syncAnnouncements
            );
          }
        );

        // Listen on each team channel
        currentUser.teams.forEach((team) => {
          window.Echo.private(`team.${team.id}`).listen(
            'BroadcastEvent',
            async (event) => {
              await processEvent(
                event,
                handleRowCreate,
                handleRowUpdate,
                syncAnnouncements
              );
            }
          );
        });
      } catch (error) {
        console.error('Error in WebSocket connection:', error);
      }
    },
    [processEvent, handleRowCreate, handleRowUpdate, syncAnnouncements]
  );

  // Reconnect the WebSocket (with a dummy request to keep the connection alive)
  const reconnectWebSocket = useCallback(async () => {
    try {
      await axios.get(import.meta.env.VITE_APP_URL, { mode: 'no-cors' });
    } catch (error) {
      // Silently handle the error
    }
    connectWebSocket(user);
  }, [connectWebSocket, user]);

  // Set up WebSocket connections on mount and clean them up on unmount
  useEffect(() => {
    reconnectWebSocket();

    return () => {
      if (user?.id) {
        window.Echo.leave(`user.${user.id}`);
      }
      user?.teams?.forEach((team) => {
        window.Echo.leave(`team.${team.id}`);
      });
    };
  }, [user, reconnectWebSocket]);

  // Compute the two sets of tasks: one for tasks assigned to the current user and one for others
  const [todoTasks, openTasks] = useMemo(() => {
    const tasksData = tasks?.data || [];
    const todo = [];
    const open = [];
    const filters = filterBarRef.current.getFilters()
    tasksData.forEach((task) => {
      // Exclude tasks marked as "Completed"
      if (task.status.name === 'Completed' && filters.status_id.value !== 'Completed') return;

      if (task.capabilities.isAssignedToCurrentUser) {
        todo.push(task);
      } else {
        open.push(task);
      }

    });

    return [todo, open];
  }, [tasks]);

  // Toggle the state of the Sheet (side-panel)
  const handleSheetClose = useCallback(() => {
    setSheetState((prevState) => ({
      ...prevState,
      open: !prevState.open,
    }));
  }, []);

  return (
    <>
      <div
        className={cn(
          'flex flex-col h-full min-h-0 p-4 fadeInUp space-y-2',
          { 'bg-app-background border-0 p-0': isMobile }
        )}
      >
        {isMobile ? (
          <TaskMobileView
            todoTasks={todoTasks}
            openTasks={openTasks}
            setTasks={setTasks}
            handleRowUpdate={handleRowUpdate}
            setSheetState={setSheetState}
            lastUpdatedTaskRef={lastUpdatedTaskRef}
            filterBarRef={filterBarRef}
          />
        ) : (
          <>
            <div className="flex flex-col xl:items-center xl:flex-row xl:items-end xl:items-start shrink-0 gap-y-3">
              <FilterBar
                filterBarRef={filterBarRef}
                statuses={statuses}
                teams={teams}
                handleFilter={(filters) =>
                  tabulatorRef.current?.setFilter(filters)
                }
              />
              <div className="ml-auto space-x-2">
                <AnnouncementSheet />
                <TaskSheet />
              </div>
            </div>
            <TaskTabulator
              tabulatorRef={tabulatorRef}
              tasks={[...todoTasks, ...openTasks]}
              setTasks={setTasks}
              setSheetState={setSheetState}
              handleRowUpdate={handleRowUpdate}
              announcements={announcements}
              setPlaceholderAnnouncements={setPlaceholderAnnouncements}
              settings={settings}
            />
          </>
        )}
        {placeholderAnnouncements &&
          createPortal(
            <AnnouncementFeed announcements={announcements} />,
            placeholderAnnouncements
          )}
      </div>

      <Sheet open={sheetState.open} onOpenChange={handleSheetClose}>
        <SheetContent className="p-0 h-full bg-app-background-secondary w-full md:w-[768px] sm:max-w-screen-md">
          <TaskDetail
            task={sheetState.task}
            handleRowUpdate={handleRowUpdate}
            handleSheetClose={handleSheetClose}
          />
        </SheetContent>
      </Sheet>
    </>
  );
};

export default Dashboard;
