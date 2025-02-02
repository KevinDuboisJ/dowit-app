const taskStatus = {
  Added: '1',
  Replaced: '2',
  InProgress: '4',
  WaitingForSomeone: '5',
  Completed: '6',
  Skipped: '12'
};

export function enum(word) {
  return NL[word] ?? 'onbekend';
}

