import { useState } from 'react'
import { usePoll, router } from '@inertiajs/react'
import {
  Card,
  CardContent,
  Badge,
  Select,
  SelectTrigger,
  SelectValue,
  SelectContent,
  SelectItem,
  ScrollArea,
  Heroicon,
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
  PaginationBar
} from '@/base-components'

const Bed = ({ beds, filters: initialFilters, filterOptions }) => {
  const rows = beds.data

  const [filters, setFilters] = useState({
    department: initialFilters?.department ?? 'all',
    campus: initialFilters?.campus ?? 'all',
    room: initialFilters?.room ?? 'all',
    bed: initialFilters?.bed ?? 'all',
    room_type: initialFilters?.room_type ?? 'all',
    needs_cleaning_only: initialFilters?.needs_cleaning_only ?? false,
    show_occupied_only: initialFilters?.show_occupied_only ?? false,
    show_cleaned_only: initialFilters?.show_cleaned_only ?? false
  })

  const hasActiveFilters =
    filters.department !== 'all' ||
    filters.campus !== 'all' ||
    filters.room !== 'all' ||
    filters.bed !== 'all' ||
    filters.room_type !== 'all' ||
    filters.needs_cleaning_only ||
    filters.show_occupied_only ||
    filters.show_cleaned_only

  const visitWithFilters = nextParams => {
    router.get('beds', nextParams, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      only: ['beds', 'filters']
    })
  }

  const applyFilters = nextFilters => {
    setFilters(nextFilters)

    visitWithFilters({
      ...nextFilters,
      page: 1
    })
  }

  const updateFilter = (key, value) => {
    applyFilters({
      ...filters,
      [key]: value
    })
  }

  const resetFilters = () => {
    const cleared = {
      department: 'all',
      campus: 'all',
      room: 'all',
      bed: 'all',
      room_type: 'all',
      needs_cleaning_only: false,
      show_occupied_only: false,
      show_cleaned_only: false
    }

    applyFilters(cleared)
  }

  const handlePageChange = page => {
    visitWithFilters({
      ...filters,
      page
    })
  }

  usePoll(10000, () => {
    router.reload({
      only: ['beds'],
      preserveState: true,
      preserveScroll: true
    })
  })

  return (
    <div className="flex flex-col h-full">
      <Accordion
        type="single"
        collapsible
        defaultValue="filters"
        className="p-4 fadeInUp"
      >
        <AccordionItem value="filters" className="border-b-0">
          <div className="flex items-center">
            <h2 className="text-lg font-medium">Bedden</h2>

            <div className="flex ml-auto items-center flex-nowrap space-x-1">
              {hasActiveFilters && (
                <button
                  onClick={resetFilters}
                  className="px-2 py-1 text-xs rounded-xl border border-slate-300 text-white bg-red-700 hover:bg-red-800 transition"
                >
                  Reset filters
                </button>
              )}
              <AccordionTrigger className="p-0 hover:no-underline" />
            </div>
          </div>

          <AccordionContent className="flex flex-col flex-wrap xl:flex-nowrap gap-y-2 p-1">
            <div className="flex w-full flex-col gap-2 xl:flex-row [&>*]:w-full xl:[&>*]:w-auto items-center">
              <Select
                value={filters.campus}
                onValueChange={value => updateFilter('campus', value)}
              >
                <SelectTrigger className="w-[200px] bg-white text-sm text-slate-500">
                  <SelectValue placeholder="Kies campus" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Alle campussen</SelectItem>
                  {filterOptions.campuses.map(campus => (
                    <SelectItem key={campus} value={campus}>
                      {campus}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>

              <Select
                value={filters.department}
                onValueChange={value => updateFilter('department', value)}
              >
                <SelectTrigger className="w-[200px] bg-white text-sm text-slate-500">
                  <SelectValue placeholder="Kies departement" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Alle departementen</SelectItem>
                  {filterOptions.departments.map(dep => (
                    <SelectItem key={dep} value={dep}>
                      {dep}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>

              <Select
                value={filters.room_type}
                onValueChange={value => updateFilter('room_type', value)}
              >
                <SelectTrigger className="w-[180px] bg-white text-sm text-slate-500">
                  <SelectValue placeholder="Kies kamertype" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Alle soort kamers</SelectItem>
                  <SelectItem value="one">Eenpersoonskamer</SelectItem>
                  <SelectItem value="two">Tweepersoonskamers</SelectItem>
                </SelectContent>
              </Select>

              <Select
                value={filters.room}
                onValueChange={value => updateFilter('room', value)}
              >
                <SelectTrigger className="w-[150px] bg-white text-sm text-slate-500">
                  <SelectValue placeholder="Kamer" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Alle kamers</SelectItem>
                  {filterOptions.rooms.map(room => (
                    <SelectItem key={room} value={room}>
                      {room}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>

              <Select
                value={filters.bed}
                onValueChange={value => updateFilter('bed', value)}
              >
                <SelectTrigger className="w-[120px] bg-white text-sm text-slate-500">
                  <SelectValue placeholder="Bed" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Alle bedden</SelectItem>
                  {filterOptions.beds.map(bed => (
                    <SelectItem key={bed} value={bed}>
                      {bed}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="flex flex-wrap gap-2 items-center justify-between xl:justify-normal">
              <label className="flex items-center gap-2 text-sm cursor-pointer">
                <input
                  type="checkbox"
                  checked={filters.needs_cleaning_only}
                  onChange={e =>
                    updateFilter('needs_cleaning_only', e.target.checked)
                  }
                />
                Poetsen nodig
              </label>

              <label className="flex items-center gap-2 text-sm cursor-pointer">
                <input
                  type="checkbox"
                  checked={filters.show_occupied_only}
                  onChange={e =>
                    updateFilter('show_occupied_only', e.target.checked)
                  }
                />
                Bezette bedden
              </label>

              <label className="flex items-center gap-2 text-sm cursor-pointer">
                <input
                  type="checkbox"
                  checked={filters.show_cleaned_only}
                  onChange={e =>
                    updateFilter('show_cleaned_only', e.target.checked)
                  }
                />
                Gepoetste bedden
              </label>
            </div>
          </AccordionContent>
        </AccordionItem>
      </Accordion>

      <ScrollArea className="flex flex-col h-full shrink-1 min-h-0">
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5 p-4 pt-2 fadeInUp">
          {rows.map(bed => {
            const currentVisit = bed.bed_visits.find(v => !v.vacated_at)
            const patient = currentVisit?.visit?.patient
            const hasToBeCleaned =
              (!bed.occupied_at && !bed.cleaned_at) ||
              (bed.occupied_at && !bed.cleaned_at)
            const occupied = bed.occupied_at && bed.cleaned_at
            const ready = !bed.occupied_at && bed.cleaned_at

            return (
              <Card key={bed.id}>
                <CardContent className="flex flex-col h-full p-4 pb-0">
                  <div className="flex justify-between items-start mb-2">
                    <div className="leading-tight">
                      <h2 className="text-lg font-semibold">
                        Kamer {bed.room?.number}, Bed {bed.number}
                      </h2>
                      <p className="text-sm text-muted-foreground">
                        {bed.room?.campus?.name}, {bed.room?.department?.number}
                      </p>
                    </div>

                    <div className="space-x-2">
                      <Badge
                        variant={
                          (occupied && 'destructive') ||
                          (ready && 'success') ||
                          (hasToBeCleaned && 'warning')
                        }
                      >
                        {hasToBeCleaned && 'Poetsen'}
                        {occupied && !hasToBeCleaned && 'Bezet'}
                        {ready && 'Klaar'}
                      </Badge>
                    </div>
                  </div>

                  {currentVisit ? (
                    <div className="mb-2 text-sm">
                      <div className="flex items-center">
                        <Heroicon
                          icon="User"
                          variant="solid"
                          className="h-4 w-4 text-slate-400 mr-1"
                        />
                        {patient?.firstname} {patient?.lastname}
                      </div>
                      <div className="flex items-center">
                        <Heroicon
                          icon="Calendar"
                          variant="solid"
                          className="h-4 w-4 text-slate-400 mr-1"
                        />
                        {new Date(currentVisit.occupied_at).toLocaleString()}
                      </div>
                    </div>
                  ) : (
                    <p className="text-sm italic text-muted-foreground mb-2">
                      Geen patiënt momenteel toegewezen
                    </p>
                  )}

                  {bed.cleaned_at && (
                    <span className="text-xs italic text-muted-foreground opacity-[0.3] mt-auto">
                      Gepoetst op: {bed.cleaned_at}
                    </span>
                  )}
                </CardContent>
              </Card>
            )
          })}
        </div>
      </ScrollArea>

      <PaginationBar {...beds} onPageChange={handlePageChange} />
    </div>
  )
}

export default Bed
