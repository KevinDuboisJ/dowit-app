import {format, parseISO, isToday} from 'date-fns'
import {nl} from 'date-fns/locale'
import {Fragment} from 'react'
import {cn} from '@/utils'
import {__} from '@/stores'
import {RichText, Heroicon, Separator} from '@/base-components'

export const TaskActivity = ({comments}) => {
  if (!comments || comments.length === 0) {
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
        <div className="p-4 py-2 relative overflow-hidden">
          {comments.map((comment, index) => (
            <Fragment key={comment.id}>
              <VerticalTimeline activity={comment} index={index} />
              {comment.created_by === 1 ? (
                <TextBox activity={comment} index={index} />
              ) : (
                <UpdateBox activity={comment} index={index} />
              )}
            </Fragment>
          ))}
        </div>
      </div>
    </div>
  )
}

const formatDate = createdAt => {
  const date = parseISO(createdAt)
  return isToday(date)
    ? `vandaag ${format(date, 'HH:mm', {locale: nl})}`
    : format(date, 'PP HH:mm', {locale: nl})
}

const VerticalTimeline = ({activity, index}) => (
  <div
    // We use `border-l-2 border-transparent` so the border still takes up space
    // even when invisible. This keeps the icons and circles in all timeline items
    // perfectly aligned with the first item, which actually has a visible border line with height 100%
    className={cn('absolute border-l-2 border-transparent fadeInUp ', {
      "h-full border-l-2 border-gray-300 before:content-[''] before:absolute before:top-0 before:left-[-8px] before:w-4 before:h-4 before:bg-primary/20 before:rounded-full before:animate-ping":
        index === 0
    })}
    style={{animationDelay: `${index * 0.14}s`}}
  >
    <Icon activity={activity} />
  </div>
)

const Icon = ({activity}) => {
  if (activity.needs_help) {
    return (
      <div className="absolute z-10 -left-2.5 top-0 w-5 h-5 rounded-full flex items-center justify-center text-[#9CA3AF]">
        <Heroicon icon="HandRaised" variant="solid" />
      </div>
    )
  }

  if (activity.status === 'Completed') {
    return (
      <div className="absolute z-10 -left-2.5 top-0 bg-green-500 w-5 h-5 rounded-full flex items-center justify-center">
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
    <div className="relative z-10 right-[8.5px] top-0 bg-gray-400 w-4 h-4 rounded-full flex items-center justify-center"></div>
  )
}

const UpdateBoxTitle = ({activity}) => (
  <p className="text-sm text-gray-600">
    {activity.needs_help ? 'Collega nodig' : 'Bewerking'}
  </p>
)

const UpdateBox = ({activity, index}) => {
  const metadata = activity.metadata ?? {}
  const changed_keys = metadata.changed_keys ?? {}

  // Exclude specific keys from display
  const filteredChanges = Object.entries(changed_keys).filter(
    ([key, value]) => {
      if (['assignees', 'unassignees'].includes(key)) return false
      if (key === 'needs_help') return !value
      return true
    }
  )

  const hasSeparator =
    Object.keys(changed_keys).length > 1 ||
    (Object.keys(changed_keys).length === 1 &&
      (!('needs_help' in changed_keys) || changed_keys.needs_help === false))

  return (
    <div
      className="relative flex mb-6 pl-8 space-x-2 fadeInUp"
      style={{animationDelay: `${index * 0.18}s`}}
    >
      <div className="flex flex-col">
        <UpdateBoxTitle activity={activity} />
        <div className="border rounded-lg px-4 py-3 bg-gray-100">
          {activity.content && (
            <RichText text={activity.content} className="text-sm" />
          )}

          {Array.isArray(changed_keys.assignees) &&
            changed_keys.assignees.length > 0 && (
              <MetaText
                id={activity.id}
                title="Toegewezen aan:"
                users={changed_keys.assignees}
              />
            )}

          {Array.isArray(changed_keys.unassignees) &&
            changed_keys.unassignees.length > 0 && (
              <MetaText
                id={activity.id}
                keytitle="Niet meer toegewezen aan:"
                users={changed_keys.unassignees}
              />
            )}

          {filteredChanges.map(([key, value]) => (
            <div className="flex" key={key}>
              <p className="text-sm capitalize mr-1">{__(key)}: </p>
              <p className="text-sm text-gray-600">
                {key === 'needs_help' ? 'Nee' : __(value)}
              </p>
            </div>
          ))}

          {hasSeparator && (
            <Separator className="my-1 bg-slate-200/60 dark:bg-darkmode-400" />
          )}

          <div className="flex items-center space-x-1">
            <p className="text-sm text-gray-600 font-medium">
              {`${activity.creator?.firstname} ${activity.creator?.lastname}`}
            </p>
            <p className="text-sm text-gray-600">
              - {formatDate(activity.created_at)}
            </p>
          </div>
        </div>
      </div>
    </div>
  )
}

const TextBox = ({activity, index}) => (
  <div
    className="relative mb-6 pl-8 fadeInUp"
    style={{animationDelay: `${index * 0.18}s`}}
  >
    <p className="text-sm text-gray-600">{activity.content}</p>
  </div>
)

const MetaText = ({id, title, keytitle, users}) => (
  <div className="flex flex-wrap">
    <p className="text-sm mr-1">{title || keytitle}</p>
    {users.map((assignee, index) => (
      <p key={`${id}-${index}`} className="text-sm text-gray-600">
        {`${assignee}${index < users.length - 1 ? ', ' : ''}`}
      </p>
    ))}
  </div>
)

export default TaskActivity
