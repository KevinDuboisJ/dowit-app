import {useState, useEffect, useCallback, useRef} from 'react'
import {router} from '@inertiajs/react'

const useFetch = (url, input) => {
  const isInitialMount = useRef(true)
  const [fetchedData, setFetchedData] = useState({
    data: [],
    isLoading: true,
    error: false
  })

  //   const fetchData = useCallback(async () => {
  //    router.post(url, {input: input}, {
  //       preserveState: true,

  //       onSuccess: response => {
  //         // setFetchedData({
            
  //         //   notificationsSocket: response.user.notifications ? response.user.notifications : null,
  //         //   isLoading: false,
  //         //   error: false
  //         // })
  //       }
  //     })
  // }, [url])
 

  useEffect(() => {

    fetch('/companies').then((response) => response.json())
    .then((json) => setFetchedData(json.companies));
    
  },[])
  
  const {data, isLoading, error} = fetchedData
  return {data, isLoading, error}
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
export default useFetch
