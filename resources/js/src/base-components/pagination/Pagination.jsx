import { MdChevronLeft, MdChevronRight } from 'react-icons/md'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from '@/base-components'
import { cn } from '@/utils'

export const PaginationBar = ({
  current_page,
  last_page,
  from,
  to,
  total,
  per_page = 50,
  perPageOptions = [25, 50, 100, 200],
  onPageChange,
  onPerPageChange
}) => {
  if (!current_page) return null

  const canPrev = current_page > 1
  const canNext = current_page < last_page

  const windowSize = 5
  const start = Math.max(1, current_page - Math.floor(windowSize / 2))
  const end = Math.min(last_page, start + windowSize - 1)
  const pages = []
  for (let p = Math.max(1, end - windowSize + 1); p <= end; p++) pages.push(p)

  return (
    <div
      id="paginationBar"
      className="flex flex-col gap-3 border-t border-slate-200 bg-white px-4 py-2 md:flex-row md:items-center md:justify-between"
    >
      <div className="flex items-center gap-3">
        <div className="flex flex-wrap items-center gap-1 text-xs text-slate-500">
          {total > 0 ? (
            <>
              <span>Toont</span>

              <span className="font-semibold text-slate-700">{from}</span>
              <span>–</span>

              {onPerPageChange ? (
                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <button
                      type="button"
                      className="rounded text-indigo-500 font-semibold transition-colors hover:bg-slate-100 focus:outline-none "
                    >
                      {to}
                    </button>
                  </DropdownMenuTrigger>

                  <DropdownMenuContent align="start" className="w-20">
                    {perPageOptions.map(option => (
                      <DropdownMenuItem
                        key={option}
                        onClick={() => onPerPageChange(option)}
                        className={cn(
                          'cursor-pointer justify-center font-medium',
                          option === per_page && 'bg-slate-50 text-slate-900'
                        )}
                      >
                        {option}
                      </DropdownMenuItem>
                    ))}
                  </DropdownMenuContent>
                </DropdownMenu>
              ) : (
                <span className="font-semibold text-slate-700">{to}</span>
              )}

              <span>van</span>
              <span className="font-semibold text-slate-700">{total}</span>
            </>
          ) : (
            <>Geen resultaten</>
          )}
        </div>
      </div>

      {last_page > 1 && (
        <div className="flex items-center gap-1 text-xs">
          <button
            type="button"
            onClick={() => canPrev && onPageChange(current_page - 1)}
            disabled={!canPrev}
            className={cn(
              'flex h-9 items-center rounded-xl border px-3 font-semibold transition-colors',
              canPrev
                ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                : 'cursor-not-allowed border-slate-200 bg-white text-slate-300'
            )}
          >
            <MdChevronLeft className="text-lg" />
            Vorige
          </button>

          <div className="x-1 hidden items-center gap-1 sm:flex">
            {pages[0] > 1 && (
              <>
                <button
                  type="button"
                  onClick={() => onPageChange(1)}
                  className="h-9 w-9 rounded-xl border border-slate-200 bg-white font-semibold text-slate-700 hover:bg-slate-50"
                >
                  1
                </button>
                {pages[0] > 2 && <span className="px-1 text-slate-400">…</span>}
              </>
            )}

            {pages.map(p => (
              <button
                key={p}
                type="button"
                onClick={() => onPageChange(p)}
                className={cn(
                  'h-9 w-9 rounded-xl border font-semibold transition-colors',
                  p === current_page
                    ? 'border-indigo-200/80 bg-indigo-50 text-indigo-700'
                    : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                )}
              >
                {p}
              </button>
            ))}

            {pages[pages.length - 1] < last_page && (
              <>
                {pages[pages.length - 1] < last_page - 1 && (
                  <span className="px-1 text-slate-400">…</span>
                )}
                <button
                  type="button"
                  onClick={() => onPageChange(last_page)}
                  className="h-9 w-9 rounded-xl border border-slate-200 bg-white font-semibold text-slate-700 hover:bg-slate-50"
                >
                  {last_page}
                </button>
              </>
            )}
          </div>

          <button
            type="button"
            onClick={() => canNext && onPageChange(current_page + 1)}
            disabled={!canNext}
            className={cn(
              'flex h-9 items-center rounded-xl border px-3 font-semibold transition-colors',
              canNext
                ? 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                : 'cursor-not-allowed border-slate-200 bg-white text-slate-300'
            )}
          >
            Volgende
            <MdChevronRight className="text-lg" />
          </button>
        </div>
      )}
    </div>
  )
}
