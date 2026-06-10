import { router, usePage } from '@inertiajs/react'
import { useMemo, useState } from 'react'
import {
  Badge,
  Button,
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  Lucide
} from '@/base-components'
import Logo from '@images/logo.png'

const SelectDevice = ({ devices = [], selectedDeviceId = null, isSwitching = false }) => {
  const { errors } = usePage().props
  const [deviceId, setDeviceId] = useState(selectedDeviceId)
  const [processing, setProcessing] = useState(false)

  const selectedDevice = useMemo(
    () => devices.find(device => device.id === Number(deviceId)),
    [devices, deviceId]
  )

  function handleSubmit(event) {
    event.preventDefault()

    if (!deviceId) return

    setProcessing(true)

    router.post(
      '/select-device',
      {
        device_id: deviceId,
        switch: isSwitching
      },
      {
        onFinish: () => setProcessing(false)
      }
    )
  }

  return (
    <main className="min-h-screen bg-slate-50 px-4 py-6 flex items-center justify-center">
      <Card className="w-full max-w-3xl shadow-sm">
        <CardHeader className="space-y-4">
          <div className="flex items-center gap-3">
            <img alt="Dowit" src={Logo} className="h-10 w-auto" />
            <div>
              <CardTitle className="text-xl text-slate-900">
                {isSwitching ? 'Poetskar wisselen' : 'Selecteer je poetskar'}
              </CardTitle>
              <CardDescription>
                Kies het toestel dat je vandaag gebruikt. Deze keuze wordt bijgehouden voor de huidige sessie.
              </CardDescription>
            </div>
          </div>
        </CardHeader>

        <form onSubmit={handleSubmit}>
          <CardContent className="space-y-4">
            {errors?.device_id && (
              <div className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                {errors.device_id}
              </div>
            )}

            {devices.length === 0 ? (
              <div className="rounded-md border border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-600">
                Er zijn momenteel geen geregistreerde toestellen beschikbaar.
              </div>
            ) : (
              <div className="grid gap-3 sm:grid-cols-2">
                {devices.map(device => {
                  const isSelected = Number(deviceId) === device.id

                  return (
                    <button
                      key={device.id}
                      type="button"
                      aria-pressed={isSelected}
                      onClick={() => setDeviceId(device.id)}
                      className={`rounded-xl border bg-white p-4 text-left transition focus:outline-none focus:ring-2 focus:ring-primary ${
                        isSelected
                          ? 'border-primary ring-1 ring-primary'
                          : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50'
                      }`}
                    >
                      <div className="flex items-start justify-between gap-3">
                        <div>
                          <div className="flex items-center gap-2 font-medium text-slate-900">
                            <Lucide icon={device.type === 'gsm' ? 'Smartphone' : 'ShoppingCart'} className="h-4 w-4" />
                            {device.identifier}
                          </div>
                          {device.description && (
                            <p className="mt-2 text-sm text-slate-500">{device.description}</p>
                          )}
                        </div>

                        <Badge variant={isSelected ? 'default' : 'outline'}>
                          {device.type === 'gsm' ? 'GSM' : 'Poetskar'}
                        </Badge>
                      </div>
                    </button>
                  )
                })}
              </div>
            )}

            {selectedDevice && (
              <div className="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600">
                Geselecteerd: <span className="font-medium text-slate-900">{selectedDevice.identifier}</span>
              </div>
            )}

            <div className="flex justify-end gap-2 pt-2">
              <Button type="submit" disabled={!deviceId || processing || devices.length === 0}>
                {processing ? 'Opslaan...' : isSwitching ? 'Wissel poetskar' : 'Verdergaan'}
              </Button>
            </div>
          </CardContent>
        </form>
      </Card>
    </main>
  )
}

export default SelectDevice
