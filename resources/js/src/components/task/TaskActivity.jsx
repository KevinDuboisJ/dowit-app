import { format, parseISO, isToday } from 'date-fns'
import { nl } from 'date-fns/locale'
import { __ } from '@/stores'
import { RichText, Heroicon } from '@/base-components'
import { LucideHandshake } from 'lucide-react'

export const CommentEventEnum = Object.freeze({
  TaskCreated: 'task_created',
  TaskStarted: 'task_started',
  TaskUpdated: 'task_updated',
  TaskCompleted: 'task_completed',
  TaskRejected: 'task_rejected',
  TaskHelpRequested: 'task_help_requested',
  TaskHelpGiven: 'task_help_given',
  Announcement: 'announcement'
})

export const TaskActivity = ({ comments }) => {
  const lastIndex = comments.length - 1

  if (!Array.isArray(comments) || comments.length === 0) {
    return (
      <div className="h-full">
        <p className="text-sm text-muted-foreground">
          Er zijn nog geen commentaren
        </p>
      </div>
    )
  }

  return (
    <div className="h-full">
      <div className="flex flex-col gap-2 pt-0">
        <div className="px-4 pb-2 mt-2 relative">
          {/* global vertical line */}
          <div className="absolute left-[15px] top-0 bottom-0 border-l-2 fadeInUp" />

          {comments.map((item, index) => {
            const isLastItem = index === lastIndex
            const activity = {
              ...item,
              isLastItem: isLastItem
            }

            return (
              <TimelineRow
                key={activity.id ?? `row-${index}`}
                activity={activity}
                index={index}
              />
            )
          })}
        </div>
      </div>
    </div>
  )
}

/* ---------------- helpers ---------------- */
const RenderBox = ({ activity }) => {
  if (activity.event === CommentEventEnum.TaskCreated) {
    return <TaskCreatedBox activity={activity} />
  }

  if (activity.event === CommentEventEnum.TaskStarted) {
    return <TaskStartedBox activity={activity} />
  }

  if (activity.event === CommentEventEnum.TaskHelpRequested) {
    return <TaskHelpRequestedBox activity={activity} />
  }

  if (activity.event === CommentEventEnum.TaskHelpGiven) {
    return <TaskHelpGivenBox activity={activity} />
  }

  if (activity.event === CommentEventEnum.TaskCompleted) {
    return <TaskCompletedBox activity={activity} />
  }

  if (activity.event === CommentEventEnum.TaskRejected) {
    return <TaskRejectedBox activity={activity} />
  }

  return <TaskUpdatedBox activity={activity} />
}

const formatDate = iso => {
  if (!iso) return ''
  const date = parseISO(iso)
  return isToday(date)
    ? `vandaag ${format(date, 'HH:mm', { locale: nl })}`
    : format(date, 'PP HH:mm', { locale: nl })
}

const fullName = person => {
  if (!person) return ''
  const f = person.firstname || ''
  const l = person.lastname || ''
  return `${f} ${l}`.trim()
}

/* ---------------- UI blocks ---------------- */

const TimelineRow = ({ activity, index }) => {
  return (
    <div
      className="relative mb-6 pl-8 fadeInUp"
      style={{ animationDelay: `${index * 0.18}s` }}
    >
      <TimelineIcon event={activity.event} index={index} />
      <div className="fadeInUp" style={{ animationDelay: `${index * 0.18}s` }}>
        <RenderBox activity={activity} />
      </div>
    </div>
  )
}

const TimelineIcon = ({ event, index }) => {
  const showPing = index === 0

  let circle = (
    <div className="absolute z-10 -left-[8px] top-0 bg-gray-400 w-4 h-4 rounded-full flex items-center justify-center" />
  )

  if (!showPing) {
    if (event === CommentEventEnum.TaskCreated) {
      circle = (
        <div className="absolute z-10 -left-[8px] top-0 bg-gray-400 w-4 h-4 rounded-full flex items-center justify-center" />
      )
    }

    if (event === CommentEventEnum.TaskStarted) {
      circle = (
        <div className="absolute z-10 p-1.5 -left-[15px] -top-0.5 rounded-full bg-white border border-gray-200 flex items-center justify-center text-[#9CA3AF] shadow-sm">
          <Heroicon icon="Play" variant="solid" className="w-4 h-4" />
        </div>
      )
    }

    if (event === CommentEventEnum.TaskHelpRequested) {
      circle = (
        <div className="absolute z-10 p-1.5 -left-[15px] -top-0.5 rounded-full bg-white border border-gray-200 flex items-center justify-center text-[#9CA3AF] shadow-sm">
          <Heroicon icon="HandRaised" variant="solid" className="w-4 h-4" />
        </div>
      )
    }

    if (event === CommentEventEnum.TaskHelpGiven) {
      circle = (
        <div className="absolute z-10 p-1.5 -left-[15px] -top-0.5 rounded-full bg-white border border-gray-200 flex items-center justify-center text-[#9CA3AF] shadow-sm">
          <Heroicon icon="Users" variant="solid" className="w-4 h-4" />
        </div>
      )
    }
  }

  if (event === CommentEventEnum.TaskCompleted) {
    circle = (
      <div className="absolute z-10 p-1 -left-[12px] -top-0.5 bg-green-500 rounded-full flex items-center justify-center">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          className="w-4 h-4 text-white"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            strokeLinecap="round"
            strokeLinejoin="round"
            strokeWidth="2"
            d="M5 13l4 4L19 7"
          />
        </svg>
      </div>
    )
  }

  if (event === CommentEventEnum.TaskRejected) {
    circle = (
      <div className="absolute z-10 p-1 -left-[12px] -top-0.5 bg-red-500 rounded-full flex items-center justify-center">
        <Heroicon icon="XMark" variant="solid" className="w-4 h-4 text-white" />
      </div>
    )
  }

  return (
    <div>
      {showPing && (
        <div className="before:content-[''] before:absolute before:top-0 before:left-[-8px] before:w-4 before:h-4 before:bg-primary/20 before:rounded-full before:animate-ping" />
      )}
      {circle}
    </div>
  )
}

