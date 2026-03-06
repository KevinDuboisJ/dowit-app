import { useEffect, useRef, useState, useCallback } from 'react'
import { usePoll, router } from '@inertiajs/react'
import { __ } from '@/stores'

import {
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

const Dashboard = ({ tasks }) => {
  const { filters } = useFilter({
    defaultValues: {
      assignedTo: { field: 'assignedTo', type: 'contains', value: '' },
      status_id: { field: 'status_id', type: 'equals', value: '' }, // you store status "name" string
      team_id: { field: 'team_id', type: 'equals', value: '' },
      dateRange: {
        field: 'dateRange',
        type: 'between',
        value: { from: null, to: null }
      },
      keyword: { field: 'keyword', type: 'contains', value: '' },
      onlyAssignedToMe: {
        field: 'onlyAssignedToMe',
        type: 'equals',
        value: false
      }
    },
    options: { filterFromUrlParams: true }
  })
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
          data={tasks}
          handleTaskUpdate={handleTaskUpdate}
          setSheetState={setSheetState}
          lastUpdatedTaskRef={lastUpdatedTaskRef}
          filters={filters}
        />
      ) : (
        <TaskDesktopView
          data={tasks}
          handleTaskUpdate={handleTaskUpdate}
          setSheetState={setSheetState}
          filters={filters}
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
