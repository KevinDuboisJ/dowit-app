const colors = {
  Waiting: "text-pink-700 font-semibold",
  InProgress: "bg-sky-100",
  Completed: "text-success",
  Skipped: "text-orange-400",
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