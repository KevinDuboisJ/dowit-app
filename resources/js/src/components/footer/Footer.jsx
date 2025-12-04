import { UserMenu } from '@/components'
import { Tooltip } from '@/base-components'
import { Notification } from '@/base-components'

const Footer = ({ user, fontSize, setFontSize }) => {
  return (
    <footer className="hidden xl:flex sticky bottom-0 z-50 w-full h-[52px] px-4 py-1 bg-white items-center drop-shadow font">
      <UserMenu user={user} fontSize={fontSize} setFontSize={setFontSize} />
      <Notification user={user} />

      <Tooltip content="Deze webapplicatie is ontwikkeld door Kevin Dubois" asChild>
        <span
          className="cursor-pointer ml-auto mt-auto font-light text-sm"
          alt="about"
        >
          about v0.9.8
        </span>
      </Tooltip>
    </footer>
  )
}

export default Footer
