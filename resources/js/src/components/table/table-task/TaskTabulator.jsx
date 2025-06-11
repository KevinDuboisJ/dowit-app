import React, { useEffect, useRef } from 'react';
import { usePage, router } from '@inertiajs/react';
import { renderToString } from 'react-dom/server';
import { __ } from '@/stores';
import { format, parseISO } from 'date-fns';
import helpAnimation from '@json/animation-help.json';
import Lottie from "lottie-react";
import { reactFormatter } from '@/utils'
import {
  Tabulator,
  ResponsiveLayoutModule,
  AjaxModule,
  FormatModule,
  GroupRowsModule,
  PageModule,
  PrintModule,
  DownloadModule,
  ResizeColumnsModule,
  SortModule,
  FilterModule,
  InteractionModule,
  MutatorModule,
} from 'tabulator-tables';

import {
  Loader,
  AvatarStack,
  Tippy,
} from '@/base-components';
import { TaskActionButton, getPriority } from '@/components';


// Priority Cell
const PriorityCell = ({ cell, settings }) => {
  const row = cell._cell.row.data;

  return (
    <Tippy className="cursor-help" content={getPriority(row.created_at, row.priority, settings.TASK_PRIORITY.value).state}>
      <div className="flex flex-col w-full">
        <div
          className="whitespace-nowrap w-4 h-4 mx-auto rounded-full"
          style={{ backgroundColor: getPriority(row.created_at, row.priority, settings.TASK_PRIORITY.value).color }}
        />
      </div>
    </Tippy>
  );
};

// Status Cell
const StatusCell = ({ cell }) => {
  const row = cell._cell.row.data;

  return (
    <Tippy content='Hulp gevraagd' options={{ allowHTML: true }}>
      <span className={`whitespace-nowrap text-sm text-${row.status.color}`}>
        {row.needs_help && !row.capabilities.isAssignedToCurrentUser && (
          <Lottie className="absolute top-0 left-1 w-6 h-6 mr-2 cursor-help" animationData={helpAnimation} loop={true} autoplay={true} />
        )}
        {__(row.status.name)}
      </span>
    </Tippy>
  );
};

// Assigned Users Cell
const AssignedCell = ({ cell }) => {
  const value = cell._cell.getValue();
  return value === <AvatarStack avatars={value} />;
}

const Action = ({ cell, user, handleTaskUpdate }) => {
  const row = cell._cell.row.data;
  return <TaskActionButton task={row} user={user} handleTaskUpdate={handleTaskUpdate} />;
}


