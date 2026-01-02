import { useEffect, useRef, useState, useCallback } from 'react'
import { usePoll, router } from '@inertiajs/react'
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

const Dashboard = () => {
  const { filters, filtersRef } = useFilter({
    defaultValues: {
      assignedTo: { field: 'assignedTo', type: 'like', value: null },
      status_id: { field: 'status_id', type: '=', value: null },
      team_id: { field: 'team_id', type: '=', value: null }
    },
    options: { filterFromUrlParams: true }
  })
  const { tasks, todoTasks, openTasks } = useTask()
  const { newEvent } = useWebSocket()
  const [sheetState, setSheetState] = useState({ open: false, task: null })
  const { isMobile } = useIsMobile()
  const lastUpdatedTaskRef = useRef(null)

  // Poll every 5 minutes as a fallback for WebSockets (300000 ms)
  usePoll(300000, {
    only: ['tasks']
  })

  // Handle WebSocket events dynamically
  useEffect(() => {
    if (!newEvent) return

    if (newEvent.type === 'task_created' || newEvent.type === 'task_updated') {
      inertiaResourceSync(['tasks'], {})
    }

    if (newEvent.type === 'announcement_created') {
      inertiaResourceSync(['announcements'])
    }
  }, [newEvent])

  const handleTaskUpdate = useCallback((updatedTask, options = {}) => {
    const { scroll = false } = options
    lastUpdatedTaskRef.current = updatedTask
    lastUpdatedTaskRef.scroll = scroll

    router.replace({
      props: prevProps => ({
        ...prevProps,
        tasks: {
          ...prevProps.tasks,
          data: prevProps.tasks.data.map(row =>
            row.id === updatedTask.id ? { ...row, ...updatedTask } : row
          )
        }
      }),
      preserveScroll: true,
      preserveState: true
    })
  })

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
          handleTaskUpdate={handleTaskUpdate}
          setSheetState={setSheetState}
          filtersRef={filtersRef}
        />
      )}

      <TaskOverviewSheet
        sheetState={sheetState}
        setSheetState={setSheetState}
        tasks={tasks}
        handleTaskUpdate={handleTaskUpdate}
        handleSheetClose={handleSheetClose}
      />
    </>
  )
}

export default Dashboard
