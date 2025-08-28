export const taskStatusEnum = {
  Added: '1',
  Replaced: '2',
  Scheduled: '3',
  InProgress: '4',
  WaitingForSomeone: '5',
  Completed: '6',
  Skipped: '12'
}

export const taskTypeEnum = {
  PatientTransportInBed: 1,
  PatientTransportInWheelchair: 6,
  PatientTransportOnFootAssisted: 7,
  PatientTransportNotify: 8,
  PatientTransportWithCrutches: 9
}

export function isPatientTransportTask(taskType) {
  const taskTypes = [
    taskTypeEnum.PatientTransportInBed,
    taskTypeEnum.PatientTransportInWheelchair,
    taskTypeEnum.PatientTransportOnFootAssisted,
    taskTypeEnum.PatientTransportNotify,
    taskTypeEnum.PatientTransportWithCrutches
  ]

  return taskTypes.includes(parseInt(taskType))
}