import { usePage } from '@inertiajs/react'
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
    const { settings, user } = usePage().props

  return (
    <div id="taskDesktopView" className="flex flex-col h-full min-h-0">
      <div className="flex flex-col h-full p-4 min-h-0 fadeInUp space-y-2">
        <div className="flex flex-col xl:items-center xl:flex-row xl:items-end xl:items-start shrink-0 gap-y-3">
          <FilterBar filters={filters} />
          <div className="ml-auto space-x-2">
            <AnnouncementSheet />
            <TaskSheet />
          </div>
        </div>

        {/* Loading Overlay */}
        {filters.loading && (
          <div className="absolute inset-0 flex justify-center items-center bg-gray-100 bg-opacity-50 z-30 transition-opacity duration-300">
            <Loader width={80} height={80} className="z-40" />
          </div>
        )}

        {filters.hasActive() ? (
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
      <PaginationBar
        {...data}
        onPageChange={page => filters.apply({ page })}
        onPerPageChange={perPage => filters.apply({ perPage })}
      />
    </div>
  )
}
