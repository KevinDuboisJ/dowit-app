import { useEffect, useMemo, useState, useRef } from 'react'
import { cn } from '@/utils'
import { __ } from '@/stores'
import { usePage } from '@inertiajs/react'
import { format } from 'date-fns'
import { Calendar } from '@/components'
import {
  Heroicon,
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from '@/base-components'

/* ─────────────────────────────────────────
   Tiny helpers
───────────────────────────────────────── */
const fmt = dateStr => {
  if (!dateStr) return null

  const [y, m, d] = dateStr.split('-').map(Number)
  const date = new Date(y, m - 1, d) // local midnight

  return date.toLocaleDateString('nl-NL', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  })
}

/* ─────────────────────────────────────────
   FilterSection — labelled section with Reset
───────────────────────────────────────── */
const FilterSection = ({ label, onReset, children }) => (
  <div className="py-4 border-b border-slate-100 last:border-b-0">
    <div className="flex items-center justify-between mb-3">
      <span className="text-sm font-semibold text-slate-700">{label}</span>
      <button
        type="button"
        onClick={onReset}
        className="text-xs text-indigo-600 hover:text-indigo-800 font-semibold transition-colors px-2 py-1 rounded-md hover:bg-indigo-100"
      >
        Reset
      </button>
    </div>
    {children}
  </div>
)

/* ─────────────────────────────────────────
   FilterDrawer — Smooth slide from LEFT
   (always mounted; animates transform+opacity)
───────────────────────────────────────── */
const FilterDrawer = ({ open, onClose, onApply, onReset, children }) => {
  const drawerRef = useRef(null)
  const backdropRef = useRef(null)
  const [mounted, setMounted] = useState(false)

  // In FilterDrawer, add this effect:
  useEffect(() => {
    const tasksView = document.getElementById('tasksView')
    const paginationBar = document.getElementById('paginationBar')
    if (!tasksView || !paginationBar) return
    tasksView.style.transition = 'filter 300ms cubic-bezier(0.4,0,0.2,1)'
    tasksView.style.filter = open ? 'blur(1px)' : 'blur(0px)'
    paginationBar.style.transition = 'filter 300ms cubic-bezier(0.4,0,0.2,1)'
    paginationBar.style.filter = open ? 'blur(1px)' : 'blur(0px)'
    return () => {
      tasksView.style.filter = 'blur(0px)'
      paginationBar.style.filter = 'blur(0px)'
    }
  }, [open])

  // ESC close
  useEffect(() => {
    const handler = e => e.key === 'Escape' && onClose()
    document.addEventListener('keydown', handler)
    return () => document.removeEventListener('keydown', handler)
  }, [onClose])

  // Lock scroll
  useEffect(() => {
    document.body.style.overflow = open ? 'hidden' : ''
    return () => {
      document.body.style.overflow = ''
    }
  }, [open])

  // Mount/unmount + WAAPI animation
  useEffect(() => {
    if (open) {
      setMounted(true)
    } else if (drawerRef.current && backdropRef.current) {
      // Animate OUT, then unmount
      const easing = 'cubic-bezier(0.4, 0, 0.2, 1)'
      const duration = 280

      drawerRef.current.animate(
        [
          { transform: 'translateX(0)', opacity: 1 },
          { transform: 'translateX(-24px)', opacity: 0 }
        ],
        { duration, easing, fill: 'forwards' }
      )

      backdropRef.current.animate([{ opacity: 1 }, { opacity: 0 }], {
        duration,
        easing,
        fill: 'forwards'
      })

      const t = setTimeout(() => setMounted(false), duration)
      return () => clearTimeout(t)
    }
  }, [open])

  // Animate IN once mounted
  useEffect(() => {
    if (!mounted || !drawerRef.current || !backdropRef.current) return

    const easing = 'cubic-bezier(0.4, 0, 0.2, 1)'
    const duration = 300

    drawerRef.current.animate(
      [
        { transform: 'translateX(-24px)', opacity: 0 },
        { transform: 'translateX(0)', opacity: 1 }
      ],
      { duration, easing, fill: 'forwards' }
    )

    backdropRef.current.animate([{ opacity: 0 }, { opacity: 1 }], {
      duration,
      easing,
      fill: 'forwards'
    })
  }, [mounted])

  if (!mounted) return null

  return (
    <div className="fixed inset-0 z-[100]">
      <div
        ref={backdropRef}
        onClick={onClose}
        style={{ opacity: 0 }}
        className="absolute inset-0 bg-black/40"
      />

      {/* Drawer */}
      <div
        ref={drawerRef}
        style={{
          opacity: 0,
          transform: 'translateX(-24px)',
          filter: 'blur(0px)'
        }}
        className="absolute top-0 left-0 h-full w-[420px] bg-white shadow-2xl flex flex-col"
        onClick={e => e.stopPropagation()}
        onKeyDown={e => {
          if (e.key === 'Enter' && !e.shiftKey) {
            const tag = e.target.tagName
            const isTextarea = tag === 'TEXTAREA'
            const isButton = tag === 'BUTTON'

            if (!isTextarea && !isButton) {
              e.preventDefault()
              onApply()
            }
          }
        }}
      >
        {/* Header */}
        <div className="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
          <div>
            <div className="text-base font-bold text-slate-900">Filters</div>
            <div className="text-xs text-slate-500">Verfijn je takenlijst</div>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="text-slate-400 hover:text-slate-700 transition-colors p-1 rounded-md hover:bg-slate-50"
            aria-label="Sluiten"
          >
            <Heroicon icon="XMark" className="w-5 h-5" />
          </button>
        </div>

        {/* Body */}
        <div className="flex-1 overflow-y-auto px-5">{children}</div>

        {/* Footer */}
        <div className="px-5 py-4 border-t border-slate-100 flex items-center gap-3">
          <button
            type="button"
            onClick={onReset}
            className="text-sm text-slate-700 font-semibold px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition-colors"
          >
            Alles resetten
          </button>
          <button
            type="button"
            onClick={onApply}
            className="flex-1 bg-indigo-500 text-white border-indigo-500 hover:bg-indigo-700 hover:border-indigo-600 shadow-lg shadow-indigo-500/30 text-sm font-bold py-2 rounded-xl shadow-sm transition-colors"
          >
            Filters toepassen
          </button>
        </div>
      </div>
    </div>
  )
}

