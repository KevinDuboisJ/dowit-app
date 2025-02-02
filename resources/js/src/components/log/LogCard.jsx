import { ChevronRightIcon } from "@radix-ui/react-icons"

const map = {
  name: 'Naam',
  campus_id: 'Campus',
  company_id: 'Onderneming',
  SpcFloorNettM2: 'm²',
  space_id: 'Lokaal',
  block: 'Blokken',
  discount: 'Korting',
  comment: 'Opmerking',
  department_ps_number: 'Afdeling',
  space_type_id: 'Ruimte type',
  invoice_type_id: 'Factuurtype',
  start_date: 'Startdatum',
  end_date: 'Einddatum',
  fixed_price: 'Vast bedrag',
  SpcRecCreateDate: 'Aangemaakt op',
}

const LogCard = ({ data }) => {

  return (
  <div className="relative flex items-center py-1">
    <div className="ml-2">
      {data?.columns && Object.keys(data.columns).map((key, index) => { // data?.columns is used to discard logs where there in no columns key.
        return (
          <div key={key + index} className="flex flex-wrap items-center dark:text-slate-200 mt-2 leading-none">
            <span className='font-medium mr-2'>{map[key] ?? key}:</span>
            <span>{data.columns[key].previousState.value ?? 'Leeg'}</span>
            <ChevronRightIcon />
            <span>{data.columns[key].currentState.value ?? 'Leeg'}</span>
          </div>)
      })}
    </div>
  </div>)
}

export default LogCard