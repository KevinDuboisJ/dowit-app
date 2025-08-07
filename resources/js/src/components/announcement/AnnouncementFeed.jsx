import {cn} from '@/utils'
import {router, usePage} from '@inertiajs/react'
import {
  RichText,
  Table,
  TableBody,
  TableCell,
  TableRow,
  Heroicon,
  Avatar,
  AvatarImage,
  AvatarFallback
} from '@/base-components'

export const AnnouncementFeed = ({className}) => {
  const {announcements} = usePage().props
  const markAsRead = announcementId => {
    router.post(
      `/announce/${announcementId}/mark-as-read`,
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
                className="bg-[#FFFBE0] hover:bg-[#FFFBE0]"
              >
                <TableCell>
                  <div className="flex items-center min-h-4 py-0 px-1">
                    <Heroicon
                      icon="InformationCircle"
                      className="h-5 w-5 shrink-0"
                    />
                    {/* <Avatar className="inline-block w-5 h-5 mr-1">
                    <AvatarImage src={announcement.creator.image_path} alt={announcement.creator.lastname} />
                    <AvatarFallback>{announcement.creator.lastname.charAt(0)}</AvatarFallback>
                  </Avatar> */}
                    <RichText
                      text={announcement.content}
                      className="text-sm px-2"
                    />
                    <span className="pr-2 self-center text-[10px] text-black opacity-30">
                      {`${announcement.creator.firstname} ${announcement.creator.lastname}`}
                    </span>
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
