import { z } from 'zod'

export const formSchema = z
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