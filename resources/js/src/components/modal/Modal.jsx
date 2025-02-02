import React from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
  DialogFooter,
  DialogClose,
} from '@/base-components';
import { cn } from '@/utils'

export default function Modal({ children, ...props }) {

  return (
    <Dialog {...props}>
      {children}
    </Dialog>
  );
};

function ModalContent({ title = 'Ben je zeker?', message = 'Weet je zeker dat je dit wilt doen?', children, ...props }) {

  return (
    <DialogContent {...props} className={cn(props.className, 'max-w-sm sm:max-w-lg')}>
      <ModalGroup>
        <DialogHeader className="space-y-0">
          <DialogTitle>{title}</DialogTitle>
          <DialogDescription>
            {message}
          </DialogDescription>
        </DialogHeader>
      </ModalGroup>
      {children}
    </DialogContent>
  )

}

function ModalFooter({ children }) {

  return (
    <DialogFooter className="gap-y-2">
      {children}
    </DialogFooter>
  )

}

function ModalGroup({ children }) {

  return (
    <div className="flex flex-col">
      {children}
    </div>
  )

}

Modal.Trigger = DialogTrigger;
Modal.Content = ModalContent;
Modal.Footer = ModalFooter;
Modal.Close = DialogClose;
Modal.Group = ModalGroup;

