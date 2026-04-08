import { useState } from 'react'

export function AppIcon({ src, name, className }) {
  const [isError, setIsError] = useState(false)

  if (!src || isError) return null

  const isPath =
    src.startsWith('/') ||
    src.startsWith('./') ||
    src.startsWith('../') ||
    src.startsWith('http://') ||
    src.startsWith('https://') ||
    src.startsWith('//')

  const iconUrl = isPath
    ? src
    : `/images/icons/${src.replace(/^az-/, '')}.svg`

  return (
    <img
      src={iconUrl}
      alt={name}
      title={name}
      className={className}
      onError={() => setIsError(true)}
    />
  )
}