/* ---------------- cards ---------------- */

const ActivityDetailsCard = ({ activity, changes = [] }) => {
  const hasContent = !!activity.content
  const hasChanges = changes.length > 0

  if (!hasContent && !hasChanges) {
    return null
  }

  return (
    <div className="overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm">
      {hasContent && (
        <div
          className={
            hasChanges
              ? 'border-b border-slate-100 bg-slate-50/70 px-4 py-3'
              : 'bg-slate-50/70 px-4 py-3'
          }
        >
          <RichText
            text={activity.content}
            className="text-sm text-slate-700"
          />
        </div>
      )}

      {hasChanges && (
        <div className="space-y-2 p-3">
          {changes.map(change => (
            <MetadataChangeRow
              key={`${activity.id}-${change.key}`}
              change={change}
            />
          ))}
        </div>
      )}
    </div>
  )
}

const TaskStartedBox = ({ activity }) => {
  return (
    <div className="space-y-1">
      <p className="text-sm text-slate-600">Gestart</p>
      <Creator
        activity={activity}
        createdAt={formatDate(activity.created_at)}
      />
    </div>
  )
}

const TaskCreatedBox = ({ activity }) => {
  return (
    <div className="space-y-1">
      <p className="text-sm text-slate-600">Taak aangemaakt</p>
      <Creator
        activity={activity}
        createdAt={formatDate(activity.created_at)}
      />
    </div>
  )
}

const TaskRejectedBox = ({ activity }) => {
  const changes = normalizeMetadataChanges(activity.metadata).filter(
    change => change.key !== 'status'
  )

  return (
    <div className="space-y-1">
      <p className="text-sm text-slate-600">Afgewezen</p>
      <ActivityDetailsCard activity={activity} changes={changes} />
      <Creator
        activity={activity}
        createdAt={formatDate(activity.created_at)}
      />
    </div>
  )
}

const TaskCompletedBox = ({ activity }) => {
  const changes = normalizeMetadataChanges(activity.metadata).filter(
    change => change.key !== 'status'
  )

  return (
    <div className="space-y-1">
      <p className="text-sm text-slate-600">Afgerond</p>
      <ActivityDetailsCard activity={activity} changes={changes} />
      <Creator
        activity={activity}
        createdAt={formatDate(activity.created_at)}
      />
    </div>
  )
}

const TaskHelpRequestedBox = ({ activity }) => {
  const changes = normalizeMetadataChanges(activity.metadata).filter(
    change => change.key !== 'help_requested'
  )

  return (
    <div className="space-y-1">
      <p className="text-sm text-slate-600">Collega nodig</p>
      <ActivityDetailsCard activity={activity} changes={changes} />
      <Creator
        activity={activity}
        createdAt={formatDate(activity.created_at)}
      />
    </div>
  )
}

const TaskHelpGivenBox = ({ activity }) => {
  return (
    <div className="space-y-1">
      <p className="text-sm text-slate-600">Hulp toegezegd</p>
      <Creator
        activity={activity}
        createdAt={formatDate(activity.created_at)}
      />
    </div>
  )
}

const TaskUpdatedBox = ({ activity }) => {
  const changes = normalizeMetadataChanges(activity.metadata)

  if (!activity.content && changes.length === 0) {
    return null
  }

  return (
    <div className="flex flex-col space-y-1.5">
      <p className="text-sm text-slate-600">Bewerking</p>
      <ActivityDetailsCard activity={activity} changes={changes} />
      <Creator
        activity={activity}
        createdAt={formatDate(activity.created_at)}
      />
    </div>
  )
}

const Creator = ({ activity, createdAt }) => {
  const creator = activity?.creator
  const fullname = fullName(creator)
  
  return (
    <div className="flex items-start gap-2">
      <span className="text-xs font-semibold text-slate-900">
        {fullname || 'Onbekende gebruiker'}
      </span>
      <div className="text-[0.7rem] text-slate-400">{createdAt}</div>
    </div>
  )
}

/* ---------------- tiny bits ---------------- */

