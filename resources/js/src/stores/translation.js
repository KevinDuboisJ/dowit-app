const NL = {
  added: 'Toegevoegd',
  scheduled: 'Gepland',
  replaced: 'Vervangen',
  inprogress: 'In verwerking',
  waitingforsomeone: 'Wacht op iemand',
  completed: 'Afgehandeld',
  skipped: 'Overgeslagen',
  rejected: 'Afgewezen',
  low: 'Laag',
  medium: 'Gemiddeld',
  high: 'Hoog',
  status: 'Status',
  needs_help: 'Collega nodig',
  priority: 'Prioriteit',
  assignees: 'Toegewezen personen',
  patienttransportinbed: 'Patiëntentransport - in bed',
  cleaning: 'Poets',
  periodiccheck: 'Periodieke controle',
  securityguardtask: 'Taak bewaking',
  endofstaycleaning: 'Eindpoets',
  patienttransportinwheelchair: 'Patiëntentransport - in rolstoel',
  patienttransportonfootassisted: 'Patiëntentransport - te voet begeleid',
  patienttransportnotify: 'Patiëntentransport - verwittigen',
  patienttransportwithcrutches: 'Patiëntentransport - met krukken'
}

export function __(word) {
  return NL[String(word || '').toLowerCase()] ?? 'onbekend'
}
