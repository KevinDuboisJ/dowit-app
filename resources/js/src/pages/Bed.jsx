import {useState, useMemo} from 'react'
import {usePoll, router} from '@inertiajs/react'
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
  AccordionTrigger
} from '@/base-components'

const Bed = ({beds, departments}) => {
  const [departmentFilter, setDepartmentFilter] = useState('all')
  const [campusFilter, setCampusFilter] = useState('all')
  const [roomFilter, setRoomFilter] = useState('all')
  const [bedFilter, setBedFilter] = useState('all')
  const [needsCleaningOnly, setNeedsCleaningOnly] = useState(false)
  const [showOccupiedOnly, setShowOccupiedOnly] = useState(false)
  const [showCleanedOnly, setShowCleanedOnly] = useState(false)
  const [roomTypeFilter, setRoomTypeFilter] = useState('all')

  const hasActiveFilters =
    departmentFilter !== 'all' ||
    campusFilter !== 'all' ||
    roomFilter !== 'all' ||
    bedFilter !== 'all' ||
    roomTypeFilter !== 'all' ||
    needsCleaningOnly ||
    showOccupiedOnly ||
    showCleanedOnly

  const resetFilters = () => {
    setDepartmentFilter('all')
    setCampusFilter('all')
    setRoomFilter('all')
    setBedFilter('all')
    setRoomTypeFilter('all')
    setNeedsCleaningOnly(false)
    setShowOccupiedOnly(false)
    setShowCleanedOnly(false)
  }

  const departmentList = departments?.map(dep => dep.number) || []

  usePoll(10000, () => {
    router.reload({only: ['beds']})
  })

  // Extract unique campuses, rooms, and beds
  const campusList = useMemo(() => {
    return [...new Set(beds.map(b => b.room?.campus?.name).filter(Boolean))]
  }, [beds])

  const roomList = useMemo(() => {
    return [...new Set(beds.map(b => b.room?.number).filter(Boolean))].sort(
      (a, b) => Number(a) - Number(b)
    ) // numeric ascending sort
  }, [beds])

  const bedList = useMemo(() => {
    return [...new Set(beds.map(b => b.number).filter(Boolean))].sort(
      (a, b) => Number(a) - Number(b)
    ) // numeric ascending sort
  }, [beds])

  const roomBedCount = useMemo(() => {
    const countMap = {}
    beds.forEach(bed => {
      const roomNumber = bed.room?.number
      if (roomNumber) {
        countMap[roomNumber] = (countMap[roomNumber] || 0) + 1
      }
    })
    return countMap
  }, [beds])

  // Filtering logic
  const filteredBeds = beds.filter(bed => {
    const hasToBeCleaned =
      (!bed.occupied_at && !bed.cleaned_at) ||
      (bed.occupied_at && !bed.cleaned_at)
    const isOccupied = !!bed.occupied_at
    const isCleaned = !!bed.cleaned_at

    const bedCountInRoom = roomBedCount[bed.room?.number] || 0

    // Department filter
    if (
      departmentFilter !== 'all' &&
      bed.room.department?.number !== departmentFilter
    )
      return false
    // Campus filter
    if (campusFilter !== 'all' && bed.room?.campus?.name !== campusFilter)
      return false
    // Room filter
    if (roomFilter !== 'all' && bed.room?.number !== roomFilter) return false
    // Bed filter
    if (bedFilter !== 'all' && bed.number !== bedFilter) return false
    // Needs cleaning filter
    if (needsCleaningOnly && !hasToBeCleaned) return false
    // Occupied filter
    if (showOccupiedOnly && !isOccupied) return false
    // Cleaned filter
    if (showCleanedOnly && !isCleaned) return false
    // Room type filter
    if (roomTypeFilter === 'one' && bedCountInRoom !== 1) return false
    if (roomTypeFilter === 'two' && bedCountInRoom !== 2) return false

    return true
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
          {/* Fixed row with heading & trigger always inline */}
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
              {/* Optional chevron icon */}
              <AccordionTrigger className="p-0 hover:no-underline" />
            </div>
          </div>

          {/* AccordionContent always below the heading row */}
          <AccordionContent className="flex flex-col flex-wrap xl:flex-nowrap gap-y-2 p-1">
            {/* FILTERS */}
            <div className="flex w-full flex-col gap-2 xl:flex-row [&>*]:w-full xl:[&>*]:w-auto items-center">
              {/* Campus Filter */}
              <Select value={campusFilter} onValueChange={setCampusFilter}>
                <SelectTrigger className="w-[200px] bg-white text-sm text-slate-500">
                  <SelectValue placeholder="Kies campus" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Alle campussen</SelectItem>
                  {campusList.map(campus => (
                    <SelectItem key={campus} value={campus}>
                      {campus}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>

              {/* Department Filter */}
              <Select
                value={departmentFilter}
                onValueChange={setDepartmentFilter}
              >
                <SelectTrigger className="w-[200px] bg-white text-sm text-slate-500">
                  <SelectValue placeholder="Kies departement" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Alle departementen</SelectItem>
                  {departmentList.map(dep => (
                    <SelectItem key={dep} value={dep}>
                      {dep}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>

              {/* Room Type Filter */}
              <Select value={roomTypeFilter} onValueChange={setRoomTypeFilter}>
                <SelectTrigger className="w-[180px] bg-white text-sm text-slate-500">
                  <SelectValue placeholder="Kies kamertype" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Alle soort kamers</SelectItem>
                  <SelectItem value="one">Eenpersoonskamer</SelectItem>
                  <SelectItem value="two">Tweepersoonskamers</SelectItem>
                </SelectContent>
              </Select>

              {/* Room Filter */}
              <Select value={roomFilter} onValueChange={setRoomFilter}>
                <SelectTrigger className="w-[150px] bg-white text-sm text-slate-500">
                  <SelectValue placeholder="Kamer" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Alle kamers</SelectItem>
                  {roomList.map(room => (
                    <SelectItem key={room} value={room}>
                      {room}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>

              {/* Bed Filter */}
              <Select value={bedFilter} onValueChange={setBedFilter}>
                <SelectTrigger className="w-[120px] bg-white text-sm text-slate-500">
                  <SelectValue placeholder="Bed" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Alle bedden</SelectItem>
                  {bedList.map(b => (
                    <SelectItem key={b} value={b}>
                      {b}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="flex flex-wrap gap-2 items-center justify-between xl:justify-normal">
              {/* Needs Cleaning Checkbox */}
              <label className="flex items-center gap-2 text-sm cursor-pointer">
                <input
                  type="checkbox"
                  checked={needsCleaningOnly}
                  onChange={e => setNeedsCleaningOnly(e.target.checked)}
                />
                Poetsen nodig
              </label>

              {/* Occupied Beds Checkbox */}
              <label className="flex items-center gap-2 text-sm cursor-pointer">
                <input
                  type="checkbox"
                  checked={showOccupiedOnly}
                  onChange={e => setShowOccupiedOnly(e.target.checked)}
                />
                Bezette bedden
              </label>

              {/* Cleaned Beds Checkbox */}
              <label className="flex items-center gap-2 text-sm cursor-pointer">
                <input
                  type="checkbox"
                  checked={showCleanedOnly}
                  onChange={e => setShowCleanedOnly(e.target.checked)}
                />
                Gepoetste bedden
              </label>
            </div>
          </AccordionContent>
        </AccordionItem>
      </Accordion>

      {/* BED CARDS */}
      <ScrollArea className="flex flex-col shrink-1 min-h-0 ">
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5 p-4 pt-2 fadeInUp">
          {filteredBeds.map(bed => {
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
    </div>
  )
}

export default Bed
