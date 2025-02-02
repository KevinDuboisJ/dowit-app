import { isValidElement, useState } from 'react';
import { format, parseISO } from 'date-fns';
import { __, getVariant } from '@/stores';
import { cn } from '@/utils'
import { usePage } from '@inertiajs/react';
import { IconContext } from "react-icons";
import { HiHandRaised } from "react-icons/hi2";
import {
  Lucide,
  Heroicon,
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
  Card,
  CardContent,
  CardHeader,
  Badge,
  Avatar,
  AvatarFallback,
  AvatarImage,
  ScrollArea,
  Tippy,
  Separator
} from '@/base-components';

import {
  TaskForm,
  AssignmentForm,
  Activity,
  PriorityCircle,
  PriorityText,
  getPriority,
} from '@/components';

export const TaskDetail = ({ task, handleRowUpdate, handleSheetClose }) => {

  const { settings } = usePage().props;
  const { name, task_type, comments, status } = task;
  const canUpdateTask = task.capabilities.can_update;
  const priorityObj = getPriority(task.created_at, task.priority, settings.TASK_PRIORITY.value);
  const [activeTab, setActiveTab] = useState('details'); // State for active tab


  const InfoRow = ({ icon = null, label, value, minWidth = '110px', className, style }) => {
    return (
      <div style={style} className={cn('flex items-center text-gray-700', className)}>
        {/* Left Section: Icon + Label */}
        <div style={{ minWidth: minWidth }} className="flex items-center space-x-1 min-w-0">
          {icon}
          <span className="text-xs text-slate-500">{label}</span>
        </div>

        {/* Right Section: Value */}
        {isValidElement(value) ? value : <span className="text-xs font-medium text-slate-500 text-ellipsis overflow-hidden whitespace-nowrap">{value}</span>}

      </div>
    );
  };

  const TeamTag = ({ teams }) => {

    // Ensure `teams` is always an array
    teams = teams || [];

    return (
      teams.length > 0 ? task.teams.map((team, index) => (
        <span key={team.id} className={cn({ 'ml-0': index === 0, 'ml-2': index > 0 }, 'text-xs text-slate-500 font-medium rounded-sm border p-1 bg-gray-100')}>
          {team.name}
        </span>

      )) : <span className='text-xs text-slate-500 font-medium'>Deze taak is niet gekoppeld aan een team</span>
    );
  };

  const DocumentList = ({ documents }) => {

    // Ensure `documents` is always an array
    documents = documents || [];

    return (
      <div className="flex flex-wrap items-center">
        {documents?.length > 0 ? (
          documents.map((document) => (
            <div
              key={document.id}
              className="opacity-70 text-xs p-[6px] w-full text-slate-800 font-normal rounded-lg border bg-yellow-50"
            >
              <a className="flex items-center" href={document.link} target="_blank" rel="noopener noreferrer">
                <Lucide
                  icon="FileText"
                  className="w-[14px] h-[14px] text-slate-800 mr-1"
                />
                {document.name}
              </a>
            </div>
          ))
        ) : (
          <span className="text-xs font-medium ml-4">
            Dit taaktype heeft geen documenten
          </span>
        )}
      </div>
    );
  };

  const AssignedUsers = ({ assignedUsers }) => {

    assignedUsers = assignedUsers || []; // Ensure it's always an array

    if (assignedUsers.length === 0) {
      return null; // Don't render anything if there are no assigned users
    }

    return (
      <div className="flex flex-col px-3 py-1">
        <span className="text-xs text-slate-500 font-medium">Toegewezen</span>
        <div>
          {assignedUsers.map((user) => (
            <Avatar key={user.id} className="w-12 h-12 inline-block mr-2">
              <AvatarImage src={user.image_path} alt={user.lastname} />
              <AvatarFallback>{user.lastname.charAt(0)}</AvatarFallback>
            </Avatar>
          ))}
        </div>
      </div>
    );
  };

  return (

    <Tabs className='flex flex-col h-full bg-gray-50' value={activeTab} onValueChange={setActiveTab}>
      <CardHeader className='flex flex-col items-center bg-white p-3 py-5 space-y-3 border-b shrink-0'>
        <div className='flex w-full'>
          {/* First Column */}
          <div className='flex flex-wrap self-start'>

            {/* Custom Close Button */}
            <button
              onClick={handleSheetClose}
              className='h-6 focus:outline-none focus:ring-0 focus-visible-ring-0'
            >
              <Heroicon icon='ChevronLeft' className="w-5 stroke-[2.6px]" />
            </button>
          </div>

          {/* Second Column */}
          <div className="flex flex-wrap flex-col w-full pl-3 leading-tight">
            {/* <PriorityCircle state={priorityObj.state} color={priorityObj.color} /> */}
            <div className="text-lg font-semibold truncate text-wrap break-all">{name} <Badge className="h-7 w-24 py-1 px-2" variant={status.name}>
              {__(status.name)}
            </Badge></div>
            <div className="w-full">{task_type.name}</div>

          </div>

          {/* third Column (Badge) */}
          <div className='flex self-start'>

          </div>

        </div>


        <TabsList className='grid w-full bg-slate-50 grid-cols-2'>
          <TabsTrigger value="details">Details</TabsTrigger>
          <Tippy
            content='Alleen toegestaan voor gebruikers die aan deze taak zijn toegewezen'
            placement='bottom'
            disabled={canUpdateTask}
          >
            <TabsTrigger
              value='edit'
              disabled={!canUpdateTask}
              className='w-full flex items-center justify-center space-x-1'
            >
              Bewerken
              {!canUpdateTask && (
                <Heroicon icon='LockClosed' className='w-4 h-4 text-gray-500' />
              )}
            </TabsTrigger>
          </Tippy>

        </TabsList>
      </CardHeader>
      <ScrollArea className='fadeInUp'>
        <TabsContent className='p-8 py-4 fadeInUp' value="details">

          <div className="space-y-3">

            <InfoRow
              icon={<Heroicon icon="Flag" className="w-4 h-4 text-slate-500" />}
              label="Prioriteit:"
              value={<PriorityText state={priorityObj.state} color={priorityObj.color} />}
            />


            <IconContext.Provider value={{ color: "black"}}>
              <InfoRow
                icon={<HiHandRaised  className="w-4 h-4 text-slate-500" />}
                label="Collega nodig:"
                value={task.needs_help ? 'Ja' : 'Nee'}
              />
            </IconContext.Provider>

            <InfoRow
              icon={<Heroicon icon="CalendarDays" className="w-4 h-4 text-slate-500" />}
              label="Tijd:"
              value={format(parseISO(task.start_date_time), "PP HH:mm")}
            />

            {task?.patient &&
              <InfoRow
                icon={<Heroicon icon="UserCircle" className="w-4 h-4 text-slate-500" />}
                label="Wie:"
                value={`${task.patient.firstname} ${task.patient.lastname} (${task.patient.birthdate}) (${task.patient.gender}) - ${task.patient.room_id}, ${task.patient.bed_id}`}
              />
            }

            {task.space &&
              <InfoRow
                icon={<Heroicon icon="MapPin" className="w-4 h-4 text-slate-500" />}
                label="Van:"
                value={task.space.name}
              />
            }

            {task?.spaceTo &&
              <InfoRow
                icon={<Lucide icon="Map" className="w-4 h-4 text-slate-500" />}
                label="Naar:"
                value={task.spaceTo.name}
              />
            }

            <InfoRow
              icon={<Lucide icon="Users" className="w-4 h-4 text-slate-500" />}
              label="Teams:"
              value={<TeamTag teams={task?.teams} />}
            />

            <AssignedUsers assignedUsers={task.assigned_users} />
            <DocumentList documents={task_type.documents} />

            <Separator className='my-3 bg-slate-200/60 dark:bg-darkmode-400' />
            <div className="text-base font-medium">Historiek</div>
            <Activity comments={comments} status={status.name} />

          </div>

        </TabsContent>
      </ScrollArea>
      <ScrollArea>
        <TabsContent className="p-8 py-4 fadeInUp" value="edit" >
          <TaskForm task={task} status={status.name} handleRowUpdate={handleRowUpdate} setActiveTab={setActiveTab} />
        </TabsContent>
      </ScrollArea>
    </Tabs>

  )
}