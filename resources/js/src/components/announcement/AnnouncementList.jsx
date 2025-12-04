import { toast } from 'sonner'
import { useState, useEffect } from 'react'
import { router, usePage } from '@inertiajs/react'
import { format, addDays, parseISO } from 'date-fns'
import { nlBE } from 'date-fns/locale'
import { __ } from '@/stores'
import {
  Heroicon,
  Button,
  Loader,
  RichText,
  AlertDialog,
  AlertDialogTrigger,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogFooter,
  AlertDialogTitle,
  AlertDialogDescription,
  AlertDialogAction,
  AlertDialogCancel
} from '@/base-components'

export const AnnouncementList = ({ onEdit, onDeleted }) => {
  const { ownAnnouncements: announcements } = usePage().props
  const [isLoading, setIsLoading] = useState(true)
  const [deletingId, setDeletingId] = useState(null)

  useEffect(() => {
    router.reload({
      only: ['ownAnnouncements'],
      onFinish: () => setIsLoading(false)
    })
  }, [])

  const handleDelete = id => {
    setDeletingId(id)

    router.delete(`/announcements/${id}`, {
      only: ['ownAnnouncements'],
      onSuccess: () => {
        toast.success('Mededeling verwijderd')
        onDeleted?.(id)
      },
      onError: () => {
        toast.error('Kon mededeling niet verwijderen')
      },
      onFinish: () => {
        setDeletingId(null)
      }
    })
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-6">
        <Loader />
      </div>
    )
  }

  if (!announcements?.length) {
    return (
      <div className="rounded-lg border border-dashed border-slate-200 bg-white/60 px-4 py-6 text-center text-sm text-slate-500">
        Je hebt nog geen mededelingen geplaatst.
      </div>
    )
  }

  return (
    <div className="p-3 space-y-3">
      <h3 className="font-medium text-slate-700">Jouw mededelingen</h3>

      <div className="space-y-3">
        {announcements.map(item => {
          const from = item.start_date ? parseISO(item.start_date) : null
          const to = item.end_date ? parseISO(item.end_date) : null
          const now = new Date()

          // Actief als: van-datum is gezet én nu >= van
          // én (geen einddatum of nu <= einddatum)
          const isActive = from && now >= from && (!to || now <= to)

          // Komend als: van-datum in de toekomst ligt
          const isUpcoming = from && now < from

          const stateClass = isActive
            ? 'text-green-700'
            : isUpcoming
            ? 'text-yellow-600'
            : 'text-red-700'

          return (
            <div
              key={item.id}
              className="rounded-lg border text-sm px-4 py-3 shadow-xs hover:shadow-sm transition-shadow"
            >
              <div className="flex flex-col">
                <div className="flex items-center text-xs text-slate-500">
                  <div className={`font-medium tracking-wide ${stateClass}`}>
                    {from &&
                      (to
                        ? `${format(from, 'dd/MM/yy', {
                            locale: nlBE
                          })} → ${format(to, 'dd/MM/yy', { locale: nlBE })}`
                        : `${format(from, 'dd/MM/yy', {
                            locale: nlBE
                          })}`)}
                  </div>
                </div>

                <RichText text={item.content} />
                <div className="flex gap-x-2 justify-between mt-2">
                  <div className="flex gap-2 items-center self-end text-xs text-muted-foreground">
                    {item.users.length > 0 && (
                      <span className="inline-flex items-center rounded-full py-0.5 gap-x-1">
                        <Heroicon icon="UserGroup" className="h-3 w-3" />
                        {item.users.length} gebruiker(s)
                      </span>
                    )}
                    {item.teams.length > 0 && (
                      <span className="inline-flex items-center rounded-full py-0.5 gap-x-1">
                        <Heroicon icon="Users" className="h-3 w-3" />
                        {item.teams.length} team(s)
                      </span>
                    )}
                  </div>
                  <div>
                    <Button
                      type="button"
                      variant="ghost"
                      size="xs"
                      onClick={() => onEdit?.(item)}
                    >
                      <Heroicon icon="PencilSquare" className="h-4 w-4" />
                    </Button>

                    <AlertDialog>
                      <AlertDialogTrigger asChild>
                        <Button
                          type="button"
                          variant="ghost"
                          size="xs"
                          className="text-red-600 hover:text-red-700 pr-0"
                        >
                          {deletingId === item.id ? (
                            <Loader
                              className="border-t-red-600"
                              variant="circle"
                              size="15"
                            />
                          ) : (
                            <Heroicon icon="Trash" className="h-4 w-4" />
                          )}
                        </Button>
                      </AlertDialogTrigger>
                      <AlertDialogContent>
                        <AlertDialogHeader>
                          <AlertDialogTitle>
                            Ben je helemaal zeker?
                          </AlertDialogTitle>
                          <AlertDialogDescription>
                            Met dit bevestig je dat je deze mededeling wilt
                            verwijderen
                          </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                          <AlertDialogCancel>Annuleren</AlertDialogCancel>
                          <AlertDialogAction
                            onClick={() => handleDelete(item.id)}
                          >
                            Verwijderen
                          </AlertDialogAction>
                        </AlertDialogFooter>
                      </AlertDialogContent>
                    </AlertDialog>
                  </div>
                </div>
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}
