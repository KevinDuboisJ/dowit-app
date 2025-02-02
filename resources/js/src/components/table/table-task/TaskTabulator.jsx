import { useEffect, useRef } from 'react';
import { usePage, router } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { renderToString } from 'react-dom/server';
import { __, getColor } from '@/stores';
import { format, parseISO } from 'date-fns';
import helpAnimation from '@json/animation-help.json';
import tippy from 'tippy.js';
import { default as lottie } from 'lottie-web';
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
  MutatorModule
} from 'tabulator-tables';

import { Loader, AvatarStack } from '@/base-components';
import { TaskActionButton, getPriority } from '@/components';

/* Helper cell components rendered via React */

// Renders the “priority” cell.
const PriorityCell = ({ row, settings }) => {
  const cellRef = useRef(null);

  useEffect(() => {
    if (cellRef.current) {
      const button = cellRef.current.querySelector('[data-tippy-content]');
      if (button) {
        tippy(button, {
          content: button.getAttribute('data-tippy-content'),
        });
      }
    }
  }, []);

  const priority = getPriority(row.created_at, row.priority, settings.TASK_PRIORITY.value);
  return (
    <div className="flex flex-col w-full" ref={cellRef}>
      <div
        data-tippy-content={priority.state}
        style={{ backgroundColor: priority.color }}
        className="whitespace-nowrap w-4 h-4 mx-auto rounded-full"
      />
    </div>
  );
};

// Renders the “status” cell.
const StatusCell = ({ row }) => {
  const cellRef = useRef(null);

  useEffect(() => {
    if (cellRef.current) {
      const button = cellRef.current.querySelector('[data-tippy-content]');
      if (button) {
        tippy(button, {
          content: button.getAttribute('data-tippy-content'),
          offset: [30, 25],
        });
        const animationContainer = button.querySelector('[data-lottie]');
        if (animationContainer) {
          lottie.loadAnimation({
            container: animationContainer,
            renderer: 'svg',
            loop: true,
            autoplay: true,
            animationData: helpAnimation,
          });
        }
      }
    }
  }, []);

  const value = row.status.name;
  const colorClass = `text-${getColor(value)}`;
  return (
    <span ref={cellRef} className={`whitespace-nowrap text-xs ${colorClass}`}>
      {row.needs_help && !row.capabilities.isAssignedToCurrentUser && (
        <button data-tippy-content="Hulp gevraagd">
          <div
            className="absolute top-1 left-0 w-5 h-5 mr-2 cursor-help"
            data-lottie="true"
          ></div>
        </button>
      )}
      {__(value)}
    </span>
  );
};

// Renders the “assigned users” cell.
const AssignedCell = ({ value }) => {
  return value === '{{loading}}' ? <Loader /> : <AvatarStack users={value} />;
};

// Renders the “action” cell.
const ActionCell = ({ task, user, handleRowUpdate }) => {
  return <TaskActionButton task={task} user={user} handleRowUpdate={handleRowUpdate} />;
};

/* Main Tabulator Component */
export const TaskTabulator = ({
  tabulatorRef,
  tasks,
  setTasks,
  handleRowUpdate,
  setSheetState,
  setPlaceholderAnnouncements,
  settings,
}) => {
  const { user } = usePage().props;
  const tableRef = useRef();
  console.log('test')
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
      data: tasks,
      height: '100%',
      width: '100%',
      rowHeight: 58,
      dataLoaderLoading: renderToString(<Loader />),
      layout: 'fitDataFill',
      placeholder: 'Geen overeenkomende records gevonden',
      headerHozAlign: 'left',

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
          formatter: (cell, _params, onRendered) => {
            const row = cell.getRow().getData();
            const container = document.createElement('div');
            createRoot(container).render(
              <PriorityCell row={row} settings={settings} />
            );
            return container;
          },
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
          formatter: (cell) => {
            const row = cell.getRow().getData();
            const container = document.createElement('div');
            createRoot(container).render(<StatusCell row={row} />);
            return container;
          },
        },
        {
          title: 'Tijd',
          field: 'start_date_time',
          minWidth: '80',
          vertAlign: 'middle',
          formatter: (cell) =>
            `<div class='whitespace-nowrap text-xs'>${format(
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
            `<div class='whitespace-nowrap text-xs'>${cell.getValue()}</div>`,
        },
        {
          title: 'Taaktype',
          field: 'task_type.name',
          minWidth: '110',
          vertAlign: 'middle',
          formatter: (cell) =>
            `<div class='whitespace-nowrap text-xs'>${cell.getValue()}</div>`,
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
              return `<div class='whitespace-nowrap text-xs'>${firstname} ${lastname}</div>`;
            }
            return '';
          },
        },
        {
          title: 'Toegewezen',
          field: 'assigned_users',
          minWidth: '130',
          vertAlign: 'middle',
          headerSort: false,
          mutateLink: 'action',
          formatter: (cell) => {
            const value = cell.getValue();
            const container = document.createElement('div');
            container.classList.add('flex', 'items-center');
            createRoot(container).render(<AssignedCell value={value} />);
            return container;
          },
          cellClick: (e) => {
            e.stopPropagation();
          },
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
            return `<div class='whitespace-nowrap text-xs'>${comment}</div>`;
          },
        },
        {
          title: 'Actie',
          field: 'action',
          minWidth: '120',
          vertAlign: 'middle',
          headerSort: false,
          formatter: (cell) => {
            const rowData = cell.getRow().getData();
            const container = document.createElement('div');
            createRoot(container).render(
              <ActionCell
                task={rowData}
                user={user}
                handleRowUpdate={handleRowUpdate}
              />
            );
            return container;
          },
        },
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
      window.removeEventListener('resize', handleResize);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []); // Run only once on mount

  return (
    <div className="overflow-auto">
      <div id="tabulator" ref={tableRef}></div>
    </div>
  );
};
