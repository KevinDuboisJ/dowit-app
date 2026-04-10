import { router, usePage } from '@inertiajs/react'
import { useLoader } from '@/hooks'
import { PaginationBar } from '@/base-components'
import {
  AnnouncementSheet,
  TaskSheet,
  FilterBar,
  TaskTable,
  TaskTableOnFilter
} from '@/components'

export const TaskDesktopView = ({
  data,
  filters,
  setSheetState,
  handleTaskUpdate
}) => {
  const { Loader } = useLoader()
  const { props, url } = usePage()
  const { settings, user } = props

  const isRequestedTab = url.startsWith('/requested-tasks')
  const isTasksTab = !isRequestedTab

  const handleTabChange = target => {
  if (target === 'tasks' && isTasksTab) return
  if (target === 'requested-tasks' && isRequestedTab) return

  router.visit(
    target === 'tasks' ? '/' : '/requested-tasks',
    {
      preserveScroll: true,
      preserveState: true,
    }
  )
}

  const tabClass = isActive =>
    `relative pb-3 text-[15px] font-medium transition-colors ${
      isActive
        ? 'text-gray-900'
        : 'text-gray-500 hover:text-gray-700'
    }`

  return (
    <div id="taskDesktopView" className="flex h-full min-h-0 flex-col">
      <div className="fadeInUp flex min-h-0 flex-1 flex-col px-6 pt-5">
        <div className="shrink-0 border-b border-gray-200">
          <h1 className="text-3xl font-semibold tracking-tight text-gray-900">
            Dashboard
          </h1>

          <div className="mt-3 flex items-end gap-8">
            <button
              type="button"
              onClick={() => handleTabChange('tasks')}
              className={tabClass(isTasksTab)}
            >
              Taken
              {isTasksTab && (
                <span className="absolute inset-x-0 -bottom-px h-[3px] rounded-full bg-emerald-600" />
              )}
            </button>

            <button
              type="button"
              onClick={() => handleTabChange('requested-tasks')}
              className={tabClass(isRequestedTab)}
            >
              Aangevraagde taken
              {isRequestedTab && (
                <span className="absolute inset-x-0 -bottom-px h-[3px] rounded-full bg-emerald-600" />
              )}
            </button>

            <div className="ml-auto flex items-center gap-2 pb-3">
              <AnnouncementSheet />
              <TaskSheet />
            </div>
          </div>
        </div>

        <div className="relative flex min-h-0 flex-1 flex-col pt-4">
          <FilterBar filters={filters} />

          {filters.loading && (
            <div className="absolute inset-0 z-30 flex items-center justify-center bg-white/70 backdrop-blur-[1px]">
              <Loader width={80} height={80} className="z-40" />
            </div>
          )}

          <div className="min-h-0 flex-1 rounded border border-gray-200">
            {filters.hasActive() || isRequestedTab ? (
              <TaskTableOnFilter
                data={data}
                setSheetState={setSheetState}
                handleTaskUpdate={handleTaskUpdate}
              />
            ) : (
              <TaskTable
                data={data}
                setSheetState={setSheetState}
                handleTaskUpdate={handleTaskUpdate}
                settings={settings}
                user={user}
              />
            )}
          </div>
        </div>
      </div>

      <PaginationBar
        {...data}
        onPageChange={page => filters.apply({ page })}
        onPerPageChange={perPage => filters.apply({ perPage, page: 1 })}
      />
    </div>
  )
}