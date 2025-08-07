import {isValidElement, useState, useMemo, useEffect} from 'react'
import {format, parseISO} from 'date-fns'
import {__} from '@/stores'
import {HiHandRaised} from 'react-icons/hi2'
import Lottie from 'lottie-react'
import fireAnimation from '@json/fire'
import {cn} from '@/utils'
import {usePage} from '@inertiajs/react'
import axios from 'axios'
import {
  Lucide,
  Heroicon,
  TabsContent,
  Separator,
  AvatarStackWrap,
  AvatarStackHeader,
  AvatarStack,
  Badge,
  RichText,
  Loader
} from '@/base-components'

import {getPriority, TaskActivity, TaskIcon, PriorityText} from '@/components'

export const TaskDetails = ({task}) => {
  const {user, settings} = usePage().props
  const priorityObj = getPriority(
    task.created_at,
    task.priority,
    settings.TASK_PRIORITY.value
  )

  const [comments, setComments] = useState([])
  const [loadingComments, setLoadingComments] = useState(true)

  useEffect(() => {
    if (!task?.id) return

    axios
      .get(`/tasks/${task.id}/comments`)
      .then(res => {
        console.log(res.data)
        setComments(res.data)
      })
      .catch(err => {
        console.error('Failed to load comments', err)
      })
      .finally(() => {
        // always runs, success or error
        setLoadingComments(false)
      })
  }, [task?.id]) // runs when opening a new task

  const opacity = useMemo(() => {
    const firstComment = task.comments?.[0]
    if (!firstComment) return 0

    const createdAt = new Date(firstComment.created_at)
    const now = new Date()
    const timeDiff = Math.floor((now - createdAt) / (1000 * 60 * 60 * 24))
    return timeDiff >= 5 ? 0 : 1 - timeDiff * 0.2
  }, [task.comments])

  return (
    <TabsContent className="p-6 py-4 fadeInUp" value="details">
      <div className="flex items-center space-x-2">
        <Badge className="rounded-xl" variant={task.status.name}>
          {__(task.status.name)}
        </Badge>

        {task?.tags.map(tag => (
          <Badge
            key={tag.id}
            className="rounded-xl"
            style={{backgroundColor: tag.bg_color}}
          >
            <TaskIcon iconName={tag.icon} />
            {tag.name}
          </Badge>
        ))}
      </div>

      <div className="space-y-5 my-2">
        <div className="space-y-3 border rounded-lg p-4 bg-white shadow-xs dark:bg-darkmode-500 dark:border-darkmode-400">
          <RichText text={task.description} className="text-sm text-gray-900" />

          <InfoRow
            icon={
              <Heroicon
                icon="Flag"
                variant="solid"
                className="w-4 h-4 text-slate-400"
              />
            }
            label="Prioriteit"
            value={
              <PriorityText
                state={priorityObj.state}
                color={priorityObj.color}
              />
            }
          />

          <InfoRow
            icon={
              <Heroicon
                icon="CalendarDays"
                variant="solid"
                className="w-4 h-4 text-slate-400"
              />
            }
            label="Tijd:"
            value={format(parseISO(task.start_date_time), 'PP HH:mm')}
          />

          {task?.visit && (
            <InfoRow
              icon={
                <Heroicon
                  icon="UserCircle"
                  variant="solid"
                  className="w-4 h-4 text-slate-400"
                />
              }
              label="Wie:"
              value={`${task.visit?.patient?.firstname} ${task.visit?.patient?.lastname} (${task.visit?.patient?.gender}) - ${task.visit?.bed?.room?.number}, ${task.visit?.bed?.number}`}
            />
          )}

          {task.space && (
            <InfoRow
              icon={
                <Heroicon
                  icon="MapPin"
                  variant="solid"
                  className="w-4 h-4 text-slate-400"
                />
              }
              label="Van:"
              value={task.space.name}
            />
          )}

          {task?.spaceTo && (
            <InfoRow
              icon={
                <Heroicon
                  icon="MapPin"
                  variant="solid"
                  className="w-4 h-4 text-slate-400"
                />
              }
              label="Naar:"
              value={task.spaceTo.name}
            />
          )}

          <InfoRow
            icon={
              <Heroicon
                icon="UserGroup"
                variant="solid"
                className="w-4 h-4 text-slate-400"
              />
            }
            label="Teams:"
            value={<TeamTag user={user} teams={task?.teams} />}
          />

          <InfoRow
            icon={<HiHandRaised className="w-4 h-4 text-slate-400" />}
            label="Collega nodig:"
            value={task.needs_help ? 'Ja' : 'Nee'}
          />

          <Separator className="bg-slate-200/60 dark:bg-darkmode-400" />

          {task.assignees.length > 0 ? (
            <AvatarStackWrap>
              <AvatarStackHeader />
              <AvatarStack
                avatars={task.assignees}
                maxAvatars={16}
                className="w-10 h-10"
              />
            </AvatarStackWrap>
          ) : (
            <span className="text-sm font-medium italic">
              Geen persoon toegewezen
            </span>
          )}
        </div>

        <AssetList assets={task?.task_type?.assets} />

        <div>
          <div className="flex text-base font-medium">
            Historiek
            {opacity > 0 && (
              <Lottie
                className="w-5 h-5 cursor-help"
                title="Er is de afgelopen 5 dagen activiteit geweest"
                animationData={fireAnimation}
                loop={true}
                style={{opacity}}
              />
            )}
          </div>

          {loadingComments ? (
            <div className="flex pt-8 items-center justify-center">
              <Loader />
            </div>
          ) : (
            <TaskActivity comments={comments} status={task.status.name} />
          )}
        </div>
      </div>
    </TabsContent>
  )
}

