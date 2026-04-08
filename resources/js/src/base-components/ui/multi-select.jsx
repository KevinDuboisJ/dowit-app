import * as React from 'react'
import { cva } from 'class-variance-authority'
import { CheckIcon, XCircle, ChevronDown, XIcon, SearchX } from 'lucide-react'

import { cn } from '@/utils'
import {
  Separator,
  Badge,
  Popover,
  PopoverTrigger,
  PopoverContent,
  ScrollArea,
  Input,
  Loader
} from '@/base-components'

// ---------------------------------------------------------------------------
// Variants
// ---------------------------------------------------------------------------

const multiSelectVariants = cva('transition-colors', {
  variants: {
    variant: {
      default:
        'border-transparent bg-primary text-primary-foreground shadow hover:bg-primary/80',
      secondary:
        'border-foreground/10 bg-secondary text-secondary-foreground hover:bg-secondary/80',
      destructive:
        'border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/80',
      inverted: 'inverted'
    }
  },
  defaultVariants: { variant: 'default' }
})

// ---------------------------------------------------------------------------
// useDebounce
// ---------------------------------------------------------------------------

/**
 * Returns a debounced version of `fn` that delays invocation by `delay` ms.
 * The timer is cleared and reset on every call.
 */
function useDebounce(fn, delay) {
  const timerRef = React.useRef(null)
  const fnRef = React.useRef(fn)
  fnRef.current = fn // always call the latest version

  return React.useCallback(
    (...args) => {
      if (timerRef.current) clearTimeout(timerRef.current)
      timerRef.current = setTimeout(() => fnRef.current(...args), delay)
    },
    [delay]
  )
}

// ---------------------------------------------------------------------------
// MultiSelect
// ---------------------------------------------------------------------------

/**
 * MultiSelect — props API
 * ──────────────────────
 * staticOptions     Option[]
 *   Pre-loaded list of options shown/filtered client-side.
 *
 * fetchOptions      (query: string) => Promise<Option[]>
 *   Async loader called on search input (and once on open with empty string).
 *   Both staticOptions and fetchOptions may be used simultaneously.
 *
 * value             Option[]        Controlled selected values.
 * defaultValue      Option[]        Uncontrolled initial value.
 * onChange          (Option[]) => void
 *
 * placeholder       string          Shown when nothing is selected.
 * searchPlaceholder string          Shown when at least one item is selected.
 * emptyText         string          Shown when there are no matching options.
 *
 * maxSelection      number          Cap on how many items can be selected.
 *                                   Omit (or pass Infinity) for unlimited.
 * maxVisibleBadges  number          Badges rendered before "+N more" overflow.
 * debounceMs        number          Debounce delay for fetchOptions (default 200).
 *
 * variant           BadgeVariant    Visual style of selected-value badges.
 * disabled          boolean
 * modalPopover      boolean         Pass true when rendered inside a Dialog/Modal.
 * className         string
 */
