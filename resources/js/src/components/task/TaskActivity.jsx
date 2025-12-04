import { format, parseISO, isToday } from 'date-fns'
import { nl } from 'date-fns/locale'
import { cn } from '@/utils'
import { __ } from '@/stores'
import { RichText, Heroicon, Separator } from '@/base-components'

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
            const isEdited = item?.metadata?.changed_keys ?? false
            const activity = {
              ...item,
              isLastItem: isLastItem,
              isEdited: isEdited,
              type: getType(item, isLastItem, isEdited)
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

const getType = (item, isLastItem, isEdited) => {
  if (isLastItem && !isEdited) return 'content' // first item only
  if (item?.needs_help) return 'help'
  if (item?.status === 'Completed') return 'done'
  if (isEdited) return 'update'
  return 'generic'
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
      <TimelineIcon type={activity.type} index={index} />
      <div className="fadeInUp" style={{ animationDelay: `${index * 0.18}s` }}>
        {renderByType({ activity })}
      </div>
    </div>
  )
}

const TimelineIcon = ({ type, index }) => {
  const showPing = index === 0

  let circle = (
    <div className="absolute z-10 -left-[8.5px] top-0 bg-gray-400 w-4 h-4 rounded-full flex items-center justify-center" />
  )

  if (type === 'help') {
    circle = (
      <div className="absolute z-10 -left-[8.5px] top-0 w-5 h-5 rounded-full flex items-center justify-center text-[#9CA3AF]">
        <Heroicon icon="HandRaised" variant="solid" />
      </div>
    )
  }

  if (type === 'done') {
    circle = (
      <div className="absolute z-10 -left-[8.5px] top-0 bg-green-500 w-5 h-5 rounded-full flex items-center justify-center">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          className="w-3 h-3 text-white"
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

  return (
    <div>
      {showPing && (
        <div className="before:content-[''] before:absolute before:top-0 before:left-[-8px] before:w-4 before:h-4 before:bg-primary/20 before:rounded-full before:animate-ping" />
      )}
      {circle}
    </div>
  )
}

const renderByType = ({ activity }) => {
  switch (activity.type) {
    case 'content':
      return <ContentBox activity={activity} />
    case 'comment':
      return <CommentBox activity={activity} />
    case 'update':
    case 'help':
    case 'done':
    case 'generic':
    default:
      return (
        <UpdateBox
          activity={activity}
          changed={activity.isEdited}
          type={activity.type}
        />
      )
  }
}

/* ---------------- cards ---------------- */

const ContentBox = ({ activity }) => {
  const name = fullName(activity.creator)
  return (
    <div className="space-y-1">
      <div className="flex flex-col border rounded-lg px-4 py-3 bg-gray-100">
        <RichText text={activity.content} className="text-sm text-gray-600"/>
      </div>
      <Creator creatorName={name} createdAt={formatDate(activity.created_at)} />
    </div>
  )
}

const UpdateBox = ({ activity, changed, type }) => {
  const name = fullName(activity.creator)

  // filter once
  const showAssignees =
    Array.isArray(changed.assignees) && changed.assignees.length > 0
  const showUnassignees =
    Array.isArray(changed.unassignees) && changed.unassignees.length > 0

  const filtered = Object.entries(changed).filter(([key, value]) => {
    if (key === 'assignees' || key === 'unassignees') return false
    if (key === 'needs_help') return !value
    return true
  })

  return (
    <div className="flex flex-col space-y-1">
      <p className="text-sm text-slate-600">
        {type === 'help'
          ? 'Collega nodig'
          : type === 'done'
          ? 'Afgerond'
          : 'Bewerking'}
      </p>

      <div className="flex flex-col border rounded-lg px-4 py-3 bg-gray-100 [&:has(>div:first-child>*)]:gap-1">
        <div>
          {activity.content && (
            <RichText text={activity.content} className="text-sm" />
          )}

          {showAssignees && (
            <MetaUsers
              id={activity.id}
              title="Toegewezen aan:"
              users={changed.assignees}
            />
          )}
          {showUnassignees && (
            <MetaUsers
              id={activity.id}
              title="Niet meer toegewezen aan:"
              users={changed.unassignees}
            />
          )}

          {filtered.map(([key, value]) => (
            <div className="flex" key={key}>
              <p className="text-sm capitalize mr-1">{__(key)}:</p>
              <p className="text-sm text-gray-600">
                {key === 'needs_help' ? 'Nee' : __(String(value))}
              </p>
            </div>
          ))}
        </div>
      </div>
      <Creator creatorName={name} createdAt={formatDate(activity.created_at)} />
    </div>
  )
}

const Creator = ({ creatorName, createdAt }) => (
  <div className="flex items-start gap-2">
    <span className="text-xs font-semibold text-slate-900">
      {creatorName || 'Onbekende gebruiker'}
    </span>

    <div className="text-[0.7rem] text-slate-400">{createdAt}</div>
  </div>
)

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

export default TaskActivity