const InfoRow = ({
  icon = null,
  label,
  value,
  minWidth = '150px',
  className,
  style
}) => {
  return (
    <div
      style={style}
      className={cn(
        'flex flex-col sm:flex-row sm:items-center text-gray-900 gap-1 sm:gap-0',
        className
      )}
    >
      {/* Left Section: Icon + Label */}
      <div
        style={{minWidth: minWidth}}
        className="flex items-center space-x-1 min-w-0"
      >
        {icon}
        <span className="text-sm text-slate-500">{label}</span>
      </div>

      {/* Right Section: Value */}
      {isValidElement(value) ? value : <span className="text-sm">{value}</span>}
    </div>
  )
}

const TeamTag = ({user, teams}) => {
  // Ensure `teams` is always an array
  teams = teams || []

  if (!Object.values(user.roles).includes('SUPER_ADMIN')) {
    teams = teams.filter(team => team.name !== 'Reserve')
  }

  return teams.length > 0 ? (
    teams.map((team, index) => (
      <span
        key={team.id}
        className={cn(
          {'ml-0': index === 0, 'ml-2': index > 0},
          'text-sm text-gray-900 rounded-sm'
        )}
      >
        {team.name}
      </span>
    ))
  ) : (
    <span className="text-sm font-medium">
      Deze taak is niet gekoppeld aan een team
    </span>
  )
}

const AssetList = ({assets}) => {
  // Ensure `assets` is always an array
  assets = assets || []

  // State to track selected asset
  const [selectedAsset, setSelectedAsset] = useState(null)

  // Don't render the component at all if no assets
  if (assets.length === 0) return null

  return (
    <div>
      <div className="flex text-base font-medium">Bestanden</div>
      <div className="flex flex-wrap items-center">
        {assets?.length > 0 ? (
          assets.map(asset => (
            <div
              key={asset.id}
              className="opacity-70 text-sm p-[6px] w-full text-slate-800 font-normal rounded-lg border bg-yellow-50 flex items-center justify-between cursor-pointer"
              onClick={() => setSelectedAsset(asset.link)} // Clicking anywhere opens the iframe
            >
              {/* Asset Name & Icon */}
              <div className="flex items-center">
                <Lucide
                  icon="FileText"
                  className="w-[14px] h-[14px] text-slate-800 mr-1"
                />
                {asset.name}
              </div>

              {/* Open in New Tab Button */}
              {/* <button
              onClick={(e) => {
                e.stopPropagation(); // Prevent the main div's click event (don't open iframe)
                window.open(asset.link, "_blank");
              }}
              className="text-blue-500 hover:text-blue-700 transition"
              title="Open in new tab"
            >
              🔗
            </button> */}
            </div>
          ))
        ) : (
          <span className="text-sm font-medium ml-4">
            Dit taaktype heeft geen bestanden
          </span>
        )}

        {/* Show iframe if a asset is selected */}
        {selectedAsset && (
          <div className="mt-4 w-full p-4 bg-gray-100 rounded-lg border relative">
            {/* Close Button */}
            <button
              onClick={() => setSelectedAsset(null)}
              className="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition"
            >
              Sluiten ✖
            </button>

            {/* Iframe Container */}
            <iframe
              src={selectedAsset}
              className="w-full h-[500px] border rounded-lg"
              title="Bestand viewer"
            />
          </div>
        )}
      </div>
    </div>
  )
}
