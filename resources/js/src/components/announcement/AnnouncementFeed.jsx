import {cn} from '@/utils'
import {router, usePage} from '@inertiajs/react'
import {
  RichText,
  Table,
  TableBody,
  TableCell,
  TableRow,
  Heroicon
} from '@/base-components'

export const AnnouncementFeed = ({className}) => {
  const {announcements} = usePage().props
  const markAsRead = announcementId => {
    router.post(
      `/announcements/${announcementId}/mark-as-read`,
      {replace: true},
      {
        only: ['announcements'],
        onError: response => {
          console.log(response)
        }
      }
    )
  }

  return (
    <div className={cn('', className)}>
      <Table>
        <TableBody>
          {announcements &&
            announcements.map(announcement => (
              <TableRow
                key={announcement.id}
                className="bg-[#FFFBE0] hover:bg-[#FFFBE0] leading-none"
              >
                <TableCell>
                  <div className="flex items-center min-h-4 py-0 px-1">
                    <Heroicon
                      icon="InformationCircle"
                      className="h-5 w-5 shrink-0"
                    />
                    <div className="flex flex-col">
                      <RichText
                        text={announcement.content}
                        className="text-sm px-2 leading-4"
                      />
                      <span className="px-2 text-[10px] text-black opacity-30">
                        Toegevoegd door{' '}
                        {`${announcement.creator.firstname} ${announcement.creator.lastname}`}
                        {`, ${new Date(announcement.start_date).toLocaleDateString('nl-BE')}`}
                      </span>
                    </div>
                    <Heroicon
                      icon="XMark"
                      className="h-4 w-4 shrink-0 cursor-pointer ml-auto"
                      onClick={() => markAsRead(announcement.id)}
                    />
                  </div>
                </TableCell>
              </TableRow>
            ))}
        </TableBody>
      </Table>
    </div>
  )
}
