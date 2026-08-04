import { Link, router, usePage } from '@inertiajs/react'
import { useState } from 'react'
import {
  Button,
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue
} from '@/base-components'
import Logo from '@images/logo.png'

const SelectDevice = ({
  devices = [],
  selectedDeviceId = null,
  isSwitching = false
}) => {
  const { errors } = usePage().props

  const [deviceId, setDeviceId] = useState(
    selectedDeviceId ? String(selectedDeviceId) : ''
  )

  const [processing, setProcessing] = useState(false)

  function handleSubmit(event) {
    event.preventDefault()

    if (!deviceId) return

    setProcessing(true)

    router.post(
      '/select-device',
      {
        device_id: Number(deviceId),
        switch: isSwitching
      },
      {
        onFinish: () => setProcessing(false)
      }
    )
  }

  return (
    <main className="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100 px-4 py-8 flex items-center justify-center">
      <Card className="w-full max-w-md min-h-[560px] border-slate-200 shadow-lg shadow-slate-200/70 rounded-2xl flex flex-col">
        <CardHeader className="text-center space-y-5 pt-10">
          <div className="mx-auto flex items-center justify-center">
            <img
              alt="Dowit"
              src={Logo}
              className="max-h-10 max-w-full object-contain"
            />
          </div>

          <div className="space-y-2">
          
          </div>
        </CardHeader>

        <form onSubmit={handleSubmit} className="flex flex-1 flex-col">
          <CardContent className="flex flex-1 flex-col justify-between space-y-8 px-6 pb-8">
            <div className="space-y-5">
              {errors?.device_id && (
                <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                  {errors.device_id}
                </div>
              )}

              {devices.length === 0 ? (
                <div className="rounded-xl border border-dashed border-slate-300 bg-white px-5 py-10 text-center">
                  <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                    !
                  </div>

                  <p className="text-sm font-medium text-slate-700">
                    Geen toestellen beschikbaar
                  </p>

                  <p className="mt-1 text-sm text-slate-500">
                    Er zijn momenteel geen geregistreerde toestellen
                    beschikbaar.
                  </p>
                </div>
              ) : (
                <div className="space-y-3">

                  <Select
                    value={deviceId}
                    onValueChange={setDeviceId}
                    disabled={processing}
                  >
                    <SelectTrigger
                      id="device_id"
                      className="h-12 w-full rounded-xl bg-white text-left"
                    >
                      <SelectValue placeholder="Selecteer een poetskar" />
                    </SelectTrigger>

                    <SelectContent
                      position="popper"
                      side="bottom"
                      align="start"
                      sideOffset={6}
                      avoidCollisions={false}
                      className="max-h-64 overflow-y-auto rounded-xl"
                    >
                      {devices.map(device => (
                        <SelectItem key={device.id} value={String(device.id)}>
                          <span className="flex flex-col py-1">
                            <span className="font-medium">
                              {device.identifier}
                            </span>
                          </span>
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>

                  <p className="text-xs leading-relaxed text-slate-500">
                    Deze selectie bepaalt met welke poetskar je verder werkt in Dowit.
                  </p>
                </div>
              )}
            </div>

            <div className="space-y-3">
              <Button
                type="submit"
                className="h-12 w-full rounded-xl text-base font-medium"
                disabled={!deviceId || processing || devices.length === 0}
              >
                {processing
                  ? 'Opslaan...'
                  : isSwitching
                    ? 'Poetskar wisselen'
                    : 'Verdergaan'}
              </Button>

              <Link
                href="/logout"
                method="post"
                as="button"
                type="button"
                className="flex h-12 w-full items-center justify-center rounded-xl border border-slate-300 bg-white text-base font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
              >
                Uitloggen
              </Link>

              {isSwitching && (
                <p className="text-center text-xs text-slate-500">
                  Je vorige poetskar wordt vervangen door de nieuwe selectie.
                </p>
              )}
            </div>
          </CardContent>
        </form>
      </Card>
    </main>
  )
}

export default SelectDevice