/* ─────────────────────────────────────────
   Main FilterBar
───────────────────────────────────────── */
export const FilterBar = ({ filters = {} }) => {
  const { url } = usePage()
  const { user, statuses } = usePage().props
  const filtersRef = filters.get()
  const [drawerOpen, setDrawerOpen] = useState(false)

  // Pathname change resets all filters
  const pathname = useMemo(() => {
    return url.split('?')[0]
  }, [url])

  useEffect(() => {
    handleResetAll()
  }, [pathname])

  // Local draft state (only committed on Apply)
  const [assignedTo, setAssignedTo] = useState(
    filtersRef.assignedTo?.value || ''
  )
  const [statusName, setStatusName] = useState(
    filtersRef.status_id?.value || ''
  )
  const [teamId, setTeamId] = useState(filtersRef.team_id?.value || '')
  const [dateRange, setDateRange] = useState(
    filtersRef.dateRange?.value ?? null
  )
  const [keyword, setKeyword] = useState(filtersRef.keyword?.value || '')
  const [onlyAssignedToMe, setOnlyAssignedToMe] = useState(
    filtersRef.onlyAssignedToMe?.value ?? false
  )

  const sync = (key, value) => {
    if (filtersRef[key]) filtersRef[key].value = value
  }

  const selectedStatus = useMemo(
    () => statuses?.find(s => s.name === statusName),
    [statuses, statusName]
  )

  const selectedTeam = useMemo(
    () => user?.teams?.find(t => String(t.id) === String(teamId)),
    [user?.teams, teamId]
  )

  const activeCount = useMemo(() => {
    const hasDate = !!(dateRange?.from || dateRange?.to)
    return [
      assignedTo,
      statusName,
      teamId,
      hasDate,
      keyword,
      onlyAssignedToMe
    ].filter(Boolean).length
  }, [assignedTo, statusName, teamId, dateRange, keyword, onlyAssignedToMe])

  const handleApply = () => {
    sync('assignedTo', assignedTo)
    sync('status_id', statusName)
    sync('team_id', teamId)
    sync('dateRange', dateRange)
    sync('keyword', keyword)
    sync('onlyAssignedToMe', onlyAssignedToMe)

    filters.apply()
    setDrawerOpen(false)
  }

  const handleRemoveApply = key => {
    sync(key, '')
    filters.apply()
  }

  const handleResetAll = () => {
    setAssignedTo('')
    setStatusName('')
    setTeamId('')
    setDateRange(null)
    setKeyword('')
    setOnlyAssignedToMe(false)
    filters.reset()
  }

  const chipBase = cn(
    'inline-flex items-center gap-1.5 h-7 pl-3 pr-2 rounded-lg text-xs font-medium',
    'bg-indigo-50 text-indigo-700 border border-indigo-200/80',
    'transition-colors duration-150'
  )

  return (
    <>
      <div className="flex items-center gap-2 mb-2">
        {/* Trigger button */}
        <button
          type="button"
          onClick={() => setDrawerOpen(true)}
          className={cn(
            'text-xs font-medium bg-gradient-to-br from-cyan-500 to-purple-600 text-white hover:shadow-md shadow shadow-indigo-500/30 group inline-flex items-center gap-2 h-8 px-3.5 rounded-xl border transition-all duration-200'
          )}
        >
          <div
            className={cn(
              'w-4 h-4 text-white transition-transform duration-200 group-hover:rotate-12',
              activeCount > 0 ? 'text-white' : 'text-white'
            )}
          >
            <svg
              viewBox="0 0 16 16"
              fill="none"
              stroke="currentColor"
              strokeWidth="1.8"
              strokeLinecap="round"
              strokeLinejoin="round"
            >
              <path d="M2 4h12M4.5 8h7M7 12h2" />
            </svg>
          </div>
          <span>Filters</span>
          {activeCount > 0 && (
            <span className="ml-0.5 bg-white/20 backdrop-blur-sm text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center leading-none ring-1 ring-white/30">
              {activeCount}
            </span>
          )}
        </button>
        {/* <button
          type="button"
          onClick={() => setDrawerOpen(true)}
          className={cn(
            'group inline-flex h-10 items-center gap-2 rounded-t-lg border-b-2 px-3 text-sm font-medium transition-all duration-200',
            activeCount > 0
              ? 'border-emerald-500 text-slate-900'
              : 'border-transparent text-slate-500 hover:text-slate-800'
          )}
        >
          <svg
            viewBox="0 0 16 16"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.8"
            strokeLinecap="round"
            strokeLinejoin="round"
            className="h-4 w-4 transition-transform duration-200 group-hover:scale-105"
          >
            <path d="M2 4h12M4.5 8h7M7 12h2" />
          </svg>

          <span>Filters</span>

          {activeCount > 0 && (
            <span className="flex h-5 min-w-5 items-center justify-center rounded-full bg-slate-100 px-1.5 text-[11px] font-semibold text-slate-700">
              {activeCount}
            </span>
          )}
        </button> */}

        {/* Active filter chips */}
        {activeCount > 0 && (
          <div className="flex flex-wrap gap-1.5 pt-[2px]">
            {assignedTo && (
              <span className={chipBase}>
                {assignedTo}
                <button
                  onClick={() => {
                    setAssignedTo('')
                    handleRemoveApply('assignedTo')
                  }}
                  className="hover:text-slate-900 ml-0.5"
                >
                  <Heroicon icon="XMark" className="w-3 h-3" />
                </button>
              </span>
            )}

            {selectedStatus && (
              <span className={chipBase}>
                {__(selectedStatus.name)}
                <button
                  onClick={() => {
                    setStatusName('')
                    handleRemoveApply('status_id')
                  }}
                  className="hover:text-slate-900 ml-0.5"
                >
                  <Heroicon icon="XMark" className="w-3 h-3" />
                </button>
              </span>
            )}

            {selectedTeam && (
              <span className={chipBase}>
                {selectedTeam.name}
                <button
                  onClick={() => {
                    setTeamId('')
                    handleRemoveApply('team_id')
                  }}
                  className="hover:text-slate-900 ml-0.5"
                >
                  <Heroicon icon="XMark" className="w-3 h-3" />
                </button>
              </span>
            )}

            {dateRange?.from && (
              <span className={chipBase}>
                {[fmt(dateRange?.from), fmt(dateRange?.to)]
                  .filter(Boolean)
                  .join(' → ')}
                <button
                  onClick={() => {
                    setDateRange(null)
                    handleRemoveApply('dateRange')
                  }}
                  className="hover:text-slate-900 ml-0.5"
                >
                  <Heroicon icon="XMark" className="w-3 h-3" />
                </button>
              </span>
            )}

            {keyword && (
              <span className={chipBase}>
                “{keyword}”
                <button
                  onClick={() => {
                    setKeyword('')
                    handleRemoveApply('keyword')
                  }}
                  className="hover:text-slate-900 ml-0.5"
                >
                  <Heroicon icon="XMark" className="w-3 h-3" />
                </button>
              </span>
            )}

            {onlyAssignedToMe && (
              <span className={chipBase}>
                Alleen aan mij toegewezen
                <button
                  onClick={() => {
                    setOnlyAssignedToMe(false)
                    handleRemoveApply('onlyAssignedToMe')
                  }}
                  className="hover:text-slate-900 ml-0.5"
                >
                  <Heroicon icon="XMark" className="w-3 h-3" />
                </button>
              </span>
            )}
          </div>
        )}
      </div>

      {/* Drawer */}
      <FilterDrawer
        open={drawerOpen}
        onClose={() => setDrawerOpen(false)}
        onApply={handleApply}
        onReset={handleResetAll}
      >
        {/* ── Datumbereik ── */}
        <FilterSection
          label="Datumbereik"
          onReset={() => {
            setDateRange(null)
          }}
        >
          <div className="flex gap-3">
            <Calendar
              value={dateRange}
              onChange={v => {
                setDateRange({
                  from: v?.from ? format(v.from, 'yyyy-MM-dd') : null,
                  to: v?.to ? format(v.to, 'yyyy-MM-dd') : null
                })
              }}
            />
          </div>
        </FilterSection>

        {/* ── Zoek op taak ── */}
        <FilterSection
          label="Zoeken"
          onReset={() => {
            setKeyword('')
          }}
        >
          <div className="relative">
            <Heroicon
              icon="MagnifyingGlass"
              className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
            />
            <input
              type="text"
              value={keyword}
              onChange={e => setKeyword(e.target.value)}
              placeholder="Zoek op taaknaam of beschrijving…"
              className="w-full h-10 pl-9 pr-3 text-sm rounded-xl border border-slate-200 bg-white text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400"
            />
          </div>
        </FilterSection>

        {/* ── Toegewezen ── */}
        <FilterSection
          label="Toegewezen"
          onReset={() => {
            setAssignedTo('')
            setOnlyAssignedToMe(false)
          }}
        >
          <label className="flex items-center justify-between gap-3 select-none">
            <span className="text-sm text-slate-700 font-medium">
              Alleen aan mij toegewezen
            </span>

            <button
              type="button"
              onClick={() => setOnlyAssignedToMe(v => !v)}
              className={cn(
                'relative w-11 h-6 rounded-full transition-colors border',
                onlyAssignedToMe
                  ? 'bg-indigo-500 border-indigo-500'
                  : 'bg-slate-200 border-slate-200'
              )}
              aria-pressed={onlyAssignedToMe}
            >
              <span
                className={cn(
                  'absolute top-1/2 -left-0.5 -translate-y-1/2 w-5 h-5 bg-white rounded-full shadow transition-transform',
                  onlyAssignedToMe && 'translate-x-6'
                )}
              />
            </button>
          </label>

          {!onlyAssignedToMe && (
            <div className="relative mt-3">
              <Heroicon
                icon="MagnifyingGlass"
                className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
              />
              <input
                type="text"
                value={assignedTo}
                onChange={e => setAssignedTo(e.target.value)}
                placeholder="Zoeken op naam…"
                className="w-full h-10 pl-9 pr-3 text-sm rounded-xl border border-slate-200 bg-white text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-teal-400/40 focus:border-teal-400"
              />
            </div>
          )}
        </FilterSection>

        {/* ── Team ── */}
        <FilterSection label="Teams" onReset={() => setTeamId('')}>
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <button
                type="button"
                className="w-full flex items-center justify-between h-10 px-3 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-400/40 transition-colors"
              >
                <span className="truncate">
                  {selectedTeam?.name ?? 'Teams'}
                </span>
                <Heroicon
                  icon="ChevronDown"
                  className="w-4 h-4 text-slate-400"
                />
              </button>
            </DropdownMenuTrigger>

            <DropdownMenuContent className="w-[320px]">
              <DropdownMenuItem onSelect={() => setTeamId('')}>
                Alle teams
              </DropdownMenuItem>
              {user?.teams?.map(t => (
                <DropdownMenuItem
                  key={t.id}
                  onSelect={() => setTeamId(String(t.id))}
                >
                  {t.name}
                </DropdownMenuItem>
              ))}
            </DropdownMenuContent>
          </DropdownMenu>
        </FilterSection>

        {/* ── Status ── */}
        <FilterSection label="Status" onReset={() => setStatusName('')}>
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <button
                type="button"
                className="w-full flex items-center justify-between h-10 px-3 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-teal-400/40 transition-colors"
              >
                <span className="flex items-center min-w-0 truncate">
                  {selectedStatus ? (
                    <>
                      <span className="truncate">
                        {__(selectedStatus.name)}
                      </span>
                    </>
                  ) : (
                    'Alle statussen'
                  )}
                </span>
                <Heroicon
                  icon="ChevronDown"
                  className="w-4 h-4 text-slate-400"
                />
              </button>
            </DropdownMenuTrigger>

            <DropdownMenuContent className="w-[320px]">
              <DropdownMenuItem onSelect={() => setStatusName('')}>
                Alle statussen
              </DropdownMenuItem>
              {statuses?.map(s => (
                <DropdownMenuItem
                  key={s.name}
                  onSelect={() => setStatusName(s.name)}
                >
                  {__(s.name)}
                </DropdownMenuItem>
              ))}
            </DropdownMenuContent>
          </DropdownMenu>
        </FilterSection>
      </FilterDrawer>
    </>
  )
}
