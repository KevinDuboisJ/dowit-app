import { __ } from '@/stores'
import {
  Tippy
} from '@/base-components'

export const PriorityText = ({ state, color }) => {
  return (
    <span style={{ color: color }} className="text-sm">{state}</span>
  )
}

export const PriorityCircle = ({ state, color }) => {
  return (
    <Tippy content={state} >
      <div style={{ backgroundColor: color }} className="w-4 h-4 mx-auto rounded-full"></div>
    </Tippy>
  )
}