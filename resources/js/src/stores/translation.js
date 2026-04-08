const NL = {
  added: 'Toegevoegd',
  scheduled: 'Gepland',
  replaced: 'Vervangen',
  inprogress: 'In verwerking',
  waiting: 'In afwachting',
  completed: 'Afgehandeld',
  skipped: 'Overgeslagen',
  rejected: 'Afgewezen',
  low: 'Laag',
  medium: 'Gemiddeld',
  high: 'Hoog',
  status: 'Status',
  help_requested: 'Collega nodig',
  priority: 'Prioriteit',
  assignees: 'Toegewezen personen',
  unassignees: 'Niet meer toegewezen personen',
}

export function __(word) {
  if (!word || String(word).trim() === '') {
    return 'onbekend'
  }

  return NL[String(word).toLowerCase()] ?? String(word)
}
