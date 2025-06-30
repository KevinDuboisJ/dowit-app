import { useState, useEffect } from 'react'
import axios from "axios";
import {
  Heroicon,
  Badge,
  Input,
  Loader,
} from '@/base-components';

const PatientAutocomplete = ({ onValueChange = null }) => {

  const [selectedPatient, setSelectedPatient] = useState({});
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    return () => {
      // Cleanup logic (runs on unmount)
      if (onValueChange) {
        onValueChange({})
      }
    };
  }, []);


  const clear = () => {

    setSelectedPatient({});

    if (onValueChange) {
      onValueChange({})
    }
  };

  const handlePatientSearch = async (visitId) => {

    if (visitId.length === 8) {
      // Set loading to true before starting the fetch
      setLoading(true);

      try {
        const response = await axios.post(
          `/patient/visitid`,
          {
            visitId: visitId,
          });

        if (response.status === 200) {

          setSelectedPatient(response.data);
          onValueChange(response.data)

        } else {
          console.warn('Unexpected response status:', response.status);
        }

      } catch (error) {
        console.error('Failed to update row:', error);
      }
      finally {
        // Set loading to false after the fetch is complete
        setLoading(false);
      }
    }
  }

  if (loading) {
    return (
      <Badge className="flex items-center justify-between text-sm text-slate-500 font-normal h-8 bg-white" variant="outline">
        <Loader />
        <Heroicon
          icon="XMark"
          className="w-3 h-3 ml-2 font-normal text-slate-500 cursor-pointer"
          onClick={clear}
        />
      </Badge>
    )
  }

  if (!loading && Object.keys(selectedPatient).length > 0) {
    return (
      <Badge className="flex items-center justify-between text-sm text-slate-500 font-normal h-8 bg-white" variant="outline">
        {`${selectedPatient.firstname} ${selectedPatient.lastname} (${selectedPatient.birthdate}) (${selectedPatient.gender}) - ${selectedPatient.room_number}, ${selectedPatient.bed_number}`}
        <Heroicon
          icon="XMark"
          className="w-3 h-3 ml-2 font-normal text-slate-500 cursor-pointer"
          onClick={clear}
        />
      </Badge>
    )
  }

  return (
    <Input
      type="text"
      className="text-sm text-slate-500 bg-white"
      placeholder="Voer het patiënt opnamenummers in (8 cijfers)"
      maxLength={8}
      autoComplete="off"
      onChange={(e) => {
        handlePatientSearch(e.target.value); // Trigger patient search
      }}
    />
  );
}

PatientAutocomplete.displayName = "PatientAutocomplete"

export { PatientAutocomplete }