import { useEffect, useRef, useState, useCallback } from 'react'

import { usePoll } from '@inertiajs/react'
import { __ } from '@/stores'

import {
  useTask,
  useIsMobile,
  useWebSocket,
  useFilter,
  inertiaResourceSync
} from '@/hooks'

import {
  TaskOverviewSheet,
  TaskDesktopView,
  TaskMobileView
} from '@/components'

const Dashboard = ({ tasks: initTasks }) => {
  const { filters, filtersRef } = useFilter({
    defaultValues: {
      assignedTo: { field: 'assignedTo', type: 'like', value: null },
      status_id: { field: 'status_id', type: '=', value: null },
      team_id: { field: 'team_id', type: '=', value: null }
    },
    options: { filterFromUrlParams: true }
  })
  const { tasks, setTasks, mergeTasks, todoTasks, openTasks, setPagination } =
    useTask(initTasks, filters.get())
  const { newEvent } = useWebSocket()
  const [sheetState, setSheetState] = useState({ open: false, task: null })
  const { isMobile } = useIsMobile()
  const lastUpdatedTaskRef = useRef(null)

  // Poll every 5 minutes as a fallback for WebSockets (300000 ms)
  usePoll(300000, {
    only: ['tasks'],
    onSuccess: ({ props }) => {
      setTasks(props?.tasks?.data)
    }
  })

  // Handle WebSocket events dynamically
  useEffect(() => {
    if (!newEvent) return

    if (newEvent.type === 'task_created' || newEvent.type === 'task_updated') {
      inertiaResourceSync(['tasks'], {
        onSuccess: ({ tasks }) => {
          handleTasksRecon(tasks)
        }
      })
    }

    // THIS WAS THE ORIGINAL CODE BEFORE  DOING IT VIA INERTIA RESOURCE SYNC
    // if (newEvent.type === "task_created") {
    //   getRecord({ url: `tasks/${newEvent.data.id}` }).then((newTask) => {
    //     setTasks((prevTasks) => [newTask, ...prevTasks]);
    //   });
    // }

    // if (newEvent.type === "task_updated") {
    //   getRecord({ url: `tasks/${newEvent.data.id}` }).then((updatedTask) => {
    //     setTasks(tasks.map(task => task.id === updatedTask.id ? updatedTask : task));
    //   });
    // }

    if (newEvent.type === 'announcement_created') {
      inertiaResourceSync(['announcements'])
    }
  }, [newEvent])

  const handleTaskUpdate = useCallback((updatedTask, options = {}) => {
    const { scroll = false } = options
    lastUpdatedTaskRef.current = updatedTask
    lastUpdatedTaskRef.scroll = scroll

    setTasks(prevTasks => {
      const index = prevTasks.findIndex(task => task.id === updatedTask.id)
      if (index === -1) return prevTasks
      const newData = [...prevTasks]
      newData[index] = updatedTask
      return newData
    })
  })

  const handleTasksRecon = useCallback(
    data => {
      const { data: tasks, ...rest } = data
      setPagination(rest)
      mergeTasks(tasks)
    },
    [sheetState]
  )

  // Toggle the state of the Sheet (side-panel)
  const handleSheetClose = useCallback(() => {
    setSheetState(prevState => ({
      ...prevState,
      open: !prevState.open
    }))
  }, [])

  return (
    <>
      {isMobile ? (
        <TaskMobileView
          todoTasks={todoTasks}
          openTasks={openTasks}
          setTasks={setTasks}
          handleTasksRecon={handleTasksRecon}
          handleTaskUpdate={handleTaskUpdate}
          setSheetState={setSheetState}
          lastUpdatedTaskRef={lastUpdatedTaskRef}
          filtersRef={filtersRef}
        />
      ) : (
        <TaskDesktopView
          tasks={tasks}
          todoTasks={todoTasks}
          openTasks={openTasks}
          setTasks={setTasks}
          handleTasksRecon={handleTasksRecon}
          handleTaskUpdate={handleTaskUpdate}
          setSheetState={setSheetState}
          filtersRef={filtersRef}
        />
      )}

      <TaskOverviewSheet
        sheetState={sheetState}
        setSheetState={setSheetState}
        tasks={tasks}
        handleTasksRecon={handleTasksRecon}
        handleTaskUpdate={handleTaskUpdate}
        handleSheetClose={handleSheetClose}
      />
    </>
  )
}

export default Dashboard
