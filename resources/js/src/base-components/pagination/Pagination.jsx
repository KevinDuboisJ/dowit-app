import React from 'react'
import { Link } from '@inertiajs/react'
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  DoubleArrowLeftIcon,
  DoubleArrowRightIcon,
  DotsHorizontalIcon
} from '@radix-ui/react-icons'
import { cn } from '@/utils'
import { buttonVariants } from '@/base-components/ui/button'

const Pagination = ({ className, ...props }) => (
  <nav
    role="navigation"
    aria-label="pagination"
    className={cn('flex items-center justify-end p-2 space-x-5', className)}
    {...props}
  />
)
Pagination.displayName = 'Pagination'

const PaginationContent = React.forwardRef(({ className, ...props }, ref) => (
  <div
    ref={ref}
    className={cn('flex items-center space-x-2', className)}
    {...props}
  />
))
PaginationContent.displayName = 'PaginationContent'

const PaginationLink = ({ className, isActive, size = 'icon', ...props }) => (
  <Link
    aria-current={isActive ? 'page' : undefined}
    className={cn(
      buttonVariants({
        variant: isActive ? 'outline' : 'ghost',
        size
      }),
      'flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50 border border-input bg-background shadow-sm hover:bg-accent hover:text-accent-foreground h-8 w-8 p-0',
      className
    )}
    {...props}
    preserveState
  />
)
PaginationLink.displayName = 'PaginationLink'

const PaginationPrevious = ({ className, ...props }) => (
  <PaginationLink
    aria-label="Ga naar de vorige pagina"
    size="default"
    className={cn('', className)}
    {...props}
  >
    <ChevronLeftIcon className="h-4 w-4" />
  </PaginationLink>
)
PaginationPrevious.displayName = 'PaginationPrevious'

const PaginationNext = ({ className, ...props }) => (
  <PaginationLink
    aria-label="Ga naar de volgende pagina"
    size="default"
    className={cn('', className)}
    {...props}
  >
    <ChevronRightIcon className="h-4 w-4" />
  </PaginationLink>
)
PaginationNext.displayName = 'PaginationNext'

const PaginationFirst = ({ className, ...props }) => (
  <PaginationLink
    aria-label="Ga naar de eerste pagina"
    size="default"
    className={cn('', className)}
    {...props}
  >
    <DoubleArrowLeftIcon className="h-4 w-4" />
  </PaginationLink>
)
PaginationFirst.displayName = 'PaginationFirst'

const PaginationLast = ({ className, ...props }) => (
  <PaginationLink
    aria-label="Ga naar de laatste pagina"
    size="default"
    className={cn('', className)}
    {...props}
  >
    <DoubleArrowRightIcon className="h-4 w-4" />
  </PaginationLink>
)
PaginationLast.displayName = 'PaginationLast'

const PaginationEllipsis = ({ className, ...props }) => (
  <span
    aria-hidden
    className={cn('flex h-9 w-9 items-center justify-center', className)}
    {...props}
  >
    <DotsHorizontalIcon className="h-4 w-4" />
    <span className="sr-only">More pages</span>
  </span>
)
PaginationEllipsis.displayName = 'PaginationEllipsis'

const PaginationCounter = ({ className, current_page, last_page }) => (
  <span
    aria-hidden
    className={cn(
      'flex items-center justify-center text-sm font-medium',
      className
    )}
  >
    Pagina {current_page} van {last_page}
  </span>
)
PaginationCounter.displayName = 'PaginationCounter'

export {
  Pagination,
  PaginationContent,
  PaginationLink,
  PaginationPrevious,
  PaginationNext,
  PaginationEllipsis,
  PaginationCounter,
  PaginationFirst,
  PaginationLast
}
