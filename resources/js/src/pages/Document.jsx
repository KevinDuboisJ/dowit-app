import { DocumentTable, DocumentColumns } from '@/components';

const Document = ({ documents }) => {

  return (
    <div className='flex flex-col h-full'>
      <div className="flex mb-2 shrink-0 items-center flex-nowrap col-span-12 fadeInUp">
        <h2 className="text-lg font-medium justify-start">Documenten</h2>
      </div>
      <DocumentTable columns={DocumentColumns} data={documents} />
    </div>
  )
}

export default Document;