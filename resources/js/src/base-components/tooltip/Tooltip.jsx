// Tooltip.jsx
import './_tooltip.css'
import { useState, useRef, isValidElement, cloneElement } from 'react'
import {
  useFloating,
  offset,
  flip,
  shift,
  autoUpdate,
  arrow,
  useHover,
  useFocus,
  useDismiss,
  useRole,
  useInteractions,
  FloatingPortal,
  useTransitionStatus
} from '@floating-ui/react'

export const Tooltip = ({
  content,
  children,
  placement = 'top',
  disabled = false,
  asChild = false
}) => {
  const [open, setOpen] = useState(false)
  const arrowRef = useRef(null)

  if (disabled) {
    return children
  }

  const {
    x,
    y,
    refs,
    strategy,
    context,
    placement: finalPlacement,
    middlewareData
  } = useFloating({
    open,
    onOpenChange: setOpen,
    placement,
    middleware: [
      offset(8),
      flip(),
      shift({ padding: 8 }),
      arrow({ element: arrowRef })
    ],
    whileElementsMounted: autoUpdate
  })

  // ✨ Transition status (placement-aware)
  const { isMounted, status } = useTransitionStatus(context, {
    duration: 200 // ms – match CSS
  })

  const hover = useHover(context, {
    move: true,
    delay: { open: 50, close: 50 }
  })
  const focus = useFocus(context)
  const dismiss = useDismiss(context)
  const role = useRole(context, { role: 'tooltip' })

  const { getReferenceProps, getFloatingProps } = useInteractions([
    hover,
    focus,
    dismiss,
    role
  ])

  const staticSide = {
    top: 'bottom',
    right: 'left',
    bottom: 'top',
    left: 'right'
  }[finalPlacement.split('-')[0]]

  // --- Referentie element ---

  let reference

  if (asChild && isValidElement(children)) {
    // Gebruik het child element zelf als reference (géén wrapper)
    reference = cloneElement(children, {
      ref: refs.setReference,
      ...getReferenceProps(children.props)
    })
  } else {
    // Veilige default: wrapper (zonder display: contents, zodat positionering klopt)
    reference = (
      <span
        ref={refs.setReference}
        {...getReferenceProps()}
        className="inline-flex"
      >
        {children}
      </span>
    )
  }

  return (
    <>
      {reference}

      {isMounted && (
        <FloatingPortal>
          <div
            ref={refs.setFloating}
            {...getFloatingProps()}
            style={{
              position: strategy,
              top: y ?? 0,
              left: x ?? 0,
              zIndex: 50
            }}
            className="fui-tooltip"
          >
            {/* Inner element krijgt de transition + data attrs */}
            <div
              className="fui-tooltip-inner"
              data-status={status}
              data-placement={finalPlacement}
            >
              {content}

              <div
                ref={arrowRef}
                className="fui-tooltip-arrow"
                style={{
                  position: 'absolute',
                  left: middlewareData.arrow?.x ?? '',
                  top: middlewareData.arrow?.y ?? '',
                  [staticSide]: '-4px'
                }}
              />
            </div>
          </div>
        </FloatingPortal>
      )}
    </>
  )
}
