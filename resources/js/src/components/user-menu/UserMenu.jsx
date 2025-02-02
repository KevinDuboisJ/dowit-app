import React from 'react'
import { Link } from '@inertiajs/react'
import {
  Lucide,
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
  DropdownMenuLabel,
} from "@/base-components";

import {
  FontSizeSlider
} from '@/components';


const UserMenu = ({ user, fontSize, setFontSize }) => {

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
          <div className="text-sm text-white xl:text-inherit font-medium">
            {user.firstname} {user.lastname}
          </div>

        </div>
        
      </DropdownMenuTrigger>
      <DropdownMenuContent sideOffset={5} align="start">
        <DropdownMenuLabel>
        <span className='text-sm font-light'>Teams</span>
          {user.teams.length > 0 ? user.teams.map(team => (
            <span key={team.id} className="block text-xs font-normal text-slate-500">{team.name}</span>
          )) : <span className="block text-xs font-normal text-slate-500">Leeg</span>}
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuLabel>
          <span className='text-sm font-light'>Rollen</span>
          {user.roles && Object.values(user.roles).map(role => (
            <div key={role} className="text-xs font-normal text-slate-500">{role}</div>
          ))}
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        <DropdownMenuItem tag="div">
          <Link href="/help" preserveState className='flex w-full' >
            <Lucide icon="HelpCircle" className="w-4 h-4 mr-2" /> Help
          </Link>
        </DropdownMenuItem>
        <DropdownMenuSeparator />
      
        <div className='px-2 py-1'><FontSizeSlider fontSize={fontSize} setFontSize={setFontSize}/> </div>
       
        <DropdownMenuSeparator />
        <DropdownMenuItem>
          <Link href="/logout" method="post" className='flex w-full' >
            <Lucide icon="ToggleRight" className="w-4 h-4 mr-2" /> Uitloggen
          </Link>
        </DropdownMenuItem>

      </DropdownMenuContent>
    </DropdownMenu>
  )
}

export default UserMenu
