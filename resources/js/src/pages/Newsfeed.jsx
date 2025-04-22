import { NewsItem } from '@/components';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
  Separator,
  Loader,
} from '@/base-components';
import { useState, useEffect, useRef, useCallback } from 'react'
import { router } from '@inertiajs/react'
import { __ } from '@/stores'

const Newsfeed = ({ newsfeed: initNewsfeed, teammates, statuses, teams }) => {

  const [newsfeed, setNewsfeed] = useState(initNewsfeed.data);
  const [page, setPage] = useState(1);
  const [loading, setLoading] = useState(false);
  const [hasMoreToLoad, setHasMoreToLoad] = useState(initNewsfeed.current_page < initNewsfeed.last_page);
  const [filters, setFilters] = useState([]);
  const observer = useRef();
  const lastNewsItemElementRef = useCallback(
    (node) => {
      if (loading) return;
      if (observer.current) observer.current.disconnect();

      observer.current = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && hasMoreToLoad) {
          setPage((prevPage) => prevPage + 1); // trigger loading of new posts by chaging page no
        }
      });

      if (node) observer.current.observe(node);
    },
    [loading, hasMoreToLoad]
  );

  const loadMoreNewsItems = (page) => {

    setLoading(true);
    router.reload({
      method: 'post',
      only: ['newsfeed'], // Specify the data to reload
      data: { page: page }, // Pass the parameters
      onSuccess: ({ props }) => {

        const newsfeed = props.newsfeed.data;
        const currentPage = props.newsfeed.current_page;
        const lastPage = props.newsfeed.last_page;

        setNewsfeed((prevNewsfeed) => [...prevNewsfeed, ...newsfeed])
        setHasMoreToLoad(currentPage < lastPage)
        setLoading(false);
      },
      onError: (error) => {
        console.error('Failed to reload newsfeed:', error);
      },
    });

  };

  useEffect(() => {

    if (page === 1) {
      return;
    }

    loadMoreNewsItems(page);
  }, [page]);


  // const debounceSearch = debounce((e) => {
  //   router.reload({ data: { search: e.target.value }, preserveState: true })
  // }, 250)

  const handleSearch = (field, type, value) => {

    let newFilters = [];

    newFilters = [
      ...filters.filter((filter) => filter.field !== field) // Remove existing filter with the same field
    ];

    if (value) {
      newFilters = [
        ...newFilters,
        {
          field: field,
          type: type,
          value: value,
        },
      ];
    }

    router.get('/newsfeed', {
      filters: newFilters
    }, {
      only: ['newsfeed'],
      queryStringArrayFormat: "indices",
      preserveState: true,

      onSuccess: ({ props }) => {

        const newsfeed = props.newsfeed.data;
        const currentPage = props.newsfeed.current_page;
        const lastPage = props.newsfeed.last_page;

        setNewsfeed(newsfeed)
        setPage(1)
        setHasMoreToLoad(currentPage < lastPage)
        setLoading(false);
      },
    })

  }

  return (
    <div className='flex flex-col h-full'>
      <div className='flex mb-2 items-center flex-nowrap col-span-12 fadeInUp'>
        <h2 className='text-lg font-medium justify-start'>Newsfeed</h2>
      </div>
      <div className='flex flex-col h-full min-h-0 fadeInUp box p-3 gap-y-2'>
        <div className='flex items-center space-x-2'>
          <Select onValueChange={(e) => {
            handleSearch('user_id', '=', e)
          }}
            defaultValue={null}
          >
            <SelectTrigger className='w-[180px] bg-white text-xs text-slate-500'>
              <SelectValue placeholder='Medewerkers' />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={null}>Medewerker</SelectItem>
              {teammates.map(member => (
                <SelectItem key={member.id} value={member.id}>{`${member.firstname} ${member.lastname}`}</SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Select onValueChange={(e) => {
            handleSearch('teams.id', '=', e)
          }}>
            <SelectTrigger className='w-[180px] bg-white text-xs text-slate-500'>
              <SelectValue placeholder='Team' />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={null}>Team</SelectItem>
              {teams.map(team => (
                <SelectItem key={team.id} value={team.id}>{team.name}</SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Select onValueChange={(e) => {
            handleSearch('status_id', '=', e)
          }}>
            <SelectTrigger className='w-[180px] bg-white text-xs text-slate-500'>
              <SelectValue placeholder='Status' />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value={null}>Status</SelectItem>
              {statuses.map(status => (
                <SelectItem key={status.id} value={status.id}>{__(status.name)}</SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>

        <Separator/>

        <div className='flex flex-col h-full overflow-auto'>

          {newsfeed.map((item, index) => (
            <div key={item.id} ref={newsfeed.length === index + 1 ? lastNewsItemElementRef : null} >
              <NewsItem newsItem={item} />
            </div>
          )
          )}

          {newsfeed.length === 0 && <p>Geen gegevens om weer te geven</p>}
          
          {loading && <div className='flex w-full intems-center justify-center'><Loader width={96} height={96}/></div>}

        </div>

      </div>
    </div>
  )
}

export default Newsfeed;