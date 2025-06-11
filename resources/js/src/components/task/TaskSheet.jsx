import React, { useEffect } from 'react'
import { toast } from 'sonner'
import { CalendarIcon } from 'lucide-react'
import { cn } from '@/utils'
import { format, isBefore, startOfToday } from 'date-fns';
import { nlBE } from 'date-fns/locale';
import { __ } from '@/stores';
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm, useWatch } from 'react-hook-form'
import { z } from 'zod'
import { useInertiaFetchList, useAxiosFetchByInput } from '@/hooks'
import axios from "axios";
import { useState } from "react";

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
  Popover,
  PopoverContent,
  PopoverTrigger,
  MultiSelect,
  Input,
  DateTimePicker,
  ScrollArea,
} from '@/base-components';

import {
  PatientAutocomplete
} from '@/components';

const FormSchema = z.object({
  name: z.string().min(1, ('Naam is verplicht')), // Required string with a custom error message
  startDateTime: z
    .date({
      required_error: 'Gelieve een Startdatum te kiezen',
      invalid_type_error: 'Startdatum moet een geldige datum zijn',
    }),

  description: z.string().min(1, 'Gelieve een omschrijving in te vullen'), // Required string
  taskType: z.string().min(1, 'Gelieve een taaktype te kiezen'), // Required string for selected campus
  campus: z.string().min(1, 'Gelieve een campus te kiezen'), // Required string for selected campus

  patient:
    z.object({
      pat_id: z.string().optional(),
      visit_id: z.string().optional(),
      firstname: z.string().optional(),
      lastname: z.string().optional(),
      gender: z.string().optional(),
      birthdate: z.string().optional(),
      ext_id_1: z.string().optional(),
      campus_id: z.string().optional(),
      ward_id: z.string().optional(),
      room_id: z.string().optional(),
      bed_id: z.string().optional(),
      adm_date: z.string().optional(),
      adm_time: z.string().optional(),
      dis_date: z.string().optional(),
      dis_time: z.string().optional(),
    }).optional(),


  space:
    z.array(
      z.object({
        label: z.string(),
        value: z.number(),
      }),
    ).min(1, "Gelieve een locatie te kiezen"),

  spaceTo:
    z.array(
      z.object({
        label: z.string(),
        value: z.number(),
      }),
    ).optional(),

  assignTo:
    z.array(
      z.object({
        label: z.string(),
        value: z.number(),
      }),
    ).optional(),

  teamsMatchingAssignment: z.array(z.any()).optional() // Add this field if not already present

}).refine((data) => !(data?.teamsMatchingAssignment?.length === 0 && data?.assignTo?.length === 0), {
  path: ["assignTo"], // Attach error to assignTo field
  message: "Gelieve een teamtaaktoewijzingsregel aan te maken of de taak handmatig toe te wijzen aan een persoon",
})
  .refine(
    (data) => {
      // If taskType is "1", ensure patient is not undefined
      if (data.taskType === '1') {
        return data.patient && data.patient.pat_id !== undefined;
      }
      return true; // If taskType is not 1, patient can be undefined
    },
    {
      path: ['patient'],
      message: 'Gelieve een patiënt te kiezen',
      required_error: 'Gelieve een patiënt te kiezen'
    }
  );


export const TaskSheet = React.memo(() => {

  const [sheetState, setSheetState] = useState(false);

  const handleSheetClose = () => {
    setSheetState((prevState) => (!prevState));
  }

  return (
    <Sheet open={sheetState} onOpenChange={handleSheetClose} >
      <SheetTrigger asChild>
        <Button type='submit' className='w-full xl:w-auto' size={'sm'}>
          <Heroicon icon='Plus' /> Nieuwe Taak
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
              <SheetTitle>Taak aanmaken</SheetTitle>
              <SheetDescription className='mt-0'>Maak een nieuwe taak aan met de benodigde details</SheetDescription>
            </div>

          </div>
        </SheetHeader>

        <ScrollArea className="h-full p-2 pb-8">
          <CreateTaskForm />
        </ScrollArea>
      </SheetContent>
    </Sheet>
  )
})

