import { MdChevronLeft, MdChevronRight } from 'react-icons/md'
import { cn } from '@/utils'

export const PaginationBar = ({
  current_page,
  last_page,
  from,
  to,
  total,
  onPageChange
}) => {
  if (!current_page) return null

  const canPrev = current_page > 1
  const canNext = current_page < last_page

  // Compact page window
  const windowSize = 5
  const start = Math.max(1, current_page - Math.floor(windowSize / 2))
  const end = Math.min(last_page, start + windowSize - 1)
  const pages = []
  for (let p = Math.max(1, end - windowSize + 1); p <= end; p++) pages.push(p)

  return (
    <div
      id="paginationBar"
      className="flex h-14 items-center justify-between gap-3 px-3 py-3 border-t border-slate-200 bg-white"
    >
      <div className="text-xs text-slate-500">
        {total > 0 ? (
          <>
            Toont <span className="font-semibold text-slate-700">{from}</span>–
            <span className="font-semibold text-slate-700">{to}</span> van{' '}
            <span className="font-semibold text-slate-700">{total}</span>
          </>
        ) : (
          <>Geen resultaten</>
        )}
      </div>

      {last_page > 1 && (
        <div className="flex items-center gap-1">
          <button
            type="button"
            onClick={() => canPrev && onPageChange(current_page - 1)}
            disabled={!canPrev}
            className={cn(
              'h-9 px-3 rounded-xl text-sm font-semibold flex items-center gap-1 transition-colors',
              canPrev
                ? 'border-slate-200 text-slate-700'
                : 'border-slate-200 text-slate-300 cursor-not-allowed'
            )}
          >
            <MdChevronLeft className="text-lg" />
            Vorige
          </button>

          <div className="hidden sm:flex items-center gap-1 mx-1">
            {pages[0] > 1 && (
              <>
                <button
                  type="button"
                  onClick={() => onPageChange(1)}
                  className="h-9 w-9 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50"
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
                  'h-9 w-9 rounded-xl border text-sm font-semibold transition-colors',
                  p === current_page
                    ? 'bg-indigo-50 text-indigo-700 border border-indigo-200/80'
                    : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50'
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
                  className="h-9 w-9 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50"
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
              'h-9 px-3 text-sm font-semibold flex items-center gap-1 transition-colors',
              canNext
                ? 'border-slate-200 text-slate-700'
                : 'border-slate-200 text-slate-300 cursor-not-allowed'
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
