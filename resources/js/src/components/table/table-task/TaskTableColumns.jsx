import React, { useMemo } from 'react';
import { usePage } from '@inertiajs/react';
import { createColumnHelper } from '@tanstack/react-table';
import { format, parseISO } from 'date-fns';
import { __ } from '@/stores';
import helpAnimation from '@json/animation-help.json';
import Lottie from "lottie-react";
import { Loader, AvatarStack, Tippy, RichText } from '@/base-components';
import { TaskActionButton, getPriority } from '@/components';

export const useTaskTableColumns = ({ handleTaskUpdate, handleTasksRecon }) => {
  const { settings, user } = usePage().props;
  const columnHelper = createColumnHelper();

  const columns = useMemo(() => [

    // Hidden grouping column (DO NOT set a header)
    columnHelper.accessor('capabilities.isAssignedToCurrentUser',
      {
        id: 'assignedGroup',
        header: () => null, // Remove header
        cell: () => null, // Prevent rendering in cells
      }
    ),

    // Priority Column
    columnHelper.accessor('priority', {
      id: 'priority',
      header: '',
      size: 40,
      cell: (cell) => {
        const data = cell.row.original;
        const priorityInfo = getPriority(data.created_at, data.priority, settings.TASK_PRIORITY.value);
        return (
          <Tippy content={priorityInfo.state}>
            <div className="w-4 h-4 mx-auto rounded-full" style={{ backgroundColor: priorityInfo.color }} />
          </Tippy>
        );
      },
      enableSorting: false,
    }),

    // Status Column
    columnHelper.accessor('status.name', {
      id: 'status',
      header: 'Status',
      size: 100,
      cell: ({ cell, row }) => {
        return row.original.needs_help ? (
          <Tippy content="Hulp gevraagd" options={{ allowHTML: true }}>
            <span className="relative whitespace-nowrap text-sm">
              <HelpAnimation
                needsHelp={row.original.needs_help}
                isAssignedToCurrentUser={row.original.capabilities.isAssignedToCurrentUser}
              />
              {__(cell.getValue())}
            </span>
          </Tippy>
        ) : (
          <span className="relative whitespace-nowrap text-sm">
            {__(cell.getValue())}
          </span>
        )
      },
    }),

    // Start Date Column
    columnHelper.accessor('start_date_time', {
      header: 'Tijd',
      size: 130,
      cell: ({ cell, row }) => {
        const dateStr = cell.getValue();
        return <div className="whitespace-nowrap text-sm">{format(parseISO(dateStr), 'PP HH:mm')}</div>;
      },
    }),

    // Task Name Column
    columnHelper.accessor('name', {
      header: 'Taak',
      size: 150,
      cell: (cell) => <div className='whitespace-nowrap text-sm'>{cell.getValue()}</div>,
    }),

    // Task Type Column
    columnHelper.accessor('task_type.name', {
      id: 'task_type',
      header: 'Taaktype',
      size: 150,
      cell: (cell) => <div className='whitespace-nowrap text-sm'>{cell.getValue()}</div>,
    }),

    // Assigned Users Column
    columnHelper.accessor('assignees', {
      id: 'assignees',
      header: 'Toegewezen',
      size: 170,
      cell: (cell) => {
        const users = cell.getValue();
        return <AvatarStack avatars={users} />;
      },
    }),

    columnHelper.accessor('comments', {
      id: 'comment',
      header: 'Recente commentaar',
      cell: ({ cell }) => {
        return <RichText text={cell.getValue()?.[0]?.content} className='whitespace-nowrap text-sm'></RichText>;
      },
    }),

    // Action Button Column
    columnHelper.display({
      id: 'action',
      meta: {
        align: 'center'
      },
      cell: (cell) => <TaskActionButton task={cell.row.original} user={user} handleTaskUpdate={handleTaskUpdate} handleTasksRecon={handleTasksRecon} />,
    }),

  ], []);

  return columns;
};

// Memoized component that only re-renders when `needsHelp` or `isAssignedToCurrentUser` changes.
const HelpAnimation = React.memo(
  ({ needsHelp, isAssignedToCurrentUser }) => {
    // Only render if help is needed and the current user is not assigned.
    if (!needsHelp || isAssignedToCurrentUser) return null;
    return (
      <Lottie
        className="absolute -top-6 -left-5 w-6 h-6 mr-2 cursor-help"
        animationData={helpAnimation}
        loop={true}
        autoplay={true}
      />
    );
  },
);