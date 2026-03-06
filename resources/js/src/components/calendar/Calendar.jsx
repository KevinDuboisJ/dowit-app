import { CalendarIcon } from 'lucide-react'
import { cn } from '@/utils'
import { format } from 'date-fns'
import { nlBE } from 'date-fns/locale'
import { __ } from '@/stores'
import {
  Button,
  Popover,
  PopoverContent,
  PopoverTrigger,
  Calendar as ReactDayCalendar
} from '@/base-components'

const Calendar = ({ label = 'Kies een datum', value, onChange = () => {} }) => {
  return (
    <Popover>
      <PopoverTrigger asChild>
        <Button
          id="date"
          variant={'outline'}
          className={cn(
            'w-[300px] justify-start text-left font-normal',
            !value && 'text-muted-foreground'
          )}
          size={'sm'}
        >
          <CalendarIcon className="text-sm text-slate-500 font-normal" />
          {value?.from ? (
            value.to ? (
              <>
                {format(new Date(value.from), 'LLL dd, y', {
                  locale: nlBE
                })}{' '}
                -{' '}
                {format(new Date(value.to), 'LLL dd, y', {
                  locale: nlBE
                })}
              </>
            ) : (
              format(new Date(value.from), 'LLL dd, y')
            )
          ) : (
            <span className="text-sm text-slate-500 font-normal">{label}</span>
          )}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-auto p-0" align="start">
        <ReactDayCalendar
          initialFocus
          mode="range"
          defaultMonth={value?.from}
          selected={value}
          onSelect={e => {
            onChange(e)
          }}
        />
      </PopoverContent>
    </Popover>
  )
}

Calendar.displayName = 'Calendar'

export { Calendar }
