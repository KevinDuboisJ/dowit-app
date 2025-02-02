import {useState, useEffect, useCallback, useRef} from 'react'
import {router} from '@inertiajs/react'

const UseNotification = (url, notifications, socket) => {
  const isInitialMount = useRef(true)
  const [fetchedData, setFetchedData] = useState({
    notificationsSocket: notifications || [],
    isLoading: true,
    error: false
  })

  const fetchData = useCallback(async () => {
    router.post(url, {socket: socket}, {
        preserveState: true,

        onSuccess: response => {
          // setFetchedData({
            
          //   notificationsSocket: response.user.notifications ? response.user.notifications : null,
          //   isLoading: false,
          //   error: false
          // })
        }
      })
  }, [notifications])
  
  useEffect(() => {
    if (isInitialMount.current) {
      isInitialMount.current = false
    } else {
      fetchData();
    }
  },[notifications])
  
  const {notificationsSocket, isLoading, error} = fetchedData
  return {notificationsSocket, isLoading, error}
}

// router.reload({data : {socket: socket},
//   preserveState: true,
//   onSuccess: response => {
//     setFetchedData({
//       data: response.props.notifications ? response.props.notifications : null,
//       isLoading: false,
//       error: false
//     })

//   }
// })
export default UseNotification
