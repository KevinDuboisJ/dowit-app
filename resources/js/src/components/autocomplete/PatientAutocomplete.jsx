import { useState, useEffect, useRef } from 'react'
import axios from 'axios'
import { Input, Loader } from '@/base-components'
import { XIcon } from 'lucide-react'

const PatientAutocomplete = ({ onValueChange = null }) => {
  const [searchValue, setSearchValue] = useState('')
  const [visitList, setVisitList] = useState([])
  const [loading, setLoading] = useState(false)
  const [selectedVisit, setSelectedVisit] = useState({})
  const [showDropdown, setShowDropdown] = useState(false)
  const containerRef = useRef(null)

  useEffect(() => {
    return () => {
      if (onValueChange) {
        onValueChange({})
      }
    }
  }, [])

  useEffect(() => {
    const handleClickOutside = e => {
      if (containerRef.current && !containerRef.current.contains(e.target)) {
        setShowDropdown(false)
      }
    }

    document.addEventListener('mousedown', handleClickOutside)
    return () => document.removeEventListener('mousedown', handleClickOutside)
  }, [])

  const clear = () => {
    setSearchValue('')
    setSelectedVisit({})
    setVisitList([])
    setShowDropdown(false)
    if (onValueChange) onValueChange({})
  }

  const formatPatientDisplay = visit => {
    if (!visit?.patient) return ''

    const { firstname = '', lastname = '', gender } = visit.patient
    const room = visit.bed?.room?.number ?? ''
    const bed = visit.bed?.number ?? ''

    const name = `${firstname} ${lastname}`.trim()
    const genderLabel = gender ? ` (${gender})` : ''
    const bedLabel = room || bed ? ` - ${room}, ${bed}` : ''

    return `${name}${genderLabel}${bedLabel}`
  }

  const handleSearch = async value => {
    setSearchValue(value)
    setSelectedVisit({})

    if (value.length === 8 || (isNaN(value) && value.length > 2)) {
      setLoading(true)
      try {
        const { data } = await axios.post('/visit/search', { search: value })

        if (Array.isArray(data)) {
          setVisitList(data)
          setShowDropdown(true)
        } else if (data && typeof data === 'object') {
          setVisitList([data])
          setShowDropdown(false)
          setSelectedVisit(data)
          if (onValueChange) onValueChange(data)
        } else {
          setVisitList([])
        }
      } catch (err) {
        console.error(err)
      } finally {
        setLoading(false)
      }
    } else {
      setVisitList([])
      setShowDropdown(false)
    }
  }

  const handleSelect = visit => {
    setSelectedVisit(visit)
    setSearchValue(formatPatientDisplay(visit))
    setShowDropdown(false)
    if (onValueChange) onValueChange(visit)
  }

  return (
    <div className="relative" ref={containerRef}>
      <Input
        type="text"
        className="w-full text-sm bg-white"
        placeholder="Zoek een patiënt (naam of opnamenummer)"
        maxLength={searchValue.match(/^\d+$/) ? 8 : undefined}
        autoComplete="off"
        value={searchValue}
        onChange={e => handleSearch(e.target.value)}
      />

      {/* Loader */}
      {loading && (
        <Loader className='absolute top-[6px] right-2' size={32} />
      )}

      {searchValue && !loading && (
        <button
          type="button"
          tabIndex={-1}
          className=""
          onClick={clear}
        >
          <XIcon className="h-4 w-4 absolute top-3 right-4 flex items-center justify-center text-slate-300 hover:text-red-600" />
        </button>
      )}

      {/* Dropdown */}
      {showDropdown && visitList.length > 0 && (
        <div className="absolute z-10 w-full bg-white border border-gray-300 rounded mt-1 max-h-48 overflow-y-auto">
          {visitList.map((visit, index) => (
            <div
              key={index}
              className="px-4 py-2 hover:bg-gray-100 cursor-pointer text-sm"
              onClick={() => handleSelect(visit)}
            >
              {formatPatientDisplay(visit)}
            </div>
          ))}
        </div>
      )}
    </div>
  )
}

PatientAutocomplete.displayName = 'PatientAutocomplete'

export { PatientAutocomplete }
