import React, {useState, useRef, useEffect, useLayoutEffect} from 'react'
import TooltipBox from './TooltipBox'
import tooltipIcon from '@images/tooltip.svg'

const getPosition = (parent, tooltip, placement, space) => {
  const elRect = parent.getBoundingClientRect()
  switch (placement) {
    case 'left':
      return {
        x: elRect.left - (tooltip.offsetWidth + space),
        y: elRect.top + (parent.offsetHeight - tooltip.offsetHeight) / 2
      }
    case 'right':
      return {
        x: elRect.right + space,
        y: elRect.top + (parent.offsetHeight - tooltip.offsetHeight) / 2
      }
    default:
      return {
        x: elRect.left + (parent.offsetWidth - tooltip.offsetWidth) / 2,
        y: elRect.bottom + space
      }
  }
}

export const Tooltip = ({
  name = null,
  children = false,
  placement = 'left',
  space = 15,
  tooltipText,
}) => {
  const [open, setOpen] = useState(false)
  const [show, setShow] = useState(0)
  const [posRef, setPosRef] = useState({x: 0, y: 0})
  const tooltipRef = useRef()
  const [text, setText] = useState(tooltipText)
  const [parent, setParent] = useState()

  async function getTooltipText() {
    const response = await fetch('/api/v1/tooltip?name='+name, {
      headers: {
        'Content-type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document
          .querySelector('meta[name="csrf-token"]')
          .getAttribute('content'),
        'X-Requested-With': 'XMLHttpRequest'
      },
    })

    const data = await response.json()
    setText(data.text.text)
  }

  useEffect(() => {
    if (name) {
      getTooltipText()
    }
  }, [])

  useLayoutEffect(() => {
    if (tooltipRef.current) {
      setPosRef(getPosition(parent, tooltipRef.current, placement, space))
    }
  }, [show])

  const onTooltipCloseHandler = () => {
    setShow(0)
    setOpen(false)
  }
  const mouseOutHandler = () => {
    if (open === false) setShow(0)
  }

  const mouseClickHandler = e => {
    setParent(e.currentTarget)

    setShow(1)
    setOpen(true)
  }

  const mouseOverHandler = e => {
    if (!open) {
      setParent(e.currentTarget)
      setShow(1)
    }
  }
  return (
    <div style={{display: 'inline'}}>
      {children ? (
        React.cloneElement(children, {
          onClick: mouseClickHandler,
          onMouseOver: mouseOverHandler,
          onMouseOut: mouseOutHandler
        })
      ) : (
        <img
          className="w-4 ml-1 inline align-super cursor-help"
          src={tooltipIcon}
          onClick={mouseClickHandler}
          onMouseOver={mouseOverHandler}
          onMouseOut={mouseOutHandler}
        />
      )}
      {show == 1 && (
        <TooltipBox
          ref={tooltipRef}
          onTooltipClose={onTooltipCloseHandler}
          position={posRef}
        >
          {text}
        </TooltipBox>
      )}
    </div>
  )
}