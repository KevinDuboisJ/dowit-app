import { useState, useEffect } from 'react'
import { router } from '@inertiajs/react'
import { toast } from 'sonner'
import { CalendarIcon } from 'lucide-react'
import { cn } from '@/utils'
import { format } from 'date-fns'
import { nlBE } from 'date-fns/locale'
import { __ } from '@/stores'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { useAxiosFetchByInput } from '@/hooks'
import { formSchema } from './'

import {
  Button,
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
  RichTextEditor,
  Popover,
  PopoverContent,
  PopoverTrigger,
  Calendar,
  MultiSelect,
  Loader
} from '@/base-components'

export const AnnouncementForm = ({ editing, onCreated, onSaved }) => {
  const [loading, setLoading] = useState(false)

  const form = useForm({
    resolver: zodResolver(formSchema),
    defaultValues: {
      selectedUsers: [],
      selectedTeams: [],
      announcement: ''
    }
  })

  const { list: users, fetchList: fetchUserList } = useAxiosFetchByInput({
    url: '/users/search',
    queryKey: 'userInput'
  })
  const { list: teams, fetchList: fetchTeamList } = useAxiosFetchByInput({
    url: '/teams/search',
    queryKey: 'userInput'
  })

  // When clicking “Bewerken”, prefill the form
  useEffect(() => {
    if (!editing) {
      form.reset({
        selectedUsers: [],
        selectedTeams: [],
        date: undefined,
        announcement: ''
      })
      return
    }

    form.reset({
      selectedUsers: editing.users ?? [],
      selectedTeams: editing.teams ?? [],
      date: editing.start_date
        ? {
            from: new Date(editing.start_date),
            to: editing.end_date ? new Date(editing.end_date) : undefined
          }
        : undefined,
      announcement: editing.content ?? ''
    })
  }, [editing, form])

  async function onSubmit(data) {
    setLoading(true)

    const payload = {
      selectedUsers: data.selectedUsers ?? [],
      selectedTeams: data.selectedTeams ?? [],
      date: data.date,
      announcement: data.announcement
    }

    const url = editing ? `/announcements/${editing.id}` : '/announcements'

    router.visit(url, {
      method: editing ? 'put' : 'post',
      data: payload,
      preserveScroll: true,
      preserveState: true,
      only: ['ownAnnouncements', 'announcements'],

      onSuccess: () => {
        if (editing) {
          toast.success('Mededeling is succesvol bijgewerkt!')
          onSaved?.()
        } else {
          form.reset()
          toast.success('Mededeling is succesvol geplaatst!')
          onCreated?.()
        }
      },

      onError: errors => {
        const errorMessages =
          errors && Object.keys(errors).length
            ? Object.values(errors).flat().join(', ')
            : 'Er is een fout opgetreden. Gelieve dit te melden aan de helpdesk'

        toast.error(errorMessages)
      },

      onFinish: () => {
        setLoading(false)
      }
    })
  }

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className="p-4 space-y-4">
        <FormField
          control={form.control}
          name="selectedUsers"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Gebruikers</FormLabel>
              <FormControl>
                <MultiSelect
                  options={users}
                  onValueChange={selected => {
                    field.onChange(selected) // Updates form state when MultiSelect changes
                  }}
                  selectedValues={field.value} // Uses form's field value as the selected value
                  placeholder="Wijs persoon toe"
                  animation={2}
                  handleInputOnChange={fetchUserList}
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="selectedTeams"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Teams</FormLabel>
              <FormControl>
                <MultiSelect
                  options={teams}
                  onValueChange={selected => {
                    field.onChange(selected) // Updates form state when MultiSelect changes
                  }}
                  selectedValues={field.value} // Uses form's field value as the selected value
                  placeholder="Wijs teams toe"
                  animation={2}
                  handleInputOnChange={fetchTeamList}
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="date"
          render={({ field }) => (
            <FormItem className="flex flex-col">
              <FormLabel>Datum</FormLabel>
              <Popover>
                <PopoverTrigger asChild>
                  <Button
                    id="date"
                    variant={'outline'}
                    className={cn(
                      'w-[300px] justify-start text-left font-normal',
                      !field.value && 'text-muted-foreground'
                    )}
                    size={'sm'}
                  >
                    <CalendarIcon className="text-sm text-slate-500 font-normal" />
                    {field.value?.from ? (
                      field.value.to ? (
                        <>
                          {format(new Date(field.value.from), 'LLL dd, y', {
                            locale: nlBE
                          })}{' '}
                          -{' '}
                          {format(new Date(field.value.to), 'LLL dd, y', {
                            locale: nlBE
                          })}
                        </>
                      ) : (
                        format(new Date(field.value.from), 'LLL dd, y')
                      )
                    ) : (
                      <span className="text-sm text-slate-500 font-normal">
                        Kies een datum
                      </span>
                    )}
                  </Button>
                </PopoverTrigger>
                <PopoverContent className="w-auto p-0" align="start">
                  <Calendar
                    initialFocus
                    mode="range"
                    defaultMonth={field.value?.from}
                    selected={field.value}
                    onSelect={e => {
                      field.onChange(e)
                    }}
                    numberOfMonths={2}
                  />
                </PopoverContent>
              </Popover>

              <FormDescription>
                Datum wordt gebruikt om te bepalen wanneer de mededeling
                zichtbaar zal zijn
              </FormDescription>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name="announcement"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Mededeling</FormLabel>
              <FormControl>
                <RichTextEditor
                  className="text-sm h-32 bg-white"
                  onUpdate={value => {
                    field.onChange(value) // Updates form state when MultiSelect changes
                  }}
                  value={field.value}
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <div className="flex items-center justify-end gap-2">
          {/* RESET BUTTON */}
          <Button
            type="button"
            variant="outline"
            onClick={() => {
              form.reset({
                selectedUsers: [],
                selectedTeams: [],
                date: undefined,
                announcement: ''
              })

              if (onSaved) onSaved()
              toast.success('Formulier is leeggemaakt')
            }}
          >
            Reset
          </Button>

          {/* SUBMIT BUTTON */}
          {loading ? (
            <Loader className="top-[1px]" />
          ) : (
            <Button type="submit">{editing ? 'Bijwerken' : 'Aanmaken'}</Button>
          )}
        </div>
      </form>
    </Form>
  )
}
