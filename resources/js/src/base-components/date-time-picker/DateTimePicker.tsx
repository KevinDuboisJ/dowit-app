import { useState } from 'react';
import {
  Calendar
} from '@/base-components'

import { setHours, setMinutes, format } from 'date-fns';



export function DateTimePicker({ selected, onSelect, ...props }) {

  const [selectedDateTime, setSelectedDateTime] = useState<Date>(selected);
  const [timeValue, setTimeValue] = useState<string>(selected ? format(new Date(selected), 'HH:mm') : '00:00');

  const handleTimeChange: ChangeEventHandler<HTMLInputElement> = (e) => {
    const time = e.target.value;

    // Update the time value in state
    setTimeValue(time);
  
    // Validate the time format
    if (!/^\d{1,2}:\d{1,2}$/.test(time) || !selectedDateTime) {
      return;
    }
  
    const [hours, minutes] = time.split(":").map((str) => parseInt(str, 10));
  
    // Safely update the selectedDateTime
    const newSelectedDateTime = setHours(setMinutes(selectedDateTime, minutes), hours);
    setSelectedDateTime(newSelectedDateTime);
  
    if (onSelect) {
      onSelect(newSelectedDateTime);
    }
  };

  const handleDaySelect = (date: Date | undefined) => {
    if (!timeValue || !date) {
      setSelectedDateTime(date);
      return;
    }
    const [hours, minutes] = timeValue
      .split(":")
      .map((str) => parseInt(str, 10));
    const newSelectedDateTime = new Date(
      date.getFullYear(),
      date.getMonth(),
      date.getDate(),
      hours,
      minutes
    );

    setSelectedDateTime(newSelectedDateTime);

    if (onSelect) {
      onSelect(newSelectedDateTime)
    }
  };

  return (
    <div>
      <Calendar
        mode='single'
        selected={selectedDateTime}
        onSelect={handleDaySelect}
        initialFocus
        {...props}
      />
      <div className='flex flex-col p-2'>
        <input className='rounded' type="time" value={timeValue} onChange={handleTimeChange} />
      </div>

    </div>
  );
}