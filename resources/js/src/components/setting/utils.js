import { __ } from '@/stores'

export const getPriority = (createdAt, taskPriority, setting) => {

  // Default color if no time window matches
  const data = { state: __('High'), color: '#000000' };
  const createdAtTime = new Date(createdAt).getTime();
  const currentTime = new Date().getTime();

  // Use task priority if it is defined
  if (taskPriority) {
    data.state = __(taskPriority);
    data.color = setting[taskPriority].color;
    return data;
  }

  for (const priority in setting) {

    const elapsedTimeSinceTaskCreation = currentTime - createdAtTime;
    const timeWindow = setting[priority].time * 60000; // Convert seconds to milliseconds

    if (elapsedTimeSinceTaskCreation <= timeWindow) {
      data.state = __(priority); // Return color if within the time window
      data.color = setting[priority].color; // Return color if within the time window

      return data;
    }
  }

  return data;
}