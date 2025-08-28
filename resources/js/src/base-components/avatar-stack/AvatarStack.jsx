import {useState, useEffect} from 'react'
import {cn} from '@/utils'
import {
  Avatar,
  AvatarImage,
  AvatarFallback,
  Heroicon,
  Loader,
  Tippy
} from '@/base-components'

export const AvatarStack = ({avatars, maxAvatars = 4, className}) => {
  avatars = avatars || [] // Ensure it's always an array
  avatars.slice(0, maxAvatars)
  const remainingAvatarsCount = avatars.length - maxAvatars

  if (avatars === '{{loading}}') {
    return <Loader />
  }

  if (avatars.length === 0) {
    return null
  }

  return (
    <div className="flex items-center">
      {avatars.map(avatar => (
        <Tippy
          key={avatar.id}
          content={`${avatar.firstname} ${avatar.lastname}`}
        >
          <Avatar
            className={cn('inline-block w-6 h-6 mr-1', className)}
          >
            <AvatarImage src={avatar.image_path} alt={avatar.lastname} />
            <AvatarFallback>{avatar.lastname.charAt(0)}</AvatarFallback>
          </Avatar>
        </Tippy>
      ))}

      {remainingAvatarsCount > 0 && (
        <div
          className={cn(
            'flex items-center justify-center w-6 h-6 bg-sky-900 text-white rounded-full text-sm'
          )}
        >
          +{remainingAvatarsCount}
        </div>
      )}
    </div>
  )
}

export const AvatarStackRemovable = ({
  avatars: initialAvatars,
  onValueChange = () => {}
}) => {
  const [avatars, setAvatars] = useState([])

  // Whenever the initialAvatars prop changes, update the local state.
  useEffect(() => {
    if (initialAvatars !== '{{loading}}' && initialAvatars.length > 0) {
      setAvatars(initialAvatars)
    }
  }, [initialAvatars])

  if (initialAvatars === '{{loading}}') {
    return <Loader />
  }

  if (initialAvatars.length === 0) {
    return null
  }

  const handleValueChange = value => {
    setAvatars(avatars.filter(avatar => avatar.id !== value))
    onValueChange(value)
  }

  return (
    <div className="flex items-center space-x-1">
      {avatars.map(avatar => (
        <div key={avatar.id} className="relative">
          <Avatar>
            <AvatarImage src={avatar.image_path} alt={avatar.lastname} />
            <AvatarFallback>{avatar.lastname.charAt(0)}</AvatarFallback>
          </Avatar>
          {/* Delete Icon */}
          <Heroicon
            title="Toewijzing verwijderen"
            icon="XMark"
            onClick={() => handleValueChange(avatar.id)}
            className="h-4 w-4 absolute top-1 right-1 transform translate-x-1/2 -translate-y-1/2 cursor-pointer text-red-800"
            size={16}
          />
        </div>
      ))}
    </div>
  )
}

export const AvatarStackWrap = ({children}) => {
  return <div className="flex flex-col">{children}</div>
}

export const AvatarStackHeader = ({title = '', icon = null}) => {
  return (
    <div className="flex items-center space-x-1 min-w-0 text-sm text-slate-500">
      {icon}
      <span>{title}</span>
    </div>
  )
}
