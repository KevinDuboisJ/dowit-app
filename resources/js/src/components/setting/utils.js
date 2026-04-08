// Determines the task priority based on how much time has passed since the task was created.
// If a taskPriority is explicitly provided, it uses that priority and its configured color.
// Otherwise, it calculates the elapsed time and compares it against the configured time
// windows for each priority (sorted from lowest to highest). The first matching time window
// determines the priority and color. If none match, the default priority is returned.

import { __ } from '@/stores'

export const getPriority = (createdAt, taskPriority, setting) => {
  // Default color if no time window matches
  const data = { state: __('High'), color: '#000000' }
  const createdAtTime = new Date(createdAt).getTime()
  const currentTime = new Date().getTime()

  // Use task priority if it is defined
  if (taskPriority) {
    data.state = __(taskPriority)
    data.color = setting[taskPriority]?.color ?? '#000000'
    return data
  }

  const elapsedTimeSinceTaskCreation = currentTime - createdAtTime
  const sortedPriorities = Object.entries(setting).sort(
    ([, a], [, b]) => Number(a.time) - Number(b.time)
  )

  for (const [priority, config] of sortedPriorities) {
    const timeWindow = Number(config.time) * 60000

    if (elapsedTimeSinceTaskCreation <= timeWindow) {
      data.state = __(priority)
      data.color = config.color
      return data
    }
  }

  return data
}
