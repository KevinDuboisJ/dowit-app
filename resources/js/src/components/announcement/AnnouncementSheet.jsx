import React, { useState } from 'react'
import { toast } from 'sonner'
import axios from "axios";
import { CalendarIcon } from 'lucide-react'
// import { DateRange } from 'react-day-picker'
import { cn } from '@/utils'
import { format, addDays, parseISO } from 'date-fns';
import { nlBE } from "date-fns/locale";
import { __, getVariant } from '@/stores';
import { router } from '@inertiajs/react'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { useAxiosFetchByInput } from '@/hooks'

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
  RichTextEditor,
  Popover,
  PopoverContent,
  PopoverTrigger,
  Calendar,
  MultiSelect,
  ScrollArea,
} from '@/base-components';


const FormSchema = z
  .object({
    selectedUsers: z
      .array(
        z.object({
          label: z.string(),
          value: z.number(),
        })
      )
      .optional(),

    selectedTeams: z
      .array(
        z.object({
          label: z.string(),
          value: z.number(),
        })
      )
      .optional(),

    announcement: z.string({
      required_error: "Gelieve een mededeling te schrijven",
    })
      .min(1, "Gelieve een mededeling te schrijven"), // Minimum of 3 character,

    date: z.object({
      from: z.date({
        required_error: 'Gelieve een startdatum te kiezen', // Specific message for missing "from"
        invalid_type_error: 'Startdatum moet een geldige datum zijn' // Message for invalid type
      }),
      to: z.date().optional().nullable(), // "to" can be optional or null
    }, { required_error: 'Gelieve een startdatum te kiezen' }),
  })
  .refine(
    (data) => (data.selectedUsers && data.selectedUsers.length > 0) || (data.selectedTeams && data.selectedTeams.length > 0),
    {
      message: '',
      path: ['selectedUsers'], // The error will appear under `selectedUsers`
    }
  )
  .refine(
    (data) => (data.selectedUsers && data.selectedUsers.length > 0) || (data.selectedTeams && data.selectedTeams.length > 0),
    {
      message: 'Gelieve minstens één gebruiker of team te selecteren',
      path: ['selectedTeams'], // The error will appear under `selectedUsers`
    }
  );


export const AnnouncementSheet = React.memo(() => {

  const [sheetState, setSheetState] = useState(false);

  const handleSheetClose = () => {
    setSheetState((prevState) => (!prevState));
  }

  return (
    <Sheet open={sheetState} onOpenChange={handleSheetClose}>
      <SheetTrigger asChild>
        <Button type='submit' className='w-full xl:w-auto' size={'sm'}>
          <Heroicon icon='ChatBubbleLeftEllipsis' /> Nieuwe Mededeling
        </Button>
      </SheetTrigger>
      <SheetContent className='flex flex-col p-0 h-full bg-app-background-secondary w-full md:w-[768px] sm:max-w-screen-md'>
        <SheetHeader className='text-left flex flex-col items-center bg-white p-3 py-5 space-y-3 border-b shrink-0'>
          <div className='flex w-full py-2'>
            {/* First Column */}
            <div className='flex flex-wrap self-start'>

              {/* Custom Close Button */}
              <button
                onClick={handleSheetClose}
                className='h-6 focus:outline-none focus:ring-0 focus-visible-ring-0'
              >
                <Heroicon icon='ChevronLeft' className="w-5 stroke-[2.6px]" />
              </button>

            </div>
            <div className="flex flex-wrap flex-col w-full pl-3 leading-tight">
              <SheetTitle>Mededeling aanmaken</SheetTitle>
              <SheetDescription className='mt-0'>Plaats een mededeling voor bepaalde gebruiker(s) of team(s)</SheetDescription>
            </div>
          </div>
        </SheetHeader>

        <ScrollArea className="h-full p-2">
          <CreateAnnouncementForm />
        </ScrollArea>

      </SheetContent>
    </Sheet>
  )
})


