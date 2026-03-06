import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { router } from '@inertiajs/react'

const uniqById = list => {
  const seen = new Set()
  return list.filter(x => {
    if (seen.has(x.id)) return false
    seen.add(x.id)
    return true
  })
}

const useInfiniteScroll = ({ request, propKey, params = {} }) => {
  const [items, setItems] = useState(() => request?.data ?? [])
  const [loading, setLoading] = useState(false)
  const [hasMore, setHasMore] = useState(
    (request?.current_page ?? 1) < (request?.last_page ?? 1)
  )

  const scrollRootRef = useRef(null)
  const observerRef = useRef(null)

  const isFetchingRef = useRef(false)
  const pageRef = useRef(request?.current_page ?? 1)
  const lastPageRef = useRef(request?.last_page ?? 1)

  const datasetKey = useMemo(
    () => JSON.stringify(params?.filters ?? []),
    [params?.filters]
  )

  const pendingResetKeyRef = useRef(datasetKey)

  // 1) Filters changed -> clear list NOW, wait for new request to arrive
  useEffect(() => {
    pendingResetKeyRef.current = datasetKey

    // clear old dataset immediately (prevents “previous data” flash)
    setItems([])
    setLoading(true)

    // reset paging refs
    pageRef.current = 1
    lastPageRef.current = 1
    setHasMore(false)

    isFetchingRef.current = false
  }, [datasetKey])

  // 2) When request updates -> if it's page 1 and matches the pending dataset, apply it
  useEffect(() => {
    const currentPage = request?.current_page ?? 1

    // only hydrate from server when it's the first page of the new dataset
    if (currentPage !== 1) return
    if (pendingResetKeyRef.current !== datasetKey) return

    const nextItems = request?.data ?? []
    const lastPage = request?.last_page ?? 1

    setItems(nextItems)
    pageRef.current = 1
    lastPageRef.current = lastPage
    setHasMore(1 < lastPage)
    setLoading(false)
  }, [request, datasetKey])

  // Keep pagination info up to date when request changes,
  // but DO NOT overwrite items (this prevents losing previous pages)
  useEffect(() => {
    const current = request?.current_page ?? pageRef.current
    const last = request?.last_page ?? lastPageRef.current

    pageRef.current = current
    lastPageRef.current = last
    setHasMore(current < last)
  }, [request?.current_page, request?.last_page])

  const loadMore = useCallback(() => {
    if (loading) return
    if (isFetchingRef.current) return
    if (pageRef.current >= lastPageRef.current) return

    isFetchingRef.current = true
    setLoading(true)

    const nextPage = pageRef.current + 1

    router.reload({
      only: [propKey],
      data: { ...params, page: nextPage },
      queryStringArrayFormat: 'indices',
      preserveState: true,
      preserveScroll: true,
      onSuccess: page => {
        const pageData = page?.props?.[propKey]
        const newData = pageData?.data ?? []

        setItems(prev => uniqById([...prev, ...newData]))

        const current = pageData?.current_page ?? nextPage
        const last = pageData?.last_page ?? lastPageRef.current

        pageRef.current = current
        lastPageRef.current = last
        setHasMore(current < last)

        setLoading(false)
        isFetchingRef.current = false
      },
      onError: err => {
        console.error(`[useInfiniteScroll] Failed to reload "${propKey}":`, err)
        setLoading(false)
        isFetchingRef.current = false
      }
    })
  }, [loading, propKey, params])

  const lastItemRef = useCallback(
    node => {
      if (!node) return
      if (loading) return
      if (!hasMore) return

      observerRef.current?.disconnect()

      observerRef.current = new IntersectionObserver(
        entries => {
          if (entries[0]?.isIntersecting) loadMore()
        },
        {
          root: scrollRootRef.current,
          rootMargin: '200px',
          threshold: 0.01
        }
      )

      observerRef.current.observe(node)
    },
    [hasMore, loading, loadMore]
  )

  useEffect(() => () => observerRef.current?.disconnect(), [])

  return { items, loading, hasMore, lastItemRef, scrollRootRef }
}

export { useInfiniteScroll }
