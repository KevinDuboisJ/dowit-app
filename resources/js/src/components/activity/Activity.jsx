import { format, parseISO, isToday } from 'date-fns'
import { nl } from "date-fns/locale";
import { Fragment } from 'react'
import { cn } from '@/utils'
import { __ } from '@/stores';
import { FaBeer } from "react-icons/fa";
import {
  Badge,
  RichText,
  RichTextEditor,
  Heroicon,
  Separator,
} from "@/base-components"

export const Activity = ({ comments }) => {

  return (
    <>
      <div className="h-full">
        {/* BEGIN: Timeline Wrapper */}
        <div className="flex flex-col gap-2 pt-0">
          {comments.length === 0 &&
            <p className="text-sm text-muted-foreground">
              Er zijn nog geen commentaren
            </p>}
          <div className="p-4 relative overflow-hidden">
            {comments.map((comment, index) => {

              return (
                <Fragment key={comment.id}>

                  <VerticalTimeline activity={comment} index={index} lastIndex={comments.length - 1} />

                  {comment.user_id === 1 ?
                    <TextBox activity={comment} />
                    : <UpdateBox activity={comment} />
                  }


                </Fragment>
              )
            })}

          </div>
        </div>

        {/* END: Timeline Wrapper */}
      </div >
    </>
  );
}

const formatDate = (createdAt) => {
  const date = parseISO(createdAt);

  if (isToday(date)) {
    return `vandaag ${format(date, "HH:mm", { locale: nl })}`; // "Vandaag HH:mm"
  }

  return format(date, "PP HH:mm", { locale: nl }); // Dutch localized date
};

const VerticalTimeline = ({ activity, index, lastIndex }) => {

  // if (index == 0) {
  //   return (<Heroicon className="absolute top-0 left-4" icon='Bolt' />);
  // }

  return (
    <>
      <div className={cn('absolute h-full border-l-2 border-gray-300 ', {
        "before:content-[''] before:absolute before:top-[0px] before:left-[-8px] before:w-4 before:h-4 before:bg-primary/20 before:rounded-full before:animate-ping": index == 0,
        // "before:content-['sds'] before:absolute before:bottom-[px]  before:w-2 before:h-2 before:bg-primary/20 before:rounded-full": index == lastIndex,
      })}>
        <Icon activity={activity} />
      </div >
    </>
  );

};

const Icon = ({ activity }) => {

  if (activity.needs_help) {
    return (
      <div className='absolute z-10 -left-2.5 top-0  w-5 h-5 rounded-full flex items-center justify-center'>
        <Heroicon icon='HandRaised' variant='solid' />
      </div>
    )
  }

  if (status === 'Completed') {
    return (<div className='absolute z-10 -left-2.5 top-0 bg-green-500 w-5 h-5 rounded-full flex items-center justify-center'><svg
      xmlns="http://www.w3.org/2000/svg"
      className='w-3 h-3 text-white'
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
    </svg></div>)
  }

  return (
    <div className='relative z-10 right-2 top-0 bg-gray-400 w-4 h-4 rounded-full flex items-center justify-center'></div>
  )

}

const UpdateBoxTitle = ({ activity }) => {

  if (activity.needs_help) {
    return (
      <p className="text-sm text-gray-500">Collega nodig</p>
    )
  }

  return (
    <p className="text-sm text-gray-500">Bewerking</p>
  )
}

const UpdateBox = ({ activity }) => {
  
  return (
    <div className="relative flex mb-6 pl-8 space-x-2">
      <div className='flex flex-col'>
        <UpdateBoxTitle activity={activity} />
        <div className="border rounded-lg px-4 py-3 bg-gray-100">

          {activity.content &&
            <>
              <RichText className='w-full border-none rounded-none shadow-none'>
                <RichTextEditor
                  className='text-xs p-0 m-0 text-gray-800 border-none rounded-md text-gray-700 resize-none overflow-hidden
                     focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none'
                  value={activity.content}
                  readonly={true}
                />
              </RichText>
              <Separator className='my-1 bg-slate-200/60 dark:bg-darkmode-400' />
            </>
          }

          {Array.isArray(activity?.metadata?.changed_keys?.assignees) &&
            activity.metadata.changed_keys.assignees.length > 0 && (
              <div className="flex">
                <p className="text-xs">Toegewezen aan:</p>
                {activity.metadata.changed_keys.assignees.map((assignee, index) => (
                  <p  key={`${activity.id}-${assignee}`} className="text-xs text-gray-800 ml-1">
                    {`${assignee} ${index < activity.metadata.changed_keys.assignees.length - 1 ? ',' : ''}`}
                  </p>
                ))}
              </div>
            )}

          {Array.isArray(activity?.metadata?.changed_keys?.unassignees) &&
            activity.metadata.changed_keys.unassignees.length > 0 && (
              <div className="flex">
                <p className="text-xs">Niet meer toegewezen aan: </p>
                {activity.metadata.changed_keys.unassignees.map((assignee, index) => (
                  <p  key={`${activity.id}-${assignee}`} className="text-xs text-gray-800 ml-1">
                    {`${assignee} ${index < activity.metadata.changed_keys.unassignees.length - 1 ? ',' : ''}`}
                  </p>
                ))}
              </div>
            )}

          {activity?.metadata?.changed_keys &&
            Object.entries(activity.metadata.changed_keys)
              .filter(([key]) => key !== 'assignees' && key !== 'unassignees') // Exclude specific keys
              .map(([key, value]) => (
                <div className="flex" key={key}>
                  <p className="text-xs capitalize">{__(key)}:</p>
                  <p className="text-xs text-gray-800 ml-1">{key === 'needs_help' ? (value ? 'Ja' : 'Nee') : __(value)}</p>
                </div>
              ))
          }

          <div className='flex items-center space-x-1'>
            <p className="text-xs text-gray-800 font-medium">{`${activity.user?.firstname} ${activity.user?.lastname}`}</p>
            <p className="text-xs text-gray-500 ">- {formatDate(activity.created_at)}</p>
          </div>
        </div>
      </div>
    </div>
  )
};

// const CommentBox = ({activity}) => (

//   <div className="relative mt-6 pl-8">

//     <div className="border rounded-lg px-4 py-3 bg-gray-100">
//       <p className="text-xs text-gray-500">
//         {activity.content}
//       </p>
//       <div className='flex items-center space-x-1'>
//         <p className="text-xs text-gray-800 font-medium">{`${activity.user.firstname} ${activity.user.lastname}`}</p>
//         <p className="text-xs text-gray-500 ">- {formatDate(activity.created_at)}</p>
//       </div>

//     </div>
//   </div>
// );

const TextBox = ({ activity }) => (

  <div className='relative mb-6 pl-8'>
    <p className="text-sm text-gray-500">{activity.content}</p>
  </div>

);


