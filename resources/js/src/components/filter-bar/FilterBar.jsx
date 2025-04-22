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

export const FilterBar = ({ defaultValues = {}, onApplyFilters }) => {
  const { statuses, teams } = usePage().props;
  const filtersRef = defaultValues;

  const onFilters = () => {
    const activeFilters = Object.values(filtersRef.current).filter(filter => filter.value);
    onApplyFilters({ activeFilters, filtersRef });
  };

  const isActive = (field) => filtersRef.current[field]?.value;

  return (
    <form
      className='flex z-40 flex-col xl:min-w-[600px] xl:items-center xl:flex-row xl:items-end xl:items-start shrink-0 gap-y-3 xl:gap-x-3'
      onSubmit={(e) => {
        e.preventDefault();
        onFilters();
      }}
    >
      <Input
        defaultValue={filtersRef.current.assignedTo.value || ''}
        className={cn("xl:items-center bg-white", isActive('assignedTo') && 'bg-[rgb(233,240,255,0.65)]')}
        type="text"
        placeholder="Gebruiker"
        onChange={(e) => filtersRef.current.assignedTo.value = e.target.value}
      />

      {/* Status Filter */}
      <Select
        onValueChange={(value) => filtersRef.current.status_id.value = value}
        defaultValue={filtersRef.current.status_id.value || ''}
      >
        <SelectTrigger className={cn("bg-white text-xs text-slate-500", isActive('status_id') && 'bg-[rgb(233,240,255,0.65)]')}>
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
      <Select
        onValueChange={(value) => filtersRef.current.team_id.value = value}
        defaultValue={String(filtersRef.current.team_id.value || '')}
      >
        <SelectTrigger className={cn("bg-white text-xs text-slate-500", isActive('team_id') && 'bg-[rgb(233,240,255,0.65)]')}>
          <SelectValue placeholder="Team" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value={null}>Team</SelectItem>
          {teams.map(team => (
            <SelectItem key={team.id} value={String(team.id)}>
              {team.name}
            </SelectItem>
          ))}
        </SelectContent>
      </Select>

      {/* Search Button */}
      <Button type="submit" className="w-full xl:w-auto" size="sm">
        <Heroicon icon="MagnifyingGlass" /> Zoeken
      </Button>
    </form>
  );
};
