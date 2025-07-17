import Lottie from 'lottie-react'
import loading from './loading.json'

export const Loader = ({height = 50, width = 50, className}) => {
  const options = {
    loop: true,
    autoplay: true,
    style: {
      position: 'relative',
      top: '-10px',
      height: height,
      width: width
    },
    animationData: loading,
    rendererSettings: {
      preserveAspectRatio: 'xMidYMid slice',
      viewBoxSize: '500 300 900 500' // crop into the center
    }
  }

  return <Lottie className={className} {...options} />
}
