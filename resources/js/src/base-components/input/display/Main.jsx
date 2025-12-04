import { helper } from '@/utils'
import {
  Tooltip,
} from '@/base-components'

function TextValue({ value }) {
  return (value?.length > 25) ? <Tooltip className="cursor-help" options={{ zIndex: 99999 }} content={value}> {helper.cutText(value, 25)} </Tooltip> : <span>{value}</span>
}
function BooleanValue({ value }) {
  return <span>{value === 1 ? '1' : '0'}</span>
}

function JsonValues({ value }) {
  return <span>{value.toString()}</span>
}
function ColorValue({ value }) {
  return <span>{value}</span>
}
function BasicButtonGroupValue({ value }) {
  return (
    <span className="mr-2 rounded-lg bg-green-200 py-1 px-4 text">
      {value === 1 ? 'Ja' : 'Nee'}
    </span>
  )
}

function DateValue({ value }) {
  if (value) {
    const date = new Date(value)
    var dd = String(date.getDate()).padStart(2, '0')
    var mm = String(date.getMonth() + 1).padStart(2, '0') //January is 0!
    var yyyy = date.getFullYear()
    return <span>{dd + '/' + mm + '/' + yyyy}</span>
  }
  return ''
}

function DateTimeValue({ value }) {
  var options = {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: 'numeric',
    minute: 'numeric'
  }
  if (value) {
    const date = new Date(value)
    return <span>{date.toLocaleString('nl', options)}</span>
  }
  return ''
}

function DecimalValue({ value }) {
  return <span>{value ? value : ''}</span>;
}
function PercentageValue({ value }) {
  return <span>{helper.formatterPercent(value)}</span>;
}
function CurrencyValue({ value }) {
  return <span>{helper.formatterCurrency(value)}</span>;
}
function SquareMeterValue({ value }) {
  return <span>{helper.formatterSquareMeter(value)}</span>;
}

function AutocompleteValue({ field, config }) {
  // return <span>{field?.label || ''}</span>
  return <span className={field?.name || field?.label ? "ml-1 text-sm inline-flex items-center font-bold leading-sm uppercase px-3 py-1 rounded-full bg-white text-gray-700 border" : ''}>{field?.name || field?.label || ''}</span>
}

export function InputDisplay({ columnType, value, config }) {
  switch (columnType) {
    case 'string':
      return <TextValue value={value} />
    case 'color_select':
      return <ColorValue value={value} />
    case 'basicButtonGroup':
      return <BasicButtonGroupValue value={value} />
    case 'datetime':
      return <DateTimeValue value={value} />
    case 'date':
      return <DateValue value={value} />
    case 'json':
      return <JsonValues value={value} />
    case 'color':
      return <ColorValue value={value} />
    case 'decimal':
      return <DecimalValue value={value} config={config} />
    case 'percentage':
      return <PercentageValue value={value} config={config} />
    case 'currency':
      return <CurrencyValue value={value} config={config} />
    case 'squareMeter':
      return <SquareMeterValue value={value} config={config} />
    case 'autocomplete':
      return <AutocompleteValue field={value} config={config} />
    case 'boolean':
      return <AutocompleteValue field={value} config={config} />

    default:
      return null
  }
}
