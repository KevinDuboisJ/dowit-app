import { useEffect, useState } from 'react'

export const useIsMobile = () => {
  const [isMobile, setIsMobile] = useState(false)

  useEffect(() => {
    const media = window.matchMedia('(max-width: 1280px)')

    const handler = () => setIsMobile(media.matches)

    handler()
    media.addEventListener('change', handler)

    return () => media.removeEventListener('change', handler)
  }, [])

  return { isMobile }
}