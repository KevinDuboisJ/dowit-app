import { useState, useEffect } from 'react'
import Switch from '@mui/material/Switch'
import ToggleButton from '@mui/material/ToggleButton'
import ToggleButtonGroup from '@mui/material/ToggleButtonGroup'
import makeAnimated from 'react-select/animated'
import Box from '@mui/material/Box'
import TextField from '@mui/material/TextField'
import Autocomplete, { createFilterOptions } from '@mui/material/Autocomplete'
import Stack from '@mui/material/Stack'
import Slider from '@mui/material/Slider'
import Typography from '@mui/material/Typography'
import { debounce } from 'lodash';

const animatedComponents = makeAnimated()

const customStyles = {
  control: base => ({
    ...base,
    minHeight: 26,
    boxShadow: 'none',
    '&:hover': {
      border: '1px solid #006062'
    },
    '&:focus': {
      borderColor: '#006062'
    },
    '&:active': {
      borderColor: '#006062'
    }
  }),
  valueContainer: (provided, value) => ({
    ...provided,
    padding: 0,
    height: '100%',
    alignItems: 'center'
  }),
  dropdownIndicator: styles => ({
    ...styles,
    padding: 0
  }),
  clearIndicator: styles => ({
    ...styles,
    padding: 0
  }),
  multiValueLabel: styles => ({
    ...styles,
    padding: 0
  }),
  option: (provided, value) => ({
    ...provided,
    borderBottom: '1px solid #f5f5f5',
    color: value.isSelected ? 'red' : '#006062',
    padding: 20
  }),
  // input: (styles) => ({ ...styles, ...dot() }),
  singleValue: (provided, value) => {
    const opacity = value.isDisabled ? 0.5 : 1
    const transition = 'opacity 300ms'

    return { ...provided, opacity, transition }
  }
}

export function Text({ label, value, setValue }) {
    return (
      <TextField
        size="small"
        id="outlined-basic"
        label={label}
        variant="outlined"
        value={value || ''}
        onChange={e => setValue(e.target.value)}
      />
    )
  }

export function Date({ label, value, setValue }) {
  return (
    <input
      label={label}
      type="date"
      value={value || ''}
      onChange={e => setValue(e.target.value)}
    />
  )
}
export function DateTimeInput({ value, setValue }) {
  return (
    <input
      type="date"
      value={value || ''}
      onChange={e => setValue(e.target.value)}
    />
  )
}


const fetchOptions = async (search, model, setShowOptions) => {

  const response = await fetch('api/options?search=' + encodeURIComponent(search) + '&modal=' + model + '', {
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
  if (data)
    setShowOptions(data);

};

export function AutocompleteSelect({ label, options, value, setValue, model, config }) {
  const [showOptions, setShowOptions] = useState(options);
  const [userInput, setUserInput] = useState('');

  if (config.fetch) {
    useEffect(() => {
      if (!userInput.trim()) return;
      fetchOptions(userInput, model, setShowOptions);
    }, [userInput]);

    if (value) {
      // Check if the value exist in the options
      const valueExists = showOptions.some((option) => option.id === value.id);

      // If the value doesn't exist in the options, add it to avoid MUI optionEqual error.
      if (!valueExists) {
        setShowOptions([...options, value]);
      }

    }
  }

  const handleKeyUpChange = debounce((event) => { setUserInput(event.target.value); }, 200)  // To avoid 429 (Too Many Requests) error.
  const handleChange = (event, newValue) => {
    setValue(newValue);
  }

  return (
    <Stack spacing={3} sx={{ width: 300 }}>
      <Autocomplete
        multiple={config.multiple}
        id="tags-standard"
        options={showOptions}
        value={value ? value : config.multiple ? [] : null}
        getOptionLabel={(option) => option.name}
        isOptionEqualToValue={(option, value) => option.id === value.id}
        renderInput={params => <TextField {...params} label={label} />}
        renderOption={(props, option) => (
          <li {...props} key={option.name}>
            {option.name}
          </li>
        )}
        noOptionsText={'Geen opties'}
        onChange={handleChange}
        onKeyUp={handleKeyUpChange}
        size="small"
      />
    </Stack>
  )
}

export function SwitchButton({ value, setValue }) {
  return (
    <Switch
      className="MenuItem"
      checked={value || false}
      onChange={e => setValue(e.target.checked)}
    />
  )
}

export default function BasicButtonGroup({ value, setValue }) {
  const handleChange = (event, newAlignment) => {
    if (newAlignment !== null) {
      setValue(newAlignment)
    }
  }

  return (
    <ToggleButtonGroup
      className="azmonicaToggleButton"
      value={value || 2}
      exclusive
      onChange={handleChange}
      sx={{
        width: 150,
        height: 26
      }}
    >
      <ToggleButton className="azmonicaToggleButton" value={1} sx={{ width: 75 }}>
        Ja
      </ToggleButton>
      <ToggleButton className="azmonicaToggleButton" value={2} sx={{ width: 75 }}>
        Nee
      </ToggleButton>
    </ToggleButtonGroup>
  )
}

export function Decimal({ label, value, setValue }) {
  return (
    <TextField id="outlined-basic" label={label} variant="outlined" value={value || ''} onChange={e => allowOnlyNumbers(e.target.value, setValue)} size="small" />
  )
}

export function Percentage({ label, value, setValue }) {
  return (
    <TextField id="outlined-basic" label={label + ' %'} variant="outlined" value={value ? value : ''} onChange={e => allowOnlyNumbers(e.target.value, setValue)} size="small" />
  )
}

export function DecimalSlider({ label, value, setValue, config }) {

  let displayValue = value ?? '0';
  if (config.multiplier)
    displayValue = displayValue * config.multiplier;
  return (
    <Box sx={{ width: 150 }}>
      <Typography id="input-slider" gutterBottom sx={{ mb: 0, mt: 0 }}>
        {`${label}: ${displayValue}  ${config.char || ''}`}
      </Typography>
      <Slider

        value={value || 0}
        step={config.step || 10}
        min={config.min || 0}
        max={config.max || 100}
        onChange={e => setValue(e.target.value)}
        size="small"
        sx={{ pb: 1, pt: 1 }}
      />
    </Box>
  )
}

const allowOnlyNumbers = (value, setValue) => {
  const pattern = /^[0-9,.\bA-Z]+$/;
  if (value === '' || pattern.test(value)) setValue(value);
}

export function Input(props) {

  switch (props.type) {
    case 'string':
      return <Text {...props} />
    case 'date':
      return <Date {...props} />
    case 'datetime':
      return <Date {...props} />
    case 'autocomplete':
      return <AutocompleteSelect {...props} />
    case 'switchButton':
    case 'boolean':
      return <SwitchButton {...props} />
    case 'BasicButtonGroup':
      return <BasicButtonGroup {...props} />
    case 'decimal':
      return <Decimal {...props} />
    case 'percentage':
      return <Percentage {...props} />
    default:
      return null
  }
}
