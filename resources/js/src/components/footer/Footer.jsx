import { UserMenu } from '@/components'
import { Tippy } from '@/base-components'
import { Notification } from '@/base-components'

const Footer = ({ user, fontSize, setFontSize }) => {

  return (
    <footer className="hidden xl:flex sticky shrink-0 bottom-0 z-50 w-full h-[52px] px-4 py-1 bg-white items-center drop-shadow font">
      <UserMenu user={user} fontSize={fontSize} setFontSize={setFontSize}/>
      <Notification user={user}/>

      <Tippy
        tag="span"
        className="cursor-pointer ml-auto mt-auto font-light text-sm"
        alt='about'
        content='Deze webapplicatie is ontwikkeld door Kevin Dubois'
      >
        about v0.5.4
      </Tippy>
    </footer >
  )
}

export default Footer
