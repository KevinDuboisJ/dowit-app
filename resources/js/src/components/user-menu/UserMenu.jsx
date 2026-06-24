import React from 'react'
import { Link, usePage } from '@inertiajs/react'
import {
  Lucide,
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
  DropdownMenuLabel
} from '@/base-components'

import {FontSizeSlider} from '@/components'

const UserMenu = ({user, fontSize, setFontSize}) => {
  const { currentDevice } = usePage().props
  const deviceName = currentDevice?.description || currentDevice?.identifier || 'Geen poetskar geselecteerd'

  return (
    <DropdownMenu className="fadeInRight z-[999] w-38">
      <DropdownMenuTrigger
        role="button"
        className="h-full fadeInUp flex items-center outline-none"
      >
        <div className="w-10 h-10 image-fit">
          <img
            alt="Spacem²"
            className="rounded-full border-2 border-white border-opacity-10 shadow-lg"
            src={user?.image_path}
          />
        </div>
        <div className="ml-3 leading-tight">
          <div className="text-sm text-white font-medium">
            {user.firstname} {user.lastname}
          </div>
        </div>
      </DropdownMenuTrigger>
      <DropdownMenuContent sideOffset={5} align="start">
        <DropdownMenuLabel>
          <span className="text-sm font-normal text-gray-700">Teams</span>
          {user.teams.length > 0 ? (
            user.teams.map(team => (
              <span
                key={team.id}
                className="block text-xs text-gray-700 font-light pl-2"
              >
                {team.name}
              </span>
            ))
          ) : (
            <span className="block text-xs font-light text-gray-700">Leeg</span>
          )}
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuLabel>
          <span className="text-sm font-normal text-gray-700">Rollen</span>
          {user.roles &&
            Object.values(user.roles).map(role => (
              <div key={role} className="text-xs text-gray-700 font-light pl-2">
                {role}
              </div>
            ))}
        </DropdownMenuLabel>

        <DropdownMenuSeparator />
        <DropdownMenuItem tag="div">
          <Link
            href="/select-device/switch"
            method="post"
            className="flex w-full font-normal text-sm text-gray-700 items-center gap-2"
          >
            <Lucide icon="ShoppingCart" className="w-4 h-4 shrink-0" />
            <span className="flex flex-col leading-tight">
              <span className="font-medium text-gray-700">{deviceName}</span>
              <span className="text-xs text-gray-500 font-light">Poetskar wisselen</span>
            </span>
          </Link>
        </DropdownMenuItem>

        <DropdownMenuSeparator />
        <DropdownMenuItem tag="div">
          <Link
            href="/help"
            preserveState
            className="flex w-full font-normal text-sm text-gray-700 items-center"
          >
            <Lucide icon="HelpCircle" className="w-4 h-4 mr-1" />
            Help
          </Link>
        </DropdownMenuItem>

        <DropdownMenuSeparator />
        <DropdownMenuItem>
          <Link
            href="/logout"
            method="post"
            className="flex w-full text-sm text-gray-700 items-center"
          >
            <Lucide icon="CircleArrowRight" className="w-4 h-4 mr-1" /> Uitloggen
          </Link>
        </DropdownMenuItem>
                <DropdownMenuSeparator />

        <div className="px-2 py-1">
          <FontSizeSlider fontSize={fontSize} setFontSize={setFontSize} />{' '}
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  )
}

export default UserMenu
