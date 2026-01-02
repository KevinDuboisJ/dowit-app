import {router} from '@inertiajs/react'
import {__} from '@/stores'
import {useLoader} from '@/hooks'
import {AnnouncementSheet, TaskSheet, FilterBar, TaskTable} from '@/components'

export const TaskDesktopView = ({
  filtersRef,
  tasks,
  setSheetState,
  handleTaskUpdate
}) => {
  const {loading, setLoading, Loader} = useLoader()

  return (
    <div className="flex flex-col h-full min-h-0 p-4 fadeInUp space-y-2">
      <div className="flex flex-col xl:items-center xl:flex-row xl:items-end xl:items-start shrink-0 gap-y-3">
        <FilterBar
          filtersRef={filtersRef}
          onApplyFilters={({activeFilters}) => {
            setLoading(true)
            router.get(
              '/',
              {filters: activeFilters},
              {
                only: ['tasks'],
                queryStringArrayFormat: 'indices',
                preserveState: true,
                onSuccess: () => {
                  setLoading(false)
                },
                onError: error => {
                  console.log(error)
                }
              }
            )
          }}
        />
        <div className="ml-auto space-x-2">
          <AnnouncementSheet/>
          <TaskSheet />
        </div>
      </div>
      {/* Loading Overlay */}
      {loading && (
        <div className="absolute inset-0 flex justify-center items-center bg-gray-100 bg-opacity-50 z-30 transition-opacity duration-300">
          <Loader width={80} height={80} className="z-40" />
        </div>
      )}
      <TaskTable
        tasks={tasks}
        setSheetState={setSheetState}
        handleTaskUpdate={handleTaskUpdate}
      />
    </div>
  )
}