export const MultiSelect = React.forwardRef(
  (
    {
      // Data
      staticOptions = [],
      fetchOptions,
      // Selection (controlled / uncontrolled)
      value: controlledValue,
      defaultValue = [],
      onChange,
      // Display
      placeholder = 'Opties selecteren',
      searchPlaceholder = 'Zoeken...',
      emptyText = 'Geen resultaten gevonden.',
      maxSelection = Infinity,
      maxVisibleBadges = 8,
      debounceMs = 200,
      // Style
      variant,
      disabled = false,
      modalPopover = false,
      className,
      ...props
    },
    ref
  ) => {
    // ── Controlled / uncontrolled ────────────────────────────────────────────
    const isControlled = controlledValue !== undefined
    const [internalSelected, setInternalSelected] = React.useState(
      () => defaultValue
    )
    const selectedValues = isControlled ? controlledValue : internalSelected

    const setSelected = React.useCallback(
      next => {
        if (!isControlled) setInternalSelected(next)
        onChange?.(next)
      },
      [isControlled, onChange]
    )

    // ── UI state ─────────────────────────────────────────────────────────────
    const [isOpen, setIsOpen] = React.useState(false)
    const [query, setQuery] = React.useState('')

    // ── Async state ──────────────────────────────────────────────────────────
    const [asyncOptions, setAsyncOptions] = React.useState([])
    const [isLoading, setIsLoading] = React.useState(false)
    // Sequence counter so stale responses from earlier requests are discarded
    const fetchSeqRef = React.useRef(0)

    // ── DOM refs ─────────────────────────────────────────────────────────────
    const inputRef = React.useRef(null)
    const containerRef = React.useRef(null)
    const [contentWidth, setContentWidth] = React.useState(undefined)

    // ── Measure trigger width for popover ────────────────────────────────────
    React.useEffect(() => {
      if (!containerRef.current) return
      const update = () => setContentWidth(containerRef.current?.offsetWidth)
      update()
      const ro = new ResizeObserver(update)
      ro.observe(containerRef.current)
      return () => ro.disconnect()
    }, [])

    // ── Fetch helpers ────────────────────────────────────────────────────────
    const runFetch = React.useCallback(
      async searchQuery => {
        if (!fetchOptions) return
        const seq = ++fetchSeqRef.current
        setIsLoading(true)
        try {
          const results = await fetchOptions(searchQuery)
          if (seq !== fetchSeqRef.current) return // stale — discard
          setAsyncOptions(Array.isArray(results) ? results : [])
          setIsOpen(true)
        } catch (err) {
          if (seq !== fetchSeqRef.current) return
          console.error('[MultiSelect] fetchOptions threw:', err)
          setAsyncOptions([])
        } finally {
          if (seq === fetchSeqRef.current) setIsLoading(false)
        }
      },
      [fetchOptions]
    )

    const debouncedFetch = useDebounce(runFetch, debounceMs)

    // Fire debounced fetch on every query change
    React.useEffect(() => {
      if (!fetchOptions || !isOpen) return

      debouncedFetch(query)
    }, [query])

    // ── Derived option list ──────────────────────────────────────────────────
    /**
     * Merge strategy:
     *  - Static options are filtered client-side against `query`.
     *  - Async options are returned pre-filtered by the server.
     *  - When both are used, async results take priority; static entries whose
     *    values are not already represented in async results are appended.
     */
    const visibleOptions = React.useMemo(() => {
      const normalised = query.trim().toLowerCase()

      const filteredStatic = normalised
        ? staticOptions.filter(o => o.label.toLowerCase().includes(normalised))
        : staticOptions

      if (!fetchOptions) return filteredStatic

      const asyncValueSet = new Set(asyncOptions.map(o => o.value))
      const uniqueStatic = filteredStatic.filter(
        o => !asyncValueSet.has(o.value)
      )
      return [...asyncOptions, ...uniqueStatic]
    }, [staticOptions, asyncOptions, query, fetchOptions])

    const selectedSet = React.useMemo(
      () => new Set(selectedValues.map(v => v.value)),
      [selectedValues]
    )

    const canSelectMore = selectedValues.length < maxSelection

    // ── Open / close ─────────────────────────────────────────────────────────

    const handleOpenChange = React.useCallback(
      open => {
        if (disabled || (isOpen && open && query === '')) return

        if (open && fetchOptions) {
          runFetch(query)
          return
        }

        if (open && !fetchOptions) {
          setIsOpen(true)
          return
        }

        setIsOpen(false)
        fetchSeqRef.current++
      },
      [disabled, fetchOptions, isOpen, runFetch, query]
    )

    // ── Selection ────────────────────────────────────────────────────────────
    const toggleOption = React.useCallback(
      option => {
        if (selectedSet.has(option.value)) {
          setSelected(selectedValues.filter(v => v.value !== option.value))
        } else {
          if (!canSelectMore) return
          setSelected([...selectedValues, option])
        }
      },
      [selectedSet, selectedValues, canSelectMore, setSelected]
    )

    const clearAll = React.useCallback(() => setSelected([]), [setSelected])

    // ── Input handlers ───────────────────────────────────────────────────────
    const handleInputChange = React.useCallback(
      e => {
        setQuery(e.target.value)
      },
      [isOpen]
    )

    const handleInputKeyDown = React.useCallback(
      e => {
        if (e.key === 'Escape') {
          handleOpenChange(false)
        }
        // Backspace on empty query removes the last selected badge
        if (
          e.key === 'Backspace' &&
          query === '' &&
          selectedValues.length > 0
        ) {
          setSelected(selectedValues.slice(0, -1))
        }
      },
      [handleOpenChange, query, selectedValues, setSelected]
    )

    // ── Display helpers ───────────────────────────────────────────────────────
    const extraCount =
      selectedValues.length > maxVisibleBadges
        ? selectedValues.length - maxVisibleBadges
        : 0

    const showEmpty =
      !isLoading &&
      visibleOptions.length === 0 &&
      (query.length > 0 || !fetchOptions)

    // ── Render ────────────────────────────────────────────────────────────────
    return (
      <Popover
        open={isOpen}
        onOpenChange={handleOpenChange}
        modal={modalPopover}
      >
        <div ref={ref} className={cn('w-full', className)} {...props}>
          {/* ── Trigger ─────────────────────────────────────────────────── */}
          <PopoverTrigger asChild onClick={e => e.preventDefault()}>
            <div
              ref={containerRef}
              role="combobox"
              aria-expanded={isOpen}
              aria-haspopup="listbox"
              aria-disabled={disabled}
              className={cn(
                'flex w-full flex-col rounded-md border bg-white',
                disabled && 'cursor-not-allowed opacity-60'
              )}
            >
              {/* Selected-value badges */}
              {selectedValues.length > 0 && (
                <div
                  className="flex flex-wrap items-center gap-1 p-2 pb-0"
                  aria-label="Selected options"
                >
                  {selectedValues.slice(0, maxVisibleBadges).map(item => {
                    const Icon = item.icon
                    return (
                      <Badge
                        key={item.value}
                        className={cn('m-0', multiSelectVariants({ variant }))}
                      >
                        {Icon && <Icon className="mr-2 h-4 w-4" />}
                        <span>{item.label}</span>
                        <button
                          type="button"
                          aria-label={`Remove ${item.label}`}
                          tabIndex={-1}
                          className="ml-2 inline-flex items-center"
                          onClick={e => {
                            e.stopPropagation()
                            toggleOption(item)
                          }}
                        >
                          <XCircle className="h-4 w-4" />
                        </button>
                      </Badge>
                    )
                  })}

                  {extraCount > 0 && (
                    <button
                      type="button"
                      aria-label={`${extraCount} extra geselecteerde opties, klik om te bekijken`}
                      tabIndex={-1}
                      className="inline-flex"
                      onClick={e => {
                        e.stopPropagation()
                        handleOpenChange(true)
                      }}
                    >
                      <Badge
                        className={cn(
                          'm-0 border bg-transparent text-foreground',
                          multiSelectVariants({ variant })
                        )}
                      >
                        +{extraCount} meer
                      </Badge>
                    </button>
                  )}
                </div>
              )}

              {/* Input row */}
              <div className="relative flex items-center">
                <Input
                  ref={inputRef}
                  value={query}
                  onChange={handleInputChange}
                  onKeyDown={handleInputKeyDown}
                  onFocus={() => {
                    if (!disabled) handleOpenChange(true)
                  }}
                  placeholder={
                    selectedValues.length ? searchPlaceholder : placeholder
                  }
                  disabled={disabled}
                  aria-autocomplete="list"
                  aria-controls="multiselect-listbox"
                  className="h-8 flex-1 !border-none !shadow-none !focus-visible:ring-0 !outline-none !outline-0 !ring-0"
                />

                {/* inputMode="text"
                  name="search"
                  autoComplete="off"
                  autoCorrect="off"
                  autoCapitalize="off"
                  spellCheck={false} */}

                {/* Async loading spinner (inline) */}
                {isLoading && <Loader className='absolute right-12' size={32} />}

                {/* Clear-all button */}
                {selectedValues.length > 0 && !disabled && (
                  <button
                    type="button"
                    aria-label="Clear all selected values"
                    tabIndex={-1}
                    className="mx-1 inline-flex items-center text-muted-foreground hover:text-foreground"
                    onClick={e => {
                      e.stopPropagation()
                      e.preventDefault()
                      clearAll()
                    }}
                  >
                    <XIcon className="h-4 w-4" />
                  </button>
                )}

                <Separator orientation="vertical" className="mx-1 h-5" />

                {/* Chevron toggle */}
                <button
                  type="button"
                  aria-label={isOpen ? 'Close options' : 'Open options'}
                  tabIndex={-1}
                  className="mx-2 mr-3 inline-flex items-center text-slate-400 hover:text-slate-500"
                  // Prevent blur on the search input when clicking chevron
                  onMouseDown={e => e.preventDefault()}
                  onClick={e => {
                    e.stopPropagation()
                    handleOpenChange(!isOpen)
                  }}
                >
                  <ChevronDown
                    className={cn(
                      'h-4 w-4 transition-transform duration-200',
                      isOpen && 'rotate-180'
                    )}
                  />
                </button>
              </div>
            </div>
          </PopoverTrigger>
        </div>

        {/* ── Dropdown ──────────────────────────────────────────────────── */}
        <PopoverContent
          style={{ width: contentWidth }}
          onOpenAutoFocus={e => e.preventDefault()}
          className={cn('p-1', className)}
        >
          <ScrollArea className="min-h-16 [&>[data-radix-scroll-area-viewport]]:max-h-48">
            {/* ── Empty state ──────────────────────────────────────────────── */}
            {showEmpty && (
              // Semantic <p> instead of a generic div; aria-live so screen-readers
              // announce it when the list empties after filtering.
              <p
                role="status"
                aria-live="polite"
                className="flex flex-col max-h-16 items-center gap-1.5 py-2 text-center
                 text-xs font-medium tracking-wide text-muted-foreground/70
                 select-none"
              >
                {/* Decorative dash-ring gives the empty state visual weight */}
                <span
                  aria-hidden="true"
                  className="flex h-8 w-8 items-center justify-center rounded-full
                   border border-dashed border-muted-foreground/30
                   text-muted-foreground/40"
                >
                  <SearchX className="h-3.5 w-3.5" />
                </span>
                {emptyText}
              </p>
            )}

            {/* ── Option list ──────────────────────────────────────────────── */}
            {visibleOptions.length > 0 && (
              // listbox > option is the correct ARIA pattern for a custom select.
              // Previously `role="group"` didn't pair with `role="option"` children.
              <div
                role="listbox"
                aria-multiselectable="true"
                className="p-1 space-y-0.5"
              >
                {visibleOptions.map((option, i) => {
                  const isSelected = selectedSet.has(option.value)
                  const isDisabled = !isSelected && !canSelectMore
                  const Icon = option.icon

                  return (
                    <div
                      key={option.value}
                      role="option"
                      aria-selected={isSelected}
                      aria-disabled={isDisabled}
                      onClick={() => {
                        if (!isDisabled) toggleOption(option)
                      }}
                      onKeyDown={e => {
                        if (
                          (e.key === 'Enter' || e.key === ' ') &&
                          !isDisabled
                        ) {
                          e.preventDefault()
                          toggleOption(option)
                        }
                      }}
                      tabIndex={isDisabled ? -1 : 0}
                      // Stagger-in on mount via inline delay — pure CSS, zero deps
                      style={{ animationDelay: `${i * 30}ms` }}
                      className={cn(
                        // Base layout
                        'group relative flex cursor-default select-none items-center',
                        'gap-2.5 rounded-md px-2 py-1.5 text-sm outline-none',
                        // Fade-slide in
                        'animate-in fade-in slide-in-from-top-1 duration-150 fill-mode-both',
                        // Interactive states — prefer focus-visible over bare :focus
                        'transition-colors duration-100',
                        'hover:bg-accent hover:text-accent-foreground',
                        'focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-1',
                        // Disabled
                        isDisabled
                          ? 'cursor-not-allowed opacity-40 pointer-events-none'
                          : 'cursor-pointer'
                      )}
                    >
                      {/* ── Checkbox indicator ──────────────────────────────── */}
                      {/*  Scale transition gives tactile "click" feedback       */}
                      <span
                        aria-hidden="true"
                        className={cn(
                          'flex h-4 w-4 shrink-0 items-center justify-center',
                          'rounded-sm border transition-all duration-150',
                          'group-hover:scale-105',
                          isSelected
                            ? 'border-primary bg-primary text-primary-foreground scale-105'
                            : 'border-muted-foreground/50 bg-muted opacity-80'
                        )}
                      >
                        {/* CheckIcon animates in instead of just appearing */}
                        <CheckIcon
                          className={cn(
                            'h-3 w-3 transition-all duration-150',
                            isSelected
                              ? 'opacity-100 scale-100'
                              : 'opacity-20 scale-50'
                          )}
                        />
                      </span>

                      {/* ── Optional leading icon ───────────────────────────── */}
                      {Icon && (
                        <Icon
                          aria-hidden="true"
                          className="h-4 w-4 shrink-0 text-muted-foreground
                           transition-colors group-hover:text-foreground"
                        />
                      )}

                      {/* ── Label ───────────────────────────────────────────── */}
                      <span className="truncate leading-none">
                        {option.label}
                      </span>

                      {/* ── "Max reached" badge — replaces the blanket dim ──── */}
                      {/*  Shown only on unselected items when the cap is hit    */}
                      {isDisabled && (
                        <span
                          aria-hidden="true"
                          className="ml-auto text-[10px] font-medium tracking-wide
                           text-muted-foreground/60 uppercase"
                        >
                          max
                        </span>
                      )}
                    </div>
                  )
                })}
              </div>
            )}
          </ScrollArea>
        </PopoverContent>
      </Popover>
    )
  }
)

MultiSelect.displayName = 'MultiSelect'