const CreateTaskForm = () => {

  const { list: { campuses, task_types: taskTypes } } = useInertiaFetchList({ only: ['campuses', 'task_types'], eager: true });
  const { list: spaces, fetchList: fetchSpaces } = useAxiosFetchByInput({
    url: "/spaces/search",
    queryKey: "userInput",
  });
  const { list: users, fetchList: fetchUsers } = useAxiosFetchByInput({
    url: "/users/search",
    queryKey: "userInput",
  });

  const form = useForm({
    resolver: zodResolver(FormSchema),
    defaultValues: {
      name: '',
      startDateTime: new Date(new Date().setSeconds(0, 0)),
      description: '',
      taskType: '',
      campus: '',
      patient: {},
      space: [],
      spaceTo: [],
      assignTo: [],
      teamsMatchingAssignment: [],
    },
  })

  // Watch the value of the 'taskType' field
  const taskType = useWatch({
    control: form.control,
    name: 'taskType', // The field to observe
    defaultValue: '', // Default value if not set
  });

  async function onSubmit(data) {

    const cleanData = { ...data }

    if (!data.patient.pat_id) {
      delete cleanData.patient;
    }

    form.reset()

    try {
      const response = await axios.post('/task/store', { ...cleanData });

      if (response.status === 200) {
        toast.success('Taak is succesvol aangemaakt');
      }

    } catch (error) {
      // Extract error messages
      const errorMessages = error.response?.data.message ? error.response?.data.message : error.response?.data.errors
        ? Object.values(error.response.data.errors)
          .flat() // Flatten arrays of messages
          .join(', ') // Join messages with commas
        : 'Er is een fout opgetreden. Gelieve dit te melden aan de helpdesk'; // Fallback to the generic error message

      // Show the combined error messages in a toast
      toast.error(`${errorMessages}`);
      console.error(`${error.message}: `, error.response);
    }
  }

  return (
    <>
      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className='p-4 space-y-4'>

          <div className="flex flex-wrap w-full gap-4 "> {/* Container with flex styling */}
            <FormField
              control={form.control}
              name='name'
              render={({ field }) => (
                <FormItem className='basis-0 grow'>
                  <FormLabel>Naam</FormLabel>
                  <FormControl>
                    <Input onChange={(value) => {
                      field.onChange(value); // Updates form state when MultiSelect changes
                    }}
                      className='bg-white'
                      type='text'
                      placeholder='Naam'
                      value={field.value}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name='startDateTime'
              render={({ field }) => (
                <FormItem className='basis-0 grow'>
                  <FormLabel>Startdatum</FormLabel>
                  <Popover>
                    <PopoverTrigger asChild>
                      <FormControl>
                        <Button
                          variant={'outline'}
                          className={cn(
                            'flex w-full h-8 rounded pl-3 text-left font-normal',
                            !field.value && 'text-muted-foreground'
                          )}
                        >
                          {field.value ? (
                            format(field.value, 'PPP p', { locale: nlBE })
                          ) : (
                            <span>Startdatum</span>
                          )}
                          <CalendarIcon className='ml-auto h-4 w-4 opacity-50' />
                        </Button>
                      </FormControl>
                    </PopoverTrigger>
                    <PopoverContent className='w-auto p-0' align='start'>
                      <DateTimePicker
                        selected={field.value}
                        onSelect={field.onChange}
                      />
                    </PopoverContent>
                  </Popover>
                  <FormMessage />
                </FormItem>
              )}
            />
          </div>
          <FormField
            control={form.control}
            name='description'
            render={({ field }) => (
              <FormItem>
                <FormLabel>Omschrijving</FormLabel>
                <FormControl>
                  <Input onChange={(value) => {
                    field.onChange(value); // Updates form state when MultiSelect changes
                  }}
                    className='bg-white xl:items-center'
                    type='text'
                    placeholder='Omschrijving'
                    value={field.value}
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />

          <div className="flex flex-wrap w-full gap-4"> {/* Container with flex styling */}
            <FormField
              control={form.control}
              name="taskType"
              render={({ field }) => {

                return (
                  <FormItem className="grow">
                    <FormLabel>Taaktype</FormLabel>
                    <Select onValueChange={field.onChange} value={field.value} defaultValue={field.value}>
                      <FormControl>
                        <SelectTrigger
                          value={field.value}
                          onClear={() => {
                            field.onChange("")
                          }}
                          className="text-sm text-slate-500 bg-white"
                        >
                          <SelectValue placeholder="Selecteer taaktype" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <CreateSelectOptions rows={taskTypes} />
                      </SelectContent>
                    </Select>
                    <FormMessage />
                  </FormItem>
                )
              }}
            />

            <FormField
              control={form.control}
              name='campus'
              render={({ field }) => (
                <FormItem className='grow'>
                  <FormLabel>Campus</FormLabel>
                  <Select onValueChange={field.onChange} value={field.value} defaultValue={field.value}>
                    <FormControl>
                      <SelectTrigger
                        value={field.value}
                        onClear={() => {
                          field.onChange("")
                        }}
                        className="text-sm text-slate-500 bg-white"
                      >
                        <SelectValue placeholder="Selecteer campus" />
                      </SelectTrigger>
                    </FormControl>
                    <SelectContent>
                      <CreateSelectOptions rows={campuses} />
                    </SelectContent>
                  </Select>
                  <FormMessage />
                </FormItem>
              )}
            />
          </div>

          {/* Conditionally Rendered Campus Field */}
          {taskType === '1' && (
            <FormField
              control={form.control}
              name="patient"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Patiënt</FormLabel>
                  <FormControl>
                    <PatientAutocomplete onValueChange={field.onChange} />

                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
          )}

          <div className="flex flex-wrap w-full gap-4"> {/* Container with flex styling */}
            <FormField
              control={form.control}
              name='space'
              render={({ field }) => (
                <FormItem className='grow'>
                  <FormLabel>Locatie</FormLabel>
                  <FormControl>
                    <MultiSelect
                      options={spaces}
                      onValueChange={(selected) => {
                        field.onChange(selected); // Updates form state when MultiSelect changes
                      }}
                      selectedValues={field.value} // Uses form's field value as the selected value
                      placeholder='Kies een locatie'
                      animation={0}
                      maxSelection={1}
                      handleInputOnChange={fetchSpaces}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Conditionally rendered spaceTo field */}
            {taskType === '1' && (
              <FormField
                control={form.control}
                name='spaceTo'
                render={({ field }) => (
                  <FormItem className='grow'>
                    <FormLabel>Bestemmingslocatie</FormLabel>
                    <FormControl>
                      <MultiSelect
                        options={spaces}
                        onValueChange={(selected) => {
                          field.onChange(selected); // Updates form state when MultiSelect changes
                        }}
                        selectedValues={field.value} // Uses form's field value as the selected value
                        placeholder='Kies een locatie'
                        animation={0}
                        maxSelection={1}
                        handleInputOnChange={fetchSpaces}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            )}

          </div>
          <FormField
            control={form.control}
            name='assignTo'
            render={({ field }) => (
              <FormItem>
                <FormLabel>Toewezen</FormLabel>
                <FormControl>
                  <MultiSelect
                    options={users}
                    onValueChange={(selected) => {
                      field.onChange(selected); // Updates form state when MultiSelect changes
                    }}
                    selectedValues={field.value} // Uses form's field value as the selected value
                    placeholder='Kies een medewerker'
                    animation={0}
                    maxSelection={1}
                    handleInputOnChange={fetchUsers}
                  />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
          <TeamsMatchingAssignmentRules control={form.control} setValue={form.setValue} />
          <div className='text-right'>
            <Button type='submit'>Aanmaken</Button>
          </div>
        </form>
      </Form>
    </>
  )
}

const CreateSelectOptions = ({ rows }) => (
  rows && rows.length > 0 ? (
    rows.map((item) => (
      <SelectItem key={item.value} value={String(item.value)}>
        {item.label}
      </SelectItem>
    ))
  ) : (
    <SelectItem disabled value='0'>
      Geen items beschikbaar
    </SelectItem>
  )
);

const TeamsMatchingAssignmentRules = ({ control, setValue }) => {

  const [taskType, campus, space, spaceTo] = useWatch({ control, name: ['taskType', 'campus'] });
  const { list: teamsMatchingAssignmentRules, fetchList: fetchTeamsMatchingAssignmentRules } = useInertiaFetchList({
    only: ['teamsMatchingAssignmentRules'],
    payload: {
      taskType: taskType,
      campus: campus,
      space: space,
      spaceTo: spaceTo,
    },
  });

  useEffect(() => {
    fetchTeamsMatchingAssignmentRules();
  }, [taskType, campus]);

  useEffect(() => {
    setValue('teamsMatchingAssignment', teamsMatchingAssignmentRules); // Update form state
  }, [teamsMatchingAssignmentRules]);


  return (
    <div>
      <h2 className='text-sm font-medium'>Teams</h2>
      <p className='text-sm text-slate-500'>Dit toont de teams waaraan deze taak zal worden toegewezen op basis van de huidige taaktoewijzingsregels</p>

      {teamsMatchingAssignmentRules.length > 0 ? (
        <div className='mt-2'>
          {teamsMatchingAssignmentRules.map((team, index) => (
            <span
              key={team.id}
              className={cn({ 'ml-2': index > 0 }, 'text-sm text-slate-500 font-medium rounded-sm border p-1 bg-gray-100')}
            >
              {team.name}
            </span>
          ))}
        </div>
      ) : <div className='mt-1 '><p className='text-sm text-yellow-700 italic'>Er is geen teamtaaktoewijzingsregel voor uw selectie</p></div>}

    </div>)
};
