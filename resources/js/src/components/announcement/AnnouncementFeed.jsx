import {
  RichText,
  RichTextEditor,
  Table,
  TableBody,
  TableCell,
  TableRow,
  Heroicon,
} from '@/base-components';

import { cn } from '@/utils'
import { router } from '@inertiajs/react'

export const AnnouncementFeed = ({ announcements, className }) => {

  const markAsRead = (announcementId) => {
    router.post(`/announce/${announcementId}/mark-as-read`, {replace:true}, {
      only: ['announcements'],
      onError: (response) => {
        console.log(response)
      },
    });
  }

  return (
    <div className={cn('', className)}>
      <Table className='overflow-hidden'>
        <TableBody>
          {announcements && announcements.map((announcement) => (
            <TableRow
              key={announcement.id}
              className='bg-[#ffd7001f] hover:bg-[#ffd7001f]'
            >
              <TableCell>
                <div className='flex items-center min-h-4 py-0'>
                  <Heroicon icon='InformationCircle' className='h-5 w-5' />
                  <RichText className='w-full p-0 border-none rounded-none shadow-none'>
                    <RichTextEditor
                      className='min-h-4 w-full text-xs text-slate-500 p-0 px-3 border-none rounded-md text-gray-700 resize-none overflow-hidden
                     focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none'
                      value={announcement.content}
                      readonly={true}
                    />
                  </RichText>

                  <Heroicon icon='XMark' className='h-4 w-4 cursor-pointer ml-auto' onClick={() => markAsRead(announcement.id)} />
                </div>
              </TableCell>

            </TableRow>

          ))
          }
        </TableBody>
      </Table>
    </div>
  )
}
