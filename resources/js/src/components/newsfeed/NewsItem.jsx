import { format, parseISO, isToday } from 'date-fns'
import { nl } from 'date-fns/locale'
import { cn } from '@/utils'
import {
  Badge,
  Avatar,
  AvatarImage,
  AvatarFallback,
  RichText
} from '@/base-components'
import { __ } from '@/stores'

export const NewsItem = ({ newsItem }) => {
  const createdAt = formatDate(newsItem.created_at)
  const meta = newsItem.metadata?.changed_keys ?? {}

  return (
    <div className="flex flex-col px-2">
      <div className="relative right-2 text-sm text-slate-800 font-semibold pb-2">
        {' '}
        {`${newsItem.creator?.firstname} ${newsItem.creator?.lastname}`}
      </div>
      <div className="flex items-start gap-3">
        {/* Avatar OUTSIDE the card */}
        <Avatar className="rounded mr-2">
          <AvatarImage
            src={newsItem.creator?.image_path}
            alt={newsItem.creator?.firstname}
          />
          <AvatarFallback>
            {newsItem.creator?.firstname.charAt(0)}
          </AvatarFallback>
        </Avatar>

        <div
          className={cn(
            'relative w-full p-3 py-2 text-sm text-muted-foreground border rounded border-slate-100 bg-slate-50',
            'transition-all duration-150',

            // ARROW
            'before:content-[""] before:absolute before:left-[-9px] before:top-[8px]',
            'before:border-y-[7px] before:border-y-transparent',
            'before:border-r-[9px] before:border-r-slate-100'
          )}
        >
          <div className="flex min-w-0 flex-col">
            {/* TOP LINE: left content + task name + date to the right */}
            <div className="flex items-baseline w-full gap-x-2">
              {newsItem?.task_id && newsItem.task?.name && (
                <span className="font-semibold text-slate-700">
                  {newsItem.task.name}
                </span>
              )}

              {/* Mededeling */}
              {!newsItem?.task_id && (
                <span className="text-xs rounded-full uppercase tracking-wide text-amber-700 shadow-xs">
                  Mededeling
                </span>
              )}

              <div className="ml-auto text-[0.7rem] text-slate-400">
                {createdAt}
              </div>
            </div>

            {/* BODY */}
            <div className="flex flex-col">
              {/* Content */}
              {newsItem.content?.length > 0 && (
                <RichText
                  text={newsItem.content}
                  className="prose prose-sm max-w-none text-muted-foreground pb-2"
                />
              )}
              
              {/* Collega nodig */}
              {newsItem?.task_id && meta.needs_help && (
                <span className="text-xs rounded-full uppercase tracking-wide text-amber-700 shadow-xs">
                  Collega nodig
                </span>
              )}

              {/* Status */}
              {Object.entries(meta)
                .filter(([key, value]) => key !== 'needs_help') // ⬅️ OMITIR
                .map(([key, value]) => (
                  <div key={key} className="flex flex-wrap items-center gap-1">
                    {key === 'assignees' ? (
                      <span className="text-gray-700">Toegewezen aan</span>
                    ) : (
                      <span className="text-gray-700">
                        {__(key.charAt(0).toUpperCase() + key.slice(1))}{' '}
                        gewijzigd naar
                      </span>
                    )}

                    <span className="text-green-600">
                      {typeof value === 'string' ? __(value) : value.toString()}
                    </span>
                  </div>
                ))}
            </div>
          </div>
        </div>
      </div>
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
