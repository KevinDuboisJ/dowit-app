import { useState, useEffect } from 'react'

const useReactPath = () => {
    const [href, setHref] = useState(window.location.href)
    const listenToPopstate = () => {
      const href = window.location.href
      setHref(href)
    }
    useEffect(() => {
      window.addEventListener('popstate', listenToPopstate)
      return () => {
        window.removeEventListener('popstate', listenToPopstate)
      }
    }, [])
    return href
  }

  export { useReactPath }