import { cn } from '@/utils'
import { router, usePage } from '@inertiajs/react'
import {
  RichText,
  Table,
  TableBody,
  TableCell,
  TableRow,
  Heroicon,
} from '@/base-components';

export const AnnouncementFeed = ({ className }) => {

  const { announcements } = usePage().props;

  const markAsRead = (announcementId) => {
    router.post(`/announce/${announcementId}/mark-as-read`, { replace: true }, {
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
                  <RichText text={announcement.content} className='whitespace-nowrap text-sm px-3'/>
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
