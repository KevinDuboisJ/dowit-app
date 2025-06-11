import React from 'react'
import {
  Pagination as CustomPagination,
  PaginationContent,
  PaginationLink,
  PaginationPrevious,
  PaginationNext,
  PaginationEllipsis,
  PaginationCounter,
  PaginationFirst,
  PaginationLast
} from '@/base-components'

export default function Pagination({
  current_page,
  prev_page_url,
  next_page_url,
  first_page_url,
  last_page,
  last_page_url,
}) {

  return (
    <CustomPagination>
      <PaginationContent>
        <PaginationCounter current_page={current_page} last_page={last_page} />
        <PaginationFirst href={first_page_url} />
        <PaginationPrevious href={prev_page_url ?? ''} />
        <PaginationNext href={next_page_url  ?? ''} />
        <PaginationLast href={last_page_url} />
      </PaginationContent>
    </CustomPagination>
  )
}
