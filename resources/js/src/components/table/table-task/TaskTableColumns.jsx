import { useMemo } from 'react'
import { usePage } from '@inertiajs/react'
import { createColumnHelper } from '@tanstack/react-table'
import { format, parseISO } from 'date-fns'
import { __ } from '@/stores'
import { AvatarStack, Tooltip, RichText } from '@/base-components'
import { TaskActionButton, getPriority, HelpAnimation } from '@/components'

const columnHelper = createColumnHelper()

export const useTaskTableColumns = ({ handleTaskUpdate }) => {
  const { settings, user, tasks } = usePage().props

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
        size: 40,
        enableSorting: false,
        meta: {
          align: 'center'
        },
        cell: ({ cell, row }) => {
          const data = cell.row.original
          const priorityInfo = getPriority(
            data.created_at,
            data.priority,
            settings.TASK_PRIORITY.value
          )
          return row.original.needs_help && row.original.is_active && !row.original.capabilities.isAssignedToCurrentUser ? (
            <Tooltip content="Hulp gevraagd">
              <HelpAnimation/>
            </Tooltip>
          ) : (
            <Tooltip content={priorityInfo.state}>
              <div
                className="w-4 h-4 mx-auto rounded-full text-center"
                style={{ backgroundColor: priorityInfo.color }}
              />
            </Tooltip>
          )
        }
      }),

      // Status Column
      columnHelper.accessor('status.name', {
        id: 'status',
        header: 'Status',
        size: 200,
        cell: ({ cell, row }) => {
          return <span className="relative text-sm">{__(cell.getValue())}</span>
        }
      }),

      // Start Date Column
      columnHelper.accessor('start_date_time', {
        header: 'Tijdstip',
        size: 200,
        cell: ({ cell, row }) => {
          const dateStr = cell.getValue()
          const taskHasPatient = row.original?.visit_id || null

          return (
            <div className="flex flex-col">
              <div className="flex text-gray-900">
                {format(parseISO(dateStr), 'dd MMM')}
                <span className="text-gray-500 ml-1">
                  {format(parseISO(dateStr), 'HH:mm')}
                </span>

                {taskHasPatient && (
                  <>
                    <span className="mx-1 text-gray-500">→</span>
                    <span className="text-gray-500">
                      {format(
                        parseISO(row.original.start_date_time_with_offset),
                        'HH:mm'
                      )}
                    </span>
                  </>
                )}
              </div>
            </div>
          )
        }
      }),

      // Task Name Column
      columnHelper.accessor('name', {
        header: 'Taak',
        size: 360,
        cell: cell => {
          const task = cell.row.original
          const description = task.description || ''
          const plainText = description.replace(/<[^>]+>/g, '') // strip HTML tags
          const TaskHasPatient = task.visit || null
          const preText =
            TaskHasPatient && task.task_planner_id
              ? `${task.visit?.patient?.firstname} ${task.visit?.patient?.lastname} (${task.visit?.patient?.gender}) - ${task.visit?.bed?.room?.number}, ${task.visit?.bed?.number}</br>`
              : ''

          return (
            <div className="flex flex-col">
              <div className="font-bold leading-4 text-sm">
                {cell.getValue()}
              </div>
              <RichText
                className="text-gray-500 text-xs"
                text={`${preText} ${
                  plainText.length > 60
                    ? plainText.slice(0, 60) + '...'
                    : plainText
                }`}
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
        size: 360,
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
        meta: {
          align: 'right'
        },
        cell: cell => (
          <div className="px-2">
            <TaskActionButton
              task={cell.row.original}
              user={user}
              handleTaskUpdate={handleTaskUpdate}
            />
          </div>
        )
      })
    ],
    [settings, user, handleTaskUpdate, tasks.data]
  )

  return columns
}
