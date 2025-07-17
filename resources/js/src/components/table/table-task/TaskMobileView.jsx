import {isValidElement, useEffect, useRef} from 'react'
import {
  AnnouncementFeed,
  PriorityText,
  getPriority,
  TaskActionButton
} from '@/components'
import {usePage, router} from '@inertiajs/react'
import {__, getColor} from '@/stores'
import Lottie from 'lottie-react'
import helpAnimation from '@json/animation-help.json'
import {useLoader} from '@/hooks'

import {
  Lucide,
  Heroicon,
  Tippy,
  Separator,
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
  AvatarStack,
  RichText,
  ScrollArea
} from '@/base-components'

import {FilterBar, AnnouncementSheet, TaskSheet} from '@/components'

export const TaskMobileView = ({
  todoTasks,
  openTasks,
  setTasks,
  setSheetState,
  handleTasksRecon,
  handleTaskUpdate,
  lastUpdatedTaskRef,
  filtersRef
}) => {
  const {announcements, settings, user} = usePage().props
  const {loading, setLoading, Loader} = useLoader()
  const todoTasksContainer = useRef()
  const openTasksContainer = useRef()

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
          style={{backgroundColor: priority.color}}
          className="shrink-0 shadow-[2px_0_3px_rgba(0,0,0,0.1)] w-[32px] transform transform transition duration-150 active:translate-x-1 cursor-pointer"
          onClick={() => setSheetState({open: true, taskId: task.id})}
        ></div>
        <div className="flex-1 min-w-0 bg-white border border-grey-400 border-x-0 shadow-sm rounded-tr-sm rounded-br-sm px-4 py-3 rounded-tl-none rounded-bl-none">
          <div className="flex items-center ">
            <div className="flex flex-col w-full min-w-0">
              {/* Task Title */}
              <div className="text-lg font-bold leading-4">{task.name}</div>

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
              minWidth="50px"
              icon={<Heroicon icon="Signal" className="w-4 h-4 text-slate-500" />}
              label="Status:"
              value={
                <span className={`text-sm font-medium ml-1 ${statusColor}`}>
                  {__(task.status.name)}
                </span>
              }
            />

            {task?.patient && (
              <InfoRow
                minWidth="50px"
                icon={
                  <Heroicon
                    icon="UserCircle"
                    className="w-4 h-4 text-slate-500"
                  />
                }
                label="Wie:"
                value={`${task.patient.firstname} ${task.patient.lastname} (${task.patient.birthdate}) (${task.patient.gender}) - ${task.patient.room_id}, ${task.patient.bed_id}`}
              />
            )}

            {task.space && (
              <InfoRow
                minWidth="50px"
                icon={
                  <Heroicon icon="MapPin" className="w-4 h-4 text-slate-500" />
                }
                label="Van:"
                value={task.space.name}
              />
            )}

            {task?.spaceTo && (
              <InfoRow
                minWidth="70px"
                icon={<Lucide icon="Map" className="w-4 h-4 text-slate-500" />}
                label="Naar:"
                value={task.spaceTo.name}
              />
            )}
          </div>
          <Separator className="my-2 bg-slate-200/60 dark:bg-darkmode-400" />

          {/* Avatars */}
          <div className="flex items-center">
            <div className="flex -space-x-2">
              {task.needs_help > 0 && (
                <Tippy
                  content={'Hulp gevraagd'}
                  options={{allowHTML: true, offset: [30, 20]}}
                >
                  <Lottie
                    className="w-5 h-5 mr-2 cursor-help"
                    animationData={helpAnimation}
                  />
                </Tippy>
              )}

              <AvatarStack avatars={task.assignees} />
            </div>

            {/* Action buttons */}
            <div className="flex items-center ml-auto space-x-2">
              <TaskActionButton
                task={task}
                user={user}
                handleTaskUpdate={handleTaskUpdate}
                handleTasksRecon={handleTasksRecon}
              />
              <Heroicon
                className="cursor-pointer w-5 h-5"
                icon="EllipsisVertical"
                onClick={() => setSheetState({open: true, taskId: task.id})}
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
                <FilterBar
                  filtersRef={filtersRef}
                  onApplyFilters={({activeFilters}) => {
                    setLoading(true)
                    router.get(
                      '/',
                      {filters: activeFilters},
                      {
                        only: ['tasks'],
                        queryStringArrayFormat: 'indices',
                        preserveState: true,
                        onSuccess: ({props}) => {
                          setTasks(props.tasks.data)
                          setLoading(false)
                        },
                        onError: error => {
                          console.log(error)
                        }
                      }
                    )
                  }}
                />
              </AccordionContent>
            </AccordionItem>
          </Accordion>
          <div className="flex gap-x-3">
            <AnnouncementSheet />
            <TaskSheet />
          </div>
          <AnnouncementFeed announcements={announcements} />

          {/* To-Do Tasks */}
          <div className="relative">
            {loading && (
              <div className="flex justify-center items-center z-30 h-full w-full absolute bg-gray-100 opacity-50 pointer-events-none">
                {' '}
                <Loader width={80} height={80} className="z-40 relative" />
              </div>
            )}
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
          </div>
        </div>
      </ScrollArea>
  )
}

const InfoRow = ({icon = null, label, value, minWidth = '90px'}) => {
  return (
    <div className="flex items-start">
      <div
        style={{minWidth: minWidth}}
        className="flex items-center space-x-1 min-w-0"
      >
        {icon}
        <span className="text-sm text-slate-500">{label}</span>
      </div>
      {isValidElement(value) ? (
        value
      ) : (
        <span className="text-sm font-medium ml-1">{value}</span>
      )}
    </div>
  )
}
