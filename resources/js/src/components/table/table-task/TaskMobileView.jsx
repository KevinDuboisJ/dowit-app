import { isValidElement, useEffect, useRef, useMemo } from 'react'
import {
  AnnouncementFeed,
  PriorityText,
  getPriority,
  TaskActionButton
} from '@/components'
import { usePage } from '@inertiajs/react'
import { __, getColor } from '@/stores'
import { useLoader, useInfiniteScroll } from '@/hooks'

import {
  Lucide,
  Heroicon,
  Tooltip,
  Separator,
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
  AvatarStack,
  RichText,
  ScrollArea
} from '@/base-components'

import {
  FilterBarMobile,
  AnnouncementSheet,
  TaskSheet,
  HelpAnimation
} from '@/components'

export const TaskMobileView = ({
  data,
  setSheetState,
  handleTaskUpdate,
  lastUpdatedTaskRef,
  filters
}) => {
  const { announcements, settings, user } = usePage().props
  const { Loader } = useLoader()
  const todoTasksContainer = useRef()
  const openTasksContainer = useRef()

  const { items, loading, lastItemRef, scrollRootRef } = useInfiniteScroll({
    request: data,
    propKey: 'tasks',
    extraData: { filters }
  })

  const [todoTasks, openTasks] = useMemo(() => {
    const assigned = []
    const notAssigned = []

    for (const task of items) {
      if (task.capabilities?.isAssignedToCurrentUser) {
        assigned.push(task)
      } else {
        notAssigned.push(task)
      }
    }

    return [assigned, notAssigned]
  }, [items])

  useEffect(() => {
    if (!lastUpdatedTaskRef.scroll) return

    setTimeout(() => {
      const index = todoTasks?.findIndex(
        t => t.id === lastUpdatedTaskRef.current?.id
      )

      if (index > -1 && todoTasksContainer.current?.children[index]) {
        // console.log("Scrolling to:", lastUpdatedTaskRef.current.id);
        todoTasksContainer.current.children[index].scrollIntoView({
          behavior: 'smooth',
          block: 'center',
          inline: 'nearest'
        })
      }
    }, 0)
  }, [todoTasks])

  // Helper to render task rows
  const renderTaskRow = task => {
    const priority = getPriority(
      task.created_at,
      task.priority,
      settings.TASK_PRIORITY.value
    )
    const statusColor = `text-${getColor(task.status.name)}`
    const description = task.description || ''
    const plainText = description.replace(/<[^>]+>/g, '') // strip HTML tags

    return (
      <div key={task.id} className="flex">
        <div
          style={{ backgroundColor: priority.color }}
          className="shrink-0 shadow-[2px_0_3px_rgba(0,0,0,0.1)] w-[32px] transform transform transition duration-150 active:translate-x-1 cursor-pointer"
          onClick={() => setSheetState({ open: true, taskId: task.id })}
        ></div>
        <div className="flex-1 min-w-0 bg-white border border-grey-400 border-x-0 shadow-sm rounded-tr-sm rounded-br-sm px-3 py-3 rounded-tl-none rounded-bl-none">
          <div className="flex items-center ">
            <div className="flex flex-col w-full min-w-0">
              {/* Task name */}
              <div className="text-base font-bold leading-4">{task.name}</div>

              {/* Patient info */}
              {task?.visit && (
                <div className="flex flex-wrap items-center gap-x-1 leading-none mt-0.5 text-gray-500 text-base">
                  <span>
                    {task.visit.patient.lastname},{' '}
                    {task.visit.patient.firstname}
                  </span>
                  <span className="text-xs py-0.5">
                    ({task.visit.patient.gender})
                  </span>
                  {task.visit.bed?.room?.number ? (
                    <span className="px-1">
                      {task.visit.bed?.room?.number}, {task.visit.bed?.number}
                    </span>
                  ) : (
                    <span className="text-xs px-1 mt-auto">
                      De patiënt is ontslagen
                    </span>
                  )}
                </div>
              )}

              {/* Task description */}
              <RichText
                className="truncate text-gray-500 text-xs"
                text={
                  plainText.length > 60
                    ? `${plainText.slice(0, 60)}...`
                    : plainText
                }
              />
            </div>
          </div>

          <div className="flex flex-col mt-3">
            {/* Priority */}
            <InfoRow
              icon={
                <Heroicon icon="Signal" className="w-4 h-4 text-slate-500" />
              }
              label="Status"
              value={
                <span className={`${statusColor}`}>{__(task.status.name)}</span>
              }
            />

            {task.space && (
              <InfoRow
                icon={
                  <Lucide icon="MapPin" className="w-4 h-4 text-slate-500" />
                }
                label="Van"
                value={`${task?.space?.name}`}
              />
            )}

            {task?.space_to && (
              <InfoRow
                icon={
                  <Lucide icon="MapPin" className="w-4 h-4 text-slate-500" />
                }
                label="Naar"
                value={`${task?.space_to?.name}`}
              />
            )}
          </div>
          <Separator className="my-2 bg-slate-200/60 dark:bg-darkmode-400" />

          {/* Avatars */}
          <div className="flex items-center">
            <div className="flex -space-x-2">
              {task.needs_help &&
                task.is_active &&
                !task.capabilities.isAssignedToCurrentUser && (
                  <Tooltip content="Hulp gevraagd">
                    <HelpAnimation />
                  </Tooltip>
                )}
              <AvatarStack avatars={task.assignees} />
            </div>

            {/* Action buttons */}
            <div className="flex items-center ml-auto space-x-2">
              <TaskActionButton
                task={task}
                user={user}
                handleTaskUpdate={handleTaskUpdate}
              />
              <Heroicon
                className="cursor-pointer w-5 h-5"
                icon="EllipsisVertical"
                onClick={() => setSheetState({ open: true, taskId: task.id })}
              />
            </div>
          </div>
        </div>
      </div>
    )
  }

  return (
    <ScrollArea className="flex flex-col h-full min-h-0 fadeInUp space-y-2">
      <div className="p-4">
        <Accordion type="single" collapsible>
          <AccordionItem className="border-b-0" value="item-1">
            <AccordionTrigger className="p-0 hover:no-underline">
              Filters
            </AccordionTrigger>
            <AccordionContent className="p-1 py-2">
              <FilterBarMobile filters={filters} />
            </AccordionContent>
          </AccordionItem>
        </Accordion>
        <div className="flex gap-x-3 my-2">
          <AnnouncementSheet />
          <TaskSheet />
        </div>
        <AnnouncementFeed announcements={announcements} />

        {/* To-Do Tasks */}
        <div className="relative">
          {filters.loading && (
            <div className="flex justify-center items-center z-30 h-full w-full absolute bg-gray-100 opacity-50 pointer-events-none">
              {' '}
              <Loader width={80} height={80} className="z-40 relative" />
            </div>
          )}
          {filters.hasActive() ? (
            <TaskArea
              items={items}
              renderItem={renderTaskRow}
              scrollRootRef={scrollRootRef}
              lastItemRef={lastItemRef}
              Loader={Loader}
              loading={loading}
            />
          ) : (
            <Accordion
              className="h-full my-2 m-0 p-0 space-y-4 shadow-none"
              type="multiple"
              defaultValue={['item-1', 'item-2']}
            >
              {/* Assigned tasks */}
              <AccordionItem
                className="bg-lime-50/30 mb-0 p-0 border rounded-xl"
                value="item-1"
              >
                <AccordionTrigger className="text-sm p-4 cursor-pointer hover:no-underline">
                  <span className="flex">
                    Aan mij toegewezen
                    <span className="text-[10px] !text-white ml-1 bg-green-700 min-w-[1.5rem] h-6 w-6 flex items-center justify-center rounded-full">
                      {todoTasks.length}
                    </span>
                  </span>
                </AccordionTrigger>
                <AccordionContent asChild>
                  <div ref={todoTasksContainer} className="space-y-3">
                    {todoTasks.length > 0 ? (
                      todoTasks.map(renderTaskRow)
                    ) : (
                      <div className="p-4 text-sm text-gray-500">
                        Geen taken toegewezen
                      </div>
                    )}
                  </div>
                </AccordionContent>
              </AccordionItem>

              {/* Open tasks */}
              <AccordionItem
                className="bg-blue-50/50 mb-0 p-0 border rounded-xl"
                value="item-2"
              >
                <AccordionTrigger className="text-sm p-4 cursor-pointer hover:no-underline">
                  <span className="flex">
                    Niet aan mij toegewezen
                    <span className="text-[10px] !text-white ml-1 bg-green-700 min-w-[1.5rem] h-6 w-6 px-2 flex items-center justify-center rounded-full">
                      {openTasks.length}
                    </span>
                  </span>
                </AccordionTrigger>
                <AccordionContent asChild>
                  <div ref={openTasksContainer} className="space-y-3">
                    {openTasks.length > 0 ? (
                      openTasks.map(renderTaskRow)
                    ) : (
                      <div className="p-4 text-sm text-gray-500">
                        Geen taken gevonden
                      </div>
                    )}
                  </div>
                </AccordionContent>
              </AccordionItem>
            </Accordion>
          )}
        </div>
      </div>
    </ScrollArea>
  )
}

