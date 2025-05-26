import {
  flexRender,
  getCoreRowModel,
  getFacetedRowModel,
  getFacetedUniqueValues,
  getFilteredRowModel,
  getSortedRowModel,
  useReactTable,
} from "@tanstack/react-table"

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
  Input,
} from "@/base-components"

import {
  Pagination,
} from "@/components"

import classNames from "classnames"
import { useState } from 'react'
import { router } from '@inertiajs/react'
import { debounce } from 'lodash';


export const AssetTable = ({ columns, data }) => {

  const [rowSelection, setRowSelection] = useState({})
  const [columnVisibility, setColumnVisibility] = useState({})
  const [columnFilters, setColumnFilters] = useState([])
  const [sorting, setSorting] = useState({ column: null, direction: 'ASC' })

  const sortHandler = ({ column }) => {

    const columnKey = column.columnDef.accessorKey
    const canSort = !column.columnDef.hasOwnProperty('canSort') || (column.columnDef.hasOwnProperty('canSort') && column.columnDef.canSort)
    let sortColumn = sorting.column;
    let sortDirection = sorting.direction;

    if (!canSort) {
      return;
    }

    if (sortColumn === columnKey) {
      // If the same column is being sorted, toggle the direction
      sortDirection = sortDirection === 'ASC' ? 'DESC' : 'ASC';
    } else {
      // If a different column is being sorted, reset the direction to 'ASC'
      sortDirection = 'ASC';
    }

    const newSorts = { column: columnKey, direction: sortDirection }

    router.reload({
      data: {
        sorting: newSorts
      },
      preserveState: true
    })

    setSorting(newSorts);

  };

  const table = useReactTable({
    data: data.data,
    columns,
    state: {
      sorting,
      columnVisibility,
      rowSelection,
      columnFilters,
    },
    enableRowSelection: true,
    onRowSelectionChange: setRowSelection,
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    onColumnVisibilityChange: setColumnVisibility,
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getFacetedRowModel: getFacetedRowModel(),
    getFacetedUniqueValues: getFacetedUniqueValues(),
  })

  const debounceSearch = debounce((e) => {
    router.reload({ data: { search: e.target.value }, preserveState: true })
  }, 250)

  const handleSearch = (e) => {
    debounceSearch(e)
  }


  return (
    <div className="flex flex-col min-h-0 fadeInUp">
      <div className="flex items-center mb-2">
        <Input
          placeholder="Zoeken"
          onChange={handleSearch}
          className="max-w-64 bg-white text-xs text-slate-500"
        />
      </div>
      <div className="border rounded-md overflow-auto">
        <Table className="table">
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map((header) => {
                  return (
                    <TableHead key={header.id} onClick={() => sortHandler(header)}>
                      {header.isPlaceholder
                        ? null
                        : flexRender(
                          header.column.columnDef.headerText || header.column.columnDef.header,
                          header.getContext()
                        )}
                    </TableHead>
                  )
                })}
              </TableRow>
            ))}

          </TableHeader>
          <TableBody>
            {table.getRowModel().rows?.length ? (

              table.getRowModel().rows?.map((row, index) => (
                <TableRow
                  key={row.original.id}
                  data-state={row.getIsSelected() && "selected"}
                  className={
                    classNames({
                      'table-row ': true,
                      'table-row-even': index % 2 === 0,
                    }, 'cursor-default')}
                >
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id}>
                      {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell colSpan={columns.length} className="h-24 text-center">
                  Geen resultaten.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>
      <Pagination {...data} />
    </div>

  )
}