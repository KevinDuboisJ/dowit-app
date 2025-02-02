const colors = {
  Added: "slate-100",
  InProgress: "bg-sky-100",
  Completed: "success",
  Skipped: "orange-400",
};

export function getColor(word) {
  return colors[word] ?? 'default';
}

const variants = {
  Added: "outline",
  InProgress: "progress",
  Completed: "complete",
  Skipped: "destructive",
};

export function getVariant(word) {
  return variants[word] ?? 'default';
}