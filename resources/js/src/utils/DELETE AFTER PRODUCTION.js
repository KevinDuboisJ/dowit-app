// i have this code is it better to extract it in individual js files? import dayjs from 'dayjs'
import duration from 'dayjs/plugin/duration'
import {clsx} from 'clsx'
import {twMerge} from 'tailwind-merge'

dayjs.extend(duration)

const helpers = {

  cutText(text, length) {
    if (text.split(' ').length > 1) {
      const string = text.substring(0, length)
      const splitText = string.split(' ')
      splitText.pop()
      return splitText.join(' ') + '...'
    } else {
      return text
    }
  },

  formatDate(date, format) {
    return dayjs(date).format(format)
  },

  capitalizeFirstLetter(string) {
    if (string) {
      return string.charAt(0).toUpperCase() + string.slice(1)
    } else {
      return ''
    }
  },

  onlyNumber(string) {
    if (string) {
      return string.replace(/\D/g, '')
    } else {
      return ''
    }
  },

  formatCurrency(num) {
    if (num) {
      const formattedNumber = num.toString().replace(/\D/g, '')
      const rest = formattedNumber.length % 3
      let currency = formattedNumber.substr(0, rest)
      const thousand = formattedNumber.substr(rest).match(/\d{3}/g)
      let separator

      if (thousand) {
        separator = rest ? '.' : ''
        currency += separator + thousand.join('.')
      }

      return currency
    } else {
      return ''
    }
  },

  timeAgo(time) {
    const date = new Date((time || '').replace(/-/g, '/').replace(/[TZ]/g, ' '))
    const diff = (new Date().getTime() - date.getTime()) / 1000
    const dayDiff = Math.floor(diff / 86400)

    if (isNaN(dayDiff) || dayDiff < 0 || dayDiff >= 31) {
      return dayjs(time).format('MMMM DD, YYYY')
    }

    return (
      (dayDiff === 0 &&
        ((diff < 60 && 'just now') ||
          (diff < 120 && '1 minute ago') ||
          (diff < 3600 && Math.floor(diff / 60) + ' minutes ago') ||
          (diff < 7200 && '1 hour ago') ||
          (diff < 86400 && Math.floor(diff / 3600) + ' hours ago'))) ||
      (dayDiff === 1 && 'Yesterday') ||
      (dayDiff < 7 && dayDiff + ' days ago') ||
      (dayDiff < 31 && Math.ceil(dayDiff / 7) + ' weeks ago')
    )
  },

  diffTimeByNow(time) {
    const startDate = dayjs(dayjs().format('YYYY-MM-DD HH:mm:ss').toString())
    const endDate = dayjs(dayjs(time).format('YYYY-MM-DD HH:mm:ss').toString())

    const duration = dayjs.duration(endDate.diff(startDate))
    const milliseconds = Math.floor(duration.asMilliseconds())

    const days = Math.round(milliseconds / 86400000)
    const hours = Math.round((milliseconds % 86400000) / 3600000)
    let minutes = Math.round(((milliseconds % 86400000) % 3600000) / 60000)
    const seconds = Math.round(
      (((milliseconds % 86400000) % 3600000) % 60000) / 1000
    )

    if (seconds < 30 && seconds >= 0) {
      minutes += 1
    }

    return {
      days: days.toString().length < 2 ? '0' + days : days,
      hours: hours.toString().length < 2 ? '0' + hours : hours,
      minutes: minutes.toString().length < 2 ? '0' + minutes : minutes,
      seconds: seconds.toString().length < 2 ? '0' + seconds : seconds
    }
  },

  isset(obj) {
    if (obj !== null && obj !== undefined) {
      if (typeof obj === 'object' || Array.isArray(obj)) {
        return Object.keys(obj).length
      } else {
        return obj.toString().length
      }
    }

    return false
  },

  toRaw(obj) {
    return JSON.parse(JSON.stringify(obj))
  },

  randomNumbers(from, to, length) {
    const numbers = [0]
    for (let i = 1; i < length; i++) {
      numbers.push(Math.ceil(Math.random() * (from - to) + to))
    }

    return numbers
  },

  toRGB(colors) {
    const tempColors = Object.assign({}, colors)
    const rgbColors = Object.entries(tempColors)
    for (const [key, value] of rgbColors) {
      if (typeof value === 'string') {
        if (value.replace('#', '').length == 6) {
          const aRgbHex = value.replace('#', '').match(/.{1,2}/g)
          tempColors[key] = (opacity = 1) =>
            `rgb(${parseInt(aRgbHex[0], 16)} ${parseInt(
              aRgbHex[1],
              16
            )} ${parseInt(aRgbHex[2], 16)} / ${opacity})`
        }
      } else {
        tempColors[key] = helpers.toRGB(value)
      }
    }
    return tempColors
  },

  truncateDecimals(value, decimals, symbol = '') {
    const num = Number(value)
    if (isNaN(num)) {
      alert(num + ' is not a valid number')
    } else {
      // str can be converted to a valid floating-point number

      // if(config === 'round')
      //   return (Math.round(num * 100) / 100).toFixed(decimals);

      return num.toFixed(decimals) + symbol
    }
  },

  formatterSquareMeter(value) {
    return (
      new Intl.NumberFormat('nl-BE', {
        style: 'unit',
        unit: 'meter',
        minimumFractionDigits: 2,  // Ensure two decimal digits
        maximumFractionDigits: 2,  // Ensure two decimal digits
      }).format(value) + '\u00B2'
    )
  },
  
  formatterCurrency(amount) {
    return new Intl.NumberFormat('nl-BE', {
      //fr-FR adds the € after the number.
      style: 'currency',
      currency: 'EUR'
      // These options are needed to round to whole numbers if that's what you want.
      //minimumFractionDigits: 0, // (this suffices for whole numbers, but will print 2500.10 as $2,500.1)
      //maximumFractionDigits: 0, // (causes 2500.99 to be printed as $2,501)
    }).format(amount)
  },

  formatterPercent(value) {
    return new Intl.NumberFormat('nl-BE', {style: 'percent'}).format(
      value / 100
    )
  },

  formatDecimal(decimal, format) {
    return new Intl.NumberFormat(format, {
      style: 'decimal',
      minimumFractionDigits: 2
    }).format(decimal)
  },

  isDecimalWithDot(value) {
    const decimalRegex = /^\d+\.\d+$/
    return decimalRegex.test(value.toString())
  },

  getLastPart(str, splitter) {
    return str.split(splitter).pop()
  },

  isNumber(number) {
    // Check if the number is a valid number.
    return !isNaN(number) || typeof number == 'number'
  },

  hasRole(obj, role) {
    // Use Object.values to get an array of the values in the roles object
    return Object.keys(obj.roles).includes(role)
  }
}
export {helpers as helper}

export function cn(...inputs) {
  return twMerge(clsx(inputs))
}
