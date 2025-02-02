import {
  Sheet,
  SheetTrigger,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
  Heroicon,
  Button,
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
  Toaster,
  RichTextEditor,
  Popover,
  PopoverContent,
  PopoverTrigger,
  Calendar,
  MultiSelect,
  TableRow
} from '@/base-components';

export const TaskFeed = ({ announcements }) => {

  return (
    <table>
      {announcements && announcements.map((announcement) => (
        <TableRow
          key={announcement.id}
          // data-state={row.getIsSelected() && "selected"}
          className={
            classNames({
              'table-row ': true,
              // 'table-row-even': index % 2 === 0,
            }, 'cursor-default')}
        >
          <TableCell key={cell.id}>
            {announcement.content}
          </TableCell>

        </TableRow>

      ))
      }
    </table>
  )
}