const CreateAnnouncementForm = () => {

  const form = useForm({
    resolver: zodResolver(FormSchema),
    defaultValues: {
      selectedUsers: [],
      selectedTeams: [],
      announcement: '',
    },
  })

  const { list: users, fetchList: fetchUserList } = useAxiosFetchByInput({
    url: "/users/search",
    queryKey: "userInput"
  });
  const { list: teams, fetchList: fetchTeamList } = useAxiosFetchByInput({
    url: "/teams/search",
    queryKey: "userInput"
  });

  async function onSubmit(data) {
    try {
      const response = await axios.post('/announce', {
        selectedUsers: data.selectedUsers,
        selectedTeams: data.selectedTeams,
        date: data.date,
        announcement: data.announcement,
      });

      if (response.status === 200) {
        form.reset()
        toast.success("Mededeling is succesvol geplaatst!")
      }
    } catch (error) {

      console.log(error)
      form.reset(formValues)
      // Extract error messages
      const errorMessages = error.response?.data
        ? Object.values(error.response.data)
          .flat() // Flatten arrays of messages
          .join(', ') // Join messages with commas
        : error.message; // Fallback to the generic error message

      // Show the combined error messages in a toast
      toast.error(`Validatie of serverfout: ${errorMessages}`);
      console.error('Validatie of serverfout:', errorMessages);
    }
  }

  return (
    <Form {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} className='p-4 space-y-4'>

        <FormField
          control={form.control}
          name='selectedUsers'
          render={({ field }) => (
            <FormItem>
              <FormLabel>Gebruikers</FormLabel>
              <FormControl>
                <MultiSelect
                  options={users}
                  onValueChange={(selected) => {
                    field.onChange(selected); // Updates form state when MultiSelect changes
                  }}
                  selectedValues={field.value} // Uses form's field value as the selected value
                  placeholder='Wijs persoon toe'
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
          name='selectedTeams'
          render={({ field }) => (
            <FormItem>
              <FormLabel>Teams</FormLabel>
              <FormControl>
                <MultiSelect
                  options={teams}
                  onValueChange={(selected) => {
                    field.onChange(selected); // Updates form state when MultiSelect changes
                  }}
                  selectedValues={field.value} // Uses form's field value as the selected value
                  placeholder='Wijs teams toe'
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
          name='date'
          render={({ field }) => (
            <FormItem className='flex flex-col'>
              <FormLabel>Datum</FormLabel>
              <Popover>
                <PopoverTrigger asChild>
                  <Button
                    id="date"
                    variant={"outline"}
                    className={cn(
                      "w-[300px] justify-start text-left font-normal",
                      !field.value && "text-muted-foreground"
                    )}
                    size={'sm'}
                  >
                    <CalendarIcon className='text-xs text-slate-500 font-normal' />
                    {field.value?.from ? (
                      field.value.to ? (
                        <>
                          {format(new Date(field.value.from), "LLL dd, y", { locale: nlBE })} -{" "}
                          {format(new Date(field.value.to), "LLL dd, y", { locale: nlBE })}
                        </>
                      ) : (
                        format(new Date(field.value.from), "LLL dd, y")
                      )
                    ) : (
                      <span className='text-xs text-slate-500 font-normal'>Kies een datum</span>
                    )}
                  </Button>
                </PopoverTrigger>
                <PopoverContent className="w-auto p-0" align="start">
                  <Calendar
                    initialFocus
                    mode="range"
                    defaultMonth={field.value?.from}
                    selected={field.value}
                    onSelect={(e) => {
                      field.onChange(e);
                    }}
                    numberOfMonths={2}
                  />
                </PopoverContent>
              </Popover>

              <FormDescription>
                Datum wordt gebruikt om te bepalen wanneer de mededeling zichtbaar zal zijn
              </FormDescription>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={form.control}
          name='announcement'
          render={({ field }) => (

            <FormItem>
              <FormLabel>Mededeling</FormLabel>
              <FormControl>
                <RichTextEditor
                  className='text-sm h-32 bg-white'
                  onUpdate={(value) => {
                    field.onChange(value); // Updates form state when MultiSelect changes
                  }}
                  value={field.value}
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />
        <div className='text-right'>
          <Button type='submit'>Aanmaken</Button>
        </div>
      </form>
    </Form>
  )
}