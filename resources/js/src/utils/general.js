import {clsx} from 'clsx'
import {twMerge} from 'tailwind-merge'
import isEqual from 'lodash/isEqual'

export function cn(...inputs) {
  return twMerge(clsx(inputs))
}

export const delay = ms => new Promise(resolve => setTimeout(resolve, ms))

export function truncateDecimals(value, decimals, symbol = '') {
  const num = Number(value)
  if (isNaN(num)) {
    alert(num + ' is not a valid number')
    return
  }
  return num.toFixed(decimals) + symbol
}

// export const getChangedFields = (data, defaultValues, options = {}) => {
//   const {onNoChanges} = options // Extract optional callback
//   const changedFields = {}

//   for (const key in data) {
//     const currentValue = data[key]
//     const defaultValue = defaultValues?.[key]

//     // Use isEqual only for arrays
//     if (
//       (Array.isArray(currentValue) && !isEqual(currentValue, defaultValue)) || // Deep compare arrays
//       (!Array.isArray(currentValue) && currentValue !== defaultValue) // Compare other types directly
//     ) {
//       changedFields[key] = currentValue
//     }
//   }

//   // If no changes are detected, execute `onNoChanges` callback
//   if (Object.keys(changedFields).length === 0 && onNoChanges) {
//     onNoChanges('No changes detected.')
//   }

//   return changedFields
// }
