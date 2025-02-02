import React from 'react';
import Lottie from 'lottie-react';
import loading from './loading.json';


export const Loader = ({ height = 64, width = 64, className }) => {

  const options = {
    loop: true,
    autoplay: true,
    style: {
      height: height,
      width: width
    },
    animationData: loading,
    rendererSettings: {
      preserveAspectRatio: 'xMidYMid slice',
      width: "100%",
      height: "100%",
      viewBoxSize:"200 0 1920 1080"
    }
  }

  return (
    <Lottie  className={className} {...options} />
  )
}