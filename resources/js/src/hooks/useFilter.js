import { useCallback, useState, useRef, useEffect, useMemo } from 'react';
import { usePage } from '@inertiajs/react';

const defaultOptions = {
  filterFromUrlParams: false
}

export const useFilter = ({ defaultValues, options = defaultOptions }) => {
  
  const { url } = usePage();
  const { filterFromUrlParams } = options;
  const filtersRef = useRef(filterFromUrlParams ? getFiltersFromUrlParams(url, defaultValues) : defaultValues);
  const setFilters = useCallback((key, value) => {
    filtersRef.current[key] = value;
  }, []);

  const getFilters = useCallback((key = null) => {
    if (key) {
      return filtersRef.current?.[key] ?? null;
    }
    return filtersRef.current;
  }, []);

  const getValue = useCallback((key) => {
    return getFilters(key)?.value ?? null;
  }, []);

  const resetFilters = useCallback(() => {
    filtersRef.current = { ...defaultValues };
  }, [defaultValues]);


  return {
    filters: {
      get: getFilters,
      value: getValue,
      set: setFilters,
      reset: resetFilters
    }, filtersRef,
  };
}

/**
 * Parses the filters from URL parameters
 */

export const getFiltersFromUrlParams = (url, filters) => {
  const params = new URLSearchParams(url);
 
  let filterMapping = {}; // Temporary storage for filters

  params.forEach((value, key) => {
    const match = key.match(/^\/?\??filters\[(\d+)]\[(\w+)]$/);
    if (match) {
      const [, index, property] = match;

      // Initialize object for each filter index
      if (!filterMapping[index]) {
        filterMapping[index] = {};
      }

      // Assign values correctly
      filterMapping[index][property] = value;
    }
  });
  
  // Convert structured filterMapping to a more usable filters object
  Object.values(filterMapping).forEach((filter) => {
    if (filter.field) {
      filters[filter.field] = {
        field: filter.field,
        type: filter.type || '=',
        value: filter.value || null,
      };
      
    }
  });
 
  return filters;
};
