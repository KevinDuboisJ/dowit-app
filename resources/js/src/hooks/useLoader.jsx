import { useState } from "react";
import { Loader as LoaderIcon } from '@/base-components'

export const useLoader = () => {

  const [loading, setLoading] = useState(false)
  const Loader = (props) => <LoaderIcon {...props}/>

  return { loading, setLoading, Loader }
};