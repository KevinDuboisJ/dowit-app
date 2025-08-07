import React, {useMemo} from 'react'
import {usePage} from '@inertiajs/react'
import {createColumnHelper} from '@tanstack/react-table'
import {format, parseISO} from 'date-fns'
import {__} from '@/stores'
import helpAnimation from '@json/animation-help.json'
import Lottie from 'lottie-react'
import {AvatarStack, Tippy, RichText} from '@/base-components'
import {TaskActionButton, getPriority} from '@/components'

export const useTaskTableColumns = ({handleTaskUpdate, handleTasksRecon}) => {
  const {settings, user} = usePage().props
  const columnHelper = createColumnHelper()

  const columns = useMemo(
    () => [
      // Hidden grouping column (DO NOT set a header)
      columnHelper.accessor('capabilities.isAssignedToCurrentUser', {
        id: 'assignedGroup',
        header: () => null, // Remove header
        cell: () => null // Prevent rendering in cells
      }),

      // Priority Column
      columnHelper.accessor('priority', {
        id: 'priority',
        header: '',
        cell: cell => {
          const data = cell.row.original
          const priorityInfo = getPriority(
            data.created_at,
            data.priority,
            settings.TASK_PRIORITY.value
          )
          return (
            <Tippy content={priorityInfo.state}>
              <div
                className="w-4 h-4 mx-auto rounded-full"
                style={{backgroundColor: priorityInfo.color}}
              />
            </Tippy>
          )
        },
        enableSorting: false
      }),

      // Status Column
      columnHelper.accessor('status.name', {
        id: 'status',
        header: 'Status',
        maxWidth: 200,
        cell: ({cell, row}) => {
          return row.original.needs_help ? (
            <Tippy content="Hulp gevraagd" options={{allowHTML: true}}>
              <span className="relative text-sm">
                <HelpAnimation
                  needsHelp={row.original.needs_help}
                  isAssignedToCurrentUser={
                    row.original.capabilities.isAssignedToCurrentUser
                  }
                />
                {__(cell.getValue())}
              </span>
            </Tippy>
          ) : (
            <span className="relative text-sm">{__(cell.getValue())}</span>
          )
        }
      }),

      // Start Date Column
      columnHelper.accessor('start_date_time', {
        header: 'Tijdstip',
        cell: ({cell, row}) => {
          const dateStr = cell.getValue()
          const taskHasPatient = row.original?.visit_id || null

          return (
            <div className="flex flex-col">
              <div className="flex text-gray-900">
                {format(parseISO(dateStr), 'dd MMM')}
                <span className="text-gray-500 ml-1">
                  {format(parseISO(dateStr), 'HH:mm')}
                </span>
              </div>
              {taskHasPatient && (
                <div className="flex text-gray-900">
                  Op locatie:{' '}
                  <span className="text-gray-500 ml-1">
                    {format(
                      parseISO(row.original.start_date_time_with_offset),
                      'HH:mm'
                    )}
                  </span>
                </div>
              )}
            </div>
          )
        }
      }),

      // Task Name Column
      columnHelper.accessor('name', {
        header: 'Taak',
        cell: cell => {
          const task = cell.row.original
          const description = task.description || ''
          const plainText = description.replace(/<[^>]+>/g, '') // strip HTML tags
          const TaskHasPatient = task.visit || null
          const preText = TaskHasPatient && task.task_planner_id ? `${task.visit?.patient?.firstname} ${task.visit?.patient?.lastname} (${task.visit?.patient?.gender}) - ${task.visit?.bed?.room?.number}, ${task.visit?.bed?.number}</br>` : '';

          return (
            <div className="flex flex-col">
              <div className="font-bold leading-4 text-sm">
                {cell.getValue()}
              </div>
              <RichText
                className="text-gray-500 text-xs"
                text={`${preText} ${plainText.length > 60 ? plainText.slice(0, 60) + '...' : plainText}`}
              />
            </div>
          )
        }
      }),

      // Task Type Column
      // columnHelper.accessor('task_type.name', {
      //   id: 'task_type',
      //   header: 'Taaktype',
      //   size: 150,
      //   cell: (cell) => <div className='text-sm'>{cell.getValue()}</div>,
      // }),

      // Assigned Users Column
      columnHelper.accessor('assignees', {
        id: 'assignees',
        header: 'Toegewezen',
        cell: cell => {
          const users = cell.getValue()
          return <AvatarStack avatars={users} />
        }
      }),

      // columnHelper.accessor('comments', {
      //   id: 'comment',
      //   header: 'Recente commentaar',
      //   cell: ({ cell }) => {
      //     return <RichText text={cell.getValue()?.[0]?.content} className='text-sm'></RichText>;
      //   },
      // }),

      // Action Button Column
      columnHelper.display({
        id: 'action',
        maxWidth: 200,
        meta: {
          align: 'right'
        },
        cell: cell => (
          <div className="px-2">
            <TaskActionButton
              task={cell.row.original}
              user={user}
              handleTaskUpdate={handleTaskUpdate}
              handleTasksRecon={handleTasksRecon}
            />
          </div>
        )
      })
    ],
    []
  )

  return columns
}

// Memoized component that only re-renders when `needsHelp` or `isAssignedToCurrentUser` changes.
const HelpAnimation = React.memo(({needsHelp, isAssignedToCurrentUser}) => {
  // Only render if help is needed and the current user is not assigned.
  if (!needsHelp || isAssignedToCurrentUser) return null
  return (
    <Lottie
      className="absolute -top-6 -left-5 w-6 h-6 mr-2 cursor-help"
      animationData={helpAnimation}
      loop={true}
      autoplay={true}
    />
  )
})
