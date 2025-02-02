export function isset(obj) {
  if (obj !== null && obj !== undefined) {
    return typeof obj === 'object' || Array.isArray(obj) ? Object.keys(obj).length : obj.toString().length;
  }
  return false;
}

export function toRaw(obj) {
  return JSON.parse(JSON.stringify(obj));
}