import { zodResolver } from "@hookform/resolvers/zod"
import { useForm } from "react-hook-form"
import { z } from "zod"
import {
  Button,
  Heroicon,
  MultiSelect,
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
  Separator,
  Avatar,
  AvatarFallback,
  AvatarImage,
} from '@/base-components';
import { toast } from 'sonner'
import axios from 'axios';
import { useInertiaFetchByInput } from '@/hooks'

const formSchema = z.object({
  selectedUsers: z.array(
    z.object({
      label: z.string(),
      value: z.number(),
    })
  ).optional(),
});


export function AssignmentForm({ task, handleRowUpdate }) {

  const { list, fetchList } = useInertiaFetchByInput({ only: ['users'] });

  const form = useForm({
    resolver: zodResolver(formSchema),
    defaultValues: {
      selectedUsers: [],
    },
  })

  const onSubmit = async (data) => {
    await handleAjaxRequest(
      () => axios.post(`/task/${task.id}/update`, {
        action: 'assign',
        selectedUsers: data.selectedUsers,
        updated_at: task.updated_at,
      }),
      (data) => {
        console.log(data)
        handleRowUpdate(data); // Success callback
        form.setValue('selectedUsers', []); // Additional logic for onSubmit
      }
    );
  };

  const handleAjaxRequest = async (requestFn, successCallback) => {
    try {
      const response = await requestFn();

      if (response.status === 200) {
        successCallback(response.data);
      } else {
        console.warn('Unexpected response status:', response.status);
      }
    } catch (error) {
      if (error.response && error.response.status === 422) {
        toast.error(error.response.data.message, {
          duration: Infinity, // Toast will remain until the user closes it
        });
      } else {
        console.log(error)
        toast.error('Er is een fout opgetreden. Controleer uw netwerkverbinding');
      }
    }
  }

  // Update internal state when selectedValues prop changes
  return (
    <>
      <Form {...form}>
        <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-8">
          <FormField
            control={form.control}
            name="selectedUsers"
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
          <Button type="submit">Toewijzen</Button>
        </form>
      </Form>
      <Separator className="my-4" />

      <div className="space-y-1">
        <h4 className="text-sm font-medium leading-none">Reeds toegewezen</h4>
        <p className="text-sm text-muted-foreground">
          Personen
        </p>
      </div>

      <div className="flex space-x-2 my-2">
        {task?.assigned_users?.map((user) => (
          <div key={user.id} className="relative">
            <Avatar>
              <AvatarImage src={user.image_path} alt={user.lastname} />
              <AvatarFallback>{user.lastname.charAt(0)}</AvatarFallback>
            </Avatar>
            {/* Delete Icon */}
            <Heroicon icon="XMark"
              onClick={() => onUnassignment(user.id)}
              className="h-5 w-5 absolute top-1 right-1 transform translate-x-1/2 -translate-y-1/2 cursor-pointer text-red-800"
              size={16}
            />
          </div>
        ))}
      </div>
    </>
  )
}