const MetaUsers = ({ id, title, users }) => (
  <div className="flex flex-wrap">
    <p className="text-sm mr-1">{title}</p>
    {users.map((u, i) => (
      <p key={`${id}-user-${i}`} className="text-sm text-gray-600">
        {`${u}${i < users.length - 1 ? ', ' : ''}`}
      </p>
    ))}
  </div>
)

const getPersonLabel = item => {
  if (!item) return ''
  if (typeof item === 'string') return item
  return item.value || item.name || ''
}

const toArray = value => {
  if (!value) return []
  if (Array.isArray(value)) return value.map(getPersonLabel).filter(Boolean)
  return [getPersonLabel(value)].filter(Boolean)
}

const hasRealValue = value => {
  if (Array.isArray(value)) return value.length > 0
  return value !== null && value !== undefined && value !== ''
}

const extractDisplayValue = value => {
  if (value === null || value === undefined) return null

  if (typeof value === 'string' || typeof value === 'number') {
    return String(value)
  }

  if (typeof value === 'boolean') {
    return value ? 'Ja' : 'Nee'
  }

  if (Array.isArray(value)) {
    return value.map(extractDisplayValue).filter(Boolean)
  }

  if (typeof value === 'object') {
    if ('value' in value && value.value !== null && value.value !== undefined) {
      return __(extractDisplayValue(value.value))
    }

    if ('name' in value && value.name !== null && value.name !== undefined) {
      return __(extractDisplayValue(value.name))
    }

    if ('firstname' in value || 'lastname' in value) {
      return `${value.firstname ?? ''} ${value.lastname ?? ''}`.trim()
    }
  }

  return null
}

const normalizeMetadataChanges = metadata => {
  const changes = metadata?.changes
  if (!changes || typeof changes !== 'object') return []

  const rows = []

  Object.entries(changes).forEach(([key, value]) => {
    if (key === 'help_requested') {
      const currentValue = extractDisplayValue(value?.to)

      if (!hasRealValue(currentValue)) return

      rows.push({
        key,
        label: __('help_requested'),
        type: 'pill',
        value: currentValue
      })
      return
    }

    if (key === 'assignees') {
      const added = toArray(value?.added)
      const removed = toArray(value?.removed)

      if (added.length > 0) {
        rows.push({
          key: `${key}-added`,
          label: 'Toegewezen aan',
          type: 'tags',
          value: added,
          tone: 'success'
        })
      }

      if (removed.length > 0) {
        rows.push({
          key: `${key}-removed`,
          label: 'Niet meer toegewezen',
          type: 'tags',
          value: removed,
          tone: 'danger'
        })
      }

      return
    }

    if (key === 'tags') {
      const added = toArray(value?.added)
      const removed = toArray(value?.removed)

      if (added.length > 0) {
        rows.push({
          key: `${key}-added`,
          label: 'Tags toegevoegd',
          type: 'tags',
          value: added,
          tone: 'success'
        })
      }

      if (removed.length > 0) {
        rows.push({
          key: `${key}-removed`,
          label: 'Tags verwijderd',
          type: 'tags',
          value: removed,
          tone: 'danger'
        })
      }

      return
    }

    if (
      value &&
      typeof value === 'object' &&
      ('from' in value || 'to' in value)
    ) {
      const currentValue = extractDisplayValue(value?.to)

      if (!hasRealValue(currentValue)) return

      rows.push({
        key,
        label: __(key),
        type: Array.isArray(currentValue) ? 'tags' : 'pill',
        value: currentValue
      })
      return
    }

    const parsedValue = extractDisplayValue(value)

    if (!hasRealValue(parsedValue)) return

    rows.push({
      key,
      label: __(key),
      type: Array.isArray(parsedValue) ? 'tags' : 'pill',
      value: parsedValue
    })
  })

  return rows
}

const MetadataChangeRow = ({ change }) => {
  const isDanger = change.tone === 'danger'

  return (
    <div className="group flex items-center justify-between gap-3 rounded-xl border border-slate-200/70 bg-gradient-to-br from-slate-50 to-white px-3.5 py-3 transition-all duration-200">
      <div className="min-w-0">
        <p className="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-500">
          {change.label}
        </p>
      </div>

      <div className="flex min-w-0 flex-1 justify-end">
        {change.type === 'tags' ? (
          <div className="flex flex-wrap justify-end gap-2">
            {change.value.map((item, index) => (
              <span
                key={`${change.key}-${index}`}
                className={`inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium ${
                  isDanger
                    ? 'border-red-200 bg-red-50 text-red-700'
                    : 'border-emerald-200 bg-emerald-50 text-emerald-700'
                }`}
              >
                {item}
              </span>
            ))}
          </div>
        ) : (
          <CurrentValuePill value={change.value} />
        )}
      </div>
    </div>
  )
}

const renderValue = value => {
  if (Array.isArray(value)) return value.join(', ')
  if (value === null || value === undefined || value === '') return '—'
  return String(value)
}

const CurrentValuePill = ({ value }) => (
  <span className="inline-flex max-w-full items-center rounded-full border border-primary/15 bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
    <span className="truncate">{renderValue(value)}</span>
  </span>
)

export default TaskActivity
