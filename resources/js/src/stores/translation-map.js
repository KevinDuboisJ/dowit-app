const NL = {
  'added': 'Toegevoegd',
  'scheduled': 'Gepland',
  'replaced': 'Vervangen',
  'inprogress': 'In verwerking',
  'waitingforsomeone': 'Wacht op iemand',
  'completed': 'Afgehandeld',
  'skipped': 'Overgeslagen',
  'low': 'Laag',
  'medium': 'Gemiddeld',
  'high': 'Hoog',
  'status': 'Status',
  'needs_help': 'Collega nodig',
  'priority': 'prioriteit',
};

export function __(word) {
  return NL[String(word || '').toLowerCase()] ?? 'onbekend';
}

