import { useEffect } from 'react'
import axios from 'axios'

export const useHeartbeat = () => {
  useEffect(() => {
    const sendHeartbeat = () => {
      axios.post('/me/heartbeat').catch(() => {})
    }

    sendHeartbeat()

    const interval = setInterval(sendHeartbeat, 60_000)

    return () => clearInterval(interval)
  }, [])
}
