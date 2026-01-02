import React, {useState, useMemo} from 'react'
import {
  useReactTable,
  getCoreRowModel,
  getGroupedRowModel,
  flexRender
} from '@tanstack/react-table'
import {cn} from '@/utils'
import {
  MdOutlineKeyboardArrowDown,
  MdOutlineKeyboardArrowRight
} from 'react-icons/md'
import {useTaskTableColumns, AnnouncementFeed} from '@/components'
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
  ScrollArea
} from '@/base-components'

export const TaskTable = ({
  tasks,
  setSheetState,
  handleTaskUpdate,
}) => {
  const [grouping, setGrouping] = useState(['assignedGroup'])
  const [expanded, setExpanded] = useState(true) // Controls expanded rows
  const columns = useTaskTableColumns({
    handleTaskUpdate,

  })

  // This is a hack to ensure that the group headers are always shown
  // even if there are no tasks in that group. This is necessary because
  // the grouping feature in react-table hides the group headers if there are no tasks.
  const groupedTasks = useMemo(() => {
    const hasAssignedGroupTrue = tasks.some(
      task => task.capabilities.isAssignedToCurrentUser === true
    )
    const hasAssignedGroupFalse = tasks.some(
      task => task.capabilities.isAssignedToCurrentUser === false
    )

    const result = [...tasks]

    if (!hasAssignedGroupTrue) {
      result.unshift({
        id: '__dummy_true__',
        capabilities: {isAssignedToCurrentUser: true},
        isDummy: true
      })
    }

    if (!hasAssignedGroupFalse) {
      result.push({
        id: '__dummy_false__',
        capabilities: {isAssignedToCurrentUser: false},
        isDummy: true
      })
    }

    return result
  }, [tasks])

  const table = useReactTable({
    data: groupedTasks,
    columns,
    state: {
      grouping, // Ensure grouping works
      expanded
    },

    onExpandedChange: setExpanded, // Handles expand/collapse
    getCoreRowModel: getCoreRowModel(),
    getGroupedRowModel: getGroupedRowModel(), // Enables grouping
    autoResetExpanded: false
  })

  return (
    <div className="flex flex-col h-full min-h-0 intro-y ">
      <ScrollArea className="relative">
        <table className="table table-fixed w-full border-separate border-spacing-0 caption-bottom text-sm">
          <TableHeader className="!sticky top-0 z-10 bg-zinc-50">
            {table.getHeaderGroups().map(headerGroup => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map(header => {

                  return (
                    header.id !== 'assignedGroup' && (
                      <TableHead
                        key={header.id}
                        style={{ width: header.getSize() !== 150 ? header.getSize() : '100%' }}
                        className="bg-zinc-50 text-sm text-primary"
                      >
                        {header.isPlaceholder
                          ? null
                          : flexRender(
                              header.column.columnDef.header,
                              header.getContext()
                            )}
                      </TableHead>
                    )
                  )
                })}
              </TableRow>
            ))}
          </TableHeader>

          <TableBody>
            <tr>
              <td colSpan="100%">
                <AnnouncementFeed />
              </td>
            </tr>
          </TableBody>

          <TableBody>
            {table.getRowModel().rows.map(row => {
              if (row.getIsGrouped()) {
                return (
                  <React.Fragment key={row.id}>
                    {/* Group Header Row */}
                    <TableRow className="bg-gray-200 text-sm text-primary">
                      <TableCell className="px-4 py-2" colSpan="100%">
                        <div className="flex items-center ">
                          <button
                            onClick={() => row.toggleExpanded()}
                            className="mr-2 text-lg"
                          >
                            {row.getIsExpanded() ? (
                              <MdOutlineKeyboardArrowDown />
                            ) : (
                              <MdOutlineKeyboardArrowRight />
                            )}
                          </button>
                          <span className="text-sm font-medium !text-slate-700">
                            {row.groupingValue === 'true'
                              ? 'Aan mij toegewezen'
                              : 'Niet aan mij toegewezen'}
                          </span>
                          <span className="text-[10px] !text-white ml-2 bg-green-700 min-w-[1.5rem] h-6 w-6 px-2 flex items-center justify-center rounded-full">
                            {row.subRows.length === 1 &&
                            row.subRows[0].original?.isDummy
                              ? 0
                              : row.subRows.length}
                          </span>
                        </div>
                      </TableCell>
                    </TableRow>

                    {/* SubRows (Tasks inside this group) */}
                    {row.getIsExpanded() &&
                      row.subRows
                        .filter(subRow => !subRow.original?.isDummy)
                        .map((subRow, index) => (
                          <TableRow
                            key={subRow.id}
                            className={cn({
                              'bg-zinc-50 opacity-40': false,
                              'table-row ': true,
                              'table-row-even': index % 2 === 0
                            })}
                          >
                            {subRow.getVisibleCells().map(cell => {
                              return (
                                cell.column.id !== 'assignedGroup' && (
                                  <TableCell
                                    key={cell.id}
                                    className={`h-[60px] ${
                                      cell.column.id !== 'action'
                                        ? 'cursor-pointer'
                                        : ''
                                    }`}
                                    align={cell.column.columnDef.meta?.align}
                                    onClick={
                                      cell.column.id !== 'action'
                                        ? () =>
                                            setSheetState({
                                              open: true,
                                              taskId: subRow.original.id
                                            })
                                        : undefined
                                    }
                                  >
                                    {flexRender(
                                      cell.column.columnDef.cell,
                                      cell.getContext()
                                    )}
                                  </TableCell>
                                )
                              )
                            })}
                          </TableRow>
                        ))}
                  </React.Fragment>
                )
              }
            })}
          </TableBody>
        </table>
      </ScrollArea>
    </div>
  )
}
