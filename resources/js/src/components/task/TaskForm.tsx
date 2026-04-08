import { useState } from 'react'
import axios from 'axios'
import { usePage, router } from '@inertiajs/react'
import { zodResolver } from '@hookform/resolvers/zod'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { toast } from 'sonner'
import { __ } from '@/stores'
import { updateTask } from '@/hooks'
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

export function TaskForm({ task, setActiveTab }) {
  const { priorities, statuses, tags } = usePage().props
  const [loading, setLoading] = useState(false)

  const defaultValues = {
    usersToAssign: [],
    assignees: task?.assignees || [],
    tags: task?.tags?.map(tag => ({ label: tag.name, value: tag.id })) || [],
    status: task.status.name,
    priority: task.priority,
    help_requested: task.help_requested,
    updated_at: task.updated_at,
    comment: ''
  }

  const form = useForm<z.infer<typeof FormSchema>>({
    resolver: zodResolver(FormSchema),
    defaultValues: defaultValues
  })

  function onSubmit(data: z.infer<typeof FormSchema>) {
    const cleanedComment =
      data.comment?.replace(/<(\w+)(\s[^>]*)?>\s*<\/\1>/g, '').trim() || ''

    const hasChanges =
      JSON.stringify(data.assignees ?? []) !==
        JSON.stringify(defaultValues.assignees ?? []) ||
      JSON.stringify(data.usersToAssign ?? []) !==
        JSON.stringify(defaultValues.usersToAssign ?? []) ||
      JSON.stringify(data.tags ?? []) !==
        JSON.stringify(defaultValues.tags ?? []) ||
      data.status !== defaultValues.status ||
      data.priority !== defaultValues.priority ||
      data.help_requested !== defaultValues.help_requested ||
      cleanedComment !== ''

    if (!hasChanges) {
      form.setError('comment', {
        type: 'manual',
        message: 'Pas minstens één veld aan'
      })
      return
    }

    const payload = {
      ...data,
      comment: data?.comment.replace(/<(\w+)(\s[^>]*)?>\s*<\/\1>/g, ''), // Remove empty HTML tags, Richt text editor adds <p> when empty
      assignees: Array.from(
        new Set([
          ...data.assignees.map(u => u.id),
          ...data.usersToAssign.map(u => u.value)
        ])
      ),
      tags: data.tags.map(i => i.value) || []
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
                        defaultValue={field.value}
                        placeholder="Wijs persoon toe"
                        maxSelection={5}
                        fetchOptions={fetchUsers}
                        onChange={field.onChange}
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

            {task?.assignees.length > 0 && <Separator className="m-0 p-0" />}

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
                      {statuses.map(status => (
                        <SegmentInput
                          key={status.name}
                          value={status.name}
                          label={status.label}
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

            {Array.isArray(tags) && tags.length > 0 && (
              <FormField
                control={form.control}
                name="tags"
                render={({ field }) => {
                  return (
                    <FormItem>
                      <FormLabel>Tags</FormLabel>
                      <FormControl>
                        <MultiSelect
                          staticOptions={tags}
                          defaultValue={field.value}
                          onChange={field.onChange}
                          placeholder="Tags toevoegen"
                        />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )
                }}
              />
            )}

            <FormField
              control={form.control}
              name="help_requested"
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

  tags: z
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

  help_requested: z.boolean().optional(),

  updated_at: z.coerce.date(),

  comment: z.string().optional()
})

const fetchUsers = query =>
  axios.post('/users/search', { userInput: query }).then(res => res.data)
