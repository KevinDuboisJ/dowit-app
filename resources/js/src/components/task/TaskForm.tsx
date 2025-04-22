import { zodResolver } from "@hookform/resolvers/zod"
import { useForm } from "react-hook-form"
import { debounce } from 'lodash';
import { z } from "zod"
import { toast } from "sonner"
import { __ } from '@/stores'
import { router } from '@inertiajs/react'
import { isValidElement, useState } from 'react'
import axios from 'axios';
import { useAxiosFetchByInput, updateTask } from '@/hooks'
import { getChangedFields } from '@/utils';
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
  Switch,
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
  Separator,
  SegmentButton,
  SegmentHeader,
  SegmentInput,
  SegmentInputContainer,
  MultiSelect,
  AvatarStackRemovable,
  Loader
} from '@/base-components'

const FormSchema = z.object({

  usersToAssign: z.array(
    z.object({
      label: z.string(),
      value: z.number(),
    })
  ).optional(),

  usersToUnassign: z.array(z.number()).optional(),

  status: z
    .string({
      required_error: "Status is leeg",
    }),

  priority: z
    .string({
      required_error: "Prioriteit is leeg",
    }).nullable(),

  needs_help: z.boolean().optional(),

  comment: z.string().optional(),
})

export function TaskForm({ task, handleTaskUpdate, handleTasksRecon, setActiveTab }) {

  const [comment, setComment] = useState('');
  const [loading, setLoading] = useState(false);

  const { list, fetchList } = useAxiosFetchByInput({
    url: "/users/search",
    queryKey: "userInput",
  });

  const defaultValues = {
    usersToAssign: [],
    usersToUnassign: [],
    status: task.status.name,
    priority: task.priority,
    needs_help: task.needs_help,
    comment: '',
  };

  const form = useForm<z.infer<typeof FormSchema>>({
    resolver: zodResolver(FormSchema),
    defaultValues: defaultValues,
  })

  function onSubmit(data: z.infer<typeof FormSchema>) {

    const changedFields = getChangedFields(data, defaultValues, {
      onNoChanges: ({ message }) => {
        toast.warning(message);
      }
    })
    
    setLoading(true);
    
    updateTask(changedFields, task, {

      onSuccess: ({data}) => {
        setActiveTab('details')
        handleTasksRecon(data);
        form.resetField('usersToAssign');
        // setComment('<p></p>');
        toast.success('De gegevens zijn bijgewerkt')
      },

      onError: ({status, data}) => {
        if (status === 409) {
          console.log(data);
          handleTaskUpdate(data.data);
        }
      },

      onComplete: () => { setLoading(false) },
    })
  };

  return (
    <>
      {task.capabilities?.can_update && (
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className='flex flex-col gap-y-4'>

            <FormField
              control={form.control}
              name="usersToAssign"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Toewijzing</FormLabel>
                  <FormControl>
                    <MultiSelect
                      options={list}
                      onValueChange={(selected) => {
                        field.onChange(selected); // Updates form state when MultiSelect changes
                      }}
                      selectedValues={field.value} // Uses form's field value as the selected value
                      placeholder="Wijs persoon toe"
                      variant="inverted"
                      animation={2}
                      maxCount={3}
                      handleInputOnChange={fetchList}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="usersToUnassign"
              render={({ field }) => (
                <FormItem>
                  <FormControl>
                    <AvatarStackRemovable avatars={task?.assignees} onValueChange={(selectedUserId) => {

                      // Check if the selected user id indeed exist in the assignees as a safe mechanism
                      if (task?.assignees.some((obj) => obj.id === selectedUserId)) {
                        const updatedValue = [...(field.value || []), selectedUserId];
                        field.onChange(updatedValue);
                      }

                    }} />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <Separator className='m-0 p-0' />

            <FormField
              control={form.control}
              name="status"
              render={({ field }) => (
                <FormItem>
                  <SegmentButton
                    onValueChange={(value) => {
                      field.onChange(value);
                    }}
                    defaultValue={field.value}>
                    <SegmentHeader>Status wijzigen</SegmentHeader>
                    <SegmentInputContainer>
                      <SegmentInput value='Added' label={__('Added')} />
                      <SegmentInput value='InProgress' label={__('InProgress')} />
                      <SegmentInput value='WaitingForSomeone' label={__('WaitingForSomeone')} />
                      <SegmentInput value='Completed' label={__('Completed')} />
                    </SegmentInputContainer>
                  </SegmentButton>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name='priority'
              render={({ field }) => (
                <FormItem>
                  <SegmentButton
                    onValueChange={(value) => {
                      field.onChange(value);
                    }}
                    defaultValue={field.value}>
                    <SegmentHeader>Prioriteit wijzigen</SegmentHeader>
                    <SegmentInputContainer>
                      <SegmentInput value={null} label='Standaard' />
                      <SegmentInput value='Low' label={__('Low')} />
                      <SegmentInput value='Medium' label={__('Medium')} />
                      <SegmentInput value='High' label={__('High')} />
                    </SegmentInputContainer>
                  </SegmentButton>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="needs_help"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className='text-sm'>Collega nodig</FormLabel>
                  <div className="flex-1 content-center h-8 px-4">
                    <Switch checked={field.value} onCheckedChange={field.onChange} disabled={!task.capabilities.can_update} />
                  </div>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="comment"
              render={({ field }) => (
                <FormItem>
                  <FormLabel className='text-sm'>Commentaar toevoegen</FormLabel>
                  <FormControl>
                      <RichTextEditor
                        className='text-sm h-32 bg-white'
                        onUpdate={(value) => {
                          field.onChange(value); // Updates form state when MultiSelect changes
                        }}
                        value={comment}
                        readonly={!task.capabilities.can_update}

                      />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <div className="ml-auto">
              {loading ? <Loader width={96} height={96} className='relative top-[-50px] left-[60px]' /> : <Button type="submit">Opslaan</Button>}
            </div>

          </form>
        </Form >
      )
      }
      {/* <DataRow task={task} /> */}
    </>
  );
}


const DataRow = ({ task }) => {

  return (
    <div className="p-2 space-y-2">
      {/* Header Row */}
      <div className="flex items-center text-gray-500 text-sm font-medium">
        <div className="flex-1 text-xs px-4">Status wijzigen</div>
        <div className="flex-1 text-xs px-4">Prioriteit wijzigen</div>
        <div className="flex-1 text-xs px-4">Collega nodig</div>
      </div>

      {/* Data Row */}
      <div className="flex items-center divide-x divide-gray-300 text-xs text-gray-800">
        {/* Status */}
        <div className="flex-1 h-8 px-4">

          {/* <Label onValueChange={onPriorityChange} defaultValue={priority}> */}
          <Select onValueChange={onStatusChange} defaultValue={task.status.name}>
            <SelectTrigger className='bg-white'>
              <SelectValue placeholder="" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="Added">{__('Added')}</SelectItem>
              <SelectItem value="WaitingForSomeone">{__('WaitingForSomeone')}</SelectItem>
              <SelectItem value="Completed">{__('Completed')}</SelectItem>
            </SelectContent>
          </Select>
        </div>

        {/* Assigned to */}
        <div className="flex-1 h-8 px-4">

          {/* <Label onValueChange={onPriorityChange} defaultValue={priority}> */}
          <Select onValueChange={onPriorityChange} defaultValue={task.priority}>
            <SelectTrigger className='bg-white'>
              <SelectValue placeholder="Prioriteit" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={null}>Standaard</SelectItem>
              <SelectItem value="Low">{__('Low')}</SelectItem>
              <SelectItem value="Medium">{__('Medium')}</SelectItem>
              <SelectItem value="High">{__('High')}</SelectItem>
            </SelectContent>
          </Select>

        </div>


      </div>
    </div>
  );
};

// Debounced function for backend updates
const debouncedBackendUpdate = debounce(async (field, value, task, handleTaskUpdate) => {

  const originalTask = { ...task };

  // Optimistically update the UI
  handleTaskUpdate({ ...task, [field]: value });

  try {
    const response = await axios.post(`/task/${task.id}/update`, {
      [field]: value,
      updated_at: task.updated_at, // Include the last known timestamp
    });
    console.log(response.data)
    if (response.status === 200) {
      // Reapply backend response to ensure consistency
      handleTaskUpdate(response.data);
    }
  } catch (error) {
    // Revert to the original state if the update fails
    handleTaskUpdate(originalTask);

    if (error.response && error.response.status === 409) {
      toast.error(error.response.data.message, { duration: 6000 });
      handleTaskUpdate(error.response.data.latestData);
    } else {
      console.error(error);
      toast.error('A network error occurred. Please try again.');
    }
  }
}, 500);

