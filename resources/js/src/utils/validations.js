export function isDecimalWithDot(value) {
  return /^\d+\.\d+$/.test(value.toString());
}

export function isNumber(number) {
  return !isNaN(number) || typeof number == 'number';
}

export function onlyNumber(string) {
  return string ? string.replace(/\D/g, '') : '';
}

export function hasRole(obj, role) {
  return obj.roles && Object.keys(obj.roles).includes(role);
}