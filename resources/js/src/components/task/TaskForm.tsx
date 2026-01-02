import { useState } from 'react'
import { usePage, router } from '@inertiajs/react'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { toast } from 'sonner'
import { __ } from '@/stores'
import { useAxiosFetchByInput, updateTask } from '@/hooks'
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
  assignees: z
    .array(
      z.object({
        id: z.number()
      })
    )
    .optional(),

  usersToAssign: z
    .array(
      z.object({
        label: z.string(),
        value: z.number()
      })
    )
    .optional(),

  status: z.string({
    required_error: 'Status is leeg'
  }),

  priority: z
    .string({
      required_error: 'Prioriteit is leeg'
    })
    .nullable(),

  needs_help: z.boolean().optional(),

  updated_at: z.coerce.date(),

  comment: z.string().optional()
})

export function TaskForm({ task, setActiveTab }) {
  const { priorities, task_statuses } = usePage().props
  const [loading, setLoading] = useState(false)
  const { list, fetchList } = useAxiosFetchByInput({
    url: '/users/search',
    queryKey: 'userInput'
  })

  const defaultValues = {
    usersToAssign: [],
    assignees: task?.assignees || [],
    status: task.status.name,
    priority: task.priority,
    needs_help: task.needs_help,
    updated_at: task.updated_at,
    comment: ''
  }

  const form = useForm<z.infer<typeof FormSchema>>({
    resolver: zodResolver(FormSchema),
    defaultValues: defaultValues
  })

  function onSubmit(data: z.infer<typeof FormSchema>) {
    const payload = {
      ...data,
      comment: data?.comment.replace(/<(\w+)(\s[^>]*)?>\s*<\/\1>/g, ''), // Remove empty HTML tags, Richt text editor adds <p> when empty
      assignees: Array.from(
        new Set([
          ...data.assignees.map(u => u.id),
          ...data.usersToAssign.map(u => u.value)
        ])
      )
    }

    delete payload.usersToAssign

    setLoading(true)

    updateTask(payload, task, {
      onSuccess: () => {
        form.resetField('usersToAssign')
        toast.success('De gegevens zijn bijgewerkt')
      },

      onComplete: () => {
        setLoading(false)
        setActiveTab('details')
      }
    })
  }

  return (
    <>
      {task.capabilities?.can_update && (
        <Form {...form}>
          <form
            onSubmit={form.handleSubmit(onSubmit)}
            className="flex flex-col gap-y-4"
          >
            <FormField
              control={form.control}
              name="usersToAssign"
              render={({ field }) => {
                return (
                  <FormItem>
                    <FormLabel>Toewijzing</FormLabel>
                    <FormControl>
                      {/* MultiSelect shows selected users based on IDs */}
                      <MultiSelect
                        options={list} // [{label, value}] -> value must be userId
                        selectedValues={field.value}
                        placeholder="Wijs persoon toe"
                        variant="inverted"
                        animation={2}
                        maxCount={3}
                        handleInputOnChange={fetchList}
                        onValueChange={selected => {
                          field.onChange(selected) // Updates form state when MultiSelect changes
                        }}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )
              }}
            />

            <FormField
              control={form.control}
              name="assignees"
              render={({ field }) => (
                <FormItem>
                  <FormControl>
                    <AvatarStackRemovable
                      avatars={field.value}
                      onRemove={userId => {
                        field.onChange(
                          field.value.filter(user => user.id !== userId)
                        )
                      }}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            <Separator className="m-0 p-0" />

            <FormField
              control={form.control}
              name="status"
              render={({ field }) => (
                <FormItem>
                  <SegmentButton
                    onValueChange={value => {
                      field.onChange(value)
                    }}
                    defaultValue={field.value}
                  >
                    <SegmentHeader>Status wijzigen</SegmentHeader>
                    <SegmentInputContainer>
                      {task_statuses.map(status => (
                        <SegmentInput
                          key={status}
                          value={status}
                          label={__(status)}
                        />
                      ))}
                    </SegmentInputContainer>
                  </SegmentButton>
                  <FormMessage />
                </FormItem>
              )}
            />

            <FormField
              control={form.control}
              name="priority"
              render={({ field }) => (
                <FormItem>
                  <SegmentButton
                    onValueChange={value => {
                      field.onChange(value)
                    }}
                    defaultValue={field.value}
                  >
                    <SegmentHeader>Prioriteit wijzigen</SegmentHeader>
                    <SegmentInputContainer>
                      <SegmentInput value={null} label="Standaard" />
                      {priorities.map(priority => (
                        <SegmentInput
                          key={priority}
                          value={priority}
                          label={__(priority)}
                        />
                      ))}
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
                  <FormLabel className="text-sm">Collega nodig</FormLabel>
                  <div className="flex-1 content-center h-8 px-4">
                    <Switch
                      checked={field.value}
                      onCheckedChange={field.onChange}
                      disabled={!task.capabilities.can_update}
                    />
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
                  <FormLabel className="text-sm">
                    Commentaar toevoegen
                  </FormLabel>
                  <FormControl>
                    <RichTextEditor
                      className="text-sm min-h-32 bg-white"
                      value={field.value}
                      onUpdate={value => {
                        field.onChange(value) // Updates form state when MultiSelect changes
                      }}
                      readonly={!task.capabilities.can_update}
                    />
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            <div className="ml-auto">
              {loading ? <Loader /> : <Button type="submit">Opslaan</Button>}
            </div>
          </form>
        </Form>
      )}
    </>
  )
}
