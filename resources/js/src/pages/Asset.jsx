import { PaginationBar } from '@/base-components'
import { AssetTable, AssetColumns } from '@/components'

const Asset = ({ assets }) => {
  return (
    <div className="flex flex-col h-full">
      <div className="flex-1 p-4">
        <div className="flex mb-2 shrink-0 items-center flex-nowrap col-span-12 fadeInUp">
          <h2 className="text-lg font-medium justify-start">Bestanden</h2>
        </div>
        <AssetTable columns={AssetColumns} data={assets} />
      </div>
      <PaginationBar {...assets} />
    </div>
  )
}

export default Asset
