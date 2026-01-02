import {useState} from 'react'
import {useSwipeable} from 'react-swipeable'
import {__} from '@/stores'

import {
  Heroicon,
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
  CardHeader,
  ScrollArea,
  Tooltip
} from '@/base-components'

import {TaskForm, TaskDetails, TaskIcon} from '@/components'

export const TaskOverview = ({
  task,
  handleSheetClose
}) => {
  const canUpdateTask = task?.capabilities?.can_update
  const [activeTab, setActiveTab] = useState('details') // State for active tab

  // Swipe handlers for the details tab
  const swipeHandlersDetails = useSwipeable({
    onSwipedLeft: () => {
      if (activeTab === 'details' && canUpdateTask) {
        setActiveTab('edit')
      }
    },
    onSwipedRight: () => {
      // No action for details tab on right swipe
    },
    delta: 70,
    preventDefaultTouchmoveEvent: false,
    trackTouch: true
  })

  // Swipe handlers for the edit tab
  const swipeHandlersEdit = useSwipeable({
    onSwipedRight: () => {
      if (activeTab === 'edit') {
        setActiveTab('details')
      }
    },
    onSwipedLeft: () => {
      // No action for edit tab on left swipe
    },
    delta: 50,
    preventDefaultTouchmoveEvent: false,
    trackTouch: true
  })

  return (
    <Tabs
      className="flex flex-col h-full bg-gray-50"
      value={activeTab}
      onValueChange={setActiveTab}
    >
      <CardHeader className="flex flex-col items-center bg-white p-3 py-5 space-y-3 border-b shrink-0">
        <div className="flex w-full">
          <div className="flex flex-wrap self-start">
            <button
              onClick={handleSheetClose}
              className="h-6 focus:outline-none focus:ring-0 focus-visible-ring-0"
            >
              <Heroicon icon="ChevronLeft" className="w-5 stroke-[2.5px]" />
            </button>
          </div>
          <div className="flex flex-wrap flex-col w-full pl-3 leading-tight">
            <div className="flex items-center">
              <TaskIcon className="h-6 w-6" iconName={task?.task_type?.icon} />{' '}
              <span>{task?.task_type?.name}</span>
            </div>

            <div className="w-full text-lg font-semibold truncate text-wrap break-all">
              {task.name}
            </div>
          </div>
          <div className="flex self-start"></div>
        </div>
        <TabsList className="grid w-full grid-cols-2">
          <TabsTrigger value="details">Details</TabsTrigger>
          <Tooltip
            content="Alleen toegestaan voor gebruikers die aan deze taak zijn toegewezen"
            placement="bottom"
            disabled={canUpdateTask}
          >
            <TabsTrigger
              value="edit"
              disabled={!canUpdateTask}
              className="w-full flex items-center justify-center space-x-1"
            >
              Bewerken
              {!canUpdateTask && (
                <Heroicon icon="LockClosed" className="w-4 h-4 text-gray-500" />
              )}
            </TabsTrigger>
          </Tooltip>
        </TabsList>
      </CardHeader>

      {activeTab === 'details' && (
        <ScrollArea className="fadeInUp">
          {/* Swipe container inside ScrollArea for Details */}
          <div {...swipeHandlersDetails} style={{touchAction: 'pan-y'}}>
            <TaskDetails task={task} />
          </div>
        </ScrollArea>
      )}

      {activeTab === 'edit' && (
        <ScrollArea>
          {/* Swipe container inside ScrollArea for Edit */}
          <div {...swipeHandlersEdit} style={{touchAction: 'pan-y'}}>
            <TabsContent className="p-8 py-4 fadeInUp" value="edit">
              <TaskForm
                task={task}
                setActiveTab={setActiveTab}
              />
            </TabsContent>
          </div>
        </ScrollArea>
      )}
    </Tabs>
  )
}
