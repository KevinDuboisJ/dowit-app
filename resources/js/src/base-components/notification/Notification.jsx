import React, { useEffect, useState } from 'react'
import {
  Heroicon,
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/base-components";
import { cn } from '@/utils'
import { format } from 'date-fns';

const Notification = ({ user }) => {
  const [message, setMessage] = useState();
  const [dateTime, setDateTime] = useState();

  const webSocketChannel = 'channel_for_everyone';

  const connectWebSocket = () => {
    window.Echo.private(webSocketChannel)
      .listen('GlobalNotificationEvent', async (e) => {
        const now = new Date();
        setMessage(e.message)
        setDateTime(format(now, 'dd/MM/yyyy HH:mm'))
      });

  }

  useEffect(() => {
    connectWebSocket();

    return () => {
      window.Echo.leave(webSocketChannel);
    }
  }, []);

  return (
    <DropdownMenu className="fadeInRight h-10">
      <DropdownMenuTrigger role="button" className="dropdown-toggle flex items-center outline-none">

        <div className={cn('dropdown-toggle notification cursor-pointer fadeInUp', {
          'notification--bullet': message,
        })} role="button">
          <Heroicon icon="Bell" className="notification__icon dark:text-slate-500" />
        </div>

      </DropdownMenuTrigger>
      <DropdownMenuContent className="z-[999] ml-10">
        <h1 className='p-1'>Notificaties</h1>
        <DropdownMenuItem className="w-80">

          {message ? <div className="cursor-pointer relative flex " onClick={() =>setMessage(null)}>
            <div className="w-10 h-10 flex-none image-fit mr-1">
              {/* <img alt="photo" className="rounded-full" src={user.image_path} /> */}
              <Heroicon icon="ChatBubbleBottomCenter" />
              {/* <div className="w-3 h-3 bg-success absolute right-0 bottom-0 rounded-full border-2 border-white dark:border-darkmode-600"></div> */}
            </div>
            <div className="ml-2">
              <a className="font-medium mr-1">Systeem</a> <span className="text-slate-500">{message} </span>
              <div className="text-sm text-slate-400 mt-1">{dateTime}</div>
            </div>
          </div> : 'Er zijn geen notificaties'}

        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>


  )
}

export default Notification