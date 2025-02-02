import { useRef } from 'react';
import { cn } from '@/utils';
import { __ } from '@/stores';
import { usePage } from '@inertiajs/react';
import {
  Input,
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
  Button,
  Heroicon
} from '@/base-components';

export const FilterBar = ({ filterBarRef, handleFilter, statuses, teams }) => {
  const { url } = usePage();
  const filtersRef = useRef(getFiltersFromUrlParams(url));

  // Expose ref only if provided
  if (filterBarRef) {
    filterBarRef.current = {
      getFilters: () => filtersRef.current,
      resetFilters: () => {
        Object.keys(filtersRef.current).forEach((key) => {
          filtersRef.current[key].value = null;
        });
      },
    };
  }

  const updateFilter = (key, value) => {
    filtersRef.current[key].value = value;
  };

  const onFilter = () => {
    const activeFilters = Object.values(filtersRef.current).filter(filter => filter.value);
    handleFilter(activeFilters);
  };

  return (

    <div className='flex flex-col xl:items-center xl:flex-row xl:items-end xl:items-start shrink-0 gap-y-3 xl:gap-x-3'>
      {/* Input for User */}
      <form
        onSubmit={(e) => {
          e.preventDefault();
          onFilter();
        }}
      >
        <Input
          defaultValue={filtersRef.current.assignedTo.value || ''}
          className="xl:items-center bg-white"
          type="text"
          placeholder="Gebruiker"
          onChange={(e) => updateFilter('assignedTo', e.target.value)}
        />
      </form>

      {/* Status Filter */}
      <Select onValueChange={(value) => updateFilter('status_id', value)} defaultValue={filtersRef.current.status_id.value || ''}>
        <SelectTrigger className="xl:w-[180px] bg-white text-xs text-slate-500">
          <SelectValue placeholder="Status" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value={null}>Status</SelectItem>
          {statuses.map((status) => (
            <SelectItem key={status.id} value={status.name}>
              {__(status.name)}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      {/* Team Filter */}
      <Select onValueChange={(value) => updateFilter('team_id', value)} defaultValue={filtersRef.current.team_id.value || ''}>
        <SelectTrigger className="xl:w-[180px] bg-white text-xs text-slate-500">
          <SelectValue placeholder="Team" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value={null}>Team</SelectItem>
          {teams.map((team) => (
            <SelectItem key={team.value} value={team.value}>
              {team.label}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      {/* Search Button */}
      <Button type="submit" className="w-full xl:w-auto" size="sm" onClick={onFilter}>
        <Heroicon icon="MagnifyingGlass" /> Zoeken
      </Button>
    </div>
  );
};

/**
 * Parses the filters from URL parameters
 */
const getFiltersFromUrlParams = (url) => {
  const params = new URLSearchParams(url);
  const filters = {
    assignedTo: { field: "assignedTo", type: "like", value: null },
    status_id: { field: "status_id", type: "=", value: null },
    team_id: { field: "team_id", type: "=", value: null }
  };

  let currentKey = null;

  params.forEach((value, key) => {
    const match = key.match(/^\/?\??filters\[(\d+)]\[(\w+)]$/);
    if (match) {
      const [, index, property] = match;
      if (index === '0' && property === 'field') {
        currentKey = value;
      }
      if (currentKey) {
        filters[currentKey][property] = value;
      }
    }
  });

  return filters;
};