/* Main Tabulator Component */
export const TaskTabulator = ({
  tabulatorRef,
  tasks,
  setTasks,
  handleTaskUpdate,
  setSheetState,
  setPlaceholderAnnouncements,
  settings,
}) => {

  const { user } = usePage().props;
  const tableRef = useRef();
  const reactRoots = useRef(new Map()); // Store React roots for cleanup

  // Initializes Tabulator with our configuration.
  const initTabulator = () => {
    Tabulator.registerModule([
      ResponsiveLayoutModule,
      AjaxModule,
      FormatModule,
      GroupRowsModule,
      PageModule,
      PrintModule,
      DownloadModule,
      ResizeColumnsModule,
      SortModule,
      FilterModule,
      InteractionModule,
      MutatorModule,
    ]);

    tabulatorRef.current = new Tabulator(tableRef.current, {
      data: tasks,

      groupBy: (data) => {
        const assigned = data.capabilities?.isAssignedToCurrentUser;
        return assigned === true || assigned === 'true'
          ? '0_Aan mij toegewezen'
          : '1_Niet aan mij toegewezen';
      },
      groupHeader: (value, count) => {
        const parts = value.split('_');
        const label = parts.length > 1 ? parts[1] : value;
        return `<span class="tabulator-group-content">${label}<span class="tabulator-group-info">${count}</span></span>`;
      },
      groupUpdateOnCellEdit:true,
      
      minHeight: '650',
      width: '100%',
      rowHeight: 58,
      dataLoaderLoading: renderToString(<Loader />),
      layout: 'fitDataFill',
      placeholder: 'Geen overeenkomende records gevonden',
      headerHozAlign: 'left',
      ajaxLoader:false,
      ajaxURL: import.meta.env.VITE_APP_URL,
      ajaxRequestFunc: (url, config, params) =>
        new Promise((resolve, reject) => {
          router.get(
            url,
            {
              page: params.page,
              size: params.size,
              filters: params.filter,
              sorters: params.sort,
            },
            {
              only: ['tasks'],
              queryStringArrayFormat: 'indices',
              preserveState: true,
              onSuccess: ({ props }) => {
                setTasks(props.tasks.data);
                resolve(props.tasks.data);
              },
              onError: (error) => {
                reject(error);
              },
            }
          );
        }),
      filterMode: 'remote',
      sortMode: 'remote',
      columns: [
        {
          field: 'priority',
          headerSort: false,
          resizable: false,
          cssClass: '!px-0',
          maxWidth: '40px',
          vertAlign: 'middle',
          hozAlign: 'center',
          formatter: reactFormatter(<PriorityCell settings={settings} />),
        },
        {
          title: 'Collega nodig',
          field: 'needs_help',
          visible: false,
          mutateLink: 'status.name',
        },
        {
          title: 'Status',
          field: 'status.name',
          width: '120',
          vertAlign: 'middle',
          formatter: reactFormatter(<StatusCell />),
        },
        {
          title: 'Tijd',
          field: 'start_date_time',
          minWidth: '80',
          vertAlign: 'middle',
          formatter: (cell) =>
            `<div class='whitespace-nowrap text-sm'>${format(
              parseISO(cell.getValue()),
              'PP HH:mm'
            )}</div>`,
        },
        {
          title: 'Taak',
          field: 'name',
          minWidth: '85',
          vertAlign: 'middle',
          formatter: (cell) =>
            `<div class='whitespace-nowrap text-sm'>${cell.getValue()}</div>`,
        },
        {
          title: 'Taaktype',
          field: 'task_type.name',
          minWidth: '110',
          vertAlign: 'middle',
          formatter: (cell) =>
            `<div class='whitespace-nowrap text-sm'>${cell.getValue()}</div>`,
        },
        {
          title: 'Wie',
          field: 'patient',
          minWidth: '110',
          vertAlign: 'middle',
          headerSort: false,
          formatter: (cell) => {
            const patient = cell.getValue();
            if (patient) {
              const { firstname, lastname } = patient;
              return `<div class='whitespace-nowrap text-sm'>${firstname} ${lastname}</div>`;
            }
            return '';
          },
        },
        {
          title: 'Toegewezen',
          field: 'assignees',
          minWidth: '130',
          vertAlign: 'middle',
          headerSort: false,
          mutateLink: 'action',
          formatter: reactFormatter(<AssignedCell />),
        },
        {
          title: 'Recente commentaar',
          field: 'comments',
          minWidth: '150',
          vertAlign: 'middle',
          headerSort: false,
          formatter: (cell) => {
            cell.getElement().style.whiteSpace = 'pre-wrap';
            const comment = cell.getValue()?.[0]?.content ?? '';
            return `<div class='whitespace-nowrap text-sm'>${comment}</div>`;
          },
        },
        {
          title: 'Actie',
          field: 'action',
          minWidth: '120',
          vertAlign: 'middle',
          headerSort: false,
          formatter: reactFormatter(<Action user={user} handleTaskUpdate={handleTaskUpdate} />),

        },
        {
          title: "Assigned",
          field: "capabilities.isAssignedToUser",
          visible: false, // Hide column from UI
          sorter: (a, b) => {
            let valA = a ? 1 : 0;
            let valB = b ? 1 : 0;
            return valA - valB;
          }
        }
      ],
    });
  };

  useEffect(() => {
    // Initialize Tabulator.
    initTabulator();

    // Define event handlers.
    const handleRowClick = (e, row) => {
      const clickedCell = e.target.closest('.tabulator-cell');
      if (clickedCell) {
        const cellField = clickedCell.getAttribute('tabulator-field');
        if (cellField === 'action' && clickedCell.hasChildNodes()) {
          e.stopPropagation();
          e.preventDefault();
          return;
        }
      }
      setSheetState({ open: true, task: row.getData() });
    };

    const handleTableBuilt = () => {

      const tableEl = tabulatorRef.current.element;
      let placeholder = tableEl.querySelector('.announcement-placeholder');
      if (!placeholder) {
        placeholder = document.createElement('div');
        placeholder.className = 'announcement-placeholder';
        tableEl.querySelector('.tabulator-tableholder').prepend(placeholder);
      }
      setPlaceholderAnnouncements(placeholder);
    };

    const handleDataLoadError = (error) => {
      console.error('Data Load Error:', error.url);
    };

    const handleResize = () => {
      if (tabulatorRef.current) {
        tabulatorRef.current.redraw();
      }
    };

    // Attach event listeners.
    if (tabulatorRef.current) {
      window.addEventListener('resize', handleResize);
      tabulatorRef.current.on('rowClick', handleRowClick);
      tabulatorRef.current.on('tableBuilt', handleTableBuilt);
      tabulatorRef.current.on('dataLoadError', handleDataLoadError);

    }

    // Cleanup on unmount.
    return () => {
      if (tabulatorRef.current) {
        tabulatorRef.current.off('rowClick', handleRowClick);
        tabulatorRef.current.off('tableBuilt', handleTableBuilt);
        tabulatorRef.current.off('dataLoadError', handleDataLoadError);
        tabulatorRef.current.destroy();
      }
      // cleanUpReactComponents();
      window.removeEventListener('resize', handleResize);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []); // Run only once on mount


  useEffect(() => {
    if (tabulatorRef.current) {
      tabulatorRef.current.replaceData(tasks);
    }
  }, [tasks]);



  return (
    <div className="overflow-auto">
      <div id="tabulator" ref={tableRef}></div>
    </div>
  );
};
