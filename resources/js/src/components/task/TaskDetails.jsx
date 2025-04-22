import { isValidElement, useState, useMemo } from 'react';
import { format, parseISO } from 'date-fns';
import { __ } from '@/stores';
import { IconContext } from 'react-icons';
import { HiHandRaised } from 'react-icons/hi2';
import Lottie from 'lottie-react';
import fireAnimation from '@json/fire';
import { cn } from '@/utils'
import { usePage } from '@inertiajs/react';
import {
  Lucide,
  Heroicon,
  TabsContent,
  Separator,
  AvatarStackWrap,
  AvatarStackHeader,
  AvatarStack,
} from '@/base-components';
import {
  getPriority,
  TaskActivity,
  PriorityText,
} from '@/components';

export const TaskDetails = ({ task }) => {

  const { settings } = usePage().props;
  const priorityObj = getPriority(task.created_at, task.priority, settings.TASK_PRIORITY.value);

  const opacity = useMemo(() => {

    const firstComment = task.comments?.[0];
    if (!firstComment) return 0;

    const createdAt = new Date(firstComment.created_at);
    const now = new Date();
    const timeDiff = Math.floor((now - createdAt) / (1000 * 60 * 60 * 24));
    return timeDiff >= 5 ? 0 : 1 - timeDiff * 0.2;

  }, [task.comments]);

  return (
    <TabsContent className='p-8 py-4 fadeInUp' value="details">

      <div className="space-y-3">

        <InfoRow
          icon={<Heroicon icon="Flag" className="w-4 h-4 text-slate-500" />}
          label="Prioriteit:"
          value={<PriorityText state={priorityObj.state} color={priorityObj.color} />}
        />


        <IconContext.Provider value={{ color: "black" }}>
          <InfoRow
            icon={<HiHandRaised className="w-4 h-4 text-slate-500" />}
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

        <AvatarStackWrap>
          <AvatarStackHeader title='Toegewezen aan' />
          <AvatarStack avatars={task.assignees} maxAvatars={16} className='w-10 h-10'/>
        </AvatarStackWrap>

        <DocumentList documents={task.task_type.documents} />

        <Separator className='my-3 bg-slate-200/60 dark:bg-darkmode-400' />
        <div className="flex text-base font-medium">
          Historiek
          {opacity > 0 && (
            <Lottie
              className="w-5 h-5 cursor-help"
              title='Er is de afgelopen 5 dagen activiteit geweest'
              animationData={fireAnimation}
              loop={true}
              style={{ opacity }}
            />
          )}
        </div>
        <TaskActivity comments={task.comments} status={task.status.name} />

      </div>

    </TabsContent>
  )
}

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
    teams.length > 0 ? teams.map((team, index) => (
      <span key={team.id} className={cn({ 'ml-0': index === 0, 'ml-2': index > 0 }, 'text-xs text-slate-500 font-medium rounded-sm border p-1 bg-gray-100')}>
        {team.name}
      </span>

    )) : <span className='text-xs text-slate-500 font-medium'>Deze taak is niet gekoppeld aan een team</span>
  );
};

const DocumentList = ({ documents }) => {
  // Ensure `documents` is always an array
  documents = documents || [];

  // State to track selected document
  const [selectedDoc, setSelectedDoc] = useState(null);

  return (
    <div className="flex flex-wrap items-center">
      {documents?.length > 0 ? (
        documents.map((document) => (
          <div
            key={document.id}
            className="opacity-70 text-xs p-[6px] w-full text-slate-800 font-normal rounded-lg border bg-yellow-50 flex items-center justify-between cursor-pointer"
            onClick={() => setSelectedDoc(document.link)} // Clicking anywhere opens the iframe
          >
            {/* Document Name & Icon */}
            <div className="flex items-center">
              <Lucide icon="FileText" className="w-[14px] h-[14px] text-slate-800 mr-1" />
              {document.name}
            </div>

            {/* Open in New Tab Button */}
            {/* <button
              onClick={(e) => {
                e.stopPropagation(); // Prevent the main div's click event (don't open iframe)
                window.open(document.link, "_blank");
              }}
              className="text-blue-500 hover:text-blue-700 transition"
              title="Open in new tab"
            >
              🔗
            </button> */}
          </div>
        ))
      ) : (
        <span className="text-xs font-medium ml-4">
          Dit taaktype heeft geen documenten
        </span>
      )}

      {/* Show iframe if a document is selected */}
      {selectedDoc && (
        <div className="mt-4 w-full p-4 bg-gray-100 rounded-lg border relative">
          {/* Close Button */}
          <button
            onClick={() => setSelectedDoc(null)}
            className="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition"
          >
            Sluiten ✖
          </button>

          {/* Iframe Container */}
          <iframe
            src={selectedDoc}
            className="w-full h-[500px] border rounded-lg"
            title="Document Viewer"
          />
        </div>
      )}
    </div>
  );
};