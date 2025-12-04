import Lottie from 'lottie-react'
import loading from './loading.json'
import { cn } from '@/utils'

export const Loader = ({
  variant = 'lottie', // 'lottie' | 'circle'
  size = 50,
  className
}) => {
  if (variant === 'circle') {
    return (
      <span
        className={
          className
            ? `${className} inline-block rounded-full border-2 border-slate-400/40 border-t-blue-800 animate-spin`
            : 'inline-block rounded-full border-2 border-slate-400/40 border-t-blue-800 animate-spin'
        }
        style={{ width: size + 'px', height: size + 'px' }}
        aria-hidden="true"
      />
    )
  }

  const options = {
    loop: true,
    autoplay: true,
    animationData: loading,
    rendererSettings: {
      preserveAspectRatio: 'xMidYMid slice',
      viewBoxSize: '500 300 900 500'
    }
  }

  return (
    <Lottie
      {...options}
      className={cn('relative -top-2.5', className)}
      style={{ height: size, width: size }}
    />
  )
}
