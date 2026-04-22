import { format, parseISO, isToday } from 'date-fns'
import { nl } from 'date-fns/locale'
import { cn } from '@/utils'
import {
  Avatar,
  AvatarImage,
  AvatarFallback,
  RichText
} from '@/base-components'
import { __ } from '@/stores'

export const EventEnum = Object.freeze({
  TaskCreated: 'task_created',
  TaskStarted: 'task_started',
  TaskUpdated: 'task_updated',
  TaskCompleted: 'task_completed',
  TaskHelpRequested: 'task_help_requested',
  TaskHelpGiven: 'task_help_given',
  Announcement: 'announcement'
})

export const NewsItem = ({ newsItem }) => {
  const createdAt = formatDate(newsItem.created_at)
  const meta = newsItem.metadata?.changes ?? {}

  const creatorName = [newsItem.creator?.firstname, newsItem.creator?.lastname]
    .filter(Boolean)
    .join(' ')

  const eventLabel = getEventLabel(newsItem.event)
  const metadataRows = renderMetadataChanges(meta, newsItem.event)
  const hasContent = !!newsItem.content?.length
  const hasMetadata = metadataRows.length > 0

  return (
    <div className="flex flex-col px-2">
      <div className="relative right-2 pb-2 text-sm font-semibold text-slate-800">
        {creatorName || 'Onbekende gebruiker'}
      </div>

      <div className="flex items-start gap-3">
        <Avatar className="mr-2 rounded">
          <AvatarImage
            src={newsItem.creator?.image_path}
            alt={newsItem.creator?.firstname}
          />
          <AvatarFallback>
            {newsItem.creator?.firstname?.charAt(0) ?? '?'}
          </AvatarFallback>
        </Avatar>

        <div
          className={cn(
            'relative w-full rounded border border-slate-100 bg-slate-50 p-3 py-2 text-sm text-muted-foreground transition-all duration-150',
            'before:absolute before:left-[-9px] before:top-[8px] before:content-[""]',
            'before:border-y-[7px] before:border-y-transparent before:border-r-[9px] before:border-r-slate-100'
          )}
        >
          <div className="flex min-w-0 flex-col">
            <div className="flex w-full items-baseline gap-x-2">
              {newsItem?.task_id && newsItem.task?.name && (
                <span className="font-semibold text-slate-700">
                  {newsItem.task.name}
                </span>
              )}

              {!newsItem?.task_id && (
                <span className="text-xs uppercase tracking-wide text-amber-700 shadow-xs">
                  Mededeling
                </span>
              )}

              <div className="ml-auto text-[0.7rem] text-slate-400">
                {createdAt}
              </div>
            </div>

            <div className="flex flex-col gap-1">
              {eventLabel && (
                <div className="flex flex-wrap items-center gap-1">
                  <span className="text-gray-700">{eventLabel}</span>
                </div>
              )}

              {hasContent && (
                <RichText
                  text={newsItem.content}
                  className={cn(
                    'prose prose-sm max-w-none text-muted-foreground',
                    hasMetadata ? 'pb-2' : ''
                  )}
                />
              )}

              {hasMetadata && <div className="flex flex-col gap-1">{metadataRows}</div>}
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

const getEventLabel = event => {
  switch (event) {
    case EventEnum.TaskCreated:
      return 'Taak aangemaakt'
    case EventEnum.TaskStarted:
      return 'Taak gestart'
    case EventEnum.TaskCompleted:
      return 'Taak afgerond'
    case EventEnum.TaskHelpRequested:
      return 'Collega nodig'
    case EventEnum.TaskHelpGiven:
      return 'Hulp toegezegd'
    default:
      return null
  }
}

const renderMetadataChanges = (meta, event) => {
  const rows = []

  const shouldHideHelpRequested =
    event === EventEnum.TaskHelpRequested && meta.help_requested?.to?.value === true

  const statusToValue = meta.status?.to?.value
  const shouldHideStatus =
    (event === EventEnum.TaskStarted && statusToValue === 'InProgress') ||
    (event === EventEnum.TaskCompleted && statusToValue === 'Completed')

  if (!shouldHideHelpRequested && meta.help_requested?.to?.value === true) {
    rows.push(
      <span
        key="help_requested"
        className="text-xs uppercase tracking-wide text-amber-700 shadow-xs"
      >
        Collega nodig
      </span>
    )
  }

  if (!shouldHideStatus && statusToValue) {
    rows.push(
      <MetadataTextRow
        key="status"
        label={`${__('Status')} gewijzigd naar`}
        value={__(statusToValue)}
        tone="success"
      />
    )
  }

  if (meta.priority?.value) {
    rows.push(
      <MetadataTextRow
        key="priority"
        label={`${__('Priority')} gewijzigd naar`}
        value={__(meta.priority.value)}
        tone="success"
      />
    )
  }

  if (meta.assignees) {
    const added = meta.assignees.added ?? []
    const removed = meta.assignees.removed ?? []

    if (added.length > 0) {
      rows.push(
        <MetadataTextRow
          key="assignees-added"
          label="Toegewezen aan"
          value={added.map(item => item?.value).filter(Boolean).join(', ')}
          tone="success"
        />
      )
    }

    if (removed.length > 0) {
      rows.push(
        <MetadataTextRow
          key="assignees-removed"
          label="Niet meer toegewezen aan"
          value={removed.map(item => item?.value).filter(Boolean).join(', ')}
          tone="danger"
        />
      )
    }
  }

  if (meta.tags) {
    const added = meta.tags.added ?? []
    const removed = meta.tags.removed ?? []

    if (added.length > 0) {
      rows.push(
        <MetadataTextRow
          key="tags-added"
          label="Tags toegevoegd"
          value={added.map(item => item?.value).filter(Boolean).join(', ')}
          tone="success"
        />
      )
    }

    if (removed.length > 0) {
      rows.push(
        <MetadataTextRow
          key="tags-removed"
          label="Tags verwijderd"
          value={removed.map(item => item?.value).filter(Boolean).join(', ')}
          tone="danger"
        />
      )
    }
  }

  return rows
}

const MetadataTextRow = ({ label, value, tone = 'success' }) => {
  const valueClass =
    tone === 'danger' ? 'text-red-600' : tone === 'warning' ? 'text-amber-600' : 'text-green-600'

  if (!value) return null

  return (
    <div className="flex flex-wrap items-center gap-1">
      <span className="text-gray-700">{label}</span>
      <span className={valueClass}>{value}</span>
    </div>
  )
}

const formatDate = createdAt => {
  const date = parseISO(createdAt)

  if (isToday(date)) {
    return `Vandaag • ${format(date, 'HH:mm', { locale: nl })}`
  }

  return format(date, 'PP • HH:mm', { locale: nl })
}