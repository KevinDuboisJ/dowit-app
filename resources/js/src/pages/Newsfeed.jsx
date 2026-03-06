import { NewsItem } from '@/components'
import { useInfiniteScroll } from '@/hooks'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
  ScrollArea,
  Loader
} from '@/base-components'
import { useState } from 'react'
import { router } from '@inertiajs/react'
import { __ } from '@/stores'

const Newsfeed = ({ newsfeed, teammates, user, statuses }) => {
  const [filters, setFilters] = useState([])
  const { items, loading, lastItemRef, scrollRootRef } = useInfiniteScroll({
    request: newsfeed,
    propKey: 'newsfeed',
    params: { filters }
  })

  const handleSearch = (field, type, value) => {
    let newFilters = filters.filter(f => f.field !== field)

    if (value) {
      newFilters = [...newFilters, { field, type, value }]
    }

    setFilters(newFilters)

    router.get(
      '/newsfeed',
      { filters: newFilters },
      {
        only: ['newsfeed'],
        queryStringArrayFormat: 'indices',
        preserveState: true
      }
    )
  }

  return (
    <div className="flex flex-col h-full p-4">
      <div className="flex mb-2 items-center flex-nowrap col-span-12 fadeInUp">
        <h2 className="text-lg font-medium justify-start">Newsfeed</h2>
      </div>

      <div className="flex flex-col h-full min-h-0 fadeInUp box p-3 gap-y-2">
        {/* Filters */}
        <div className="flex items-center space-x-2">
          <Select
            onValueChange={e => handleSearch('created_by', '=', e)}
            defaultValue={null}
          >
            <SelectTrigger className="w-[180px] bg-white text-sm text-slate-500">
              <SelectValue placeholder="Medewerkers" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={null}>Medewerker</SelectItem>
              {teammates?.map(member => (
                <SelectItem key={member.id} value={member.id}>
                  {`${member.firstname} ${member.lastname}`}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Select onValueChange={e => handleSearch('team_id', '=', e)}>
            <SelectTrigger className="w-[180px] bg-white text-sm text-slate-500">
              <SelectValue placeholder="Team" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={null}>Team</SelectItem>
              {user?.teams?.map(team => (
                <SelectItem key={team.id} value={team.id}>
                  {team.name}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          {/* <Select onValueChange={e => handleSearch('status_id', '=', e)}>
            <SelectTrigger className="w-[180px] bg-white text-sm text-slate-500">
              <SelectValue placeholder="Status" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={null}>Status</SelectItem>
              {statuses?.map(status => (
                <SelectItem key={status.id} value={status.id}>
                  {__(status.name)}
                </SelectItem>
              ))}
            </SelectContent>
          </Select> */}
        </div>

        {/* Infinite scroll list */}
        <ScrollArea ref={scrollRootRef} className={`fadeInUp pt-3`}>
          <div className="flex flex-col h-full gap-8">
            {items?.map((item, index) => (
              <div
                key={item.id}
                ref={items.length === index + 1 ? lastItemRef : null}
              >
                <NewsItem newsItem={item} />
              </div>
            ))}

            {items?.length === 0 && !loading && (
              <p className="text-sm text-slate-500">
                Geen gegevens om weer te geven
              </p>
            )}

            {loading && (
              <div className="flex w-full items-center justify-center">
                <Loader />
              </div>
            )}
          </div>
        </ScrollArea>
      </div>
    </div>
  )
}

export default Newsfeed
