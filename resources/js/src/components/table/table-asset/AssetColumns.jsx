import {
  Button,
  Heroicon,
} from "@/base-components"

export const AssetColumns = [
  {
    accessorKey: "name",
    header: 'naam',
    headerText: ({ column }) => <HeaderText column={column} />,
    cell: ({ row }) => (
      <a className="text-primary text-sm" href={row.original.link} target="_blank">
        {row.original.name}
      </a>

    )
  },
]

const HeaderText = ({ column }) => {
  return (
    <Button
      variant="ghost"
    >
      {column.columnDef.header}
      <Heroicon icon='ArrowsUpDown' className="ml-2 h-3 w-3" />
    </Button>
  )
}
