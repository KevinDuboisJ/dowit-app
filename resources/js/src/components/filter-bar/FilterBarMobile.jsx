import { cn } from '@/utils'
import { __ } from '@/stores'
import { usePage } from '@inertiajs/react'
import {
  Input,
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
  Button,
  Heroicon
} from '@/base-components'

export const FilterBarMobile = ({ filters = {} }) => {
  const { user, statuses, task_types: taskTypes } = usePage().props
  const filtersRef = filters.get()
  const isActive = field => filtersRef[field]?.value
  const isTruthy = value => [true, 'true', 1, '1'].includes(value)
  const onlyAssignedToMe = isTruthy(filtersRef.onlyAssignedToMe?.value)

  return (
    <form
      className="flex z-40 flex-col xl:min-w-[600px] xl:items-center xl:flex-row xl:items-end xl:items-start shrink-0 gap-y-3 xl:gap-x-3 font-light"
      onSubmit={e => {
        e.preventDefault()
        filters.apply()
      }}
    >
      <Input
        defaultValue={filtersRef.keyword?.value || ''}
        className={cn(
          'xl:items-center bg-white',
          isActive('keyword') && 'bg-[rgb(233,240,255,0.65)]'
        )}
        type="text"
        placeholder="Zoeken"
        onChange={e => (filtersRef.keyword.value = e.target.value)}
      />

      {/* Status Filter */}
      <Select
        onValueChange={value => (filtersRef.status_id.value = value)}
        defaultValue={filtersRef.status_id.value || ''}
      >
        <SelectTrigger
          className={cn(
            'bg-white text-sm text-slate-500',
            isActive('status_id') && 'bg-[rgb(233,240,255,0.65)]'
          )}
        >
          <SelectValue placeholder="Status" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value={null}>Status</SelectItem>
          {statuses.map(status => (
            <SelectItem key={status.id} value={status.name}>
              {__(status.name)}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      {/* Team Filter */}
      <Select
        onValueChange={value => (filtersRef.team_id.value = value)}
        defaultValue={String(filtersRef.team_id.value || '')}
      >
        <SelectTrigger
          className={cn(
            'bg-white text-sm text-slate-500',
            isActive('team_id') && 'bg-[rgb(233,240,255,0.65)]'
          )}
        >
          <SelectValue placeholder="Team" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value={null}>Team</SelectItem>
          {user.teams.map(team => (
            <SelectItem key={team.id} value={String(team.id)}>
              {team.name}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      {/* Task type Filter */}
      <Select
        onValueChange={value => (filtersRef.task_type_id.value = value)}
        defaultValue={String(filtersRef.task_type_id.value || '')}
      >
        <SelectTrigger
          className={cn(
            'bg-white text-sm text-slate-500',
            isActive('task_type_id') && 'bg-[rgb(233,240,255,0.65)]'
          )}
        >
          <SelectValue placeholder="Taaktype" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value={null}>Alle taaktypes</SelectItem>
          {Object.values(taskTypes ?? {}).map(taskType => (
            <SelectItem
              key={taskType.value}
              value={String(taskType.value)}
            >
              {taskType.name}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      <label className="flex items-center gap-2 text-sm text-slate-700">
        <input
          type="checkbox"
          defaultChecked
          onChange={event => {
            filtersRef.showInactive.value = event.target.checked
          }}
          className="rounded border-slate-300 text-indigo-500 focus:ring-indigo-500"
        />
        <span>Toon inactieve</span>
      </label>

      <label className="flex items-center gap-2 text-sm text-slate-700">
        <input
          type="checkbox"
          defaultChecked={onlyAssignedToMe}
          onChange={event => {
            filtersRef.onlyAssignedToMe.value = event.target.checked
          }}
          className="rounded border-slate-300 text-indigo-500 focus:ring-indigo-500"
        />
        <span>Alleen aan mij toegewezen</span>
      </label>

      {/* Search Button */}
      <Button type="submit" className="w-full xl:w-auto font-normal" size="sm">
        <Heroicon icon="MagnifyingGlass" /> Zoeken
      </Button>
    </form>
  )
}
