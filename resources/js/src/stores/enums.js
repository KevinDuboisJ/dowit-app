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
  PatientTransportInWheelchair: 4,
  PatientTransportOnFootAssisted: 5,
  PatientTransportNotify: 6,
  PatientTransportWithCrutches: 7
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
