import { useState, useRef } from 'react'
import { __ } from '@/stores'
import { AnnouncementForm, AnnouncementList } from './'

import {
  Sheet,
  SheetTrigger,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
  Heroicon,
  Button,
  ScrollArea
} from '@/base-components'

export const AnnouncementSheet = () => {
  const [isOpen, setIsOpen] = useState(false)
  const [editingAnnouncement, setEditingAnnouncement] = useState(null)
  const formRef = useRef(null)

  const scrollFormIntoView = () => {
    setTimeout(() => {
      formRef.current?.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      })
    }, 50) // small delay to ensure rendering
  }

  const handleOpenChange = () => {
    setIsOpen((prevState) => !prevState)

    if (isOpen) {
      setEditingAnnouncement(null)
    }
  }

  return (
    <Sheet open={isOpen} onOpenChange={handleOpenChange}>
      <SheetTrigger asChild>
        <Button type="submit" className="w-full xl:w-auto" size={'sm'}>
          <Heroicon icon="ChatBubbleLeftEllipsis" /> Mededeling
        </Button>
      </SheetTrigger>
      <SheetContent className="flex flex-col p-0 h-full bg-app-background-secondary w-full md:w-[768px] sm:max-w-screen-md">
        <SheetHeader className="text-left flex flex-col items-center bg-white p-3 pl-1 space-y-3 border-b shrink-0">
          <div className="flex w-full py-2">
            <div className="flex flex-wrap self-start">
              <button
                onClick={handleOpenChange}
                className="h-6 focus:outline-none focus:ring-0 focus-visible-ring-0"
              >
                <Heroicon icon="ChevronLeft" className="w-5 stroke-[2.6px]" />
              </button>
            </div>
            <div className="flex flex-wrap flex-col w-full pl-2 leading-tight">
              <SheetTitle>Mededeling aanmaken</SheetTitle>
              <SheetDescription className="mt-0">
                Plaats een mededeling voor bepaalde gebruiker(s) of team(s)
              </SheetDescription>
            </div>
          </div>
        </SheetHeader>

        <ScrollArea className="flex-1">
          <div ref={formRef} className="space-y-6">
            {/* form */}
            <AnnouncementForm
              editing={editingAnnouncement}
              onSaved={() => setEditingAnnouncement(null)}
            />

            {/* list */}
            <AnnouncementList
              onEdit={(item) => {
                setEditingAnnouncement(item);
                scrollFormIntoView();
              }}
            />
          </div>
        </ScrollArea>
      </SheetContent>
    </Sheet>
  )
}
