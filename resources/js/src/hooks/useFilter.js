// The reason i use useCallback and useMemo here is to ensure that the filter functions are stable references,
// which can be important if you pass them down to child components that might optimize with React.memo or useEffect dependencies.
// It also helps prevent unnecessary re-renders in those child components when the filter functions don't actually change.

import { useCallback, useRef, useMemo } from 'react'
import { usePage, router } from '@inertiajs/react'
import { useLoader } from '@/hooks'

const defaultOptions = {
  filterFromUrlParams: false
}

export const useFilter = ({ defaultValues, options = defaultOptions }) => {
  const { url } = usePage()
  const { loading, setLoading } = useLoader()
  const { filterFromUrlParams } = options
  const filtersRef = useRef()

  if (!filtersRef.current) {
    filtersRef.current = filterFromUrlParams
      ? getFiltersFromUrlParams(url, defaultValues)
      : defaultValues
  }

  const setFilters = useCallback((key, value) => {
    filtersRef.current[key] = value
  }, [])

  const getFilters = useCallback((key = null) => {
    if (key) {
      return filtersRef.current?.[key] ?? null
    }
    return filtersRef.current
  }, [])

  const resetFilters = useCallback(() => {
    filtersRef.current = JSON.parse(JSON.stringify(defaultValues))
  }, [defaultValues])

  const isValidDate = d => d instanceof Date && !Number.isNaN(d.getTime())

  const isActiveValue = v => {
    if (typeof v === 'boolean') return v === true
    if (v == null) return false

    // Date support (your missing case)
    if (isValidDate(v)) return true

    if (typeof v === 'string') return v.trim().length > 0
    if (typeof v === 'number') return true

    if (typeof v === 'object') {
      // Support objects like { from, to }
      return Object.values(v).some(isActiveValue)
    }

    return false
  }

  const getActiveFilters = useCallback(() => {
    return Object.values(filtersRef.current).filter(f =>
      isActiveValue(f?.value)
    )
  }, [])

  const hasActiveFilters = useCallback(() => {
    return Object.values(filtersRef.current).some(f => isActiveValue(f?.value))
  }, [])

  const getPerPageFromUrl = () => {
    const params = new URLSearchParams(window.location.search)
    const perPage = params.get('perPage')
    return perPage ? Number(perPage) : undefined
  }

  const getCurrentPath = useCallback(() => {
    return url.split('?')[0] || window.location.pathname || '/'
  }, [url])

  const apply = ({ page, perPage } = {}) => {
    const resolvedPerPage = perPage ?? getPerPageFromUrl()
    const currentPath = getCurrentPath()

    setLoading(true)
    router.get(
      currentPath,
      { filters: getActiveFilters(), page, perPage: resolvedPerPage },
      {
        only: ['tasks'],
        queryStringArrayFormat: 'indices',
        preserveState: true,
        replace: true,
        onFinish: () => setLoading(false),
        onError: err => {
          console.log(err)
          setLoading(false)
        }
      }
    )
  }

  const filters = useMemo(
    () => ({
      get: getFilters,
      set: setFilters,
      reset: resetFilters,
      active: getActiveFilters,
      hasActive: hasActiveFilters,
      apply: apply,
      loading: loading
    }),
    [
      getFilters,
      setFilters,
      resetFilters,
      getActiveFilters,
      hasActiveFilters,
      apply,
      loading
    ]
  )

  return { filters }
}

/**
 * Parses the filters from URL parameters
 */

export const getFiltersFromUrlParams = (url, filters = {}) => {
  const queryString = url.includes('?') ? url.split('?')[1] : url
  const params = new URLSearchParams(queryString)

  const filterMapping = {}

  params.forEach((value, key) => {
    const match = key.match(/^filters\[(\d+)\]\[(\w+)\](?:\[(\w+)\])?$/)
    if (!match) return

    const [, index, prop, subProp] = match
    filterMapping[index] ??= {}

    if (subProp) {
      filterMapping[index][prop] ??= {}
      filterMapping[index][prop][subProp] = parseValue(value)
    } else {
      filterMapping[index][prop] = parseValue(value)
    }
  })

  Object.values(filterMapping).forEach(f => {
    if (!f.field) return

    filters[f.field] = {
      field: f.field,
      type: f.type || '=',
      value: f.value ?? null
    }
  })

  return filters
}

const parseValue = value => {
  // Detect ISO date string
  if (
    typeof value === 'string' &&
    /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/.test(value)
  ) {
    const date = new Date(value)
    if (!isNaN(date.getTime())) return date
  }

  return value
}
