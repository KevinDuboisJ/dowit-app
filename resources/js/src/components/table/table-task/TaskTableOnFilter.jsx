import {
  useReactTable,
  getCoreRowModel,
  flexRender
} from '@tanstack/react-table'
import { cn } from '@/utils'
import { useTaskTableColumnsOnFilter, AnnouncementFeed } from '@/components'
import {
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
  ScrollArea
} from '@/base-components'

export const TaskTableOnFilter = ({
  data,
  setSheetState,
  handleTaskUpdate
}) => {
  const columns = useTaskTableColumnsOnFilter({ handleTaskUpdate })
  const tasks = data?.data ?? [] // Extract tasks from paginated response

  const table = useReactTable({
    data: tasks ?? [],
    columns,
    getCoreRowModel: getCoreRowModel()
  })

  return (
    <div className="flex flex-col h-full min-h-0 intro-y">
      <ScrollArea className="relative">
        <table className="table table-fixed w-full border-separate border-spacing-0 caption-bottom text-sm">
          <TableHeader className="!sticky top-0 z-10 bg-zinc-50">
            {table.getHeaderGroups().map(headerGroup => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map(header => (
                  <TableHead
                    key={header.id}
                    style={{
                      width:
                        header.getSize() !== 150 ? header.getSize() : '100%'
                    }}
                    className="bg-zinc-50 text-sm text-primary"
                  >
                    {header.isPlaceholder
                      ? null
                      : flexRender(
                          header.column.columnDef.header,
                          header.getContext()
                        )}
                  </TableHead>
                ))}
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
            {table.getRowModel().rows.map((row, index) => (
              <TableRow
                key={row.id}
                className={cn({
                  'bg-zinc-50 opacity-40': false,
                  'table-row': true,
                  'table-row-even': index % 2 === 0
                })}
              >
                {row.getVisibleCells().map(cell => (
                  <TableCell
                    key={cell.id}
                    className={`h-[60px] ${cell.column.id !== 'action' ? 'cursor-pointer' : ''}`}
                    align={cell.column.columnDef.meta?.align}
                    onClick={
                      cell.column.id !== 'action'
                        ? () =>
                            setSheetState({
                              open: true,
                              taskId: row.original.id
                            })
                        : undefined
                    }
                  >
                    {flexRender(cell.column.columnDef.cell, cell.getContext())}
                  </TableCell>
                ))}
              </TableRow>
            ))}

            {(!tasks || tasks.length === 0) && (
              <TableRow>
                <TableCell
                  colSpan="100%"
                  className="py-10 text-center text-slate-500"
                >
                  Geen taken gevonden met deze filters.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </table>
      </ScrollArea>
    </div>
  )
}
