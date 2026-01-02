// Tasks state is tracked by inertia.js, by implementing router.replace in V2 i can change the tasks data for
import { useState, useMemo } from 'react'
import { usePage } from '@inertiajs/react'

export const useTask = () => {
  const { tasks } = usePage().props
  const [pagination, setPagination] = useState(() => {
    const { data, ...rest } = tasks
    return rest
  })

  // Compute `todoTasks`, `openTasks`, and merged `tasks` together in one go
  const { todoTasks, openTasks, sortedTasks } = useMemo(() => {
    const tasksData = tasks.data || []
    const todo = []
    const open = []

    tasksData.forEach(task => {
      if (task.capabilities.isAssignedToCurrentUser) {
        todo.push(task)
      } else {
        open.push(task)
      }
    })

    return {
      todoTasks: todo,
      openTasks: open,
      sortedTasks: [...todo, ...open]
    }
  }, [tasks, tasks.data]) // Runs only when `tasks` change

  return {
    tasks: sortedTasks,
    todoTasks,
    openTasks,
    pagination,
    setPagination
  }
}