const InfoRow = ({ icon = null, label, value, minWidth = '45px' }) => {
  return (
    <div className="flex items-center gap-1 min-w-0 py-0.5 rounded-md relative">
      {/* Icon pill */}
      {icon && (
        <div
          className="w-[18px] h-[18px] flex items-center justify-center  
          text-slate-500 shrink-0 transition-all duration-200 ease-in-out 0"
        >
          {icon}
        </div>
      )}

      {/* Label */}
      <span
        className="shrink-0 text-[10px] font-semibold uppercase tracking-widest text-slate-400 "
        style={{ minWidth }}
      >
        {label}
      </span>

      {/* Divider */}
      <span className="shrink-0 w-px h-4 bg-slate-200" />

      {/* Value */}
      <div className="min-w-0 pl-1 flex-1">
        {isValidElement(value) ? (
          value
        ) : (
          <span className="text-slate-700 break-words">{value}</span>
        )}
      </div>
    </div>
  )
}

const TaskArea = ({
  items,
  renderItem,
  scrollRootRef,
  lastItemRef,
  Loader,
  loading
}) => {
  return (
    <ScrollArea ref={scrollRootRef} className={`fadeInUp pt-3`}>
      <div className="flex flex-col h-full gap-8">
        {items?.map((item, index) => (
          <div
            key={item.id}
            ref={items.length === index + 1 ? lastItemRef : null}
          >
            {renderItem(item)}
          </div>
        ))}

        {items?.length === 0 && !loading && (
          <p className="text-sm text-slate-500">
            Geen gegevens om weer te geven
          </p>
        )}

        {loading && (
          <div className="flex w-full items-center justify-center">
            <Loader />
          </div>
        )}
      </div>
    </ScrollArea>
  )
}
