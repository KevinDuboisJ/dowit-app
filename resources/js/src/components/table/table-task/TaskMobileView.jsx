import { isValidElement, useEffect, useRef } from 'react';
import { AnnouncementFeed, PriorityText, getPriority, TaskActionButton } from '@/components';
import { usePage, router } from '@inertiajs/react';
import { __, getColor } from '@/stores';
import Lottie from 'lottie-react';
import helpAnimation from '@json/animation-help.json';
import { useLoader } from '@/hooks'

import {
  Lucide,
  Heroicon,
  Tippy,
  Avatar,
  AvatarFallback,
  AvatarImage,
  Separator,
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/base-components';

import {
  FilterBar,
  AnnouncementSheet,
  TaskSheet,
} from '@/components';


export const TaskMobileView = ({ todoTasks, openTasks, setTasks, setSheetState, handleRowUpdate, lastUpdatedTaskRef, filterBarRef }) => {

  const { announcements, settings, user, teams, statuses } = usePage().props;
  const { loading, setLoading, Loader } = useLoader();
  const todoTasksContainer = useRef();
  const openTasksContainer = useRef();

  useEffect(() => {

    if (!lastUpdatedTaskRef.scroll) return;

    setTimeout(() => {
      const index = todoTasks?.findIndex((t) => t.id === lastUpdatedTaskRef.current?.id);

      if (index > -1 && todoTasksContainer.current?.children[index]) {
        // console.log("Scrolling to:", lastUpdatedTaskRef.current.id);
        todoTasksContainer.current.children[index].scrollIntoView({
          behavior: "smooth",
          block: "center",
          inline: "nearest",
        });
      }
    }, 0);

  }, [todoTasks]);


  // Helper to render task rows
  const renderTaskRow = (task) => {
    const priority = getPriority(task.created_at, task.priority, settings.TASK_PRIORITY.value);
    const statusColor = `text-${getColor(task.status.name)}`;

    const AssignedUsers = () => {

      if (task.assigned_users === "{{loading}}") return <Loader />;

      return task.assigned_users.map((user) => (
        <Avatar key={user.id} className="w-8 h-8 inline-block mr-2">
          <AvatarImage src={user.image_path} alt={user.lastname} />
          <AvatarFallback>{user.lastname.charAt(0)}</AvatarFallback>
        </Avatar>
      ));
    };

    return (
      <div key={task.id} className='flex'>

        <div style={{ backgroundColor: priority.color }}
          className="flex w-[32px] shadow-[3px_0_4px_rgba(0,0,0,0.2)] border-r border-r-black/10 transform transform transition duration-150 active:translate-x-1 cursor-pointer"
          onClick={() => setSheetState({ open: true, task: task })}>
        </div>
        <div
          className='w-full bg-white border border-grey-400 border-x-0 shadow-sm rounded-tr-sm rounded-br-sm p-4 w-80 rounded-tl-none rounded-bl-none '
        >
          <div className="flex items-center">
            <div className='flex flex-col'>

              {/* Task Title */}
              <div className="text-lg font-bold">{task.name}</div>

              {/* Task description */}
              <div className="text-gray-500 text-sm">{task.task_type.name}</div>

              {/* Priority */}
              <div className='flex'>
                <span className={`text-xs text-slate-500 font-medium ${statusColor}`}>{__(task.status.name)}</span>

              </div>
            </div>
          </div>

          {/* Progress Bar */}
          {/* <div className="flex items-center mt-4">
          <div className="flex-1 flex gap-1">
            <div className="h-1 w-full bg-red-500 rounded"></div>
            <div className="h-1 w-full bg-orange-500 rounded"></div>
            <div className="h-1 w-full bg-yellow-500 rounded"></div>
            <div className="h-1 w-full bg-green-500 rounded"></div>
            <div className="h-1 w-full bg-teal-500 rounded"></div>
          </div>
        </div> */}

          <div className="flex flex-col mt-4 space-y-1">
            {task?.patient &&
              <InfoRow
                minWidth="50px"
                icon={<Heroicon icon="UserCircle" className="w-4 h-4 text-slate-500" />}
                label="Wie:"
                value={`${task.patient.firstname} ${task.patient.lastname} (${task.patient.birthdate}) (${task.patient.gender}) - ${task.patient.room_id}, ${task.patient.bed_id}`}
              />
            }

            {task.space &&
              <InfoRow
                minWidth="50px"
                icon={<Heroicon icon="MapPin" className="w-4 h-4 text-slate-500" />}
                label="Van:"
                value={task.space.name}
              />
            }

            {task?.spaceTo &&
              <InfoRow
                minWidth="70px"
                icon={<Lucide icon="Map" className="w-4 h-4 text-slate-500" />}
                label="Naar:"
                value={task.spaceTo.name}
              />
            }
          </div>
          <Separator className='my-3 bg-slate-200/60 dark:bg-darkmode-400' />

          {/* Avatars */}
          <div className="flex items-center mt-4">
            <div className="flex -space-x-2">

              {task.needs_help > 0 && <Tippy content={'Hulp gevraagd'} options={{ allowHTML: true, offset: [30, 20] }}>
                <Lottie className="w-5 h-5 mr-2 cursor-help" animationData={helpAnimation} />
              </Tippy>}

              <AssignedUsers />
            </div>

            {/* Action buttons */}
            <div className="flex items-center ml-auto space-x-2">
              <TaskActionButton task={task} user={user} handleRowUpdate={handleRowUpdate} />
              <Heroicon className='cursor-pointer w-5 h-5' icon="EllipsisVertical" onClick={() => setSheetState({ open: true, task: task })} />
            </div>
          </div>

        </div>
      </div>
    )
  };

  return (
    <>
      <Accordion type='single' collapsible>
        <AccordionItem className='border-b-0' value='item-1'>
          <AccordionTrigger className='p-0 hover:no-underline'>Filters</AccordionTrigger>
          <AccordionContent className='p-1 py-2'>

            <FilterBar
              filterBarRef={filterBarRef}
              handleFilter={(filters) => {
                setLoading(true)
                router.get('/', { filters: filters }, {
                  only: ['tasks'],
                  queryStringArrayFormat: 'indices',
                  preserveState: true,
                  onSuccess: ({ props }) => {
                    setTasks(props.tasks);
                    setLoading(false)
                  },
                  onError: (error) => {
                    console.log(error)
                  }
                });
              }}
              statuses={statuses}
              teams={teams}
            />


          </AccordionContent>
        </AccordionItem>
      </Accordion >
      <div className="flex shrink-0 gap-x-3">
        <AnnouncementSheet />
        <TaskSheet />
      </div>
      <AnnouncementFeed announcements={announcements} />

      {/* To-Do Tasks */}
      <div className='relative'>
        {loading && <div className='flex justify-center items-center z-30 h-full w-full absolute bg-gray-100 opacity-50 pointer-events-none'> <Loader width={150} height={150} className='z-40 relative' /></div>}
        <Accordion className='h-full my-2 m-0 p-0 space-y-4 shadow-none' type='multiple' defaultValue={['item-1', 'item-2']}>
          {/* Assigned tasks */}
          <AccordionItem className='bg-lime-50/30 mb-0 p-0 border rounded-xl' value='item-1'>
            <AccordionTrigger className='text-sm font-bold p-4 cursor-pointer hover:no-underline'>
              <span>
                Aan mij toegewezen
                <span className='ml-1 text-xs text-white bg-red-600 p-1 px-2 rounded-xl'>{todoTasks.length}</span>
              </span>
            </AccordionTrigger>
            <AccordionContent asChild>
              <div ref={todoTasksContainer} className="space-y-3">

                {todoTasks.length > 0 ? todoTasks.map(renderTaskRow) : <div className="p-4 text-sm text-gray-500">Geen taken toegewezen</div>}
              </div>
            </AccordionContent>
          </AccordionItem>

          {/* Open tasks */}
          <AccordionItem className='bg-blue-50/50 mb-0 p-0 border rounded-xl' value="item-2">
            <AccordionTrigger className='text-sm font-bold p-4 cursor-pointer hover:no-underline'>
              <span>
                Niet aan mij toegewezen
                <span className="ml-1 text-xs text-white bg-red-600 p-1 px-2 rounded-xl">{openTasks.length}</span>
              </span>


            </AccordionTrigger>
            <AccordionContent asChild>
              <div ref={openTasksContainer} className="space-y-3">
                {openTasks.length > 0 ? openTasks.map(renderTaskRow) : <div className="p-4 text-sm text-gray-500">Geen taken gevonden</div>}
              </div>
            </AccordionContent>
          </AccordionItem>
        </Accordion>
      </div>

    </>
  );
};

const InfoRow = ({ icon = null, label, value, minWidth = '90px' }) => {
  return (
    <div className="flex items-start text-gray-700">
      <div style={{ minWidth: minWidth }} className="flex items-center space-x-1 min-w-0">
        {icon}
        <span className="text-xs text-slate-500">{label}</span>
      </div>
      {isValidElement(value) ? value : <span className="text-xs font-medium text-slate-500">{value}</span>}
    </div>
  );
};
